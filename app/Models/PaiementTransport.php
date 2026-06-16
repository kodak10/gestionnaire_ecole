<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementTransport extends Model
{
    protected $table = 'paiement_transports';

    protected $fillable = [
        'inscription_id',
        'user_id',
        'annee_scolaire_id',
        'ecole_id',
        'type_frais_id',  // AJOUTER
        'montant',
        'mode_paiement',
        'reference',
        'created_at',
        'updated_at'
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function details()
    {
        return $this->hasMany(PaiementDetailTransport::class, 'paiement_transport_id');
    }
}