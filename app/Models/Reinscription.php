<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reinscription extends Model
{
    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire',
        'statut',
        'user_id',
        'date_reinscription',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
    'date_reinscription' => 'datetime',
];

}
