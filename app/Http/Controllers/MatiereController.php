<?php
// app/Http/Controllers/MatiereController.php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MatiereController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
        $this->tableService = $tableService;
    }

    /**
     * Afficher la liste des matières
     */
    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Récupérer les noms des tables dynamiques
        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        $niveauMatiereTable = $this->tableService->getTableName('niveau_matiere', $ecoleId, $annee);

        // Vérifier si les tables existent
        if (!Schema::hasTable($matieresTable)) {
            Log::error('❌ Table des matières non trouvée', ['table' => $matieresTable]);
            return redirect()->route('dashboard')
                ->with('error', 'La table des matières pour l\'année ' . $annee . ' n\'existe pas.');
        }

        // Récupérer les niveaux (table dynamique)
        if (Schema::hasTable($niveauxTable)) {
            $niveaux = DB::table($niveauxTable)
                ->where('ecole_id', $ecoleId)
                ->orderBy('ordre', 'asc')
                ->get();
        } else {
            $niveaux = collect();
        }

        // Récupérer les matières (sans filtre année_scolaire_id car c'est une table dynamique)
        $matieresQuery = DB::table($matieresTable . ' as m')
            ->where('m.ecole_id', $ecoleId)
            ->orderBy('m.nom', 'asc');

        // Filtre par niveau
        if ($request->filled('niveau_id')) {
            if (Schema::hasTable($niveauMatiereTable)) {
                $matieresQuery->join($niveauMatiereTable . ' as nm', 'm.id', '=', 'nm.matiere_id')
                    ->where('nm.niveau_id', $request->niveau_id)
                    ->select('m.*');
            }
        }

        $matieres = $matieresQuery->get();

        // Pour chaque matière, récupérer les niveaux associés
        if (Schema::hasTable($niveauMatiereTable) && Schema::hasTable($niveauxTable)) {
            foreach ($matieres as $matiere) {
                $matiere->niveaux = DB::table($niveauMatiereTable . ' as nm')
                    ->join($niveauxTable . ' as n', 'nm.niveau_id', '=', 'n.id')
                    ->where('nm.matiere_id', $matiere->id)
                    ->where('nm.ecole_id', $ecoleId)
                    ->select('n.id', 'n.nom', 'nm.coefficient', 'nm.ordre', 'nm.denominateur')
                    ->orderBy('n.ordre', 'asc')
                    ->get();
            }
        }

        return view('dashboard.pages.parametrage.matiere', [
            'matieres' => $matieres,
            'niveaux' => $niveaux,
            'annee_active' => [
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_nom' => session('current_ecole_nom'),
                'annee_scolaire' => $annee,
                'sigle' => $this->tableService->getEcoleSigle($ecoleId)
            ]
        ]);
    }

    /**
     * Récupérer les matières d'un niveau
     */
    public function getMatieres($niveauId)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);
        $niveauMatiereTable = $this->tableService->getTableName('niveau_matiere', $ecoleId, $annee);

        // Vérifier si le niveau existe
        $niveau = DB::table($niveauxTable)
            ->where('id', $niveauId)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$niveau) {
            return response()->json(['error' => 'Niveau non trouvé'], 404);
        }

        // Récupérer les matières du niveau
        $matieres = DB::table($niveauMatiereTable . ' as nm')
            ->join($matieresTable . ' as m', 'nm.matiere_id', '=', 'm.id')
            ->where('nm.niveau_id', $niveauId)
            ->where('nm.ecole_id', $ecoleId)
            ->orderBy('nm.ordre', 'asc')
            ->select(
                'm.id',
                'm.nom',
                'nm.coefficient',
                'nm.ordre',
                'nm.denominateur'
            )
            ->get();
        return response()->json($matieres);
    }

    /**
     * Créer une nouvelle matière
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);

        if (!Schema::hasTable($matieresTable)) {
            return redirect()->back()
                ->with('error', 'La table des matières n\'existe pas pour cette année.')
                ->withInput();
        }

        // Vérifier si la matière existe déjà
        $exists = DB::table($matieresTable)
            ->where('ecole_id', $ecoleId)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Cette matière existe déjà.')
                ->withInput();
        }

        // Insérer la nouvelle matière (sans annee_scolaire_id)
        $id = DB::table($matieresTable)->insertGetId([
            'ecole_id' => $ecoleId,
            'nom' => $request->nom,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('matieres.index')
            ->with('success', 'Matière créée avec succès.');
    }

    /**
     * Mettre à jour une matière
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);

        if (!Schema::hasTable($matieresTable)) {
            return redirect()->back()
                ->with('error', 'La table des matières n\'existe pas pour cette année.');
        }

        // Vérifier si la matière existe
        $matiere = DB::table($matieresTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$matiere) {
            return redirect()->back()
                ->with('error', 'Matière non trouvée.');
        }

        // Vérifier si une autre matière a le même nom
        $exists = DB::table($matieresTable)
            ->where('ecole_id', $ecoleId)
            ->where('nom', $request->nom)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Une autre matière porte déjà ce nom.')
                ->withInput();
        }

        // Mettre à jour
        DB::table($matieresTable)
            ->where('id', $id)
            ->update([
                'nom' => $request->nom,
                'updated_at' => now(),
            ]);

        return redirect()->route('matieres.index')
            ->with('success', 'Matière mise à jour avec succès.');
    }

    /**
     * Supprimer une matière
     */
    public function destroy($id)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);
        $niveauMatiereTable = $this->tableService->getTableName('niveau_matiere', $ecoleId, $annee);

        if (!Schema::hasTable($matieresTable)) {
            return redirect()->back()
                ->with('error', 'La table des matières n\'existe pas pour cette année.');
        }

        // Vérifier si la matière existe
        $matiere = DB::table($matieresTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$matiere) {
            return redirect()->back()
                ->with('error', 'Matière non trouvée.');
        }

        // Vérifier si la matière est associée à des niveaux
        if (Schema::hasTable($niveauMatiereTable)) {
            $niveauxCount = DB::table($niveauMatiereTable)
                ->where('matiere_id', $id)
                ->where('ecole_id', $ecoleId)
                ->count();

            if ($niveauxCount > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer cette matière car elle est associée à ' . $niveauxCount . ' niveau(x).');
            }
        }

        // Supprimer la matière
        DB::table($matieresTable)
            ->where('id', $id)
            ->delete();

        return redirect()->route('matieres.index')
            ->with('success', 'Matière supprimée avec succès.');
    }

    /**
     * Assigner des matières à un niveau
     */
    public function assignMatieres(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'niveau_id' => 'required|exists:niveaux,id',
            'matieres' => 'required|array',
            'matieres.*' => 'required|integer',
            'coefficients' => 'required|array',
            'coefficients.*' => 'required|numeric|min:0.1|max:10',
            'ordres' => 'required|array',
            'ordres.*' => 'required|integer|min:1',
            'denominateurs' => 'required|array',
            'denominateurs.*' => 'required|integer|min:1',
        ]);

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);
        $niveauMatiereTable = $this->tableService->getTableName('niveau_matiere', $ecoleId, $annee);

        // Vérifier si le niveau existe
        $niveau = DB::table($niveauxTable)
            ->where('id', $request->niveau_id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$niveau) {
            return redirect()->back()
                ->with('error', 'Niveau non trouvé.');
        }

        if (!Schema::hasTable($niveauMatiereTable)) {
            return redirect()->back()
                ->with('error', 'La table niveau_matiere n\'existe pas pour cette année.');
        }

        // Supprimer les anciennes associations
        DB::table($niveauMatiereTable)
            ->where('niveau_id', $request->niveau_id)
            ->where('ecole_id', $ecoleId)
            ->delete();

        // Créer les nouvelles associations
        foreach ($request->matieres as $matiereId) {
            $coefficient = $request->coefficients[$matiereId] ?? 1;
            $ordre = $request->ordres[$matiereId] ?? 1;
            $denominateur = $request->denominateurs[$matiereId] ?? 20;

            DB::table($niveauMatiereTable)->insert([
                'niveau_id' => $request->niveau_id,
                'matiere_id' => $matiereId,
                'coefficient' => $coefficient,
                'ordre' => $ordre,
                'denominateur' => $denominateur,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Matières assignées avec succès.');
    }

    /**
     * Mettre à jour les classes associées à une matière (via niveau_matiere)
     */
    public function updateClasses(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'niveaux' => 'required|array',
            'niveaux.*' => 'integer|min:0|max:10'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $matieresTable = $this->tableService->getTableName('matieres', $ecoleId, $annee);
        $niveauMatiereTable = $this->tableService->getTableName('niveau_matiere', $ecoleId, $annee);

        // Vérifier si la matière existe
        $matiere = DB::table($matieresTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$matiere) {
            return redirect()->back()
                ->with('error', 'Matière non trouvée.');
        }

        if (!Schema::hasTable($niveauMatiereTable)) {
            return redirect()->back()
                ->with('error', 'La table niveau_matiere n\'existe pas pour cette année.');
        }

        // Supprimer les associations existantes
        DB::table($niveauMatiereTable)
            ->where('matiere_id', $id)
            ->where('ecole_id', $ecoleId)
            ->delete();

        // Créer les nouvelles associations
        foreach ($request->niveaux as $niveauId => $coefficient) {
            if ($coefficient > 0) {
                DB::table($niveauMatiereTable)->insert([
                    'niveau_id' => $niveauId,
                    'matiere_id' => $id,
                    'coefficient' => $coefficient,
                    'ordre' => 1,
                    'denominateur' => 20,
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('matieres.index')
            ->with('success', 'Classes associées mises à jour avec succès.');
    }
}