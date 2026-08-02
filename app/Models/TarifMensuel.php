<?php
// app/Models/TarifMensuel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class TarifMensuel extends Model
{
    use HasFactory;

    protected $table = 'tarifs_mensuels';

    protected $fillable = [
        'tarif_id',     
        'niveau_id',
        'mois_id',
        'montant',
        'ecole_id',
        'annee_scolaire_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'tarifs_mensuels') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getTarifsMensuelsTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    // Relation avec le tarif annuel
    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function moisScolaire()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}