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
    protected $apiKey;
    protected $webhookUrl;
    protected $ecole;

    public function __construct($ecoleId = null, $provider = 'quicknotify')
    {
        $this->provider = $provider;
        
        $this->apiKey = config('services.quick_notify.api_key') ?? env('QUICK_NOTIFY_API_KEY');
        $this->apiUrl = config('services.quick_notify.api_url') ?? env('QUICK_NOTIFY_API_URL', 'https://api.quick-notify.pro/api/messages/request');
        $this->senderName = config('services.quick_notify.sender_name') ?? env('QUICK_NOTIFY_SENDER_NAME', 'MonEcole');
        $this->webhookUrl = config('services.quick_notify.webhook_url') ?? env('QUICK_NOTIFY_WEBHOOK_URL', null);
        
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }
    }

    public function sendSms($phone, $message, $ecoleId = null)
    {
        if ($ecoleId) {
            $this->ecole = Ecole::find($ecoleId);
        }

        if ($this->ecole && !$this->ecole->canSendSms()) {
            return [
                'success' => false,
                'message' => 'L\'école n\'a pas de crédits SMS disponibles ou les notifications sont désactivées'
            ];
        }

        try {
            $phone = $this->formatPhoneNumber($phone);
            
            Log::info('📱 Envoi SMS', [
                'phone_formatted' => $phone,
                'message_length' => strlen($message)
            ]);

            $result = $this->sendViaQuickNotify($phone, $message);

            if ($result['success']) {
                if ($this->ecole) {
                    $this->ecole->decrementSmsAvailable();
                }

                return [
                    'success' => true,
                    'response' => $result['response'] ?? null,
                    'sms_restant' => $this->ecole ? $this->ecole->sms_disponible : null
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function sendViaQuickNotify($phone, $message)
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('Clé API QuickNotify non configurée');
                return [
                    'success' => false,
                    'message' => 'Clé API QuickNotify non configurée'
                ];
            }

            $payload = [
                'phone' => $phone,
                'message' => $message,
                'sender_name' => $this->senderName,
            ];

            if ($this->webhookUrl) {
                $payload['webhook_url'] = $this->webhookUrl;
            }

            Log::info('📤 Envoi SMS via QuickNotify', [
                'phone' => $phone,
                'sender_name' => $this->senderName,
                'api_url' => $this->apiUrl
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'ApiKey' => $this->apiKey,
            ])->post($this->apiUrl, $payload);

            Log::info('📥 Réponse QuickNotify', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'response' => $responseData,
                    'message_id' => $responseData['id'] ?? null,
                ];
            }

            $errorMessage = $response->body();
            try {
                $errorData = $response->json();
                if (isset($errorData['detail'])) {
                    $errors = [];
                    foreach ($errorData['detail'] as $detail) {
                        if (isset($detail['msg'])) {
                            $errors[] = $detail['msg'];
                        }
                    }
                    $errorMessage = implode(', ', $errors);
                } elseif (isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                }
            } catch (\Exception $e) {
                // Garder le message brut
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Exception QuickNotify: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur de connexion à l\'API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro pour QuickNotify
     * Format attendu: 2250501578052 (225 + 0 + 9 chiffres)
     */
    protected function formatPhoneNumber($phone)
    {
        // Nettoyer le numéro (enlever espaces, tirets, etc.)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro est déjà au format 2250XXXXXXXXX (13 chiffres)
        if (strlen($phone) === 13 && substr($phone, 0, 3) === '225') {
            return $phone;
        }
        
        // Si le numéro commence par 0 (ex: 0501578052) → 2250501578052
        if (strlen($phone) === 10 && $phone[0] === '0') {
            return '225' . $phone; // 2250501578052
        }
        
        // Si le numéro fait 9 chiffres (ex: 501578052) → 2250501578052
        if (strlen($phone) === 9) {
            return '2250' . $phone; // 2250501578052
        }
        
        // Si le numéro fait 12 chiffres (ex: 225501578052) → 2250501578052
        if (strlen($phone) === 12 && substr($phone, 0, 3) === '225') {
            // Ajouter un 0 après 225
            return substr($phone, 0, 3) . '0' . substr($phone, 3); // 2250501578052
        }
        
        // Si le numéro fait 8 chiffres
        if (strlen($phone) === 8) {
            return '2250' . $phone;
        }
        
        // Fallback: prendre les 9 derniers chiffres
        if (strlen($phone) >= 9) {
            $last9 = substr($phone, -9);
            return '2250' . $last9;
        }
        
        // Dernier recours
        return '2250' . $phone;
    }

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
            'can_send_sms' => $ecole->canSendSms()
        ];
    }
}