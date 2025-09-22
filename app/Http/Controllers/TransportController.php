<?php

namespace App\Http\Controllers;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class TransportController extends Controller
{
    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();

        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('id')
            ->get();

        $moisScolaires = MoisScolaire::orderBy('id')->get(); 

        return view('dashboard.pages.transports.index', compact('classes', 'moisScolaires'));
    }

    public function elevesByClasseTransport(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        try {
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $eleves = Inscription::with('eleve')
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $request->classe_id)
                ->where('transport_active', true)
                ->get()
                ->sortBy(function($inscription) {
                    // Tri par nom puis prénom
                    return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
                })
                ->values()
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                        'matricule' => $inscription->eleve->matricule,
                        'transport_active' => $inscription->transport_active
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
        ]);

        try {
            $inscription = Inscription::with(['eleve', 'classe.niveau'])->findOrFail($request->inscription_id);

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $niveauId = $inscription->classe->niveau->id;

            // Type de frais Transport
            $typeTransport = TypeFrais::where('nom', "Transport")->first();

            $tarifTransport = Tarif::where([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'type_frais_id' => $typeTransport->id ?? 0
            ])->first();

            $montantTransport = $tarifTransport->montant ?? 0;

            // Récupérer les paiements liés au Transport
            $paiements = Paiement::with('details.typeFrais')
                ->whereHas('details', function($q) use ($inscription, $typeTransport) {
                    $q->where('inscription_id', $inscription->id)
                    ->where('type_frais_id', $typeTransport->id ?? 0);
                })
                ->orderByDesc('created_at')
                ->get();

            // Calcul du total payé pour le Transport
            $totalPayeTransport = $paiements->sum(function($paiement) use ($typeTransport) {
                return $paiement->details->where('type_frais_id', $typeTransport->id ?? 0)->sum('montant');
            });

            $resteTransport = max(0, $montantTransport - $totalPayeTransport);

            return response()->json([
                'success' => true,
                'eleve' => [
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
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
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
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

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $inscription = Inscription::with('eleve', 'classe.niveau')->findOrFail($request->inscription_id);

            if (!$inscription->transport_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'a pas le transport actif.'
                ]);
            }
            $niveauId = $inscription->classe->niveau->id;

            $typeTransport = TypeFrais::where('nom', 'Transport')->first();
            if (!$typeTransport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de frais "Transport" non trouvé.'
                ]);
            }

            $tarifTransport = Tarif::where([
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeTransport->id
            ])->first();

            if (!$tarifTransport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif de transport non trouvé pour cette configuration.'
                ]);
            }

            $totalPayeTransport = PaiementDetail::where('inscription_id', $request->inscription_id)
                ->where('type_frais_id', $typeTransport->id)
                ->sum('montant');

            $resteAPayer = max(0, $tarifTransport->montant - $totalPayeTransport);

            if ($request->montant_transport > $resteAPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant saisi dépasse le reste à payer (' . $resteAPayer . ' FCFA).'
                ]);
            }

            // Paiement global
            $paiement = Paiement::create([
                'user_id' => auth()->id(),
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'montant' => $request->montant_transport,
                'mode_paiement' => $request->mode_paiement,
                'reference' => null,
                'description' => 'Paiement Transport',
                'created_at' => $request->date_paiement,
                'updated_at' => $request->date_paiement
            ]);

            // Paiement détail
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $request->inscription_id,
                'annee_scolaire_id' => $anneeScolaireId,
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

    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with([
            'details.inscription.eleve',
            'details.inscription.classe.niveau',
            'details.typeFrais',
            'user',
            'anneeScolaire', // probleme
            'ecole'
        ])->find($paiementId);

        if (!$paiement) {
            abort(404, "Paiement introuvable.");
        }

        $inscription = $paiement->details->first()?->inscription;
        if (!$inscription) {
            abort(404, "Inscription introuvable pour ce paiement.");
        }

        $eleve  = $inscription->eleve;
        $classe = $inscription->classe;
        $ecole  = $paiement->ecole;

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();

        // Type de frais Transport
        $typeTransport = TypeFrais::where('nom', "Transport")->first();
        if (!$typeTransport) {
            abort(404, "Type de frais 'Transport' introuvable.");
        }

        // Tarif transport
        $tarifTransport = Tarif::where([
            'annee_scolaire_id' => $anneeScolaireId,
            'niveau_id' => $classe->niveau->id,
            'ecole_id' => $ecole->id,
            'type_frais_id' => $typeTransport->id
        ])->first();

        $montantTransport = $tarifTransport->montant ?? 0;

        // Paiements liés au Transport
        $paiementsTransport = Paiement::with('details.typeFrais')
            ->whereHas('details', function($q) use ($inscription, $typeTransport) {
                $q->where('inscription_id', $inscription->id)
                ->where('type_frais_id', $typeTransport->id);
            })
            ->get();

        // Total payé
        $totalPayeTransport = $paiementsTransport->sum(function($p) use ($typeTransport) {
            return $p->details->where('type_frais_id', $typeTransport->id)->sum('montant');
        });

        // Reste à payer
        $reste_total = max(0, $montantTransport - $totalPayeTransport);

        // Montant total payé sur CE reçu
        $montant_total = $paiement->details->sum('montant');

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', compact(
            'paiement',
            'eleve',
            'classe',
            'ecole',
            'montant_total',
            'reste_total',
        ));

        return $pdf->stream("recu_paiement_{$paiement->id}.pdf");
    }

    public function printScolarite($eleveId)
    {
        
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        // Récupérer les données de scolarité
        $typeScolarite = TypeFrais::where('nom', 'like', '%transport%')->first();
        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id)
            ->where('niveau_id', $eleve->classe->niveau_id)
            ->first();

        $paiements = Paiement::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId->id)
            ->where('type_frais_id', $typeScolarite->id)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $reduction = ReductionTransport::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId->id)
            ->where('type_frais', 'scolarite')
            ->sum('montant');

        $totalPaye = $paiements->sum('montant');
        $montantScolarite = $tarifScolarite ? $tarifScolarite->montant : 0;
        $montantApresReduction = max($montantScolarite - $reduction, 0);
        $resteAPayer = max($montantApresReduction - $totalPaye, 0);

        $data = [
            'eleve' => $eleve,
            'anneeScolaire' => $anneeScolaireId, // probleme
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

   
}
