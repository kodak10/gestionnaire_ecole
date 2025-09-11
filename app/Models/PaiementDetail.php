<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementDetail extends Model
{
    protected $fillable = [
        'paiement_id', 'montant', 'inscription_id', 'type_frais_id'
    ];

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }
}
