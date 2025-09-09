<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['niveau_id', 'annee_scolaire_id', 'nom', 'ecole_id'];
    
    public function classes()
    {
        return $this->belongsToMany(Classe::class)
                    ->withPivot('coefficient')
                    ->withTimestamps();
    }

    public function scopePrincipales($query)
    {
        return $query->whereIn('nom', [
            'Français',
            'Mathématiques',
            'Éducation scientifique',
            'Histoire-Géographie'
        ]);
    }

    /**
     * Scope pour les matières spécifiques
     */
    public function scopeSpecifiques($query)
    {
        return $query->whereIn('nom', [
            'Lecture',
            'Écriture',
            'Langues nationales',
            'Informatique'
        ]);
    }
}
