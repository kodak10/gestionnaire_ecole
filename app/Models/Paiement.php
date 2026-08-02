<?php
// app/Models/Paiement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class Paiement extends Model
{
    protected $fillable = [
        'eleve_id',
        'montant', 
        'mode_paiement', 
        'reference', 
        'user_id', 
        'annee_scolaire_id', 
        'ecole_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    protected $table = 'paiements';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'paiements') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getPaiementsTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Relation avec l'école
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'id');
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Relation avec l'année scolaire
     */
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    /**
     * Relation avec les détails de paiement
     */
    public function details()
    {
        return $this->hasMany(PaiementDetail::class);
    }

    /**
     * Relation avec l'élève
     */
    public function eleve()
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    /**
     * Calculer le total payé
     */
    public function getTotalPayeAttribute()
    {
        return $this->details->sum('montant');
    }

    /**
     * Calculer le reste à payer
     */
    public function getResteAPayerAttribute()
    {
        return max(0, $this->montant - $this->total_paye);
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
     * Scope pour filtrer par élève
     */
    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les paiements validés
     */
    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    /**
     * Scope pour les paiements en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les paiements annulés
     */
    public function scopeAnnules($query)
    {
        return $query->where('statut', 'annule');
    }

    /**
     * Vérifier si le paiement est validé
     */
    public function isValid()
    {
        return $this->statut === 'valide';
    }

    /**
     * Vérifier si le paiement est en attente
     */
    public function isPending()
    {
        return $this->statut === 'en_attente';
    }

    /**
     * Vérifier si le paiement est annulé
     */
    public function isCancelled()
    {
        return $this->statut === 'annule';
    }
}