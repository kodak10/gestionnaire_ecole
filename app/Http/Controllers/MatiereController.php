<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MatiereController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
    }

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveaux = Niveau::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('created_at', 'asc')
            ->get();

        $matieresQuery = Matiere::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('niveau_id')) {
            $matieresQuery->where('niveau_id', $request->niveau_id);
        }

        $matieres = $matieresQuery->get();

        return view('dashboard.pages.parametrage.matiere', compact('matieres', 'niveaux'));
    }

    public function getMatieres($id)
{
    $niveau = Niveau::findOrFail($id);

    $matieres = $niveau->matieres()
                ->orderBy('niveau_matiere.ordre', 'asc')
                ->get()
                ->map(function($matiere) {
                    return [
                        'id' => $matiere->id,
                        'nom' => $matiere->nom,
                        'coefficient' => $matiere->pivot->coefficient,
                        'ordre' => $matiere->pivot->ordre,
                        'denominateur' => $matiere->pivot->denominateur,
                    ];
                });
    Log::info('Matières:', $matieres->toArray());

    return response()->json($matieres);
}



    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:matieres,nom',
        ]);

        // Récupérer ecole et année depuis la session
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Matiere::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'nom' => $validated['nom'],
        ]);

        return redirect()->route('matieres.index')->with('success', 'Matière créée avec succès');
    }

    public function update(Request $request, Matiere $matiere)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:matieres,nom,'.$matiere->id,
        ]);

        $matiere->update([
            'nom' => $validated['nom'],
        ]);

        return redirect()->route('matieres.index')->with('success', 'Matière mise à jour avec succès');
    }

    public function destroy(Matiere $matiere)
    {
        // Vérifier si la matière est utilisée dans des classes
        if ($matiere->niveaux()->count() > 0) {
            return redirect()->route('matieres.index')->with('error', 'Impossible de supprimer : cette matière est associée à des classes');
        }

        $matiere->delete();
        
        return redirect()->route('matieres.index')->with('success', 'Matière supprimée avec succès');
    }

    public function updateClasses(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);

        $validated = $request->validate([
            'niveau' => 'required|array',
            'niveau.*' => 'integer|min:0|max:10'
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveauxToSync = [];
        foreach ($request->niveau as $niveauId => $coefficient) {
            if ($coefficient > 0) {
                $niveauxToSync[$niveauId] = [
                    'coefficient' => $coefficient,
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId
                ];
            }
        }

        $matiere->niveaux()->sync($niveauxToSync);

        return redirect()->route('matieres.index')->with('success', 'Classes associées mises à jour avec succès');
    }

    public function assignMatieres(Request $request)
{
    $request->validate([
        'niveau_id' => 'required|exists:niveaux,id',
        'matieres' => 'required|array',
        'matieres.*' => 'exists:matieres,id',
        'coefficients' => 'required|array',
        'coefficients.*' => 'integer|min:1|max:10',
        'ordres' => 'required|array',
        'ordres.*' => 'integer|min:1',
        'denominateurs' => 'required|array',
        'denominateurs.*' => 'integer|min:1',
    ]);

    $niveau = Niveau::findOrFail($request->niveau_id);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');

    $syncData = [];

    foreach ($request->matieres as $matiereId) {
        $syncData[$matiereId] = [
            'coefficient' => $request->coefficients[$matiereId],
            'ordre' => $request->ordres[$matiereId],
            'denominateur' => $request->denominateurs[$matiereId],
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
        ];
    }

    $niveau->matieres()->sync($syncData);

    return redirect()->back()->with('success', 'Matières affectées avec succès au niveau.');
}


    public function show($id)
    {
        $matiere = Matiere::findOrFail($id);
        
        if (request()->routeIs('matieres.classes')) {
            $classes = Classe::with('niveau')->get();
            return view('dashboard.matieres.index', [
                'matieres' => Matiere::all(),
                'currentMatiere' => $matiere,
                'classes' => $classes,
                'showClassesManagement' => true
            ]);
        }
        
        return view('dashboard.matieres.index', [
            'matieres' => Matiere::all(),
            'currentMatiere' => $matiere
        ]);
    }

}
