<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Classe extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'annee_scolaire_id', 'nom', 'capacite', 'moy_base', 'enseignant_id'];

    protected $table = 'classes';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'classes') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getClassesTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Obtenir le nom de la table dynamique
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getClassesTableName($ecoleId, $annee);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
    
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    /**
     * Scope pour trier les classes par ordre du niveau
     */
    public function scopeOrdered($query)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        $tableService = App::make(TableService::class);
        $classesTable = $this->getTable();
        $niveauxTable = $tableService->getNiveauxTableName($ecoleId, $annee);
        
        return $query->join($niveauxTable . ' as niveaux', 'classes.niveau_id', '=', 'niveaux.id')
                    ->orderBy('niveaux.ordre', 'asc')
                    ->orderBy('classes.nom', 'asc')
                    ->select('classes.*');
    }

    /**
     * Scope pour filtrer par école et année scolaire
     */
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        $tableService = App::make(TableService::class);
        $annee = \DB::table('annee_scolaires')->where('id', $anneeScolaireId)->value('annee');
        $tableName = $tableService->getClassesTableName($ecoleId, $annee);
        
        Log::debug('📋 ForEcoleAndAnnee', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'annee' => $annee,
            'table_name' => $tableName
        ]);
        
        return $query->from($tableName . ' as classes')
                    ->where('classes.ecole_id', $ecoleId)
                    ->where('classes.annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Obtenir les élèves de cette classe pour l'année en cours
     */
    public function eleves()
    {
        $ecoleId = $this->ecole_id;
        $annee = \DB::table('annee_scolaires')->where('id', $this->annee_scolaire_id)->value('annee');
        $tableService = App::make(TableService::class);
        $tableName = $tableService->getElevesTableName($ecoleId, $annee);
        
        return $this->hasMany(Eleve::class, 'classe_id')->from($tableName);
    }

    /**
     * Compter les élèves de cette classe
     */
    public function countEleves()
    {
        return $this->eleves()->count();
    }
}