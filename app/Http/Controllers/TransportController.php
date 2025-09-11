<?php

namespace App\Http\Controllers;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\PaiementTransport;
use App\Models\Reduction;
use App\Models\ReductionTransport;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class TransportController extends Controller
{
    public function index()
{
    $classes = Classe::with('niveau')->orderBy('nom')->get();
    $anneesScolaires = AnneeScolaire::orderBy('est_active', 'desc')->orderBy('annee', 'desc')->get();
    $moisScolaires = MoisScolaire::orderBy('id')->get(); 

    return view('dashboard.pages.transports.index', compact('classes', 'anneesScolaires', 'moisScolaires'));
}


   public function elevesByClasseTransport(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);
        
        try {
            $eleves = Inscription::with('eleve')
                ->where('classe_id', $request->classe_id)
                ->where('transport_active', true) // Filtrer uniquement les élèves avec transport active
                ->whereHas('anneeScolaire', function($query) {
                    $query->where('est_active', true);
                })
                ->get()
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                        'matricule' => $inscription->eleve->matricule,
                        'transport_active' => $inscription->transport_active // Inclure l'état de la transport
                    ];
                });
                
            return response()->json($eleves);
            
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

   public function getEleveTransport(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'annee_scolaire_id' => 'required|exists:annee_scolaires,id'
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->findOrFail($request->inscription_id);

        if (!$inscription->transport_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'a pas le transport actif.'
            ]);
        }

        $anneeId = session('annee_scolaire_id');
        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        $typeTransport = TypeFrais::where('nom', "Transport")->first();
        if (!$typeTransport) {
            return response()->json([
                'success' => false,
                'message' => 'Type de frais "Transport" non trouvé.'
            ]);
        }

        $tarifTransport = Tarif::where([
            'annee_scolaire_id' => $anneeId,
            'niveau_id' => $niveauId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeTransport->id
        ])->first();

        $montantTransport = $tarifTransport->montant ?? 0;

        // Récupérer les paiements via PaiementDetail
        $paiements = Paiement::with('details.typeFrais')
            ->whereHas('details', function($q) use ($inscription, $typeTransport) {
                $q->where('inscription_id', $inscription->id)
                  ->where('type_frais_id', $typeTransport->id ?? 0);
            })
            ->orderByDesc('created_at')
            ->get();

        $totalPayeTransport = 0;
        foreach ($paiements as $paiement) {
            foreach ($paiement->details as $detail) {
                if ($detail->type_frais_id == ($typeTransport->id ?? 0)) {
                    $totalPayeTransport += $detail->montant;
                }
            }
        }

        $resteTransport = max(0, $montantTransport - $totalPayeTransport);

        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'frais' => [
                'transport' => $montantTransport
            ],
            'total_paye' => [
                'transport' => $totalPayeTransport
            ],
            'reste_a_payer' => [
                'transport' => $resteTransport
            ],
            'paiements' => $paiements
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données de transport: ' . $e->getMessage()
        ]);
    }
}


public function store(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'montant_transport' => 'required|numeric|min:0',
        'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
        'date_paiement' => 'required|date'
    ]);

    try {
        DB::beginTransaction();

        $anneeId = session('annee_scolaire_id');
        if (!$anneeId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année scolaire active dans la session.'
            ], 422);
        }

        $inscription = Inscription::with('eleve', 'classe.niveau')->findOrFail($request->inscription_id);

        if (!$inscription->transport_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'a pas le transport actif.'
            ]);
        }

        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        $typeTransport = TypeFrais::where('nom', 'Transport')->first();
        if (!$typeTransport) {
            return response()->json([
                'success' => false,
                'message' => 'Type de frais "Transport" non trouvé.'
            ]);
        }

        $tarifTransport = Tarif::where([
            'annee_scolaire_id' => $anneeId,
            'niveau_id' => $niveauId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeTransport->id
        ])->first();

        $totalPayeTransport = PaiementDetail::where('inscription_id', $request->inscription_id)
            ->where('type_frais_id', $typeTransport->id)
            ->sum('montant');

        $resteAPayer = max(0, ($tarifTransport->montant ?? 0) - $totalPayeTransport);

        if ($request->montant_transport > $resteAPayer) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant saisi dépasse le reste à payer (' . $resteAPayer . ' FCFA).'
            ]);
        }

        // Paiement global
        $paiement = Paiement::create([
            'user_id' => auth()->id(),
            'annee_scolaire_id' => $anneeId,
            'ecole_id' => $ecoleId,
            'montant' => $request->montant_transport,
            'mode_paiement' => $request->mode_paiement,
            'reference' => null,
            'description' => 'Paiement Transport',
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
        ]);

        // Détail paiement
        PaiementDetail::create([
            'paiement_id' => $paiement->id,
            'inscription_id' => $request->inscription_id,
            'annee_scolaire_id' => $anneeId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeTransport->id,
            'montant' => $request->montant_transport,
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Paiement Transport enregistré avec succès.',
            'paiement_id' => $paiement->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur storePaiementTransport', ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage()
        ]);
    }
}


  

    public function applyReduction(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'reduction' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();

        try {
            $eleve = Eleve::findOrFail($request->eleve_id);
            
            // Supprimer les anciennes réductions pour la scolarité de cette année
            $eleve->reductions()
                ->where('annee_scolaire_id', $request->annee_scolaire_id)
                ->where('type_frais', 'scolarite')
                ->delete();
            
            // Ajouter la nouvelle réduction si > 0
            if ($request->reduction > 0) {
                $eleve->reductions()->create([
                    'annee_scolaire_id' => $request->annee_scolaire_id,
                    'montant' => $request->reduction,
                    'raison' => 'Réduction manuelle sur scolarité',
                    'type_frais' => 'scolarite'
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
        
        // Récupérer les données de scolarité
        $typeScolarite = TypeFrais::where('nom', 'like', '%transport%')->first();
        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id)
            ->where('niveau_id', $eleve->classe->niveau_id)
            ->first();

        $paiements = Paiement::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('type_frais_id', $typeScolarite->id)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $reduction = ReductionTransport::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('type_frais', 'scolarite')
            ->sum('montant');

        $totalPaye = $paiements->sum('montant');
        $montantScolarite = $tarifScolarite ? $tarifScolarite->montant : 0;
        $montantApresReduction = max($montantScolarite - $reduction, 0);
        $resteAPayer = max($montantApresReduction - $totalPaye, 0);

        $data = [
            'eleve' => $eleve,
            'anneeScolaire' => $anneeScolaire,
            'paiements' => $paiements,
            'montantScolarite' => $montantScolarite,
            'reduction' => $reduction,
            'montantApresReduction' => $montantApresReduction,
            'totalPaye' => $totalPaye,
            'resteAPayer' => $resteAPayer
        ];

        $pdf = PDF::loadView('scolarite.print', $data);
        return $pdf->stream('scolarite-' . $eleve->matricule . '.pdf');
    }

    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with(['eleve', 'typeFrais', 'anneeScolaire'])
            ->findOrFail($paiementId);
        
        $data = [
            'paiement' => $paiement
        ];

        $pdf = PDF::loadView('scolarite.receipt', $data);
        return $pdf->stream('recu-paiement-' . $paiement->id . '.pdf');
    }
}
