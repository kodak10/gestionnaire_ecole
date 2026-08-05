<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class Matiere extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'nom', 'annee_scolaire_id'];

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
            $this->table = $tableService->getMatieresTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Obtenir le nom de la table des matières
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getMatieresTableName($ecoleId, $annee);
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
        $niveauMatiereTable = $tableService->getNiveauMatiereTableName($ecoleId, $annee);
        
        if (!Schema::hasTable($niveauxTable) || !Schema::hasTable($niveauMatiereTable)) {
            return $this->belongsToMany(Niveau::class, 'niveau_matiere');
        }
        
        return $this->belongsToMany(
            Niveau::class,
            $niveauMatiereTable,
            'matiere_id',
            'niveau_id'
        )->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id')
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

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Scope pour filtrer par école
     */
    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForAnnee($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Scope pour filtrer par niveau
     */
    public function scopeForNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    /**
     * Scope pour ordonner par nom
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('nom', 'asc');
    }
}