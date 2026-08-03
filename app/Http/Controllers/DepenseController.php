<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\DepenseCategorie;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepenseController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
        $this->tableService = $tableService;
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        // Récupérer les catégories depuis la table dynamique
        $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);
        
        $categories = DB::table($categoriesTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')
            ->get();
            
        return view('dashboard.pages.depenses.index', compact('categories'));
    }

    public function getDepensesData(Request $request)
    {
        $request->validate([
            'depense_category_id' => 'nullable|numeric',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date'
        ]);

        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        try {
            // Récupérer les noms des tables dynamiques
            $depensesTable = $this->tableService->getDepensesTableName($ecoleId, $annee);
            $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);

            $query = DB::table($depensesTable . ' as d')
                ->leftJoin($categoriesTable . ' as c', 'd.depense_category_id', '=', 'c.id')
                ->where('d.annee_scolaire_id', $anneeScolaireId)
                ->where('d.ecole_id', $ecoleId)
                ->select('d.*', 'c.nom as categorie_nom');
                
            if ($request->depense_category_id) {
                $query->where('d.depense_category_id', $request->depense_category_id);
            }
            
            if ($request->date_debut) {
                $query->where('d.date_depense', '>=', $request->date_debut);
            }
            
            if ($request->date_fin) {
                $query->where('d.date_depense', '<=', $request->date_fin);
            }
            
            $depenses = $query->orderBy('d.date_depense', 'desc')->get();
            
            $totalDepenses = $depenses->sum('montant');
            
            // Statistiques par catégorie
            $statsParCategorie = DB::table($depensesTable . ' as d')
                ->join($categoriesTable . ' as c', 'd.depense_category_id', '=', 'c.id')
                ->where('d.annee_scolaire_id', $anneeScolaireId)
                ->where('d.ecole_id', $ecoleId)
                ->select('c.nom as categorie', DB::raw('SUM(d.montant) as total'))
                ->groupBy('c.nom')
                ->get();

            return response()->json([
                'success' => true,
                'depenses' => $depenses,
                'total_depenses' => $totalDepenses,
                'stats_categories' => $statsParCategorie
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur getDepensesData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Ajouter une nouvelle catégorie de dépense
     */
    public function storeCategorie(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);

            // Vérifier si la table des catégories existe, sinon la créer
            if (!$this->tableService->tableExistsExact($categoriesTable)) {
                $this->createCategoriesTable($categoriesTable);
            }

            // Vérifier si la catégorie existe déjà
            $existing = DB::table($categoriesTable)
                ->where('nom', $request->nom)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette catégorie existe déjà'
                ]);
            }

            $id = DB::table($categoriesTable)->insertGetId([
                'nom' => $request->nom,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $categorie = DB::table($categoriesTable)
                ->where('id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie ajoutée avec succès',
                'categorie' => $categorie
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur storeCategorie: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la catégorie: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer la table des catégories si elle n'existe pas
     */
    private function createCategoriesTable($tableName)
    {
        try {
            DB::statement("CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `nom` varchar(255) NOT NULL,
                `ecole_id` bigint(20) UNSIGNED NOT NULL,
                `annee_scolaire_id` bigint(20) UNSIGNED NOT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ecole_id` (`ecole_id`),
                KEY `idx_annee_scolaire_id` (`annee_scolaire_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            Log::info('Table des catégories créée avec succès: ' . $tableName);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la table des catégories: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer la table des dépenses si elle n'existe pas
     */
    private function createDepensesTable($tableName)
    {
        try {
            DB::statement("CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `ecole_id` bigint(20) UNSIGNED NOT NULL,
                `annee_scolaire_id` bigint(20) UNSIGNED NOT NULL,
                `libelle` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `montant` decimal(15,2) NOT NULL,
                `date_depense` date NOT NULL,
                `depense_category_id` bigint(20) UNSIGNED NOT NULL,
                `mode_paiement` varchar(50) NOT NULL,
                `beneficiaire` varchar(255) NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `justificatif` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ecole_id` (`ecole_id`),
                KEY `idx_annee_scolaire_id` (`annee_scolaire_id`),
                KEY `idx_category_id` (`depense_category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            Log::info('Table des dépenses créée avec succès: ' . $tableName);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la table des dépenses: ' . $e->getMessage());
            throw $e;
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'required|numeric|min:1',
            'date_depense' => 'required|date',
            'depense_category_id' => 'required|numeric',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'beneficiaire' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'justificatif' => 'nullable|string',
        ]);

        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        try {
            $depensesTable = $this->tableService->getDepensesTableName($ecoleId, $annee);
            $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);

            // Vérifier que la table des catégories existe
            if (!$this->tableService->tableExistsExact($categoriesTable)) {
                $this->createCategoriesTable($categoriesTable);
            }

            // Vérifier que la table des dépenses existe
            if (!$this->tableService->tableExistsExact($depensesTable)) {
                $this->createDepensesTable($depensesTable);
            }

            // Vérifier que la catégorie existe
            $categorie = DB::table($categoriesTable)
                ->where('id', $request->depense_category_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée. Veuillez créer une catégorie d\'abord.'
                ]);
            }

            $id = DB::table($depensesTable)->insertGetId([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'libelle' => $request->libelle,
                'description' => $request->description ?? '',
                'montant' => $request->montant,
                'date_depense' => $request->date_depense,
                'depense_category_id' => $request->depense_category_id,
                'mode_paiement' => $request->mode_paiement,
                'beneficiaire' => $request->beneficiaire,
                'reference' => $request->reference ?? '',
                'justificatif' => $request->justificatif ?? '',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Récupérer la dépense avec sa catégorie
            $depense = DB::table($depensesTable . ' as d')
                ->leftJoin($categoriesTable . ' as c', 'd.depense_category_id', '=', 'c.id')
                ->where('d.id', $id)
                ->select('d.*', 'c.nom as categorie_nom')
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Dépense enregistrée avec succès',
                'depense' => $depense
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la dépense: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'required|numeric|min:1',
            'date_depense' => 'required|date',
            'depense_category_id' => 'required|numeric',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'beneficiaire' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'justificatif' => 'nullable|string'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $depensesTable = $this->tableService->getDepensesTableName($ecoleId, $annee);
            $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);

            // Vérifier que la catégorie existe
            $categorie = DB::table($categoriesTable)
                ->where('id', $request->depense_category_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ]);
            }

            DB::table($depensesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->update([
                    'libelle' => $request->libelle,
                    'description' => $request->description ?? '',
                    'montant' => $request->montant,
                    'date_depense' => $request->date_depense,
                    'depense_category_id' => $request->depense_category_id,
                    'mode_paiement' => $request->mode_paiement,
                    'beneficiaire' => $request->beneficiaire,
                    'reference' => $request->reference ?? '',
                    'justificatif' => $request->justificatif ?? '',
                    'updated_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Dépense modifiée avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la dépense: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $depensesTable = $this->tableService->getDepensesTableName($ecoleId, $annee);
            $categoriesTable = $this->tableService->getDepenseCategoriesTableName($ecoleId, $annee);

            $depense = DB::table($depensesTable . ' as d')
                ->leftJoin($categoriesTable . ' as c', 'd.depense_category_id', '=', 'c.id')
                ->where('d.id', $id)
                ->where('d.ecole_id', $ecoleId)
                ->where('d.annee_scolaire_id', $anneeScolaireId)
                ->select('d.*', 'c.nom as categorie_nom')
                ->first();

            if (!$depense) {
                throw new \Exception('Dépense non trouvée');
            }

            return response()->json($depense);

        } catch (\Exception $e) {
            Log::error('Erreur show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la dépense: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $depensesTable = $this->tableService->getDepensesTableName($ecoleId, $annee);

            DB::table($depensesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Dépense supprimée avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la dépense: ' . $e->getMessage()
            ]);
        }
    }
}