<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paiement;
use App\Models\TypeFrais;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class JournalCaisseController extends Controller
{
    public function index()
    {
        $anneesScolaires = AnneeScolaire::orderBy('annee', 'desc')->get();
        $typesFrais = TypeFrais::orderBy('nom')->get();
        
        return view('dashboard.pages.comptabilites.journal_caisse', compact('anneesScolaires', 'typesFrais'));
    }

    // public function getData(Request $request)
    // {
    //     // Validation des filtres
    //     $request->validate([
    //         'type_frais_id' => 'nullable|exists:type_frais,id',
    //         'date_debut' => 'nullable|date',
    //         'date_fin' => 'nullable|date',
    //     ]);

    //     // 👇 Log pour vérifier ce que l'utilisateur a choisi
    // Log::info('Type de frais sélectionné : ' . $request->type_frais_id);
    
    //     // Récupérer l'année scolaire de l'utilisateur
    //     $userId = Auth::id();
    //     $ecoleId = Auth::user()->ecole_id;

    //     $userAnnee = \App\Models\UserAnneeScolaire::where('user_id', $userId)
    //                     ->where('ecole_id', $ecoleId)
    //                     ->latest('id')
    //                     ->first();

    //     if (!$userAnnee) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Aucune année scolaire définie pour cet utilisateur.'
    //         ]);
    //     }

    //     $anneeScolaireId = $userAnnee->annee_scolaire_id;

    //     try {
    //         // Construire la requête
    //         $query = Paiement::with(['typeFrais', 'inscription.eleve', 'user', 'anneeScolaire'])
    //                     ->where('annee_scolaire_id', $anneeScolaireId);

    //         // Appliquer les filtres
    //         if ($request->type_frais_id) {
    //             $query->where('type_frais_id', $request->type_frais_id);
    //         }

    //         if ($request->date_debut) {
    //             $query->whereDate('created_at', '>=', $request->date_debut);
    //         }

    //         if ($request->date_fin) {
    //             $query->whereDate('created_at', '<=', $request->date_fin);
    //         }

    //         // Récupérer les paiements
    //         $paiements = $query->orderBy('created_at', 'desc')
    //                            ->orderBy('created_at', 'desc')
    //                            ->get();

    //         // Calcul des totaux
    //         $totalPaiements = $paiements->sum('montant');
    //         $nombrePaiements = $paiements->count();

    //         return response()->json([
    //             'success' => true,
    //             'paiements' => $paiements,
    //             'total_paiements' => $totalPaiements,
    //             'nombre_paiements' => $nombrePaiements
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
    //         ]);
    //     }
    // }


public function getData(Request $request)
{
    $request->validate([
        'type_frais_id' => 'nullable|exists:type_frais,id',
        'date_debut' => 'nullable|date',
        'date_fin' => 'nullable|date',
    ]);

    Log::info('Requête getData reçue', $request->all());

    $userId = Auth::id();
    $ecoleId = Auth::user()->ecole_id;

    $userAnnee = \App\Models\UserAnneeScolaire::where('user_id', $userId)
        ->where('ecole_id', $ecoleId)
        ->latest('id')
        ->first();

    if (!$userAnnee) {
        Log::warning("Aucune année scolaire définie pour l'utilisateur $userId");
        return response()->json([
            'success' => false,
            'message' => 'Aucune année scolaire définie pour cet utilisateur.'
        ]);
    }

    $anneeScolaireId = $userAnnee->annee_scolaire_id;
    Log::info("Année scolaire utilisée: $anneeScolaireId pour l'utilisateur $userId");

    try {
        $paiements = Paiement::with([
            'details.typeFrais',
            'details.inscription.eleve'
        ])
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->when($request->date_debut, fn($q) => $q->whereDate('created_at', '>=', $request->date_debut))
        ->when($request->date_fin, fn($q) => $q->whereDate('created_at', '<=', $request->date_fin))
        ->orderBy('created_at', 'desc')
        ->get();

        Log::info("Nombre de paiements récupérés: " . $paiements->count());

        $paiementsData = collect();
        $totalPaiements = 0;

        foreach ($paiements as $paiement) {
            Log::info("Traitement du paiement ID: {$paiement->id}, mode: {$paiement->mode_paiement}, date: {$paiement->created_at}");

            foreach ($paiement->details as $detail) {
                if ($request->type_frais_id && $detail->type_frais_id != $request->type_frais_id) {
                    Log::info("Détail ignoré car type_frais_id filtré: {$detail->type_frais_id}");
                    continue;
                }

                $eleveNom = $detail->inscription && $detail->inscription->eleve
                    ? $detail->inscription->eleve->prenom . ' ' . $detail->inscription->eleve->nom
                    : 'N/A';

                $typeFrais = $detail->typeFrais ? $detail->typeFrais->nom : 'N/A';
                $montant = $detail->montant;

                $totalPaiements += $montant;

                Log::info("Détail paiement ID: {$detail->id}, élève: $eleveNom, type frais: $typeFrais, montant: $montant");

                $paiementsData->push([
                    'date' => $paiement->created_at,
                    'eleve' => $eleveNom,
                    'type_frais' => $typeFrais,
                    'montant' => $montant,
                    'mode_paiement' => $paiement->mode_paiement
                ]);
            }
        }

        Log::info("Total paiements calculé: $totalPaiements, nombre de paiements: " . $paiementsData->count());

        return response()->json([
            'success' => true,
            'paiements' => $paiementsData->values(),
            'total_paiements' => $totalPaiements,
            'nombre_paiements' => $paiementsData->count()
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur getData: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
        ]);
    }
}






    public function destroy(Paiement $paiement)
    {
        try {
            $paiement->delete();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ]);
        }
    }

    public function show(Paiement $paiement)
    {
        return response()->json([
            'success' => true,
            'paiement' => $paiement->load(['typeFrais', 'inscription.eleve', 'user', 'anneeScolaire'])
        ]);
    }

    public function getInscriptionsByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $inscriptions = Inscription::with('eleve')
            ->where('classe_id', $request->classe_id)
            ->where('statut', 'active')
            ->get()
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                    'matricule' => $inscription->eleve->matricule
                ];
            });

        return response()->json($inscriptions);
    }
}