<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'libelle',
        'description',
        'montant',
        'date_depense',
        'depense_category_id',
        'mode_paiement',
        'beneficiaire',
        'reference',
        'justificatif',
        'annee_scolaire_id'
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant' => 'decimal:2'
    ];

    protected $table = 'depenses';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'depenses') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getDepensesTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function category()
    {
        return $this->belongsTo(DepenseCategorie::class, 'depense_category_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
}