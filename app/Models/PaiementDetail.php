<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementDetail extends Model
{
    protected $fillable = [
        'paiement_id', 
        'montant', 
        'inscription_id', 
        'tarif_id'
    ];

    protected $casts = [
        'montant' => 'decimal:2'
    ];

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function eleve()
    {
        return $this->inscription->eleve ?? null;
    }

    public function getTypeFraisAttribute()
    {
        return $this->tarif?->typeFrais;
    }
}