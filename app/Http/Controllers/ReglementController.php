<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log; // si ce n'est pas déjà importé

class ReglementController extends Controller
{
    public function index()
    {
        $anneesScolaires = AnneeScolaire::all();
        $classes = Classe::all();

        return view('dashboard.pages.comptabilites.reglement', compact('anneesScolaires', 'classes'));
    }

    public function elevesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $eleves = Inscription::with('eleve')
            ->where('classe_id', $request->classe_id)
            ->whereHas('anneeScolaire', fn($q) => $q->where('est_active', true))
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'nom_complet' => $i->eleve->prenom . ' ' . $i->eleve->nom,
                'matricule' => $i->eleve->matricule
            ]);

        return response()->json($eleves);
    }

    public function eleveData(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'annee_scolaire_id' => 'required|exists:annee_scolaires,id'
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
            ->findOrFail($request->inscription_id);

        $anneeId = session('annee_scolaire_id');
        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();

        $tarifInscription = Tarif::where([
            'annee_scolaire_id' => $anneeId,
            'niveau_id' => $niveauId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeInscription->id ?? 0
        ])->first();

        $tarifScolarite = Tarif::where([
            'annee_scolaire_id' => $anneeId,
            'niveau_id' => $niveauId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeScolarite->id ?? 0
        ])->first();

        $montantInscription = $tarifInscription->montant ?? 0;
        $montantScolarite  = $tarifScolarite->montant ?? 0;

        $reduction = $inscription->reductions->sum('montant');
        Log::info('Réduction totale pour l\'inscription', ['inscription_id' => $inscription->id, 'reduction' => $reduction]);
        $montantScolarite = max(0, $montantScolarite - $reduction);

        // Récupérer directement les paiements liés à cette inscription
        $paiements = Paiement::with('details.typeFrais')
            ->whereHas('details', function($q) use ($inscription) {
                $q->where('inscription_id', $inscription->id);
            })
            ->orderByDesc('created_at')
            ->get();

        Log::info('Nombre de paiements récupérés', ['count' => $paiements->count()]);

        // Calcul du total payé par type
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
                'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
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
                'scolarite' => $reduction   // <= ICI tu ajoutes la réduction totale
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
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'montant_inscription' => 'nullable|numeric|min:0',
            'montant_scolarite' => 'nullable|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $inscription = Inscription::findOrFail($request->inscription_id);
            $ecoleId = $inscription->ecole_id;

            $total = ($request->montant_inscription ?? 0) + ($request->montant_scolarite ?? 0);

            // Paiement global
            $paiement = Paiement::create([
                'annee_scolaire_id' => $request->annee_scolaire_id, // <--- Obligatoire
                'ecole_id' => $ecoleId,  
                'montant' => $total,
                'mode_paiement' => $request->mode_paiement,
                'reference' => $request->reference,
                'user_id' => auth()->id(),
                'created_at' => $request->date_paiement
            ]);

            // Détails
            if ($request->montant_inscription > 0) {
                $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'annee_scolaire_id' => $request->annee_scolaire_id,
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
                    'inscription_id' => $request->inscription_id, // obligatoire !
                    'type_frais_id' => $typeScolarite->id,       // obligatoire !
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
