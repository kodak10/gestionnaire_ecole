<?php
// app/Models/MoyenneMois.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenneMois extends Model
{
    protected $table = 'moyenne_mois';
    
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
        'date_generation'
    ];
    
    protected $casts = [
        'details_notes' => 'array',
        'exaequo' => 'boolean',
        'moyenne' => 'decimal:2',
        'moyenne_classe' => 'decimal:2',
        'moyenne_min' => 'decimal:2',
        'moyenne_max' => 'decimal:2',
        'date_generation' => 'datetime'
    ];
    
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
    
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
    
    public function mois(): BelongsTo
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
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
}