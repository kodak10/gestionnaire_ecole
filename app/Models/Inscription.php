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
        'transport_active',
        'statut',
    ];

    protected $casts = [
        'statut' => 'boolean',
        'cantine_active' => 'boolean',
        'transport_active' => 'boolean',
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

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    
 public function reductions()
    {
        return $this->hasMany(Reduction::class);
    }

public function getNaissanceFormatteeAttribute()
{
    return $this->naissance ? $this->naissance->format('d/m/Y') : 'N/A';
}

}
