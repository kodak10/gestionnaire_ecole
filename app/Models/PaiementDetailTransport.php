<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementDetailTransport extends Model
{
    protected $table = 'paiement_detail_transports';

    protected $fillable = [
        'paiement_transport_id',
        'montant',
    ];

    public function paiement()
    {
        return $this->belongsTo(PaiementTransport::class, 'paiement_transport_id');
    }

    // AJOUTEZ CETTE RELATION (comme dans PaiementDetail)
    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }
}