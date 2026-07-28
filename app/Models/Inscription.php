<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    protected $fillable = [
        'eleve_id',
        'classe_id',
        'ecole_id',
        'annee_scolaire_id',
        'cantine_active',
        'cantine_start_date',
        'cantine_tarif_id',   
        'transport_active',
        'transport_tarif_id',
        'transport_start_date',
        
        'statut',
    ];

    protected $casts = [
        'statut' => 'string',
        'cantine_active' => 'boolean',
        'cantine_start_date' => 'date',
        'transport_active' => 'boolean',
        'transport_start_date' => 'date', 
        'date_inscription' => 'datetime',
    ];

    public function eleve() {
        return $this->belongsTo(Eleve::class);
    }

    public function classe() {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire() {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function paiements() {
        return $this->hasMany(Paiement::class);
    }

    public function paiementDetails() {
        return $this->hasMany(PaiementDetail::class);
    }

    public function ecole() {
        return $this->belongsTo(Ecole::class);
    }

    public function reductions() {
        return $this->hasMany(Reduction::class);
    }

    public function notes() {
        return $this->hasMany(Note::class);
    }

    public function transportTarif()
    {
        return $this->belongsTo(Tarif::class, 'transport_tarif_id');
    }

    public function cantineTarif()
    {
        return $this->belongsTo(Tarif::class, 'cantine_tarif_id');
    }
    
    // Accesseurs
    public function getNaissanceFormatteeAttribute() {
        return $this->naissance ? $this->naissance->format('d/m/Y') : 'N/A';
    }
}