<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Reduction extends Model
{
    protected $fillable = [
        'ecole_id',
        'annee_scolaire_id',
        'eleve_id',       
        'tarif_id',
        'user_id',
        'montant',
        'raison'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $table = 'reductions';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'reductions') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getReductionsTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    // Relations
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function eleve()  // Changé de inscription() à eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        return $query->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForEleve($query, $eleveId)  // Changé de scopeForInscription à scopeForEleve
    {
        return $query->where('eleve_id', $eleveId);
    }
}