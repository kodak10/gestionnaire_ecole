<?php
// app/Http/Controllers/Admin/AnneeScolaireController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecole;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\Matiere;
use App\Models\MoisScolaire;
use App\Services\AnneeScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AnneeScolaireController extends Controller
{
    protected $anneeService;

    public function __construct(AnneeScolaireService $anneeService)
    {
        $this->anneeService = $anneeService;
    }

    /**
     * Afficher le dashboard d'administration
     */
    public function dashboard()
    {
        $totalEcoles = Ecole::count();
        $totalAnnees = AnneeScolaire::count();
        $totalClasses = Classe::count();
        $totalNiveaux = Niveau::count();
        $totalMatieres = Matiere::count();
        
        $ecoles = Ecole::with(['anneesScolaires' => function($query) {
            $query->orderBy('annee', 'desc');
        }])->get();
        
        $anneesScolaires = AnneeScolaire::with('ecole')
            ->orderBy('annee', 'desc')
            ->get();
        
        return view('dashboard.pages.admin.dashboard', compact(
            'totalEcoles',
            'totalAnnees',
            'totalClasses',
            'totalNiveaux',
            'totalMatieres',
            'ecoles',
            'anneesScolaires'
        ));
    }

    /**
     * Créer une nouvelle école
     */
    public function createEcole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:ecoles',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            Log::info('🔒 Transaction démarrée - Création école');

            $ecole = Ecole::create([
                'nom' => $request->nom,
                'code' => $request->code,
                'adresse' => $request->adresse,
                'telephone' => $request->telephone,
                'email' => $request->email,
            ]);

            DB::commit();
            Log::info('✅ École créée avec succès', ['id' => $ecole->id]);

            return response()->json([
                'success' => true,
                'message' => 'École créée avec succès',
                'ecole' => $ecole
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur création école', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle année scolaire avec ses tables
     */
    public function createAnneeScolaire(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ecole_id' => 'required|exists:ecoles,id',
            'annee' => 'required|string|max:50',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier l'unicité
        $existing = AnneeScolaire::where('annee', $request->annee)
            ->where('ecole_id', $request->ecole_id)
            ->first();

        if ($existing) {
            Log::warning('⚠️ Année scolaire déjà existante', [
                'annee' => $request->annee,
                'ecole_id' => $request->ecole_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Cette année scolaire existe déjà pour cette école'
            ], 422);
        }

        $anneeScolaire = null;
        $result = null;

        try {
            // ============================================
            // 1. TRANSACTION : Création année + mois
            // ============================================
            DB::beginTransaction();
            Log::info('🔒 Transaction démarrée - Création année scolaire');

            $anneeScolaire = AnneeScolaire::create([
                'annee' => $request->annee,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'est_active' => $request->has('est_active') ? 1 : 0,
                'ecole_id' => $request->ecole_id,
            ]);
            Log::info('✅ Année scolaire créée', ['id' => $anneeScolaire->id, 'annee' => $request->annee]);

            // Créer les mois scolaires
            $moisNoms = ['Septembre', 'Octobre', 'Novembre', 'Décembre', 
                         'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'];

            foreach ($moisNoms as $index => $nom) {
                MoisScolaire::create([
                    'nom' => $nom,
                    'numero' => $index + 1,
                ]);
            }
            Log::info('✅ Mois scolaires créés', ['count' => count($moisNoms)]);

            // COMMIT de la transaction (année + mois)
            DB::commit();
            Log::info('✅ Transaction commitée avec succès');

            // ============================================
            // 2. CRÉATION DES TABLES (HORS TRANSACTION)
            // ============================================
            Log::info('🚀 Création des tables pour l\'année', ['annee' => $request->annee]);
            
            $result = $this->anneeService->createTablesForYear($request->annee, $request->ecole_id);

            if (!$result['success']) {
                Log::error('❌ Échec création des tables, suppression de l\'année', [
                    'annee' => $request->annee,
                    'error' => $result['message']
                ]);
                
                $anneeScolaire->delete();
                Log::info('🗑️ Année scolaire supprimée', ['id' => $anneeScolaire->id]);
                
                $this->anneeService->forceDropTables($this->anneeService->formatSuffix($request->annee), $request->ecole_id);
                Log::info('🧹 Tables nettoyées');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création des tables: ' . $result['message']
                ], 500);
            }
            
            Log::info('✅ Tables créées avec succès', [
                'tables' => $result['tables'] ?? []
            ]);

            // ============================================
            // 3. MIGRATION DES DONNÉES (HORS TRANSACTION)
            // ============================================
            Log::info('🔄 Migration des inscriptions...');
            
            $migrationResult = $this->anneeService->migrateInscriptionsToEleves(
                $request->annee,
                $request->ecole_id
            );

            if (!$migrationResult['success']) {
                Log::warning('⚠️ Migration partielle ou échouée', [
                    'message' => $migrationResult['message']
                ]);
            } else {
                Log::info('✅ Migration terminée', [
                    'count' => $migrationResult['count'] ?? 0
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Année scolaire créée avec succès',
                'annee' => $anneeScolaire,
                'tables' => $result,
                'migration' => $migrationResult
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRÉATION ANNÉE SCOLAIRE', [
                'annee' => $request->annee,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            try {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                    Log::info('🔄 Rollback effectué');
                }
            } catch (\Exception $rollbackException) {
                Log::warning('⚠️ Erreur lors du rollback', [
                    'error' => $rollbackException->getMessage()
                ]);
            }

            if ($anneeScolaire) {
                try {
                    $anneeScolaire->delete();
                    Log::info('🗑️ Année scolaire supprimée', ['id' => $anneeScolaire->id]);
                } catch (\Exception $delEx) {
                    Log::warning('⚠️ Erreur suppression année', [
                        'error' => $delEx->getMessage()
                    ]);
                }
            }

            try {
                $suffix = $this->anneeService->formatSuffix($request->annee);
                $this->anneeService->forceDropTables($suffix, $request->ecole_id);
                Log::info('🧹 Tables nettoyées après erreur');
            } catch (\Exception $cleanEx) {
                Log::warning('⚠️ Erreur nettoyage tables', [
                    'error' => $cleanEx->getMessage()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une année scolaire
     */
    public function deleteAnneeScolaire($id)
    {
        try {
            $annee = AnneeScolaire::findOrFail($id);
            $anneeLibelle = $annee->annee;
            $ecoleId = $annee->ecole_id;
            
            Log::info('🗑️ Suppression année scolaire', [
                'id' => $id,
                'annee' => $anneeLibelle
            ]);

            $result = $this->anneeService->dropTablesForYear($anneeLibelle, $ecoleId);

            if (!$result['success']) {
                Log::error('❌ Erreur suppression tables', [
                    'error' => $result['message']
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression des tables: ' . $result['message']
                ], 500);
            }

            DB::transaction(function() use ($annee) {
                MoisScolaire::where('numero', '>=', 1)
                    ->where('numero', '<=', 10)
                    ->delete();
                $annee->delete();
            });
            
            Log::info('✅ Année scolaire supprimée avec succès', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Année scolaire supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression année scolaire', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver une année scolaire
     */
    public function toggleAnneeScolaire($id)
    {
        try {
            $annee = AnneeScolaire::findOrFail($id);
            
            Log::info('🔄 Toggle année scolaire', [
                'id' => $id,
                'current_status' => $annee->est_active
            ]);
            
            AnneeScolaire::where('ecole_id', $annee->ecole_id)
                ->where('id', '!=', $id)
                ->update(['est_active' => false]);
            
            $annee->est_active = !$annee->est_active;
            $annee->save();
            
            Log::info('✅ Année ' . ($annee->est_active ? 'activée' : 'désactivée'), [
                'id' => $id,
                'new_status' => $annee->est_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Année scolaire ' . ($annee->est_active ? 'activée' : 'désactivée'),
                'est_active' => $annee->est_active
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur toggle année scolaire', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Régénérer les tables d'une année
     */
    public function regenerateTables($id)
    {
        try {
            $annee = AnneeScolaire::findOrFail($id);
            $ecoleId = $annee->ecole_id;
            
            Log::info('🔄 Régénération des tables', [
                'id' => $id,
                'annee' => $annee->annee
            ]);
            
            $result = $this->anneeService->dropTablesForYear($annee->annee, $ecoleId);
            
            if (!$result['success']) {
                Log::error('❌ Erreur suppression tables', [
                    'error' => $result['message']
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression: ' . $result['message']
                ], 500);
            }
            
            $result = $this->anneeService->createTablesForYear($annee->annee, $ecoleId);
            
            if (!$result['success']) {
                Log::error('❌ Erreur création tables', [
                    'error' => $result['message']
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création: ' . $result['message']
                ], 500);
            }

            $migrationResult = $this->anneeService->migrateInscriptionsToEleves(
                $annee->annee,
                $annee->ecole_id
            );
            
            Log::info('✅ Tables régénérées avec succès');

            return response()->json([
                'success' => true,
                'message' => 'Tables régénérées avec succès',
                'tables' => $result,
                'migration' => $migrationResult
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur régénération tables', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier l'existence des tables
     */
    public function checkTables($id)
    {
        try {
            $annee = AnneeScolaire::findOrFail($id);
            $ecoleId = $annee->ecole_id;
            
            $tablesExist = $this->anneeService->checkTablesExist($annee->annee, $ecoleId);

            return response()->json([
                'success' => true,
                'tables' => $tablesExist
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification tables', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les classes par école
     */
    public function getClassesByEcole($ecoleId)
    {
        try {
            $classes = Classe::where('ecole_id', $ecoleId)
                ->with('niveau')
                ->orderBy('nom')
                ->get();

            return response()->json([
                'success' => true,
                'classes' => $classes
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération classes', [
                'ecole_id' => $ecoleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les niveaux par école
     */
    public function getNiveauxByEcole($ecoleId)
    {
        try {
            $niveaux = Niveau::where('ecole_id', $ecoleId)
                ->orderBy('ordre')
                ->get();

            return response()->json([
                'success' => true,
                'niveaux' => $niveaux
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération niveaux', [
                'ecole_id' => $ecoleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les matières par école
     */
    public function getMatieresByEcole($ecoleId)
    {
        try {
            $matieres = Matiere::where('ecole_id', $ecoleId)
                ->orderBy('nom')
                ->get();

            return response()->json([
                'success' => true,
                'matieres' => $matieres
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération matières', [
                'ecole_id' => $ecoleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}