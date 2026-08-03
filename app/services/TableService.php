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
     * Normaliser un sigle (supprime les espaces, points, caractères spéciaux)
     */
    public function normalizeSigle(string $sigle): string
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
     * Récupérer le sigle de l'école depuis la base de données
     */
    public function getEcoleSigle(int $ecoleId): string
    {
        $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
        
        if (!$ecole) {
            Log::warning('⚠️ École non trouvée', ['ecole_id' => $ecoleId]);
            return 'ecole';
        }
        
        // Utiliser le sigle_ecole s'il existe
        if (!empty($ecole->sigle_ecole)) {
            // Normaliser le sigle (supprimer les espaces, caractères spéciaux)
            $sigle = $this->normalizeSigle($ecole->sigle_ecole);
            
            Log::debug('✅ Sigle trouvé', [
                'ecole_id' => $ecoleId,
                'sigle_original' => $ecole->sigle_ecole,
                'sigle_normalise' => $sigle
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
            'nom' => $ecole->nom_ecole,
            'sigle_genere' => $sigle
        ]);
        
        return $sigle;
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
        $tableName = $base . '_' . $sigle . '_' . $anneeFormatted;
        
        Log::debug('📋 Nom de table généré', [
            'base' => $base,
            'sigle' => $sigle,
            'annee' => $annee,
            'table_name' => $tableName
        ]);
        
        return $tableName;
    }

    /**
     * Vérifier si une table existe pour une année donnée
     */
    public function tableExists(string $base, int $ecoleId, string $annee): bool
    {
        $tableName = $this->getTableName($base, $ecoleId, $annee);
        $exists = Schema::hasTable($tableName);
        
        Log::debug('🔍 Vérification table', [
            'table' => $tableName,
            'exists' => $exists
        ]);
        
        return $exists;
    }

    /**
     * Vérifier si une table existe avec son nom exact
     */
    public function tableExistsExact(string $tableName): bool
    {
        $exists = Schema::hasTable($tableName);
        Log::debug('🔍 Vérification table exacte', [
            'table' => $tableName,
            'exists' => $exists
        ]);
        return $exists;
    }

    /**
     * Récupérer toutes les tables de la base de données
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
            
            Log::warning('⚠️ Table non trouvée, tables similaires', [
                'recherche' => $tableName,
                'similaires' => $similarTables
            ]);
            
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

    // ============================================
    // MÉTHODES SPÉCIFIQUES PAR TABLE
    // ============================================

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
     * Obtenir le nom de la table des moyennes générales
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
     * Obtenir le nom de la table des pré-inscriptions
     */
    public function getPreinscriptionsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('preinscriptions', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des ré-inscriptions
     */
    public function getReinscriptionsTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('reinscriptions', $ecoleId, $annee);
    }

    /**
     * Obtenir le nom de la table des niveau_matiere
     */
    public function getNiveauMatiereTableName(int $ecoleId, string $annee): string
    {
        return $this->getTableName('niveau_matiere', $ecoleId, $annee);
    }

    // ============================================
    // MÉTHODES POUR LES TABLES STATIQUES
    // ============================================

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

    /**
     * Obtenir le nom de la table des mois scolaires (statique)
     */
    public function getMoisScolairesTableName(): string
    {
        return 'mois_scolaires';
    }

    /**
     * Obtenir le nom de la table des écoles (statique)
     */
    public function getEcolesTableName(): string
    {
        return 'ecoles';
    }

    /**
     * Obtenir le nom de la table des utilisateurs (statique)
     */
    public function getUsersTableName(): string
    {
        return 'users';
    }

    /**
     * Obtenir le nom de la table des permissions (statique)
     */
    public function getPermissionsTableName(): string
    {
        return 'permissions';
    }

    /**
     * Obtenir le nom de la table des rôles (statique)
     */
    public function getRolesTableName(): string
    {
        return 'roles';
    }

    /**
     * Obtenir le nom de la table des documents (statique)
     */
    public function getDocumentsTableName(): string
    {
        return 'documents';
    }

    /**
     * Obtenir le nom de la table des templates de documents (statique)
     */
    public function getDocumentTemplatesTableName(): string
    {
        return 'document_templates';
    }

    // ============================================
    // MÉTHODES UTILITAIRES
    // ============================================

    /**
     * Récupérer toutes les tables dynamiques pour une année et une école
     */
    public function getDynamicTablesForYear(int $ecoleId, string $annee): array
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        $suffix = $sigle . '_' . $anneeFormatted;
        
        $allTables = $this->getAllTables();
        $dynamicTables = [];
        
        foreach ($allTables as $table) {
            if (str_ends_with($table, $suffix)) {
                $dynamicTables[] = $table;
            }
        }
        
        Log::debug('📋 Tables dynamiques trouvées', [
            'ecole_id' => $ecoleId,
            'annee' => $annee,
            'sigle' => $sigle,
            'tables' => $dynamicTables
        ]);
        
        return $dynamicTables;
    }

    /**
     * Récupérer le nom de base d'une table à partir de son nom complet
     * Ex: "classes_epv_me_2026_2027" -> "classes"
     */
    public function getBaseTableName(string $fullTableName, int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        $suffix = '_' . $sigle . '_' . $anneeFormatted;
        
        if (str_ends_with($fullTableName, $suffix)) {
            return substr($fullTableName, 0, -strlen($suffix));
        }
        
        return $fullTableName;
    }

    /**
     * Vérifier si une table est dynamique (a un suffixe d'année)
     */
    public function isDynamicTable(string $tableName, int $ecoleId, string $annee): bool
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        $suffix = '_' . $sigle . '_' . $anneeFormatted;
        
        return str_ends_with($tableName, $suffix);
    }

    /**
     * Récupérer le suffixe d'une table dynamique
     */
    public function getTableSuffix(int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        $anneeFormatted = str_replace('-', '_', $annee);
        return $sigle . '_' . $anneeFormatted;
    }
}