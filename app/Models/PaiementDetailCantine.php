<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementDetailCantine extends Model
{
    protected $table = 'paiement_detail_cantines';

    protected $fillable = [
        'paiement_id',
        'mois_id',
        'montant',
    ];

    public function paiement()
    {
        return $this->belongsTo(PaiementCantine::class, 'paiement_id');
    }

    public function mois()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }
}
