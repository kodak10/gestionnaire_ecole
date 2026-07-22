<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    protected $fillable = ['nom', 'ordre', 'ecole_id', 'annee_scolaire_id'];

    public function anneeScolaire() 
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class)->orderBy('nom', 'asc');
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

    /**
     * Scope pour trier les niveaux par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }

    /**
     * Scope pour filtrer par école et année scolaire
     */
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        return $query->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId);
    }
}