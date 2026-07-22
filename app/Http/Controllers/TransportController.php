<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\PaiementTransport;
use App\Models\PaiementDetailTransport;
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
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
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

            $eleves = Inscription::with('eleve')
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $request->classe_id)
                ->where('transport_active', true)
                ->get()
                ->sortBy(function($inscription) {
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
            Log::error('Erreur elevesByClasseTransport', ['message' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    public function getEleveTransport(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
        ]);

        try {
            $inscription = Inscription::with(['eleve', 'classe.niveau'])
                ->findOrFail($request->inscription_id);

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $niveauId = $inscription->classe->niveau->id;

            $typeTransport = TypeFrais::where('nom', "Transport")->first();

            if (!$typeTransport) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Type de frais "Transport" non trouvé.'
                ]);
            }

            $tarifTransport = Tarif::where([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'type_frais_id' => $typeTransport->id
            ])->first();

            $montantTransport = $tarifTransport->montant ?? 0;

            $paiements = PaiementTransport::with(['details'])
                ->where('inscription_id', $inscription->id)
                ->where('type_frais_id', $typeTransport->id)
                ->orderByDesc('created_at')
                ->get();

            $totalPayeTransport = PaiementDetailTransport::whereHas('paiement', function($q) use ($inscription, $typeTransport) {
                $q->where('inscription_id', $inscription->id)
                  ->where('type_frais_id', $typeTransport->id);
            })->sum('montant');

            $resteTransport = max(0, $montantTransport - $totalPayeTransport);

            $paiementsFormatted = $paiements->map(function($paiement) {
                return [
                    'id' => $paiement->id,
                    'montant' => $paiement->montant,
                    'mode_paiement' => $paiement->mode_paiement,
                    'created_at' => $paiement->created_at,
                ];
            });

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
                'paiements' => $paiementsFormatted
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur getEleveTransport', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false, 
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
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

            $totalPayeTransport = PaiementDetailTransport::whereHas('paiement', function($q) use ($request, $typeTransport) {
                $q->where('inscription_id', $request->inscription_id)
                  ->where('type_frais_id', $typeTransport->id);
            })->sum('montant');

            $resteAPayer = max(0, $tarifTransport->montant - $totalPayeTransport);

            if ($request->montant_transport > $resteAPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant saisi (' . number_format($request->montant_transport, 0, ',', ' ') . ' F) dépasse le reste à payer (' . number_format($resteAPayer, 0, ',', ' ') . ' F).'
                ]);
            }

            $paiement = PaiementTransport::create([
                'inscription_id' => $request->inscription_id,
                'user_id' => auth()->id(),
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeTransport->id,
                'montant' => $request->montant_transport,
                'mode_paiement' => $request->mode_paiement,
                'reference' => null,
                'created_at' => $request->date_paiement,
                'updated_at' => $request->date_paiement
            ]);

            PaiementDetailTransport::create([
                'paiement_transport_id' => $paiement->id,
                'montant' => $request->montant_transport
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

    public function deletePaiement(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiement_transports,id'
        ]);

        try {
            DB::beginTransaction();

            PaiementDetailTransport::where('paiement_transport_id', $request->paiement_id)->delete();
            
            $paiement = PaiementTransport::findOrFail($request->paiement_id);
            $paiement->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur deletePaiement Transport', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ]);
        }
    }

public function generateReceipt($paiementId)
{
    $paiement = PaiementTransport::with([
        'details.typeFrais', // Charge la relation typeFrais pour chaque détail
        'inscription.eleve',
        'inscription.classe.niveau',
        'typeFrais',
        'user',
        'ecole',
        'anneeScolaire'
    ])->find($paiementId);

    if (!$paiement) {
        abort(404, "Paiement introuvable.");
    }

    $inscription = $paiement->inscription;
    if (!$inscription) {
        abort(404, "Inscription introuvable pour ce paiement.");
    }

    $eleve = $inscription->eleve;
    $classe = $inscription->classe;
    $ecole = $paiement->ecole;

    $montant_total = $paiement->montant;

    $typeTransport = TypeFrais::where('nom', 'Transport')->first();
    $totalPayeTransport = PaiementDetailTransport::whereHas('paiement', function($q) use ($inscription, $typeTransport) {
        $q->where('inscription_id', $inscription->id)
          ->where('type_frais_id', $typeTransport->id);
    })->sum('montant');

    $totalTransport = Tarif::where('niveau_id', $classe->niveau->id)
        ->where('type_frais_id', $typeTransport->id ?? 0)
        ->where('annee_scolaire_id', session('current_annee_scolaire_id'))
        ->where('ecole_id', session('current_ecole_id'))
        ->value('montant') ?? 0;

    $reste_total = max(0, $totalTransport - $totalPayeTransport);

    $typeFrais = $paiement->typeFrais;

    $pdf = PDF::loadView('dashboard.documents.scolarite.recu_paiement', compact(
        'paiement',
        'eleve',
        'classe',
        'ecole',
        'montant_total',
        'reste_total',
        'typeFrais'
    ));

    return $pdf->stream("recu_transport_{$paiement->id}.pdf");
}
}