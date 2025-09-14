<?php

namespace App\Http\Controllers;

use App\Exports\ClassesExport;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ClasseController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $ecoleId = $user->ecole_id;

        // Récupérer l'année scolaire assignée à l'utilisateur pour cette école
        $userAnnee = DB::table('user_annees_scolaires')
                        ->where('user_id', $user->id)
                        ->where('ecole_id', $ecoleId)
                        ->latest('created_at')
                        ->first();

        if (!$userAnnee) {
            return redirect()->back()->with('error', 'Aucune année scolaire assignée à cet utilisateur.');
        }

        $anneeScolaireId = $userAnnee->annee_scolaire_id;

        $query = Classe::with(['niveau', 'inscriptions'])
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId);

        $classes = $query->get();
        $niveaux = Niveau::orderBy('nom')->get();

        return view('dashboard.pages.parametrage.classe', [
            'classes' => $classes,
            'niveaux' => $niveaux,
            'annee_active' => $userAnnee // on garde la variable pour la vue
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
        ]);

        $niveau = Niveau::findOrFail($request->niveau_id);
        $nomComplet = $niveau->nom . '_' . $request->nom;

        // Vérifier si ce nomComplet existe déjà dans la même école et année scolaire
        $exists = Classe::where('ecole_id', auth()->user()->ecole_id ?? 1)
            ->where('annee_scolaire_id', AnneeScolaire::active()->id)
            ->where('nom', $nomComplet)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        $anneeScolaireId = session('annee_scolaire_id') ?? auth()->user()->annee_scolaire_id ?? 1; // faux


        Classe::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => auth()->user()->ecole_id ?? 1,
            'niveau_id' => $request->niveau_id,
            'nom' => $nomComplet,
            'capacite' => $request->capacite,
        ]);

        return redirect()->route('classes.index')->with('success', 'Classe créée avec succès');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'nom' => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
        ]);

        $classe = Classe::findOrFail($id);
        $niveau = Niveau::findOrFail($request->niveau_id);
        $nomComplet = $niveau->nom . '_' . $request->nom;

        $exists = Classe::where('ecole_id', auth()->user()->ecole_id ?? 1)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->where('nom', $nomComplet)
            ->where('id', '!=', $classe->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nom' => 'Cette classe existe déjà'])->withInput();
        }

        $classe->update([
            'niveau_id' => $request->niveau_id,
            'nom' => $nomComplet,
            'capacite' => $request->capacite,
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
