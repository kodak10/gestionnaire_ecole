<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class Niveau extends Model
{
    protected $fillable = ['ecole_id', 'nom', 'ordre', 'moy_base', 'coeff_transfert', 'actif'];

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

    /**
     * Obtenir le nom de la table des niveaux
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getNiveauxTableName($ecoleId, $annee);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Relation avec les matières (dynamique)
     */
    public function matieres()
    {
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        $matieresTable = $tableService->getMatieresTableName($ecoleId, $annee);
        $niveauMatiereTable = $tableService->getNiveauMatiereTableName($ecoleId, $annee);
        
        if (!Schema::hasTable($matieresTable) || !Schema::hasTable($niveauMatiereTable)) {
            return $this->belongsToMany(Matiere::class, 'niveau_matiere');
        }
        
        return $this->belongsToMany(
            Matiere::class,
            $niveauMatiereTable,
            'niveau_id',
            'matiere_id'
        )->withPivot('coefficient', 'ordre', 'denominateur', 'ecole_id')
         ->withTimestamps();
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }
}