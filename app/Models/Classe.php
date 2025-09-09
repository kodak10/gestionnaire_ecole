<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'annee_scolaire_id', 'nom','capacite' ,'enseignant_id'];

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
    
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class)
                    ->withPivot('coefficient')
                    ->withTimestamps();
    }

    /**
     * Attacher des matières à une classe avec des coefficients spécifiques
     */
    public function attacherMatieres(array $matieresAvecCoefficients)
    {
        foreach ($matieresAvecCoefficients as $matiereId => $coefficient) {
            $this->matieres()->attach($matiereId, ['coefficient' => $coefficient]);
        }
    }

    /**
     * Synchroniser les matières d'une classe
     */
    public function synchroniserMatieres(array $matieresAvecCoefficients)
    {
        $this->matieres()->sync($matieresAvecCoefficients);
    }

    
}
