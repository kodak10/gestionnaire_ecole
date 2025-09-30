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
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('id')
            ->get();

        $typesFrais = TypeFrais::orderBy('nom')->get();

        $moisScolaires = MoisScolaire::get();

        return view('dashboard.pages.scolarites.index', compact(
            'classes',
            'typesFrais',
            'moisScolaires',
        ));
    }

    public function getElevesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeId = session('current_annee_scolaire_id');

            $eleves = Inscription::with(['eleve', 'classe'])
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeId)
                ->where('classe_id', $request->classe_id)
                ->get()
                ->sortBy(fn($inscription) => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom)
                ->values()
                ->map(fn($inscription) => [
                    'inscription_id' => $inscription->id,
                    'eleve_id' => $inscription->eleve->id,
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                    'matricule' => $inscription->eleve->matricule,
                    'classe_nom' => $inscription->classe->nom,
                ]);

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
            $ecoleId = session('current_ecole_id');
            $anneeId = session('current_annee_scolaire_id');

            $inscription = Inscription::with('classe.niveau', 'eleve')
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeId)
                ->findOrFail($request->inscription_id);

            $niveauId = $inscription->classe->niveau_id;

            $paiements = Paiement::with(['typeFrais'])
                ->where('inscription_id', $inscription->id)
                ->where('annee_scolaire_id', $anneeId)
                ->get();

            $typeScolarite = TypeFrais::where('nom', 'like', '%Scolarité%')->first();

            $montantScolarite = 0;
            if ($typeScolarite) {
                $tarifScolarite = Tarif::where('annee_scolaire_id', $anneeId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $niveauId)
                    ->where('type_frais_id', $typeScolarite->id)
                    ->first();

                $montantScolarite = $tarifScolarite?->montant ?? 0;
            }

            $totalPayeScolarite = $paiements
                ->where('type_frais_id', $typeScolarite->id ?? 0)
                ->sum('montant');

            $reductionScolarite = Reduction::where('inscription_id', $inscription->id)
                ->where('annee_scolaire_id', $anneeId)
                ->where('type_frais_id', $typeScolarite->id ?? 0)
                ->sum('montant');

            $resteAPayer = max($montantScolarite - $reductionScolarite - $totalPayeScolarite, 0);

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

        $ecoleId = session('current_ecole_id');
        $anneeId = session('current_annee_scolaire_id');

        DB::beginTransaction();

        try {
            $inscription = Inscription::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeId)
                ->findOrFail($request->inscription_id);

            $typeScolarite = TypeFrais::where('nom', 'like', '%Scolarité%')->first();

            if (!$typeScolarite) {
                throw new \Exception("Type de frais 'Scolarité' introuvable");
            }

            // Supprimer anciennes réductions pour cette année et ce type
            $inscription->reductions()
                ->where('annee_scolaire_id', $anneeId)
                ->where('ecole_id', $ecoleId)
                ->where('type_frais_id', $typeScolarite->id)
                ->delete();

            // Ajouter la nouvelle réduction si > 0
            if ($request->reduction > 0) {
                $inscription->reductions()->create([
                    'annee_scolaire_id' => $anneeId,
                    'montant' => $request->reduction,
                    'raison' => 'Réduction manuelle sur scolarité',
                    'type_frais_id' => $typeScolarite->id,
                    'ecole_id' => $ecoleId,
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

        $paiement = Paiement::with(['eleve', 'typeFrais'])
            ->findOrFail($paiementId);

        $ecoleId = session('current_ecole_id');
        $anneeId = session('current_annee_scolaire_id');
        
        $data = [
            'paiement' => $paiement
        ];

        $pdf = PDF::loadView('dashboard.documents.scolarite.recu_paiement', $data);
        return $pdf->stream('recu-paiement-' . $paiement->id . '.pdf');
        
    }
}