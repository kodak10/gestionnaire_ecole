<?php

namespace App\Http\Controllers;

use App\Exports\ClassesExport;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Niveau;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ClasseController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
    }


    public function index(Request $request)
    {
        $user = auth()->user();

        // Récupérer l'école et l'année depuis la session
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $enseignants = Enseignant::where('ecole_id', $ecoleId)
                        ->orderBy('nom_prenoms')
                        ->get();

        // Récupérer les classes avec relations
        $classes = Classe::with(['niveau', 'inscriptions'])
            ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
            ->where('classes.ecole_id', $ecoleId)          
            ->where('classes.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('niveaux.ordre')
            ->orderBy('classes.nom')
            ->select('classes.*')
            ->get();

        $niveaux = Niveau::orderBy('ordre')->orderBy('nom')->get();

        // On peut passer les infos de l'année active depuis la session
        $anneeActive = [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_nom' => session('current_ecole_nom'),
            'annee_scolaire' => session('current_annee_scolaire'),
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
        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
            'enseignant_id'=> 'required|exists:enseignants,id',

        ]);

        $niveau = Niveau::findOrFail($request->niveau_id);
        $nomComplet = $niveau->nom . '_' . $request->nom;

        // Récupérer l'école et l'année depuis la session
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Vérifier si la classe existe déjà
        $exists = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $nomComplet)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        Classe::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'niveau_id' => $request->niveau_id,
            'nom' => $nomComplet,
            'capacite' => $request->capacite,
            'enseignant_id'     => $request->enseignant_id,
        ]);

        return redirect()->route('classes.index')->with('success', 'Classe créée avec succès');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
            'enseignant_id'=> 'required|exists:enseignants,id',

        ]);

        $classe = Classe::findOrFail($id);
        $niveau = Niveau::findOrFail($request->niveau_id);
        $nomComplet = $niveau->nom . '_' . $request->nom;

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $exists = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $nomComplet)
            ->where('id', '!=', $classe->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        $nomClasse = $request->nom;

        // Vérifier si le nom commence déjà par le niveau
        if (!str_starts_with($nomClasse, $niveau->nom . '_')) {
            $nomClasse = $niveau->nom . '_' . $nomClasse;
        }

        $classe->update([
            'niveau_id' => $request->niveau_id,
            'nom' => $nomClasse,
            'capacite' => $request->capacite,
            'enseignant_id' => $request->enseignant_id,
        ]);

        return redirect()->route('classes.index')->with('success', 'Classe mise à jour avec succès');
    }

    public function destroy(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);
        
        if ($classe->inscriptions()->count() > 0) {
            return redirect()->back()>with('error', 'Impossible de supprimer une classe avec des élèves');
        }

        $classe->delete();
        return redirect()->route('classes.index')->with('success', 'Classe supprimée avec succès');
    }

    public function export($type)
    {
        if ($type == 'pdf') {
            $classes = Classe::with(['niveau', 'enseignant'])
                          ->where('annee_scolaire_id', AnneeScolaire::active()->id)
                          ->get();
            $pdf = PDF::loadView('exports.classes-pdf', compact('classes'));
            return $pdf->download('classes-list.pdf');
        }

        return Excel::download(new ClassesExport, 'classes-list.xlsx');
    }
}
