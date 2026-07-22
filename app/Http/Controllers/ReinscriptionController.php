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

        $anneescolaires = AnneeScolaire::where('ecole_id', $ecoleId)
            ->orderBy('annee', 'desc')
            ->get();

        // Utiliser la même fonction que dans EleveController
        $classesNouvelles = Classe::forEcoleAndAnnee($ecoleId, $anneeId)
            ->ordered()
            ->get();

        return view('dashboard.pages.eleves.reinscriptions.create', [
            'anneescolaires' => $anneescolaires,
            'classesNouvelles' => $classesNouvelles,
            'annee' => $annee,
            'anneeId' => $anneeId,
        ]);
    }

    /**
     * Récupérer les classes d'une année scolaire donnée
     */
    public function getClassesByAnnee(Request $request)
    {
        try {
            $anneeId = $request->input('annee_id');
            $ecoleId = session('current_ecole_id');

            if (!$anneeId) {
                return response()->json([]);
            }

            if (!$ecoleId) {
                return response()->json(['error' => 'Ecole non définie'], 400);
            }

            // Utiliser la même fonction que dans EleveController
            $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeId)
                ->ordered()
                ->get();

            return response()->json($classes);
            
        } catch (\Exception $e) {
            Log::error('Erreur getClassesByAnnee: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les élèves d'une classe et année scolaire
     */
    public function getElevesByClasse(Request $request, $classe)
    {
        try {
            $anneeSourceId = $request->input('annee_source_id');
            $anneeDestinationId = session('current_annee_scolaire_id');

            if (!$anneeSourceId) {
                return response()->json([]);
            }

            $eleves = Inscription::with(['eleve', 'classe'])
                ->where('classe_id', $classe)
                ->where('annee_scolaire_id', $anneeSourceId)
                ->whereDoesntHave('eleve.reinscriptions', function ($q) use ($anneeDestinationId) {
                    $q->where('annee_scolaire_id', $anneeDestinationId);
                })
                ->get()
                ->sortBy(function($inscription) {
                    return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
                })
                ->values();

            $result = $eleves->map(function ($inscription) {
                return [
                    'id'        => $inscription->eleve->id,
                    'matricule' => $inscription->eleve->matricule,
                    'nom'       => $inscription->eleve->nom,
                    'prenom'    => $inscription->eleve->prenom,
                    'classe'    => $inscription->classe->nom,
                ];
            });

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Erreur getElevesByClasse: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enregistrer les réinscriptions groupées
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'eleves'    => 'required|array',
                'eleves.*'  => 'exists:eleves,id',
                'classe_id' => 'required|exists:classes,id',
                'annee_source_id' => 'required|exists:annee_scolaires,id',
            ]);

            $ecoleId = session('current_ecole_id');
            $anneeDestinationId = session('current_annee_scolaire_id');

            if (!$ecoleId || !$anneeDestinationId) {
                return redirect()->back()->withErrors('Ecole ou année scolaire non définie en session.');
            }

            DB::transaction(function () use ($validated, $ecoleId, $anneeDestinationId) {
                foreach ($validated['eleves'] as $eleveId) {

                    $inscriptionSource = Inscription::where('eleve_id', $eleveId)
                        ->where('annee_scolaire_id', $validated['annee_source_id'])
                        ->first();

                    $existingReinscription = Reinscription::where('eleve_id', $eleveId)
                        ->where('annee_scolaire_id', $anneeDestinationId)
                        ->first();

                    if (!$existingReinscription) {
                        Reinscription::create([
                            'annee_scolaire_id'  => $anneeDestinationId,
                            'ecole_id'           => $ecoleId,
                            'eleve_id'           => $eleveId,
                            'classe_id'          => $validated['classe_id'],
                            'statut'             => 'validée',
                            'user_id'            => auth()->id(),
                            'date_reinscription' => now(),
                        ]);

                        Inscription::create([
                            'annee_scolaire_id'  => $anneeDestinationId,
                            'ecole_id'           => $ecoleId,
                            'eleve_id'           => $eleveId,
                            'classe_id'          => $validated['classe_id'],
                            'cantine_active'     => $inscriptionSource ? $inscriptionSource->cantine_active : 0,
                            'transport_active'   => $inscriptionSource ? $inscriptionSource->transport_active : 0,
                            'date_inscription'   => now(),
                            'statut'             => 'active',
                        ]);
                    }
                }
            });

            return redirect()->route('reinscriptions.index')
                ->with('success', 'Réinscriptions groupées enregistrées avec succès');
                
        } catch (\Exception $e) {
            Log::error('Erreur store: ' . $e->getMessage());
            return redirect()->back()->withErrors('Erreur lors de l\'enregistrement: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        return redirect()->route('reinscriptions.index');
    }

    public function edit($id)
    {
        return redirect()->route('reinscriptions.index');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('reinscriptions.index');
    }

    public function destroy($id)
    {
        return redirect()->route('reinscriptions.index');
    }
}