<?php
// app/Models/PaiementDetail.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class PaiementDetail extends Model
{
    protected $fillable = [
        'paiement_id',
        'eleve_id',
        'tarif_id',
        'type_frais_id',
        'montant',
        'mois_id',
        'annee_scolaire_id',
        'ecole_id'
    ];

    protected $casts = [
        'montant' => 'decimal:2'
    ];

    protected $table = 'paiement_details';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'paiement_details') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getPaiementDetailsTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Relation avec le paiement principal
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    /**
     * Relation avec le tarif
     */
    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    /**
     * Relation avec l'élève
     */
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    /**
     * Relation avec le type de frais
     */
    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }

    /**
     * Relation avec le mois scolaire
     */
    public function moisScolaire()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Relation avec l'école
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Scope pour filtrer par paiement
     */
    public function scopeForPaiement($query, $paiementId)
    {
        return $query->where('paiement_id', $paiementId);
    }

    /**
     * Scope pour filtrer par élève
     */
    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    /**
     * Scope pour filtrer par type de frais
     */
    public function scopeForTypeFrais($query, $typeFraisId)
    {
        return $query->where('type_frais_id', $typeFraisId);
    }

    /**
     * Scope pour filtrer par tarif
     */
    public function scopeForTarif($query, $tarifId)
    {
        return $query->where('tarif_id', $tarifId);
    }

    /**
     * Scope pour filtrer par mois
     */
    public function scopeForMois($query, $moisId)
    {
        return $query->where('mois_id', $moisId);
    }

    /**
     * Scope pour filtrer par école
     */
    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForAnnee($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Obtenir le libellé du type de frais
     */
    public function getTypeFraisNomAttribute()
    {
        return $this->typeFrais ? $this->typeFrais->nom : 'Non défini';
    }

    /**
     * Obtenir le nom de l'élève
     */
    public function getEleveNomAttribute()
    {
        return $this->eleve ? $this->eleve->nom . ' ' . $this->eleve->prenom : 'Non défini';
    }

    /**
     * Obtenir le montant formaté
     */
    public function getMontantFormateAttribute()
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }
}