<?php

namespace App\Services;

use App\Models\Ecole;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $apiUrl;
    protected $senderName;
    protected $ecole;

    public function __construct($ecoleId = null)
    {
        $this->apiKey = config('services.quick_notify.api_key');
        $this->apiUrl = config('services.quick_notify.api_url', 'https://api.quick-notify.pro/api/messages/request');
        $this->senderName = config('services.quick_notify.sender_name', 'MonEcole');
        
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }
    }

    /**
     * Envoyer un SMS avec vérification des crédits
     */
    public function sendSms($phone, $message, $ecoleId = null)
    {
        // Si un ecoleId est passé, charger l'école
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }

        // Vérifier si l'école peut envoyer des SMS
        if ($this->ecole && !$this->ecole->canSendSms()) {
            $status = $this->ecole->hasSmsNotificationEnabled() ? 'Activée' : 'Désactivée';
            $credits = $this->ecole->sms_disponible ?? 0;
            
            Log::warning('Tentative d\'envoi SMS - école non autorisée', [
                'ecole_id' => $this->ecole->id,
                'ecole_nom' => $this->ecole->nom_ecole,
                'sms_notification' => $status,
                'sms_disponible' => $credits
            ]);
            
            return [
                'success' => false,
                'message' => 'L\'école n\'a pas de crédits SMS disponibles ou les notifications sont désactivées',
                'status' => $status,
                'credits' => $credits
            ];
        }

        try {
            // Nettoyer et formater le numéro de téléphone
            $phone = $this->formatPhoneNumber($phone);

            Log::info('Tentative d\'envoi SMS', [
                'ecole_id' => $this->ecole ? $this->ecole->id : null,
                'phone' => $phone,
                'message' => substr($message, 0, 50) . '...',
                'sms_disponible_avant' => $this->ecole ? $this->ecole->sms_disponible : null
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'ApiKey' => $this->apiKey,
            ])->post($this->apiUrl, [
                'phone' => $phone,
                'message' => $message,
                'sender_name' => $this->senderName,
                'webhook_url' => config('services.quick_notify.webhook_url', null)
            ]);

            if ($response->successful()) {
                // Décrémenter le nombre de SMS disponibles
                $smsRestant = null;
                if ($this->ecole) {
                    $this->ecole->decrementSmsAvailable();
                    $smsRestant = $this->ecole->sms_disponible;
                    
                    Log::info('SMS envoyé avec succès - crédit déduit', [
                        'ecole_id' => $this->ecole->id,
                        'sms_restant' => $smsRestant,
                        'response' => $response->json()
                    ]);
                }

                return [
                    'success' => true,
                    'response' => $response->json(),
                    'sms_restant' => $smsRestant
                ];
            } else {
                Log::error('Erreur lors de l\'envoi du SMS', [
                    'ecole_id' => $this->ecole ? $this->ecole->id : null,
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => $response->body(),
                    'status' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi du SMS', [
                'ecole_id' => $this->ecole ? $this->ecole->id : null,
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro de téléphone pour QuickNotify
     * Format attendu : 22501XXXXXXXXX ou 22505XXXXXXXXX ou 22507XXXXXXXXX
     */
    protected function formatPhoneNumber($phone)
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro commence par 225
        if (strpos($phone, '225') === 0) {
            // Si après 225 il n'y a pas de 0, l'ajouter
            if (strlen($phone) >= 4 && $phone[3] !== '0') {
                $localNumber = substr($phone, 3);
                if (strlen($localNumber) >= 9 && in_array($localNumber[0], ['1', '5', '7'])) {
                    return '2250' . $localNumber;
                }
                return '2250' . $localNumber;
            }
            return $phone;
        }
        
        // Si le numéro commence par 0 (ex: 0748039722)
        if (strlen($phone) >= 10 && $phone[0] === '0') {
            return '225' . $phone;
        }
        
        // Si le numéro fait 9 chiffres (ex: 748039722)
        if (strlen($phone) === 9) {
            return '2250' . $phone;
        }
        
        // Si le numéro fait 10 chiffres
        if (strlen($phone) === 10) {
            if (in_array($phone[0], ['1', '5', '7'])) {
                return '2250' . $phone;
            }
            return '225' . $phone;
        }
        
        // Fallback: prendre les 9 derniers chiffres
        $last9 = substr($phone, -9);
        return '2250' . $last9;
    }

    /**
     * Formater le message de paiement
     */
    public function formatPaymentMessage($eleve, $classe, $montantPaye, $resteAPayer, $typeFrais = 'Scolarité')
    {
        $message = "Paiement de {$typeFrais} de {$eleve->nom} {$eleve->prenom} en classe de {$classe->nom}.\n";
        $message .= "Montant payé : " . number_format($montantPaye, 0, ',', ' ') . " FCFA\n";
        $message .= "Reste à payer : " . number_format($resteAPayer, 0, ',', ' ') . " FCFA\n";
        $message .= "Merci pour votre paiement.";

        return $message;
    }

    /**
     * Vérifier le solde de SMS d'une école
     */
    public function checkSmsBalance($ecoleId)
    {
        $ecole = Ecole::find($ecoleId);
        if (!$ecole) {
            return [
                'success' => false,
                'message' => 'École non trouvée'
            ];
        }

        return [
            'success' => true,
            'ecole_id' => $ecole->id,
            'ecole_nom' => $ecole->nom_ecole,
            'sms_notification' => $ecole->sms_notification == 1,
            'sms_disponible' => $ecole->sms_disponible,
            'sms_status' => $ecole->sms_status,
            'sms_alert' => $ecole->sms_alert,
            'can_send_sms' => $ecole->canSendSms()
        ];
    }
}