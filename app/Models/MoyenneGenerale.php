<?php
// app/Models/MoyenneGenerale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenneGenerale extends Model
{
    protected $table = 'moyenne_generale';
    
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
        'user_id', 
        'date_cloture'
    ];
    
    protected $casts = [
        'moyennes_par_mois' => 'array',
        'rangs_par_mois' => 'array',
        'moyennes_par_matiere' => 'array',
        'rangs_par_matiere' => 'array',
        'details_notes' => 'array',
        'distinctions' => 'array',
        'sanctions' => 'array',
        'mois_selectionnes' => 'array',
        'date_cloture' => 'datetime',
        'exaequo' => 'boolean',
        'moyenne_annuelle' => 'decimal:2',
    ];
    
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
    
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
    
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
    
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Récupérer la moyenne d'une matière spécifique
     */
    public function getMoyenneMatiere($matiereId): ?array
    {
        if (!$this->moyennes_par_matiere) {
            return null;
        }
        
        foreach ($this->moyennes_par_matiere as $matiere) {
            if (isset($matiere['matiere_id']) && $matiere['matiere_id'] == $matiereId) {
                return $matiere;
            }
        }
        
        return null;
    }
    
    /**
     * Récupérer la moyenne d'un mois spécifique
     */
    public function getMoyenneMois($moisId): ?array
    {
        if (!$this->moyennes_par_mois) {
            return null;
        }
        
        return $this->moyennes_par_mois[$moisId] ?? null;
    }
}