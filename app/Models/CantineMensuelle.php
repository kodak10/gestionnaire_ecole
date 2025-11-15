<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CantineMensuelle extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_id',
        'mois_scolaire_id',
        'paiement_id',
        'montant',
        'est_coche',
        'est_paye'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'est_coche' => 'boolean',
        'est_paye' => 'boolean'
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function moisScolaire()
    {
        return $this->belongsTo(MoisScolaire::class);
    }
}