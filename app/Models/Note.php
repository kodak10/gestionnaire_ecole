<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Note extends Model
{
    protected $fillable = [
        'eleve_id',
        'matiere_id',
        'classe_id',
        'annee_scolaire_id',
        'ecole_id',
        'valeur',
        'coefficient',
        'appreciation',
        'user_id',
        'mois_id',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'coefficient' => 'decimal:2',
    ];

    // Table par défaut (sera remplacée par la table dynamique)
    protected $table = 'notes';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'notes') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getNotesTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Obtenir le nom de la table des notes
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getNotesTableName($ecoleId, $annee);
    }

    // ==================== RELATIONS ====================

    public function mois()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    // ==================== SCOPES ====================

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
     * Scope pour filtrer par classe
     */
    public function scopeForClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    /**
     * Scope pour filtrer par matière
     */
    public function scopeForMatiere($query, $matiereId)
    {
        return $query->where('matiere_id', $matiereId);
    }

    /**
     * Scope pour filtrer par mois
     */
    public function scopeForMois($query, $moisId)
    {
        return $query->where('mois_id', $moisId);
    }

    /**
     * Scope pour filtrer par élève
     */
    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeForPeriode($query, $moisId, $anneeScolaireId)
    {
        return $query->where('mois_id', $moisId)
                     ->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Scope pour filtrer par recherche (nom/prénom élève)
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->whereHas('eleve', function($q) use ($search) {
            $q->where('nom', 'like', '%' . $search . '%')
              ->orWhere('prenom', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope pour filtrer par note
     */
    public function scopeFilter($query, $request)
    {
        if ($request->filled('eleve_id')) {
            $query->where('eleve_id', $request->eleve_id);
        }

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        if ($request->filled('mois_id')) {
            $query->where('mois_id', $request->mois_id);
        }

        if ($request->filled('valeur_min')) {
            $query->where('valeur', '>=', $request->valeur_min);
        }

        if ($request->filled('valeur_max')) {
            $query->where('valeur', '<=', $request->valeur_max);
        }

        return $query;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Vérifier si la note est valide par rapport à la base
     */
    public function isValid($base = 20)
    {
        return $this->valeur !== null && $this->valeur <= $base;
    }

    /**
     * Obtenir la note sur 20
     */
    public function getNoteSur20($base = 20)
    {
        if ($this->valeur === null || $base <= 0) {
            return null;
        }
        return ($this->valeur / $base) * 20;
    }

    /**
     * Obtenir l'appréciation en fonction de la note
     */
    public function getAppreciation($base = 20)
    {
        if ($this->valeur === null || $base <= 0) {
            return 'Non évalué';
        }
        
        $noteSur20 = ($this->valeur / $base) * 20;
        
        if ($noteSur20 < 8) return 'Très insuffisant';
        if ($noteSur20 < 10) return 'Insuffisant';
        if ($noteSur20 < 12) return 'Passable';
        if ($noteSur20 < 14) return 'Assez Bien';
        if ($noteSur20 < 16) return 'Bien';
        if ($noteSur20 < 18) return 'Très Bien';
        return 'Excellent';
    }

    /**
     * Mettre à jour l'appréciation automatiquement
     */
    public function updateAppreciation($base = 20)
    {
        $this->appreciation = $this->getAppreciation($base);
        return $this;
    }

    /**
     * Vérifier si la note est une note excéo (note maximale)
     */
    public function isExeco($base = 20)
    {
        return $this->valeur !== null && $this->valeur == $base;
    }
}