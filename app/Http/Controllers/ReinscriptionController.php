<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Reinscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReinscriptionController extends Controller
{
    

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeId = session('current_annee_scolaire_id');

        $annee = session('current_annee_scolaire');

        // Toutes les années scolaires de cette école
        $anneescolaires = AnneeScolaire::where('ecole_id', $ecoleId)
            ->orderBy('annee', 'desc')
            ->get();

        // Classes de cette école
        $classes = Classe::where('ecole_id', $ecoleId)
            ->orderBy('nom')
            ->get();

        return view('dashboard.pages.eleves.reinscriptions.create', compact('classes', 'anneescolaires', 'annee', 'anneeId'));
    }

    /**
     * Récupérer les élèves d’une classe et année scolaire (qui ne sont pas encore réinscrits)
     */
    public function getElevesByClasse(Request $request, Classe $classe)
    {
        $anneeId = $request->input('annee_scolaire_id');

        Log::info($anneeId);

        $eleves = Inscription::with('eleve')
            ->where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $anneeId)
            ->whereDoesntHave('eleve.reinscriptions', function ($q) use ($anneeId) {
                $q->where('annee_scolaire_id', $anneeId);
            })
            ->get()
            ->map(function ($inscription) {
                return [
                    'id'        => $inscription->eleve->id,
                    'matricule' => $inscription->eleve->matricule,
                    'nom'       => $inscription->eleve->nom,
                    'prenom'    => $inscription->eleve->prenom,
                    'classe'    => $inscription->classe->nom,
                ];
            });

        return response()->json($eleves);
    }

    /**
     * Enregistrer les réinscriptions groupées
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'eleves'    => 'required|array',
            'eleves.*'  => 'exists:eleves,id',
            'classe_id' => 'required|exists:classes,id', // destination
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeId = session('current_annee_scolaire_id');

        // sécurité : vérifier que la session contient bien ces valeurs
        if (!$ecoleId || !$anneeId) {
            return redirect()->back()->withErrors('Ecole ou année scolaire non définie en session.');
        }

        DB::transaction(function () use ($validated, $ecoleId, $anneeId) {
            foreach ($validated['eleves'] as $eleveId) {

                Reinscription::create([
                    'annee_scolaire_id'  => $anneeId,
                    'ecole_id'           => $ecoleId,
                    'eleve_id'           => $eleveId,
                    'classe_id'          => $validated['classe_id'],
                    'statut'             => 'validée',
                    'user_id'            => auth()->id(),
                    'date_reinscription' => now(),
                ]);

                Inscription::create([
                    'annee_scolaire_id'  => $anneeId,
                    'ecole_id'           => $ecoleId,
                    'eleve_id'           => $eleveId,
                    'classe_id'          => $validated['classe_id'],
                    'date_inscription'   => now(),
                    'statut'             => true,
                ]);
            }
        });

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