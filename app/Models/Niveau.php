<?php
// app/Models/Niveau.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Niveau extends Model
{
    protected $fillable = ['nom', 'ordre', 'ecole_id'];

    protected $table = 'niveaux';

    /**
     * Normaliser un sigle (minuscules, sans espaces, sans caractères spéciaux)
     */
    private function normalizeSigle(string $sigle): string
    {
        $sigle = strtolower(trim($sigle));
        $sigle = str_replace(' ', '_', $sigle);
        $sigle = preg_replace('/[^a-z0-9_]/', '_', $sigle);
        $sigle = preg_replace('/_+/', '_', $sigle);
        return trim($sigle, '_');
    }

    /**
     * Récupérer le sigle de l'école normalisé
     */
    private function getEcoleSigle(int $ecoleId): string
    {
        $ecole = \DB::table('ecoles')->where('id', $ecoleId)->first();
        
        if (!$ecole) {
            return 'ecole';
        }
        
        if (!empty($ecole->sigle_ecole)) {
            return $this->normalizeSigle($ecole->sigle_ecole);
        }
        
        $nom = strtolower(trim($ecole->nom_ecole ?? 'ecole'));
        $sigle = preg_replace('/[^a-z0-9]/', '', $nom);
        $sigle = substr($sigle, 0, 10);
        
        return !empty($sigle) ? $sigle : 'ecole';
    }

    /**
     * Obtenir le nom de la table dynamique
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        return 'niveaux_' . $sigle . '_' . $anneeFormatted;
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
                    ->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id')
                    ->withTimestamps();
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }

    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }
}