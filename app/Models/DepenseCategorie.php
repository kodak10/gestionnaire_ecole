<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\TableService;
use Illuminate\Support\Facades\App;

class DepenseCategorie extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'ecole_id',
        'annee_scolaire_id'
    ];

    protected $table = 'depense_categories';

    /**
     * Définir la table dynamique pour le modèle
     */
    public function getTable()
    {
        if ($this->table !== 'depense_categories') {
            return $this->table;
        }
        
        $tableService = App::make(TableService::class);
        $ecoleId = $this->ecole_id ?? session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        if ($ecoleId && $annee) {
            $this->table = $tableService->getDepenseCategoriesTableName($ecoleId, $annee);
        }
        
        return $this->table;
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class, 'depense_category_id');
    }

    public function anneeScolaire() 
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
}