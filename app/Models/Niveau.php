<?php
// app/Models/Niveau.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Niveau extends Model
{
    protected $fillable = ['nom', 'ordre', 'ecole_id'];

    protected $table = 'niveaux';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'niveaux') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getNiveauxTableName($ecoleId, $annee);
        }
        
        return $this->table;
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