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

    public function getData(Request $request)
    {
        // Validation des filtres
        $request->validate([
            'type_frais_id' => 'nullable|exists:type_frais,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ]);

        // 👇 Log pour vérifier ce que l'utilisateur a choisi
    Log::info('Type de frais sélectionné : ' . $request->type_frais_id);
    
        // Récupérer l'année scolaire de l'utilisateur
        $userId = Auth::id();
        $ecoleId = Auth::user()->ecole_id;

        $userAnnee = \App\Models\UserAnneeScolaire::where('user_id', $userId)
                        ->where('ecole_id', $ecoleId)
                        ->latest('id')
                        ->first();

        if (!$userAnnee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année scolaire définie pour cet utilisateur.'
            ]);
        }

        $anneeScolaireId = $userAnnee->annee_scolaire_id;

        try {
            // Construire la requête
            $query = Paiement::with(['typeFrais', 'inscription.eleve', 'user', 'anneeScolaire'])
                        ->where('annee_scolaire_id', $anneeScolaireId);

            // Appliquer les filtres
            if ($request->type_frais_id) {
                $query->where('type_frais_id', $request->type_frais_id);
            }

            if ($request->date_debut) {
                $query->whereDate('created_at', '>=', $request->date_debut);
            }

            if ($request->date_fin) {
                $query->whereDate('created_at', '<=', $request->date_fin);
            }

            // Récupérer les paiements
            $paiements = $query->orderBy('created_at', 'desc')
                               ->orderBy('created_at', 'desc')
                               ->get();

            // Calcul des totaux
            $totalPaiements = $paiements->sum('montant');
            $nombrePaiements = $paiements->count();

            return response()->json([
                'success' => true,
                'paiements' => $paiements,
                'total_paiements' => $totalPaiements,
                'nombre_paiements' => $nombrePaiements
            ]);

        } catch (\Exception $e) {
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