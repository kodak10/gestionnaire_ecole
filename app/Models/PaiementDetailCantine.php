<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementDetailCantine extends Model
{
    protected $table = 'paiement_detail_cantines';

    protected $fillable = [
        'paiement_cantine_id',
        'montant',
    ];

    public function paiement()
    {
        return $this->belongsTo(PaiementCantine::class, 'paiement_cantine_id');
    }

    // AJOUTEZ CETTE RELATION (comme dans PaiementDetail)
    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }
}