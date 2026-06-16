<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\PaiementCantine;
use App\Models\PaiementDetailCantine;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class CantineController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('id')
            ->get();

        $moisScolaires = MoisScolaire::orderBy('id')->get();

        return view('dashboard.pages.cantines.index', compact('classes', 'moisScolaires'));
    }

    public function elevesByClasseCantine(Request $request)
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
                ->where('cantine_active', true)
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
                        'cantine_active' => $inscription->cantine_active
                    ];
                });

            return response()->json($eleves);

        } catch (\Exception $e) {
            Log::error('Erreur elevesByClasseCantine', ['message' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    public function getEleveCantine(Request $request)
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

            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            if (!$typeCantine) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Type de frais "Cantine" non trouvé.'
                ]);
            }

            // Récupérer le tarif Cantine total annuel
            $tarifCantine = Tarif::where([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'type_frais_id' => $typeCantine->id
            ])->first();

            $montantCantine = $tarifCantine->montant ?? 0;

            // Récupérer les paiements CANTINE
            $paiements = PaiementCantine::with(['details'])
                ->where('inscription_id', $inscription->id)
                ->where('type_frais_id', $typeCantine->id)
                ->orderByDesc('created_at')
                ->get();

            // Calcul du total payé
            $totalPayeCantine = PaiementDetailCantine::whereHas('paiement', function($q) use ($inscription, $typeCantine) {
                $q->where('inscription_id', $inscription->id)
                  ->where('type_frais_id', $typeCantine->id);
            })->sum('montant');

            $resteCantine = max(0, $montantCantine - $totalPayeCantine);

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
                    'cantine' => $montantCantine
                ],
                'total_paye' => [
                    'cantine' => $totalPayeCantine
                ],
                'reste_a_payer' => [
                    'cantine' => $resteCantine
                ],
                'paiements' => $paiementsFormatted
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur getEleveCantine', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
            'montant_cantine' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'date_paiement' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $inscription = Inscription::with('eleve', 'classe.niveau')->findOrFail($request->inscription_id);

            if (!$inscription->cantine_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'a pas la cantine active.'
                ]);
            }

            $niveauId = $inscription->classe->niveau->id;

            $typeCantine = TypeFrais::where('nom', 'Cantine')->first();
            if (!$typeCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de frais "Cantine" non trouvé.'
                ]);
            }

            $tarifCantine = Tarif::where([
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeCantine->id
            ])->first();

            if (!$tarifCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif de cantine non trouvé pour cette configuration.'
                ]);
            }

            $totalPayeCantine = PaiementDetailCantine::whereHas('paiement', function($q) use ($request, $typeCantine) {
                $q->where('inscription_id', $request->inscription_id)
                  ->where('type_frais_id', $typeCantine->id);
            })->sum('montant');

            $resteAPayer = max(0, $tarifCantine->montant - $totalPayeCantine);

            if ($request->montant_cantine > $resteAPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant saisi (' . number_format($request->montant_cantine, 0, ',', ' ') . ' F) dépasse le reste à payer (' . number_format($resteAPayer, 0, ',', ' ') . ' F).'
                ]);
            }

            // Créer le paiement cantine
            $paiement = PaiementCantine::create([
                'inscription_id' => $request->inscription_id,
                'user_id' => auth()->id(),
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeCantine->id,
                'montant' => $request->montant_cantine,
                'mode_paiement' => $request->mode_paiement,
                'reference' => null,
                'created_at' => $request->date_paiement,
                'updated_at' => $request->date_paiement
            ]);

            // Créer le détail du paiement - SANS mois_id
            PaiementDetailCantine::create([
                'paiement_cantine_id' => $paiement->id,
                'montant' => $request->montant_cantine
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement Cantine enregistré avec succès.',
                'paiement_id' => $paiement->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur storePaiementCantine', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage()
            ]);
        }
    }

    public function deletePaiement(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiement_cantines,id'
        ]);

        try {
            DB::beginTransaction();

            PaiementDetailCantine::where('paiement_cantine_id', $request->paiement_id)->delete();
            
            $paiement = PaiementCantine::findOrFail($request->paiement_id);
            $paiement->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur deletePaiement Cantine', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ]);
        }
    }


}