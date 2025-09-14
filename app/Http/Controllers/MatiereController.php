<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    public function index(Request $request)
    {
        $ecoleId = auth()->user()->ecole_id;
        $anneeScolaireId = auth()->user()->annee_scolaire_id;

        // Récupérer les niveaux de l'école
        $niveaux = Niveau::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        // Requête de base sur les matières
        $matieresQuery = Matiere::query()
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom');

        // Filtrer par niveau si demandé
        if ($request->filled('niveau_id')) {
            $niveauId = $request->niveau_id;
            $matieresQuery->whereHas('niveaux', function($q) use ($niveauId, $ecoleId) {
                $q->where('niveaux.id', $niveauId)
                ->where('niveaux.ecole_id', $ecoleId);
            });
        }

        $matieres = $matieresQuery->get();

        return view('dashboard.pages.parametrage.matiere', compact('matieres', 'niveaux'));
    }

   public function getMatieres($id)
    {
        $niveau = Niveau::with('matieres')->findOrFail($id);

        // Préparer un tableau matières avec id, nom, coefficient (du pivot)
        $matieres = $niveau->matieres->map(function($matiere) {
            return [
                'id' => $matiere->id,
                'nom' => $matiere->nom,
                'coefficient' => $matiere->pivot->coefficient ?? 1,
            ];
        });

        return response()->json($matieres);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:matieres,nom',
            'coefficient' => 'required|integer|min:1|max:10',
        ]);

        $ecoleId = auth()->user()->ecole_id;
   
        $anneeScolaireId = auth()->user()->annee_scolaire_id ;


        Matiere::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'nom' => $request->nom,
            'coefficient' => $request->coefficient,
        ]);


        return redirect()->route('matieres.index')
            ->with('success', 'Matière créée avec succès');
    }

    public function update(Request $request, Matiere $matiere)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:matieres,nom,'.$matiere->id,
            'coefficient' => 'required|integer|min:1|max:10',
        ]);

        $matiere->update($validated);

        return redirect()->route('matieres.index')
            ->with('success', 'Matière mise à jour avec succès');
    }

    public function destroy(Matiere $matiere)
    {
        // Vérifier si la matière est utilisée dans des classes
        if ($matiere->niveaux()->count() > 0) {
            return redirect()->route('matieres.index')
                ->with('error', 'Impossible de supprimer : cette matière est associée à des classes');
        }

        $matiere->delete();
        
        return redirect()->route('matieres.index')
            ->with('success', 'Matière supprimée avec succès');
    }

   public function assignMatieres(Request $request)
    {
        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'matieres' => 'required|array',
            'matieres.*' => 'exists:matieres,id',
            'coefficients' => 'required|array',
            'coefficients.*' => 'integer|min:1|max:10',
        ]);

        $niveau = Niveau::findOrFail($request->niveau_id);

        $ecoleId = auth()->user()->ecole_id;
    
     $anneeScolaireId = auth()->user()->annee_scolaire_id ;



        // Préparer les données à sync (matiere_id => ['coefficient' => x])
        $syncData = [];
        foreach ($request->matieres as $matiereId) {
            $coef = $request->coefficients[$matiereId];
            $syncData[$matiereId] = [
                'coefficient' => $coef,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId
            ];
            

        }

        // Synchroniser les matières avec leurs coefficients
        $niveau->matieres()->sync($syncData);

        return redirect()->back()->with('success', 'Matières affectées avec succès au niveau.');
    }

    public function updateClasses(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);
        
        $validated = $request->validate([
            'niveau' => 'required|array',
            'niveaux.*' => 'integer|min:0|max:10'
        ]);

        $niveauxToSync = [];
        foreach ($request->niveaux as $niveauId => $coefficient) {
            if ($coefficient > 0) {
                $niveauxToSync[$niveauId] = ['coefficient' => $coefficient];
            }
        }

        $matiere->niveaux()->sync($niveauxToSync);

        return redirect()->route('matieres.index')
            ->with('success', 'Classes associées mises à jour avec succès');
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
