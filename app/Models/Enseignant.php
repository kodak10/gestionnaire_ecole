<?php

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

   
}
