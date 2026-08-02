<?php
// app/Models/Classe.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'annee_scolaire_id', 'nom', 'capacite', 'moy_base', 'enseignant_id'];

    // Le nom de la table est défini dynamiquement
    protected $table = 'classes';

    public function getTableName(int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        return 'classes_' . $sigle . '_' . str_replace('-', '_', $annee);
    }

    private function getEcoleSigle(int $ecoleId): string
    {
        $ecole = \DB::table('ecoles')->where('id', $ecoleId)->first();
        if (!$ecole) {
            return 'ecole';
        }
        if (!empty($ecole->sigle_ecole)) {
            return strtoupper(trim($ecole->sigle_ecole));
        }
        return 'ecole';
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
    
    public function enseignants()
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    /**
     * Scope pour trier les classes par ordre du niveau
     */
    public function scopeOrdered($query)
    {
        return $query->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
                    ->orderBy('niveaux.ordre', 'asc')
                    ->orderBy('classes.nom', 'asc')
                    ->select('classes.*');
    }

    /**
     * Scope pour filtrer par école et année scolaire
     */
    public function scopeForEcoleAndAnnee($query, $ecoleId, $anneeScolaireId)
    {
        // Utiliser le nom de la table dynamique
        $annee = \DB::table('annee_scolaires')->where('id', $anneeScolaireId)->value('annee');
        $sigle = $this->getEcoleSigle($ecoleId);
        $tableName = 'classes_' . $sigle . '_' . str_replace('-', '_', $annee);
        
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
        $sigle = $this->getEcoleSigle($ecoleId);
        $tableName = 'eleves_' . $sigle . '_' . str_replace('-', '_', $annee);
        
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