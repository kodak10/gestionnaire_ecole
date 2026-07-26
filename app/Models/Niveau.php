<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    protected $fillable = ['nom', 'ordre', 'ecole_id'];

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
                    ->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id')
                    ->withTimestamps();
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Scope pour trier les niveaux par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }

    /**
     * Scope pour filtrer par école
     */
    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }
}