<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // si ce n'est pas déjà importé
use Illuminate\Support\Facades\Auth;

class ReglementController extends Controller
{
    public function index()
    {
        $classes = Classe::all();

        return view('dashboard.pages.comptabilites.reglement', compact('classes'));
    }

    public function elevesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');


        $eleves = Inscription::with('eleve')
            ->where('classe_id', $request->classe_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->whereHas('eleve', fn($q) => $q->where('est_active', true)) // corrige ici
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'nom_complet' => $i->eleve->nom . ' ' . $i->eleve->prenom,
                'matricule' => $i->eleve->matricule
            ]);

        return response()->json($eleves);
    }

    public function eleveData(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
        ]);

        try {
            $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
                ->findOrFail($request->inscription_id);

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $niveauId = $inscription->classe->niveau->id;

            $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
            $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();

            $tarifInscription = Tarif::where([
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeInscription->id ?? 0
            ])->first();

            $tarifScolarite = Tarif::where([
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeScolarite->id ?? 0
            ])->first();

            $montantInscription = $tarifInscription->montant ?? 0;
            $montantScolarite  = $tarifScolarite->montant ?? 0;

            $reduction = $inscription->reductions->sum('montant');
            $montantScolarite = max(0, $montantScolarite - $reduction);

            // Paiements liés
            $paiements = Paiement::with('details.typeFrais')
                ->whereHas('details', fn($q) => $q->where('inscription_id', $inscription->id))
                ->orderByDesc('created_at')
                ->get();

            $totalPayeInscription = 0;
            $totalPayeScolarite = 0;

            foreach ($paiements as $paiement) {
                foreach ($paiement->details as $detail) {
                    if ($detail->type_frais_id == ($typeInscription->id ?? 0)) {
                        $totalPayeInscription += $detail->montant;
                    }
                    if ($detail->type_frais_id == ($typeScolarite->id ?? 0)) {
                        $totalPayeScolarite += $detail->montant;
                    }
                }
            }

            $resteInscription = max(0, $montantInscription - $totalPayeInscription);
            $resteScolarite   = max(0, $montantScolarite - $totalPayeScolarite);

            return response()->json([
                'success' => true,
                'eleve' => [
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                    'matricule' => $inscription->eleve->matricule,
                    'classe' => $inscription->classe->nom
                ],
                'frais' => [
                    'inscription' => $montantInscription,
                    'scolarite' => $montantScolarite
                ],
                'total_paye' => [
                    'inscription' => $totalPayeInscription,
                    'scolarite' => $totalPayeScolarite
                ],
                'reste_a_payer' => [
                    'inscription' => $resteInscription,
                    'scolarite' => $resteScolarite
                ],
                'reduction' => [
                    'scolarite' => $reduction
                ],
                'paiements' => $paiements
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function storePaiement(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant_inscription' => 'nullable|numeric|min:0',
            'montant_scolarite' => 'nullable|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $inscription = Inscription::findOrFail($request->inscription_id);
           
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $total = ($request->montant_inscription ?? 0) + ($request->montant_scolarite ?? 0);

            $paiement = Paiement::create([
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'montant' => $total,
                'mode_paiement' => $request->mode_paiement,
                'reference' => $request->reference,
                'user_id' => auth()->id(),
                'created_at' => $request->date_paiement
            ]);

            if ($request->montant_inscription > 0) {
                $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'annee_scolaire_id' => $anneeScolaireId,
                    'ecole_id' => $ecoleId,
                    'inscription_id' => $request->inscription_id,
                    'type_frais_id' => $typeInscription->id,
                    'montant' => $request->montant_inscription
                ]);
            }

            if ($request->montant_scolarite > 0) {
                $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'annee_scolaire_id' => $anneeScolaireId,
                    'ecole_id' => $ecoleId,
                    'inscription_id' => $request->inscription_id,
                    'type_frais_id' => $typeScolarite->id,
                    'montant' => $request->montant_scolarite,
                    'created_at' => $request->date_paiement,
                    'updated_at' => $request->date_paiement,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'paiement_id' => $paiement->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with([
            'details.inscription.eleve',
            'details.inscription.classe',
            'details.inscription.reductions',
            'details.typeFrais',
            'user',
            'anneeScolaire',
            'ecole'
        ])->find($paiementId);

        if (!$paiement) {
            abort(404, "Paiement introuvable.");
        }

        $inscription = $paiement->details->first()?->inscription;
        if (!$inscription) {
            abort(404, "Inscription introuvable pour ce paiement.");
        }

        $eleve = $inscription->eleve;
        $classe = $inscription->classe;
        $ecole = $paiement->ecole;

        // Récupérer l'année scolaire
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();


        // Trouver les types de frais
        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();

        // Récupérer les tarifs
        $tarifInscription = Tarif::where([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'niveau_id' => $classe->niveau->id,
            'type_frais_id' => $typeInscription->id ?? 0
        ])->first();

        $tarifScolarite = Tarif::where([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'niveau_id' => $classe->niveau->id,
            'type_frais_id' => $typeScolarite->id ?? 0
        ])->first();

        $montantInscription = $tarifInscription->montant ?? 0;
        $montantScolarite = $tarifScolarite->montant ?? 0;

        // Appliquer les réductions
        $reduction = $inscription->reductions->sum('montant');
        $montantScolarite = max(0, $montantScolarite - $reduction);

        // Calculer les totaux déjà payés pour cette inscription
        $totalPayeInscription = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('type_frais_id', $typeInscription->id ?? 0)
            ->sum('montant');

        $totalPayeScolarite = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('type_frais_id', $typeScolarite->id ?? 0)
            ->sum('montant');

        // Calculer les restes à payer
        $resteInscription = max(0, $montantInscription - $totalPayeInscription);
        $resteScolarite = max(0, $montantScolarite - $totalPayeScolarite);

        // Montant total du paiement actuel
        $montant_total = $paiement->details->sum('montant');

        // Reste total à payer (inscription + scolarité)
        $reste_total = $resteInscription + $resteScolarite;

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', compact(
            'paiement',
            'eleve',
            'classe',
            'ecole',
            'montant_total',
            'reste_total'
        ));

        return $pdf->stream("recu_paiement_{$paiement->id}.pdf");
    }



    public function deletePaiement(Request $request)
    {
        $request->validate(['paiement_id' => 'required|exists:paiements,id']);

        try {
            DB::beginTransaction();

            $paiement = Paiement::findOrFail($request->paiement_id);
            $paiement->details()->delete();
            $paiement->delete();

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function receipt($paiementId)
    {
        $paiement = Paiement::with(['details.inscription.eleve', 'details.inscription.classe.niveau', 'user'])
            ->findOrFail($paiementId);

        $eleve = optional($paiement->details->first()->inscription)->eleve;
        $classe = optional($paiement->details->first()->inscription)->classe;

        $data = [
            'paiement' => $paiement,
            'eleve' => $eleve,
            'classe' => $classe,
            'details' => $paiement->details
        ];

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', $data)
            ->setPaper('A5', 'portrait');

        return $pdf->stream("recu_paiement_$paiementId.pdf");
    }
}
