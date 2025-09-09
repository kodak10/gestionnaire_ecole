<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreInscription extends Model
{
    protected $table = 'preinscriptions';

    protected $fillable = [
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'adresse',
        'telephone',
        'email',
        'classe_demandee',
        'ecole_provenance',
        'nom_parent',
        'telephone_parent',
        'email_parent',
        'statut',
        'date_preinscription',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_preinscription' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatutColorAttribute()
    {
        return match($this->statut) {
            'en_attente' => 'warning',
            'validée' => 'success',
            'refusée' => 'danger',
            default => 'secondary'
        };
    }


    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}
