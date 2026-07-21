<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    protected $fillable = [
        'nom_ecole',
        'sigle_ecole',
        'code',
        'logo',
        'adresse',
        'telephone',
        'fax',
        'email',
        'directeur',
        'footer_bulletin',
        'sms_notification',
        'sms_disponible' // Ajout de ce champ
    ];

    public function getNomAttribute()
    {
        return $this->nom_ecole;
    }

    public function anneesScolaires()
    {
        return $this->hasMany(AnneeScolaire::class, 'ecole_id');
    }

    /**
     * Vérifier si l'école a activé les notifications SMS
     */
    public function hasSmsNotificationEnabled()
    {
        return $this->sms_notification == 1;
    }

    /**
     * Vérifier si l'école a des SMS disponibles
     */
    public function hasSmsAvailable()
    {
        return $this->sms_disponible > 0;
    }

    /**
     * Vérifier si l'école peut envoyer des SMS
     */
    public function canSendSms()
    {
        return $this->hasSmsNotificationEnabled() && $this->hasSmsAvailable();
    }

    /**
     * Décrémenter le nombre de SMS disponibles
     */
    public function decrementSmsAvailable($count = 1)
    {
        if ($this->sms_disponible >= $count) {
            $this->sms_disponible -= $count;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Incrémenter le nombre de SMS disponibles
     */
    public function incrementSmsAvailable($count = 1)
    {
        $this->sms_disponible += $count;
        $this->save();
        return $this->sms_disponible;
    }

    /**
     * Obtenir le statut des notifications SMS
     */
    public function getSmsStatusAttribute()
    {
        if (!$this->hasSmsNotificationEnabled()) {
            return 'Désactivée';
        }
        
        if (!$this->hasSmsAvailable()) {
            return 'Crédits épuisés';
        }
        
        return 'Activée (' . $this->sms_disponible . ' SMS disponibles)';
    }

    /**
     * Vérifier le solde et retourner un message d'alerte si nécessaire
     */
    public function getSmsAlertAttribute()
    {
        if (!$this->hasSmsNotificationEnabled()) {
            return 'Les notifications SMS sont désactivées pour cette école.';
        }
        
        if (!$this->hasSmsAvailable()) {
            return 'Aucun crédit SMS disponible. Veuillez recharger.';
        }
        
        if ($this->sms_disponible <= 10) {
            return 'Attention : Il ne reste que ' . $this->sms_disponible . ' SMS disponibles. Veuillez recharger bientôt.';
        }
        
        return null;
    }
}