<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\PreInscription;
use Illuminate\Http\Request;

class PreInscriptionController extends Controller
{
    public function index()
    {
        $preinscriptions = PreInscription::orderBy('date_preinscription', 'desc')
            ->paginate(20);

        return view('dashboard.pages.eleves.preinscriptions.index', compact('preinscriptions'));
    }

    public function create()
    {
        $classes = Classe::orderBy('nom')->get();
        return view('dashboard.pages.eleves.preinscriptions.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'classe_demandee' => 'required|string',
            'ecole_provenance' => 'nullable|string',
            'nom_parent' => 'required|string|max:255',
            'telephone_parent' => 'required|string|max:20',
            'email_parent' => 'nullable|email',
            'statut' => 'required|in:en_attente,validée,refusée',
            'notes' => 'nullable|string'
        ]);

        $validated['date_preinscription'] = now();
        $validated['user_id'] = 1;

        Preinscription::create($validated);

        return redirect()->route('preinscriptions.index')
            ->with('success', 'Préinscription enregistrée avec succès');
    }

    public function show(Preinscription $preinscription)
    {
        return view('preinscriptions.show', compact('preinscription'));
    }

    public function edit(Preinscription $preinscription)
    {
        $classes = Classe::orderBy('nom')->get();
        return view('dashboard.pages.eleves.preinscriptions.edit', compact('preinscription', 'classes'));
    }

    public function update(Request $request, Preinscription $preinscription)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'classe_demandee' => 'required|string',
            'ecole_provenance' => 'nullable|string',
            'nom_parent' => 'required|string|max:255',
            'telephone_parent' => 'required|string|max:20',
            'email_parent' => 'nullable|email',
            'statut' => 'required|in:en_attente,validée,refusée',
            'notes' => 'nullable|string'
        ]);

        $preinscription->update($validated);

        return redirect()->route('preinscriptions.index')
            ->with('success', 'Préinscription mise à jour avec succès');
    }

    public function destroy(Preinscription $preinscription)
    {
        $preinscription->delete();

        return redirect()->route('preinscriptions.index')
            ->with('success', 'Préinscription supprimée avec succès');
    }

    public function valider(Preinscription $preinscription)
    {
        $preinscription->update(['statut' => 'validée']);

        return back()->with('success', 'Préinscription validée avec succès');
    }

    public function refuser(Preinscription $preinscription)
    {
        $preinscription->update(['statut' => 'refusée']);

        return back()->with('success', 'Préinscription refusée avec succès');
    }
}
