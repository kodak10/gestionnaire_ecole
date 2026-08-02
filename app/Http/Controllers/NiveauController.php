<?php
// app/Http/Controllers/NiveauController.php

namespace App\Http\Controllers;

use App\Models\Niveau;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NiveauController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
        $this->tableService = $tableService;
    }

    /**
     * Afficher la liste des niveaux
     */
    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        Log::info('📋 CHARGEMENT NIVEAUX', [
            'ecole_id' => $ecoleId,
            'annee' => $annee
        ]);

        // Récupérer le nom de la table des niveaux
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        Log::info('📋 Table dynamique', [
            'niveaux' => $niveauxTable
        ]);

        // Vérifier si la table existe
        if (!Schema::hasTable($niveauxTable)) {
            Log::error('❌ Table des niveaux non trouvée', ['table' => $niveauxTable]);
            return redirect()->route('dashboard')
                ->with('error', 'La table des niveaux pour l\'année ' . $annee . ' n\'existe pas.');
        }

        // Récupérer les niveaux
        $niveaux = DB::table($niveauxTable)
            ->where('ecole_id', $ecoleId)
            ->orderBy('ordre', 'asc')
            ->get();

        Log::info('📊 Niveaux trouvés', ['count' => $niveaux->count()]);

        // Compter les classes par niveau
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        if (Schema::hasTable($classesTable)) {
            foreach ($niveaux as $niveau) {
                $niveau->classes_count = DB::table($classesTable)
                    ->where('niveau_id', $niveau->id)
                    ->where('ecole_id', $ecoleId)
                    ->count();
            }
        } else {
            foreach ($niveaux as $niveau) {
                $niveau->classes_count = 0;
            }
        }

        return view('dashboard.pages.parametrage.niveaux', [
            'niveaux' => $niveaux,
            'annee_active' => [
                'ecole_id' => $ecoleId,
                'ecole_nom' => session('current_ecole_nom'),
                'annee_scolaire' => $annee,
                'sigle' => $this->tableService->getEcoleSigle($ecoleId)
            ]
        ]);
    }

    /**
     * Enregistrer un nouveau niveau
     */
    public function store(Request $request)
    {
        Log::info('📝 CRÉATION NIVEAU', ['data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'ordre' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        if (!Schema::hasTable($niveauxTable)) {
            return redirect()->back()
                ->with('error', 'La table des niveaux n\'existe pas pour cette année.')
                ->withInput();
        }

        // Vérifier si le niveau existe déjà
        $exists = DB::table($niveauxTable)
            ->where('ecole_id', $ecoleId)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Ce niveau existe déjà.')
                ->withInput();
        }

        // Insérer le nouveau niveau
        $id = DB::table($niveauxTable)->insertGetId([
            'ecole_id' => $ecoleId,
            'nom' => $request->nom,
            'ordre' => $request->ordre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('✅ Niveau créé', [
            'id' => $id,
            'nom' => $request->nom
        ]);

        return redirect()->route('niveaux.index')
            ->with('success', 'Niveau créé avec succès.');
    }

    /**
     * Mettre à jour un niveau
     */
    public function update(Request $request, $id)
    {
        Log::info('📝 MISE À JOUR NIVEAU', [
            'id' => $id,
            'data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'ordre' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        if (!Schema::hasTable($niveauxTable)) {
            return redirect()->back()
                ->with('error', 'La table des niveaux n\'existe pas pour cette année.');
        }

        // Vérifier si le niveau existe
        $niveau = DB::table($niveauxTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$niveau) {
            return redirect()->back()
                ->with('error', 'Niveau non trouvé.');
        }

        // Vérifier si un autre niveau a le même nom
        $exists = DB::table($niveauxTable)
            ->where('ecole_id', $ecoleId)
            ->where('nom', $request->nom)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Un autre niveau porte déjà ce nom.')
                ->withInput();
        }

        // Mettre à jour
        DB::table($niveauxTable)
            ->where('id', $id)
            ->update([
                'nom' => $request->nom,
                'ordre' => $request->ordre,
                'updated_at' => now(),
            ]);

        Log::info('✅ Niveau mis à jour', [
            'id' => $id,
            'nom' => $request->nom
        ]);

        return redirect()->route('niveaux.index')
            ->with('success', 'Niveau mis à jour avec succès.');
    }

    /**
     * Supprimer un niveau
     */
    public function destroy($id)
    {
        Log::info('🗑️ SUPPRESSION NIVEAU', ['id' => $id]);

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        if (!Schema::hasTable($niveauxTable)) {
            return redirect()->back()
                ->with('error', 'La table des niveaux n\'existe pas pour cette année.');
        }

        // Vérifier si le niveau existe
        $niveau = DB::table($niveauxTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$niveau) {
            return redirect()->back()
                ->with('error', 'Niveau non trouvé.');
        }

        // Vérifier si des classes sont associées à ce niveau
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        if (Schema::hasTable($classesTable)) {
            $classesCount = DB::table($classesTable)
                ->where('niveau_id', $id)
                ->where('ecole_id', $ecoleId)
                ->count();

            if ($classesCount > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer ce niveau car il contient ' . $classesCount . ' classe(s).');
            }
        }

        // Supprimer le niveau
        DB::table($niveauxTable)
            ->where('id', $id)
            ->delete();

        Log::info('✅ Niveau supprimé', ['id' => $id]);

        return redirect()->route('niveaux.index')
            ->with('success', 'Niveau supprimé avec succès.');
    }

    /**
     * Réorganiser les niveaux (drag & drop)
     */
    public function reorder(Request $request)
    {
        Log::info('🔄 RÉORGANISATION NIVEAUX', ['data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:niveaux,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.'
            ], 422);
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        if (!Schema::hasTable($niveauxTable)) {
            return response()->json([
                'success' => false,
                'message' => 'Table des niveaux non trouvée.'
            ], 404);
        }

        try {
            foreach ($request->order as $index => $id) {
                DB::table($niveauxTable)
                    ->where('id', $id)
                    ->where('ecole_id', $ecoleId)
                    ->update(['ordre' => $index + 1]);
            }

            Log::info('✅ Niveaux réorganisés');

            return response()->json([
                'success' => true,
                'message' => 'Ordre des niveaux mis à jour.'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur réorganisation niveaux', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réorganisation: ' . $e->getMessage()
            ], 500);
        }
    }
}