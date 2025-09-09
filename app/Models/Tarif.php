<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $fillable = ['annee_scolaire_id', 'ecole_id', 'type_frais_id','obligatoire', 'niveau_id', 'montant'];

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    // Ajoutez cette relation si elle n'existe pas
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
}
