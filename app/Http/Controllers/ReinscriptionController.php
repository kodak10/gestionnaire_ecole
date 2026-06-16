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
        $anneeId = session('current_annee_scolaire_id'); // Nouvelle année (ID = 3)
        $annee = session('current_annee_scolaire');

        // Récupérer les années scolaires
        $anneescolaires = AnneeScolaire::where('ecole_id', $ecoleId)
            ->orderBy('annee', 'desc')
            ->get();

        // Récupérer toutes les classes pour l'année actuelle (nouvelle année)
        $classesNouvelles = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeId)
            ->orderBy('nom')
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

            $classes = Classe::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeId)
                ->orderBy('nom')
                ->get(['id', 'nom']);

            return response()->json($classes);
            
        } catch (\Exception $e) {
            Log::error('Erreur getClassesByAnnee: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les élèves d'une classe et année scolaire (qui ne sont pas encore réinscrits)
     */
    public function getElevesByClasse(Request $request, Classe $classe)
    {
        try {
            // Récupérer l'année source (celle où sont les élèves actuellement)
            $anneeSourceId = $request->input('annee_source_id');
            
            // Récupérer l'année de destination (nouvelle année)
            $anneeDestinationId = session('current_annee_scolaire_id');

            // Vérifier si l'année source est spécifiée
            if (!$anneeSourceId) {
                Log::warning('Aucune année source spécifiée');
                return response()->json([]);
            }

            // Récupérer les élèves inscrits dans la classe pour l'année source
            // qui n'ont pas encore de réinscription pour l'année de destination
            $eleves = Inscription::with(['eleve', 'classe'])
                ->where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $anneeSourceId)
                ->whereDoesntHave('eleve.reinscriptions', function ($q) use ($anneeDestinationId) {
                    $q->where('annee_scolaire_id', $anneeDestinationId);
                })
                ->get()

            ->sortBy(function($inscription) {
                // Tri par nom puis prénom
                return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
            })
            ->values(); // Réindexer les clés après le tri


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
                'classe_id' => 'required|exists:classes,id', // destination
                'annee_source_id' => 'required|exists:annee_scolaires,id', // Année source
            ]);

            $ecoleId = session('current_ecole_id');
            $anneeDestinationId = session('current_annee_scolaire_id'); // Nouvelle année (3)

            // sécurité : vérifier que la session contient bien ces valeurs
            if (!$ecoleId || !$anneeDestinationId) {
                return redirect()->back()->withErrors('Ecole ou année scolaire non définie en session.');
            }

            DB::transaction(function () use ($validated, $ecoleId, $anneeDestinationId) {
                foreach ($validated['eleves'] as $eleveId) {

                    // Récupérer l'inscription source pour avoir les valeurs de cantine et transport
                    $inscriptionSource = Inscription::where('eleve_id', $eleveId)
                        ->where('annee_scolaire_id', $validated['annee_source_id'])
                        ->first();

                    // Vérifier si l'élève a déjà une réinscription pour cette année
                    $existingReinscription = Reinscription::where('eleve_id', $eleveId)
                        ->where('annee_scolaire_id', $anneeDestinationId)
                        ->first();

                    if (!$existingReinscription) {
                        // Créer la réinscription
                        Reinscription::create([
                            'annee_scolaire_id'  => $anneeDestinationId,
                            'ecole_id'           => $ecoleId,
                            'eleve_id'           => $eleveId,
                            'classe_id'          => $validated['classe_id'],
                            'statut'             => 'validée',
                            'user_id'            => auth()->id(),
                            'date_reinscription' => now(),
                        ]);

                        // Créer la nouvelle inscription pour l'année de destination
                        // En conservant les valeurs de cantine et transport de l'inscription source
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