<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'annee_scolaire_id', 'nom','capacite' , 'moy_base', 'enseignant_id'];

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
        return $this->belongsTo(Enseignant::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class)
                    ->withPivot('coefficient')
                    ->withTimestamps();
    }

    /**
     * Scope pour trier les classes par ordre du niveau
     */
    public function scopeOrdered($query)
    {
        return $query->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
                    ->orderBy('niveaux.ordre', 'asc')
                    ->orderBy('classes.nom', 'asc')
                    ->select('classes.*');
    }

    /**
     * Scope pour filtrer par école et année scolaire
     */
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        return $query->where('classes.ecole_id', $ecoleId)
                    ->where('classes.annee_scolaire_id', $anneeScolaireId);
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