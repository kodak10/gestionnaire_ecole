<?php
// app/Models/AnneeScolaire.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnneeScolaire extends Model
{
    use HasFactory;

    protected $table = 'annee_scolaires';

    protected $fillable = [
        'ecole_id',
        'annee',
        'date_debut',
        'date_fin',
        'est_active'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_active' => 'boolean'
    ];

    /**
     * Récupérer l'année scolaire active
     */
    public static function active()
    {
        return self::where('est_active', true)->firstOrFail();
    }

    /**
     * Relation avec l'école
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Relation avec les utilisateurs
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_annees_scolaires')
            ->withPivot('ecole_id')
            ->withTimestamps();
    }

    /**
     * Relation avec les classes
     */
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    /**
     * Relation avec les tarifs
     */
    public function tarifs()
    {
        return $this->hasMany(Tarif::class);
    }

    /**
     * Relation avec les types de frais
     */
    public function typeFrais()
    {
        return $this->hasMany(TypeFrais::class);
    }

    /**
     * Relation avec les mois scolaires
     */
    public function moisScolaires()
    {
        return $this->hasMany(MoisScolaire::class);
    }

    /**
     * Relation avec les élèves
     */
    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }

    /**
     * Relation avec les inscriptions
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Scope pour l'année active
     */
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    /**
     * Scope pour l'année en cours
     */
    public function scopeCurrent($query)
    {
        return $query->where('annee', date('Y') . '-' . (date('Y') + 1));
    }
}