<?php
// app/Models/Enseignant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enseignant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'nom_prenoms',
        'matricule',
        'email',
        'telephone',
        'genre',
        'specialite',
        'date_naissance',
        'adresse',
        'photo_path',
    ];

    // Table statique (pas de suffixe)
    protected $table = 'enseignants';

    /**
     * Relation avec l'école
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Un enseignant peut avoir plusieurs classes
     */
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    /**
     * Scope pour filtrer par école
     */
    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }
}