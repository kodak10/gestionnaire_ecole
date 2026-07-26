<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $fillable = [
        'annee_scolaire_id', 
        'ecole_id', 
        'type_frais_id',
        'obligatoire', 
        'niveau_id', 
        'montant',
        'libelle'
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'montant' => 'decimal:2'
    ];

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        return $query->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForTypeFrais($query, $typeFraisId)
    {
        return $query->where('type_frais_id', $typeFraisId);
    }

    public function scopeForNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }
}