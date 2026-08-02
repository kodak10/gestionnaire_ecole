<?php
// app/Http/Controllers/ClasseController.php

namespace App\Http\Controllers;

use App\Exports\ClassesExport;
use App\Models\Enseignant;
use App\Models\Niveau;
use App\Models\Classe;
use App\Services\TableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ClasseController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
        $this->tableService = $tableService;
    }

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        Log::info('📋 CHARGEMENT CLASSES', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'annee' => $annee
        ]);

        // Récupérer les noms des tables
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        Log::info('📋 Tables dynamiques', [
            'classes' => $classesTable,
            'niveaux' => $niveauxTable,
            'enseignants' => 'enseignants (statique)'
        ]);

        // Vérifier les tables
        if (!Schema::hasTable($classesTable)) {
            Log::error('❌ Table des classes non trouvée', ['table' => $classesTable]);
            return redirect()->route('dashboard')
                ->with('error', 'La table des classes pour l\'année ' . $annee . ' n\'existe pas.');
        }

        if (!Schema::hasTable($niveauxTable)) {
            Log::error('❌ Table des niveaux non trouvée', ['table' => $niveauxTable]);
            return redirect()->route('dashboard')
                ->with('error', 'La table des niveaux pour l\'année ' . $annee . ' n\'existe pas.');
        }

        // Enseignants (table statique)
        $enseignants = Enseignant::where('ecole_id', $ecoleId)
                        ->orderBy('nom_prenoms')
                        ->get();

        // Récupérer les classes avec jointure
        $classes = DB::table($classesTable . ' as c')
            ->leftJoin($niveauxTable . ' as n', 'c.niveau_id', '=', 'n.id')
            ->leftJoin('enseignants as e', 'c.enseignant_id', '=', 'e.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('n.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select(
                'c.*',
                'n.nom as niveau_nom',
                'n.ordre as niveau_ordre',
                'e.nom_prenoms as enseignant_nom'
            )
            ->get();

        Log::info('📊 Classes trouvées', ['count' => $classes->count()]);

        // Niveaux (table dynamique)
        $niveaux = DB::table($niveauxTable)
                    ->where('ecole_id', $ecoleId)
                    ->orderBy('ordre', 'asc')
                    ->get();

        // Compter les élèves
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        if (Schema::hasTable($elevesTable)) {
            foreach ($classes as $classe) {
                $classe->eleves_count = DB::table($elevesTable)
                    ->where('classe_id', $classe->id)
                    ->where('ecole_id', $ecoleId)
                    ->count();
            }
        } else {
            foreach ($classes as $classe) {
                $classe->eleves_count = 0;
            }
        }

        $sigle = $this->tableService->getEcoleSigle($ecoleId);
        $anneeActive = [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_nom' => session('current_ecole_nom'),
            'annee_scolaire' => $annee,
            'sigle' => $sigle,
        ];

        return view('dashboard.pages.parametrage.classe', [
            'classes' => $classes,
            'niveaux' => $niveaux,
            'annee_active' => $anneeActive,
            'enseignants' => $enseignants,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('📝 CRÉATION CLASSE', ['data' => $request->all()]);

        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
            'moy_base' => 'required|integer|min:10',
            'enseignant_id' => 'required|exists:enseignants,id',
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        
        // Récupérer le niveau depuis la table dynamique
        $niveau = DB::table($niveauxTable)->where('id', $request->niveau_id)->first();
        
        if (!$niveau) {
            return back()->withErrors(['niveau_id' => 'Niveau non trouvé'])->withInput();
        }
        
        $nomComplet = $niveau->nom . '_' . $request->nom;

        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        if (!Schema::hasTable($classesTable)) {
            return back()->withErrors(['error' => 'La table des classes n\'existe pas pour cette année.'])->withInput();
        }

        $exists = DB::table($classesTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $nomComplet)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        DB::table($classesTable)->insert([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'niveau_id' => $request->niveau_id,
            'nom' => $nomComplet,
            'capacite' => $request->capacite,
            'moy_base' => $request->moy_base,
            'enseignant_id' => $request->enseignant_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('✅ Classe créée', ['nom' => $nomComplet]);

        return redirect()->route('classes.index')->with('success', 'Classe créée avec succès');
    }

    public function update(Request $request, $id)
    {
        Log::info('📝 MISE À JOUR CLASSE', ['id' => $id]);

        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
            'moy_base' => 'required|integer|min:10',
            'enseignant_id' => 'required|exists:enseignants,id',
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        $niveau = DB::table($niveauxTable)->where('id', $request->niveau_id)->first();
        
        if (!$niveau) {
            return back()->withErrors(['niveau_id' => 'Niveau non trouvé'])->withInput();
        }
        
        $nomComplet = $niveau->nom . '_' . $request->nom;

        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        if (!Schema::hasTable($classesTable)) {
            return back()->withErrors(['error' => 'La table des classes n\'existe pas pour cette année.'])->withInput();
        }

        $exists = DB::table($classesTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $nomComplet)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        DB::table($classesTable)
            ->where('id', $id)
            ->update([
                'niveau_id' => $request->niveau_id,
                'nom' => $nomComplet,
                'capacite' => $request->capacite,
                'moy_base' => $request->moy_base,
                'enseignant_id' => $request->enseignant_id,
                'updated_at' => now(),
            ]);

        Log::info('✅ Classe mise à jour', ['id' => $id]);

        return redirect()->route('classes.index')->with('success', 'Classe mise à jour avec succès');
    }

    public function destroy($id)
    {
        Log::info('🗑️ SUPPRESSION CLASSE', ['id' => $id]);

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

        if (Schema::hasTable($elevesTable)) {
            $elevesCount = DB::table($elevesTable)
                ->where('classe_id', $id)
                ->where('ecole_id', $ecoleId)
                ->count();

            if ($elevesCount > 0) {
                return redirect()->back()->with('error', 'Impossible de supprimer une classe avec des élèves');
            }
        }

        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        if (Schema::hasTable($classesTable)) {
            DB::table($classesTable)->where('id', $id)->delete();
            Log::info('✅ Classe supprimée', ['id' => $id]);
        }

        return redirect()->route('classes.index')->with('success', 'Classe supprimée avec succès');
    }

    public function export($type)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        if (!Schema::hasTable($classesTable) || !Schema::hasTable($niveauxTable)) {
            return redirect()->back()->with('error', 'Tables non trouvées pour l\'année ' . $annee);
        }

        $classes = DB::table($classesTable . ' as c')
            ->join($niveauxTable . ' as n', 'c.niveau_id', '=', 'n.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('n.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select('c.*', 'n.nom as niveau_nom')
            ->get();

        if ($type == 'pdf') {
            $pdf = Pdf::loadView('exports.classes-pdf', compact('classes', 'annee'));
            return $pdf->download('classes-' . $annee . '.pdf');
        }

        return Excel::download(new ClassesExport($classes), 'classes-' . $annee . '.xlsx');
    }

    public function getByNiveau($niveauId)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        if (!Schema::hasTable($classesTable)) {
            return response()->json([]);
        }

        $classes = DB::table($classesTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('niveau_id', $niveauId)
            ->orderBy('nom', 'asc')
            ->get();

        return response()->json($classes);
    }
}