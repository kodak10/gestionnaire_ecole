<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reduction extends Model
{
    protected $fillable = [
        'ecole_id',
        'annee_scolaire_id',
        'inscription_id',
        'tarif_id',
        'user_id',
        'montant',
        'raison'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relations
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        return $query->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForInscription($query, $inscriptionId)
    {
        return $query->where('inscription_id', $inscriptionId);
    }
}