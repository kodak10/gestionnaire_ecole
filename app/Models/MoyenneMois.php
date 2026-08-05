<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class MoyenneMois extends Model
{
    protected $fillable = [
        'eleve_id',
        'classe_id',
        'mois_id',
        'annee_scolaire_id',
        'ecole_id',
        'moyenne',
        'rang',
        'exaequo',
        'appreciation',
        'details_notes',
        'moyenne_classe',
        'moyenne_min',
        'moyenne_max',
        'effectif_classe',
        'user_id',
        'date_generation',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2',
        'exaequo' => 'boolean',
        'details_notes' => 'array',
        'moyenne_classe' => 'decimal:2',
        'moyenne_min' => 'decimal:2',
        'moyenne_max' => 'decimal:2',
        'date_generation' => 'datetime',
    ];

    protected $table = 'moyenne_mois';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'moyenne_mois') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getMoyenneMoisTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Obtenir le nom de la table des moyennes mensuelles
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getMoyenneMoisTableName($ecoleId, $annee);
    }

    // ==================== RELATIONS ====================

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function mois()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeForAnnee($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeForMois($query, $moisId)
    {
        return $query->where('mois_id', $moisId);
    }

    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }
}