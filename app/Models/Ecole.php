<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    protected $fillable = [
        'nom_ecole',
        'iepp',
        'secteur_pedagogique',
        'sous_prefecture',
        'circonscription_primaire',
        'num_registre',

        'sigle_ecole',
        'code',
        'logo',
        'adresse',
        'ville',
        'telephone',
        'fax',
        'email',
        'directeur',
        'directeur_etudes',

        'footer_bulletin',
        'sms_notification',
        'sms_disponible',
        'arrondi_moyenne',

        'entete_document',
        'logo_republique',
        'sous_entete_document',
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

    /**
     * Applique l'arrondi selon la configuration de l'école
     */
    public function appliquerArrondi($valeur)
    {
        if ($valeur === null) {
            return null;
        }

        switch ($this->arrondi_moyenne) {
            case 'coupe':
                // Coupe à 2 chiffres sans arrondi (floor)
                return floor($valeur * 100) / 100;
            
            case 'arrondi_superieur':
                // Arrondi au supérieur (ceil)
                return ceil($valeur * 100) / 100;
            
            case 'arrondi':
            default:
                // Arrondi classique
                return round($valeur, 2);
        }
    }

    /**
     * Vérifie si l'arrondi est de type "coupe"
     */
    public function isCoupeMoyenne()
    {
        return $this->arrondi_moyenne === 'coupe';
    }

    /**
     * Vérifie si l'arrondi est de type "arrondi supérieur"
     */
    public function isArrondiSuperieur()
    {
        return $this->arrondi_moyenne === 'arrondi_superieur';
    }

    /**
     * Vérifie si l'arrondi est de type "arrondi classique"
     */
    public function isArrondiClassique()
    {
        return $this->arrondi_moyenne === 'arrondi';
    }

    /**
     * Obtient le libellé du mode d'arrondi
     */
    public function getArrondiMoyenneLabelAttribute()
    {
        $labels = [
            'coupe' => 'Coupe à 2 chiffres (ex: 12.345 → 12.34)',
            'arrondi' => 'Arrondi classique (ex: 12.345 → 12.35)',
            'arrondi_superieur' => 'Arrondi au supérieur (ex: 12.001 → 12.01)',
        ];
        
        return $labels[$this->arrondi_moyenne] ?? 'Coupe à 2 chiffres';
    }
}