<?php
// app/Services/TableService.php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TableService
{
    /**
     * Tables qui sont statiques (sans suffixe d'année)
     */
    private static $staticTables = [
        'ecoles',
        'enseignants',
        'users',
        'type_frais',
        'mois_scolaires',
        'permissions',
        'roles',
        'depense_categories',
        'documents',
        'document_templates',
    ];

    /**
     * Vérifier si une table est statique
     */
    public function isStaticTable(string $base): bool
    {
        return in_array($base, self::$staticTables);
    }

    /**
     * Normaliser un sigle
     */
    public function normalizeSigle(string $sigle): string
    {
        $sigle = strtolower(trim($sigle));
        $sigle = str_replace(' ', '_', $sigle);
        $sigle = preg_replace('/[^a-z0-9_]/', '_', $sigle);
        $sigle = preg_replace('/_+/', '_', $sigle);
        return trim($sigle, '_');
    }

    /**
     * Récupérer le sigle de l'école
     */
    public function getEcoleSigle(int $ecoleId): string
    {
        $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
        
        if (!$ecole) {
            return 'ecole';
        }
        
        if (!empty($ecole->sigle_ecole)) {
            return $this->normalizeSigle($ecole->sigle_ecole);
        }
        
        $nom = strtolower(trim($ecole->nom_ecole ?? 'ecole'));
        $sigle = preg_replace('/[^a-z0-9]/', '', $nom);
        $sigle = substr($sigle, 0, 10);
        
        return !empty($sigle) ? $sigle : 'ecole';
    }

    /**
     * Obtenir le nom de la table (dynamique ou statique)
     */
    public function getTableName(string $base, int $ecoleId, string $annee): string
    {
        // Si la table est statique, retourner le nom simple
        if ($this->isStaticTable($base)) {
            return $base;
        }
        
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        return $base . '_' . $sigle . '_' . $anneeFormatted;
    }

    /**
     * Vérifier si une table existe
     */
    public function tableExists(string $base, int $ecoleId, string $annee): bool
    {
        $tableName = $this->getTableName($base, $ecoleId, $annee);
        return Schema::hasTable($tableName);
    }

    /**
     * Vérifier si une table existe avec son nom exact
     */
    public function tableExistsExact(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    /**
     * Récupérer toutes les tables de la base
     */
    public function getAllTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = [];
        
        foreach ($tables as $table) {
            $tableArray = (array) $table;
            $tableNames[] = reset($tableArray);
        }
        
        return $tableNames;
    }

    /**
     * Vérifier une table avec recherche de similarité
     */
    public function checkTableForYear(string $base, int $ecoleId, string $annee): array
    {
        // Si la table est statique, vérifier simplement son existence
        if ($this->isStaticTable($base)) {
            $exists = Schema::hasTable($base);
            return [
                'exists' => $exists,
                'table' => $base,
                'is_static' => true
            ];
        }
        
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        $tableName = $base . '_' . $sigle . '_' . $anneeFormatted;
        
        $exists = Schema::hasTable($tableName);
        
        if (!$exists) {
            $allTables = $this->getAllTables();
            $similarTables = [];
            
            foreach ($allTables as $table) {
                if (str_contains($table, $base . '_') && str_contains($table, $anneeFormatted)) {
                    $similarTables[] = $table;
                }
            }
            
            return [
                'exists' => false,
                'table' => $tableName,
                'similar_tables' => $similarTables,
                'is_static' => false
            ];
        }
        
        return [
            'exists' => true,
            'table' => $tableName,
            'is_static' => false
        ];
    }

    /**
     * Obtenir le nom de la table des classes
     */
    public function getClassesTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('classes', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des niveaux
     */
    public function getNiveauxTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('niveaux', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des matières
     */
    public function getMatieresTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('matieres', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des mentions
     */
    public function getMentionsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('mentions', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des tarifs
     */
    public function getTarifsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('tarifs', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des tarifs mensuels
     */
    public function getTarifsMensuelsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('tarifs_mensuels', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des élèves
     */
    public function getElevesTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('eleves', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des notes
     */
    public function getNotesTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('notes', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des paiements
     */
    public function getPaiementsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('paiements', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des détails de paiement
     */
    public function getPaiementDetailsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('paiement_details', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des réductions
     */
    public function getReductionsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('reductions', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des dépenses
     */
    public function getDepensesTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('depenses', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des catégories de dépenses
     */
    public function getDepenseCategoriesTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('depense_categories', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des moyennes
     */
    public function getMoyenneGeneraleTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('moyenne_generale', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des moyennes par mois
     */
    public function getMoyenneMoisTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('moyenne_mois', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des enseignants (statique)
     */
    public function getEnseignantsTableName(): string
    {
        return 'enseignants';
    }

    /**
     * Obtenir le nom de la table des type_frais (statique)
     */
    public function getTypeFraisTableName(): string
    {
        return 'type_frais';
    }
}