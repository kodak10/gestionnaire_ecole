<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Reinscription;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReinscriptionController extends Controller
{
    

    public function getElevesByClasse(Classe $classe)
    {
        $currentYear = Carbon::now()->format('Y');
        $nextYear = Carbon::now()->addYear()->format('Y');
        $anneeScolaire = $currentYear . '-' . $nextYear;

        // On récupère les élèves qui n'ont pas encore été réinscrits pour cette année
        $eleves = $classe->eleves()
            ->whereDoesntHave('reinscriptions', function ($q) use ($anneeScolaire) {
                $q->where('annee_scolaire', $anneeScolaire);
            })
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom', 'matricule']);

        return response()->json($eleves);
    }

    public function index()
    {
        $classes = Classe::orderBy('nom')->get();
        
        $currentYear = Carbon::now()->format('Y');
        $nextYear = Carbon::now()->addYear()->format('Y');
        $anneeScolaire = $currentYear . '-' . $nextYear;

        return view('dashboard.pages.eleves.reinscriptions.create', compact('classes', 'anneeScolaire'));
    }

    // CORRECTION: Renommez Store en store (minuscule)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'eleves' => 'required|array',
            'eleves.*' => 'exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'annee_scolaire' => 'required|string',
        ]);
        //dd($validated);

        // Récupérer l'année scolaire
        $anneeScolaireModel = AnneeScolaire::where('annee', $validated['annee_scolaire'])->first();
        
        if (!$anneeScolaireModel) {
            return redirect()->back()->with('error', 'Année scolaire non trouvée');
        }

        foreach ($validated['eleves'] as $eleveId) {
            // Créer la réinscription
            $reinscription = Reinscription::create([
                'eleve_id' => $eleveId,
                'classe_id' => $validated['classe_id'],
                'annee_scolaire' => $validated['annee_scolaire'],
                'statut' => 'validée',
                'user_id' => auth()->id(),
                'date_reinscription' => now()
            ]);

            // Créer l'inscription pour l'année suivante
            Inscription::create([
                'eleve_id' => $eleveId,
                'classe_id' => $validated['classe_id'],
                'annee_scolaire_id' => $anneeScolaireModel->id,
                'date_inscription' => now(),
            ]);
        }

        return redirect()->route('reinscriptions.index')
            ->with('success', 'Réinscriptions groupées enregistrées avec succès');
    }

    public function show($id)
    {
        return redirect()->route('reinscriptions.index');
    }

    // Ajoutez les méthodes manquantes pour le resource controller
    public function edit($id)
    {
        // Implémentez la logique d'édition si nécessaire
        return redirect()->route('reinscriptions.index');
    }

    public function update(Request $request, $id)
    {
        // Implémentez la logique de mise à jour si nécessaire
        return redirect()->route('reinscriptions.index');
    }

    public function destroy($id)
    {
        // Implémentez la logique de suppression si nécessaire
        return redirect()->route('reinscriptions.index');
    }
}