<?php

namespace App\Services;

use App\Models\Ecole;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;
    protected $apiUrl;
    protected $senderName;
    protected $senderAddress;
    protected $authorizationHeader;
    protected $ecole;
    protected $accessToken;
    protected $tokenExpiresAt;

    // Configuration Orange
    protected $clientId;
    protected $clientSecret;
    protected $applicationId;
    protected $tokenUrl;

    public function __construct($ecoleId = null, $provider = 'orange')
    {
        $this->provider = $provider;
        
        // Configuration Orange
        $this->clientId = config('services.orange.client_id');
        $this->clientSecret = config('services.orange.client_secret');
        $this->applicationId = config('services.orange.application_id');
        $this->tokenUrl = config('services.orange.token_url', 'https://api.orange.com/oauth/v2/token');
        $this->apiUrl = config('services.orange.api_url');
        $this->senderName = config('services.orange.sender_name', 'MonEcole');
        $this->senderAddress = config('services.orange.sender_address', 'tel:+2250000');
        $this->authorizationHeader = config('services.orange.authorization_header');
        
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }
    }

    /**
     * Envoyer un SMS avec vérification des crédits
     */
    public function sendSms($phone, $message, $ecoleId = null)
    {
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }

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
            $phone = $this->formatPhoneNumber($phone);

            Log::info('Tentative d\'envoi SMS via ' . ucfirst($this->provider), [
                'ecole_id' => $this->ecole ? $this->ecole->id : null,
                'phone' => $phone,
                'message' => substr($message, 0, 50) . '...',
                'sms_disponible_avant' => $this->ecole ? $this->ecole->sms_disponible : null
            ]);

            // Envoyer via le provider choisi
            if ($this->provider === 'orange') {
                $result = $this->sendViaOrange($phone, $message);
            } else {
                $result = $this->sendViaQuickNotify($phone, $message);
            }

            if ($result['success']) {
                if ($this->ecole) {
                    $this->ecole->decrementSmsAvailable();
                    $smsRestant = $this->ecole->sms_disponible;
                    
                    Log::info('SMS envoyé avec succès via ' . ucfirst($this->provider), [
                        'ecole_id' => $this->ecole->id,
                        'sms_restant' => $smsRestant,
                        'response' => $result['response'] ?? null
                    ]);
                }

                return [
                    'success' => true,
                    'response' => $result['response'] ?? null,
                    'sms_restant' => $this->ecole ? $this->ecole->sms_disponible : null
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi du SMS via ' . ucfirst($this->provider), [
                'ecole_id' => $this->ecole ? $this->ecole->id : null,
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'échec avec Orange, essayer QuickNotify en fallback
            if ($this->provider === 'orange') {
                Log::info('Tentative de fallback vers QuickNotify');
                return $this->sendViaQuickNotify($phone, $message);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un SMS via Orange API
     */
    protected function sendViaOrange($phone, $message)
    {
        try {
            // Obtenir un token d'accès
            $tokenResult = $this->getAccessToken();
            if (!$tokenResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Impossible d\'obtenir le token Orange: ' . $tokenResult['message']
                ];
            }

            $accessToken = $tokenResult['access_token'];

            $payload = [
                'outboundSMSMessageRequest' => [
                    'address' => 'tel:' . $phone,
                    'senderAddress' => $this->senderAddress,
                    'senderName' => $this->senderName,
                    'outboundSMSTextMessage' => [
                        'message' => $message
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json()
                ];
            }

            Log::error('Erreur Orange API', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un SMS via QuickNotify (fallback)
     */
    protected function sendViaQuickNotify($phone, $message)
    {
        try {
            $apiKey = config('services.quick_notify.api_key');
            $apiUrl = config('services.quick_notify.api_url');

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'ApiKey' => $apiKey,
            ])->post($apiUrl, [
                'phone' => $phone,
                'message' => $message,
                'sender_name' => config('services.quick_notify.sender_name', 'MonEcole')
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir un token d'accès Orange
     */
    protected function getAccessToken()
    {
        // Vérifier si le token est encore valide
        if ($this->accessToken && $this->tokenExpiresAt && now()->lt($this->tokenExpiresAt)) {
            return [
                'success' => true,
                'access_token' => $this->accessToken
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->tokenExpiresAt = now()->addSeconds($data['expires_in'] - 60); // 60 secondes de marge
                
                Log::info('Token Orange généré avec succès', [
                    'expires_in' => $data['expires_in']
                ]);
                
                return [
                    'success' => true,
                    'access_token' => $this->accessToken
                ];
            }

            Log::error('Erreur lors de la génération du token Orange', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro de téléphone
     */
    protected function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si le numéro commence par +, le garder tel quel
        if (strpos($phone, '+') === 0) {
            // Enlever le + pour Orange (ils utilisent ++)
            $phone = substr($phone, 1);
        }
        
        // Nettoyer les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro commence par 225
        if (strpos($phone, '225') === 0) {
            if (strlen($phone) >= 4 && $phone[3] !== '0') {
                $localNumber = substr($phone, 3);
                if (strlen($localNumber) >= 9 && in_array($localNumber[0], ['1', '5', '7'])) {
                    return '2250' . $localNumber;
                }
                return '2250' . $localNumber;
            }
            return $phone;
        }
        
        // Si le numéro commence par 0
        if (strlen($phone) >= 10 && $phone[0] === '0') {
            return '225' . $phone;
        }
        
        // Si le numéro fait 9 chiffres
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
        
        // Fallback
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