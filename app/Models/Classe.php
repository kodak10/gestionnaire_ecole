<?php
// app/Models/Classe.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Classe extends Model
{
    protected $fillable = ['ecole_id', 'niveau_id', 'annee_scolaire_id', 'nom', 'capacite', 'moy_base', 'enseignant_id'];

    // Le nom de la table est défini dynamiquement
    protected $table = 'classes';

    /**
     * Normaliser un sigle (minuscules, sans espaces, sans caractères spéciaux)
     */
    private function normalizeSigle(string $sigle): string
    {
        // Convertir en minuscules
        $sigle = strtolower(trim($sigle));
        
        // Remplacer les espaces par des underscores
        $sigle = str_replace(' ', '_', $sigle);
        
        // Remplacer les points, virgules et autres caractères spéciaux par des underscores
        $sigle = preg_replace('/[^a-z0-9_]/', '_', $sigle);
        
        // Supprimer les underscores multiples
        $sigle = preg_replace('/_+/', '_', $sigle);
        
        // Supprimer les underscores en début et fin
        $sigle = trim($sigle, '_');
        
        return $sigle;
    }

    /**
     * Récupérer le sigle de l'école normalisé
     */
    private function getEcoleSigle(int $ecoleId): string
    {
        $ecole = \DB::table('ecoles')->where('id', $ecoleId)->first();
        
        if (!$ecole) {
            Log::warning('⚠️ École non trouvée', ['ecole_id' => $ecoleId]);
            return 'ecole';
        }
        
        if (!empty($ecole->sigle_ecole)) {
            $sigle = $this->normalizeSigle($ecole->sigle_ecole);
            Log::debug('✅ Sigle normalisé', [
                'ecole_id' => $ecoleId,
                'original' => $ecole->sigle_ecole,
                'normalized' => $sigle
            ]);
            return $sigle;
        }
        
        // Fallback : générer un sigle à partir du nom
        $nom = strtolower(trim($ecole->nom_ecole ?? 'ecole'));
        $sigle = preg_replace('/[^a-z0-9]/', '', $nom);
        $sigle = substr($sigle, 0, 10);
        
        if (empty($sigle)) {
            $sigle = 'ecole';
        }
        
        Log::debug('⚠️ Sigle généré depuis le nom', [
            'ecole_id' => $ecoleId,
            'sigle' => $sigle
        ]);
        
        return $sigle;
    }

    /**
     * Obtenir le nom de la table dynamique
     */
    public function getTableName(int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        $tableName = 'classes_' . $sigle . '_' . $anneeFormatted;
                
        return $tableName;
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
    
    public function enseignant()
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
        
        Log::debug('📋 ForEcoleAndAnnee', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'annee' => $annee,
            'sigle' => $sigle,
            'table_name' => $tableName
        ]);
        
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