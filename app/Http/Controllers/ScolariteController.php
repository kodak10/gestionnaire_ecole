<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\Reduction;
use App\Models\Tarif;

use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class ScolariteController extends Controller
{
   public function index()
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;

        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->orderBy('nom')
            ->get();

        $typesFrais = TypeFrais::orderBy('nom')->get();

        $moisScolaires = MoisScolaire::get();

        $anneesScolaires = AnneeScolaire::where('ecole_id', $ecoleId)
            ->orderBy('est_active', 'desc')
            ->orderBy('annee', 'desc')
            ->get();

        return view('dashboard.pages.scolarites.index', compact('classes', 'typesFrais', 'moisScolaires', 'anneesScolaires'));
    }

  public function getElevesByClasse(Request $request)
{
    $request->validate(['classe_id' => 'required|exists:classes,id']);

    try {
        // Récupérer l'année scolaire depuis user_annees_scolaires
        $userAnnee = DB::table('user_annees_scolaires')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->first();

        if (!$userAnnee) {
            return response()->json(['error' => 'Aucune année scolaire assignée à cet utilisateur'], 404);
        }

        $anneeId = $userAnnee->annee_scolaire_id;

        $eleves = Inscription::with(['eleve', 'classe'])
            ->where('classe_id', $request->classe_id)
            ->where('annee_scolaire_id', $anneeId)
            ->get()
            ->map(function ($inscription) {
                return [
                    'inscription_id' => $inscription->id, 
                    'eleve_id' => $inscription->eleve->id,
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                    'matricule' => $inscription->eleve->matricule,
                    'classe_nom' => $inscription->classe->nom,
                ];
            });

        return response()->json($eleves);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur lors du chargement des élèves: ' . $e->getMessage()], 500);
    }
}

public function getElevePaiements(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id'
    ]);

    try {
        $inscription = Inscription::with('classe.niveau', 'eleve')->findOrFail($request->inscription_id);
        $ecoleId = $inscription->ecole_id;

        // Récupérer l'année scolaire depuis user_annees_scolaires
        $userAnnee = DB::table('user_annees_scolaires')
            ->where('user_id', auth()->id())
            ->where('ecole_id', $ecoleId)
            ->latest('id')
            ->first();

        if (!$userAnnee) {
            return response()->json([
                'success' => false,
                'message' => "Aucune année scolaire assignée à cet utilisateur pour cette école."
            ]);
        }

        $anneeId = $userAnnee->annee_scolaire_id;
        $niveauId = $inscription->classe->niveau_id;

        // Récupérer tous les paiements
        $paiements = Paiement::with(['typeFrais'])
            ->where('inscription_id', $inscription->id)
            ->where('annee_scolaire_id', $anneeId)
            ->get();

        // Récupérer le type "Scolarité"
        $typeScolarite = TypeFrais::where('nom', 'like', '%Scolarité%')->first();

        // Montant scolarité (tarif de référence)
        $montantScolarite = 0;
        if ($typeScolarite) {
            $tarifScolarite = Tarif::where('annee_scolaire_id', $anneeId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $niveauId)
                ->where('type_frais_id', $typeScolarite->id)
                ->first();

            $montantScolarite = $tarifScolarite?->montant ?? 0;
        }

        // Total payé scolarité
        $totalPayeScolarite = $paiements
            ->where('type_frais_id', $typeScolarite->id ?? 0)
            ->sum('montant');

        // Réduction appliquée à la scolarité
        $reductionScolarite = Reduction::where('inscription_id', $inscription->id)
            ->where('annee_scolaire_id', $anneeId)
            ->where('type_frais_id', $typeScolarite->id ?? 0)
            ->sum('montant');

        // Calculs finaux
        $montantApresReduction = max($montantScolarite - $reductionScolarite, 0);
        $resteAPayer = max($montantApresReduction - $totalPayeScolarite, 0);

        \Log::info("Résumé paiements élève", [
            'inscription_id' => $inscription->id,
            'ecole_id' => $ecoleId,
            'niveau_id' => $niveauId,
            'montant_scolarite' => $montantScolarite,
            'reduction_scolarite' => $reductionScolarite,
            'total_paye_scolarite' => $totalPayeScolarite,
            'reste_a_payer' => $resteAPayer,
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_scolarite' => $montantScolarite,
                'total_paye_scolarite' => $totalPayeScolarite,
                'reste_payer_scolarite' => $resteAPayer,
                'reduction_scolarite' => $reductionScolarite,
            ],
            'paiements' => $paiements 
        ]);

    } catch (\Exception $e) {
        \Log::error("Erreur getElevePaiements", ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
        ], 500);
    }
}

public function applyReduction(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'reduction' => 'required|numeric|min:0'
    ]);
    
    $ecole_id = auth()->user()->ecole_id;

    DB::beginTransaction();

    try {
        $inscription = Inscription::findOrFail($request->inscription_id);

        // Récupérer l'année scolaire depuis user_annees_scolaires
        $userAnnee = DB::table('user_annees_scolaires')
            ->where('user_id', auth()->id())
            ->where('ecole_id', $ecole_id)
            ->latest('id')
            ->first();

        if (!$userAnnee) {
            return response()->json([
                'success' => false,
                'message' => "Aucune année scolaire assignée à cet utilisateur pour cette école."
            ]);
        }

        $anneeId = $userAnnee->annee_scolaire_id;

        // Récupérer l'ID du type de frais Scolarité
        $typeScolarite = TypeFrais::where('nom', 'like', '%Scolarité%')->first();

        if (!$typeScolarite) {
            throw new \Exception("Type de frais 'Scolarité' introuvable");
        }

        // Supprimer les anciennes réductions pour la scolarité de cette année
        $inscription->reductions()
            ->where('annee_scolaire_id', $anneeId)
            ->where('type_frais_id', $typeScolarite->id)
            ->where('ecole_id', $ecole_id)
            ->delete();

        // Ajouter la nouvelle réduction si > 0
        if ($request->reduction > 0) {
            $inscription->reductions()->create([
                'annee_scolaire_id' => $anneeId,
                'montant' => $request->reduction,
                'raison' => 'Réduction manuelle sur scolarité',
                'type_frais_id' => $typeScolarite->id, 
                'ecole_id' => $ecole_id,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Réduction appliquée avec succès'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'application de la réduction: ' . $e->getMessage()
        ], 500);
    }
}

    public function printScolarite($eleveId, $anneeId)
    {
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);
        $anneeScolaire = AnneeScolaire::findOrFail($anneeId);
        
        // Récupérer les paiements
        $paiements = Paiement::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->with(['typeFrais', 'mois'])
            ->orderBy('date_paiement', 'desc')
            ->get();
        
        return view('scolarite.print', compact('eleve', 'anneeScolaire', 'paiements'));
    }

    public function generateReceipt($paiementId)
    {

        $paiement = Paiement::with(['eleve', 'typeFrais', 'anneeScolaire'])
            ->findOrFail($paiementId);
        
        $data = [
            'paiement' => $paiement
        ];

        $pdf = PDF::loadView('dashboard.documents.scolarite.recu_paiement', $data);
        return $pdf->stream('recu-paiement-' . $paiement->id . '.pdf');
        
        // $paiement = Paiement::with(['eleve', 'typeFrais', 'mois', 'anneeScolaire'])
        //     ->findOrFail($paiementId);
        
        // return view('dashboard.documents.scolarite.recu_paiement', compact('paiement'));
    }
}