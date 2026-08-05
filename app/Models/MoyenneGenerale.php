<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class MoyenneGenerale extends Model
{
    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire_id',
        'ecole_id',
        'moyennes_par_mois',
        'rangs_par_mois',
        'moyennes_par_matiere',
        'rangs_par_matiere',
        'details_notes',
        'moyenne_annuelle',
        'rang_general',
        'exaequo',
        'appreciation_generale',
        'decision',
        'distinctions',
        'sanctions',
        'mois_selectionnes',
        'mois_coefficients',
        'user_id',
        'date_cloture',
    ];

    protected $casts = [
        'moyennes_par_mois' => 'array',
        'rangs_par_mois' => 'array',
        'moyennes_par_matiere' => 'array',
        'rangs_par_matiere' => 'array',
        'details_notes' => 'array',
        'moyenne_annuelle' => 'decimal:2',
        'exaequo' => 'boolean',
        'distinctions' => 'array',
        'sanctions' => 'array',
        'mois_selectionnes' => 'array',
        'mois_coefficients' => 'array',
        'date_cloture' => 'datetime',
    ];

    protected $table = 'moyenne_generale';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'moyenne_generale') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getMoyenneGeneraleTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    /**
     * Obtenir le nom de la table des moyennes générales
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $tableService = App::make(TableService::class);
        return $tableService->getMoyenneGeneraleTableName($ecoleId, $annee);
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

    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Obtenir la moyenne annuelle formatée
     */
    public function getMoyenneFormattedAttribute()
    {
        return number_format($this->moyenne_annuelle ?? 0, 2, ',', ' ');
    }

    /**
     * Obtenir le rang formaté
     */
    public function getRangFormattedAttribute()
    {
        if (!$this->rang_general) {
            return '';
        }
        
        $suffix = $this->rang_general == 1 ? 'er' : 'e';
        $texte = $this->rang_general . $suffix;
        
        if ($this->exaequo) {
            $texte .= ' ex æquo';
        }
        
        return $texte;
    }

    /**
     * Vérifier si l'élève est admis
     */
    public function isAdmis()
    {
        return strpos($this->decision ?? '', 'ADMIS') !== false;
    }
}