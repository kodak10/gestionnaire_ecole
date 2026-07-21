<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'nom',
        'type',
        'content',
        'variables',
        'is_default',
        'is_active',
        'description'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Remplacer les variables dans le contenu
     */
    public function render(array $data)
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("%{$key}%", $value, $content);
        }
        
        return $content;
    }

    /**
     * Variables communes à tous les types
     */
    public static function getCommonVariables()
    {
        return [
            '%ECOLE%' => 'Nom de l\'école',
            '%ECOLE_ADRESSE%' => 'Adresse de l\'école',
            '%ECOLE_TELEPHONE%' => 'Téléphone de l\'école',
            '%ECOLE_EMAIL%' => 'Email de l\'école',
            '%DATE%' => 'Date du jour',
            '%DATE_FR%' => 'Date en français',
            '%ANNEE%' => 'Année scolaire',
        ];
    }

    /**
     * Variables pour les reçus de paiement
     */
    public static function getPaiementVariables()
    {
        return array_merge(self::getCommonVariables(), [
            '%NOM%' => 'Nom de l\'élève',
            '%PRENOM%' => 'Prénom de l\'élève',
            '%MATRICULE%' => 'Matricule',
            '%CLASSE%' => 'Classe',
            '%NUMERO_RECU%' => 'Numéro du reçu',
            '%MONTANT%' => 'Montant payé',
            '%MONTANT_LETTRES%' => 'Montant en lettres',
            '%RESTE%' => 'Reste à payer',
            '%TOTAL%' => 'Total à payer',
            '%TYPE_FRAIS%' => 'Type de frais',
            '%MODE_PAIEMENT%' => 'Mode de paiement',
            '%REFERENCE%' => 'Référence du paiement',
            '%MENSUALITE%' => 'Mensualité',
            '%MOIS%' => 'Mois de paiement',
        ]);
    }

    /**
     * Variables pour les relances
     */
    public static function getRelanceVariables()
    {
        return array_merge(self::getCommonVariables(), [
            '%NOM%' => 'Nom de l\'élève',
            '%PRENOM%' => 'Prénom de l\'élève',
            '%MATRICULE%' => 'Matricule',
            '%CLASSE%' => 'Classe',
            '%MONTANT_DU%' => 'Montant dû',
            '%DATE_ECHEANCE%' => 'Date d\'échéance',
            '%RETARD%' => 'Nombre de jours de retard',
            '%MOIS_CONCERNE%' => 'Mois concerné',
            '%NOMBRE_RELANCE%' => 'Numéro de relance',
            '%DELAI%' => 'Délai de paiement',
            '%SANCTION%' => 'Sanction en cas de non-paiement',
            '%NOM_RESPONSABLE%' => 'Nom du responsable légal',
            '%PRENOM_RESPONSABLE%' => 'Prénom du responsable légal',
        ]);
    }

    /**
     * Variables pour les messages d'information
     */
    public static function getInformationVariables()
    {
        return array_merge(self::getCommonVariables(), [
            '%NOM%' => 'Nom de l\'élève',
            '%PRENOM%' => 'Prénom de l\'élève',
            '%CLASSE%' => 'Classe',
            '%EVENEMENT%' => 'Événement',
            '%DATE_EVENEMENT%' => 'Date de l\'événement',
            '%LIEU%' => 'Lieu',
            '%HEURE%' => 'Heure',
            '%OBJET%' => 'Objet du message',
            '%DETAIL%' => 'Détails supplémentaires',
        ]);
    }

    /**
     * Variables pour les bulletins
     */
    public static function getBulletinVariables()
    {
        return array_merge(self::getCommonVariables(), [
            '%NOM%' => 'Nom de l\'élève',
            '%PRENOM%' => 'Prénom de l\'élève',
            '%MATRICULE%' => 'Matricule',
            '%CLASSE%' => 'Classe',
            '%MOYENNE%' => 'Moyenne générale',
            '%RANG%' => 'Rang dans la classe',
            '%EFFECTIF%' => 'Effectif de la classe',
            '%APPRECIATION%' => 'Appréciation générale',
        ]);
    }

    /**
     * Obtenir les variables selon le type
     */
    public static function getVariablesByType($type)
    {
        return match($type) {
            'recu_paiement' => self::getPaiementVariables(),
            'relance' => self::getRelanceVariables(),
            'information' => self::getInformationVariables(),
            'bulletin' => self::getBulletinVariables(),
            default => self::getCommonVariables(),
        };
    }

    /**
     * Obtenir les types disponibles
     */
    public static function getTypes()
    {
        return [
            'recu_paiement' => 'Reçu de paiement',
            'relance' => 'Relance de paiement',
            'information' => "Message d'information",
            'bulletin' => 'Bulletin de notes',
            'autre' => 'Autre',
        ];
    }

    /**
     * Obtenir le template par défaut pour un type
     */
    public static function getDefaultForType($ecoleId, $type)
    {
        return self::where('ecole_id', $ecoleId)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Obtenir le template actif pour un type
     */
    public static function getActiveForType($ecoleId, $type)
    {
        return self::where('ecole_id', $ecoleId)
            ->where('type', $type)
            ->where('is_active', true)
            ->get();
    }
}