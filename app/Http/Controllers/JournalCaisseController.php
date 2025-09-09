<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paiement;
use App\Models\TypeFrais;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $request->validate([
            'type_frais_id' => 'nullable|exists:type_frais,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'mode_paiement' => 'nullable|in:especes,cheque,virement,mobile_money'
        ]);

        $anneeScolaireId = session('annee_scolaire_id');

        if (!$anneeScolaireId) {
            return response()->json(['success'=>false,'message'=>'Aucune année scolaire en session']);
        }


        try {
            // Construction de la requête
            $query = Paiement::with(['typeFrais', 'inscription.eleve', 'user', 'anneeScolaire'])
                ->where('annee_scolaire_id', $anneeScolaireId);

            // Application des filtres
            if ($request->type_frais_id) {
                $query->where('type_frais_id', $request->type_frais_id);
            }

            if ($request->mode_paiement) {
                $query->where('mode_paiement', $request->mode_paiement);
            }

            if ($request->date_debut) {
                $query->whereDate('date_paiement', '>=', $request->date_debut);
            }

            if ($request->date_fin) {
                $query->whereDate('date_paiement', '<=', $request->date_fin);
            }

            // Récupération des paiements
            $paiements = $query->orderBy('date_paiement', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->get();

            // Calcul des totaux
            $totalPaiements = $paiements->sum('montant');
            $nombrePaiements = $paiements->count();

            // Statistiques par type de frais
            $statsParType = Paiement::select('type_frais_id', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as count'))
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->when($request->type_frais_id, fn($q) => $q->where('type_frais_id', $request->type_frais_id))
                ->when($request->mode_paiement, fn($q) => $q->where('mode_paiement', $request->mode_paiement))
                ->when($request->date_debut, fn($q) => $q->whereDate('date_paiement', '>=', $request->date_debut))
                ->when($request->date_fin, fn($q) => $q->whereDate('date_paiement', '<=', $request->date_fin))
                ->with('typeFrais')
                ->groupBy('type_frais_id')
                ->get();

            // Statistiques par mode de paiement
            $statsParMode = Paiement::select('mode_paiement', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as count'))
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->when($request->type_frais_id, fn($q) => $q->where('type_frais_id', $request->type_frais_id))
                ->when($request->mode_paiement, fn($q) => $q->where('mode_paiement', $request->mode_paiement))
                ->when($request->date_debut, fn($q) => $q->whereDate('date_paiement', '>=', $request->date_debut))
                ->when($request->date_fin, fn($q) => $q->whereDate('date_paiement', '<=', $request->date_fin))
                ->groupBy('mode_paiement')
                ->get();

            return response()->json([
                'success' => true,
                'paiements' => $paiements,
                'total_paiements' => $totalPaiements,
                'nombre_paiements' => $nombrePaiements,
                'stats_par_type' => $statsParType,
                'stats_par_mode' => $statsParMode
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
