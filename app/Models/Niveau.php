<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    protected $fillable = ['nom', 'ordre'];

    public function anneeScolaire() 
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function tarifs()
    {
        return $this->hasMany(Tarif::class);
    }

    

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class, 'niveau_matiere')
                    ->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id', 'annee_scolaire_id')
                    ->withTimestamps();
    }

}
