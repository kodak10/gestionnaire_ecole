<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Matiere;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    public function index(Request $request)
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;

        // Classes de l'école
        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->get();

        // Matières
        $matieresQuery = Matiere::query()
            ->where('ecole_id', $ecoleId) // filtrer par école
            ->orderBy('nom');

        if ($request->filled('niveau_id')) {
            $niveauId = $request->niveau_id;
            $matieresQuery->whereHas('classes', function($q) use ($niveauId, $ecoleId) {
                $q->where('niveau_id', $niveauId)
                ->where('ecole_id', $ecoleId); // s'assurer que la classe est de la même école
            });
        }

        $matieres = $matieresQuery->get();

        $niveaux = Classe::where('ecole_id', $ecoleId)
            ->select('niveau_id')
            ->with('niveau')
            ->get()
            ->pluck('niveau')
            ->unique('id')
            ->filter();

        return view('dashboard.pages.parametrage.matiere', compact('matieres', 'classes', 'niveaux'));
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

public function updateClasses(Request $request, $id)
{
    $matiere = Matiere::findOrFail($id);
    
    $validated = $request->validate([
        'classes' => 'required|array',
        'classes.*' => 'integer|min:0|max:10'
    ]);

    $classesToSync = [];
    foreach ($request->classes as $classeId => $coefficient) {
        if ($coefficient > 0) {
            $classesToSync[$classeId] = ['coefficient' => $coefficient];
        }
    }

    $matiere->classes()->sync($classesToSync);

    return redirect()->route('matieres.classes', $matiere->id)
        ->with('success', 'Classes associées mises à jour avec succès');
}

    public function create()
    {
        return view();
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:matieres,nom',
            'coefficient' => 'required|integer|min:1|max:10',
        ]);

        $validated['ecole_id'] = auth()->user()->ecole_id ?? 1;

        $matiere = Matiere::create($validated);

        return redirect()->route('matieres.index')
            ->with('success', 'Matière créée avec succès');
    }


    

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matiere $matiere)
    {
        // Vérifier si la matière est utilisée dans des classes
        if ($matiere->classes()->count() > 0) {
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
        'classe_id' => 'required|exists:classes,id',
        'matieres' => 'required|array',
        'matieres.*' => 'exists:matieres,id',
        'coefficients' => 'required|array',
        'coefficients.*' => 'integer|min:1|max:10',
    ]);

    $classe = Classe::findOrFail($request->classe_id);
    $ecoleId = auth()->user()->ecole_id ?? 1;


    // Préparer les données à sync (matiere_id => ['coefficient' => x])
    $syncData = [];
    foreach ($request->matieres as $matiereId) {
        $coef = $request->coefficients[$matiereId] ?? 1;
        $syncData[$matiereId] = [
            'coefficient' => $coef,
            'ecole_id' => $ecoleId
        ];
        

    }

    // Synchroniser les matières avec leurs coefficients
    $classe->matieres()->sync($syncData);

    return redirect()->back()->with('success', 'Matières affectées avec succès à la classe.');
}

public function getMatieres($id)
{
    $classe = Classe::with('matieres')->findOrFail($id);

    // Préparez un tableau matières avec l'id, nom, coefficient (du pivot)
    $matieres = $classe->matieres->map(function($matiere) {
        return [
            'id' => $matiere->id,
            'nom' => $matiere->nom,
            'coefficient' => $matiere->pivot->coefficient ?? 1,
        ];
    });

    return response()->json($matieres);
}



}
