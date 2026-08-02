<?php
// app/Models/Matiere.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Matiere extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'nom'];

    protected $table = 'matieres';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'matieres') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getTableName('matieres', $ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Relation avec les niveaux (dynamique)
     */
    public function niveaux()
    {
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        $niveauxTable = $tableService->getNiveauxTableName($ecoleId, $annee);
        $niveauMatiereTable = $tableService->getTableName('niveau_matiere', $ecoleId, $annee);
        
        if (!Schema::hasTable($niveauxTable) || !Schema::hasTable($niveauMatiereTable)) {
            return $this->belongsToMany(Niveau::class, 'niveau_matiere');
        }
        
        return $this->belongsToMany(Niveau::class, 'niveau_matiere')
                    ->from($niveauxTable)
                    ->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id')
                    ->withTimestamps();
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}