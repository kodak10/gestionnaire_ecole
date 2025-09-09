<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'eleve_id',
        'annee_scolaire_id',
        'montant',
        'raison',
        'type_frais_id'
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }
}