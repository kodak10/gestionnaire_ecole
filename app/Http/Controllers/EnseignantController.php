<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $enseignants = Enseignant::where('ecole_id', $ecoleId)->get();

        return view('dashboard.pages.parametrage.enseignant', compact('enseignants'));
    }
    
    public function store(Request $request)
{
    $validated = $request->validate([
        'nom_prenoms'       => 'required|string|max:255',
        'telephone' => 'nullable|string|max:20',
    ]);

    $enseignant = Enseignant::create([
        'ecole_id'  => session('current_ecole_id'),
        'nom_prenoms'       => $validated['nom_prenoms'],
        'telephone' => $validated['telephone'] ?? null,
    ]);

    return redirect()->back()->with('success', 'Enseignant ajouté avec succès.');
}


    public function update(Request $request, Enseignant $enseignant)
{
    $validated = $request->validate([
        'nom_prenoms' => 'required|string|max:255',
        'telephone'   => 'nullable|string|max:20',
    ]);

    $enseignant->update([
        'nom_prenoms' => $validated['nom_prenoms'],
        'telephone'   => $validated['telephone'] ?? null,
    ]);

    return redirect()->back()->with('success', 'Enseignant modifié avec succès.');
}


    public function destroy(Enseignant $enseignant)
    {
        // Optionnel : vérifier si l'enseignant est attaché à une classe avant suppression
        if ($enseignant->classes()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer un enseignant lié à une classe.');
        }

        $enseignant->delete();
        return redirect()->back()->with('success', 'Enseignant supprimé avec succès.');
    }
}
