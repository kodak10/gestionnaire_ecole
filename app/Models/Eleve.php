<?php
// app/Models/Eleve.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Eleve extends Model
{
    protected $fillable = [
        'annee_scolaire_id', 'ecole_id', 'classe_id', 'matricule',
        'code_national', 'nom', 'prenom', 'sexe', 'naissance',
        'lieu_naissance', 'nationalite', 'num_extrait', 'photo_path',
        'infos_medicales', 'parent_nom', 'parent_telephone',
        'parent_telephone02', 'parent_email', 'pere_nom', 'pere_contact',
        'pere_contact02', 'mere_nom', 'mere_contact', 'mere_contact02',
        'parent_adresse', 'transport_active', 'transport_tarif_id',
        'transport_start_date', 'cantine_active', 'cantine_tarif_id',
        'cantine_start_date', 'statut', 'is_active'
    ];

    protected $table = 'eleves';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'eleves') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getElevesTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getElevesTableName($ecoleId, $annee);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function reductions()
    {
        return $this->hasMany(Reduction::class);
    }

    public function reinscriptions()
    {
        return $this->hasMany(Reinscription::class);
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
     * Scope pour filtrer par classe
     */
    public function scopeForClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }
}