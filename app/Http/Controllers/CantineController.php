<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\MoisScolaire;
use App\Models\PaiementCantine;
use App\Models\Reduction;
use App\Models\ReductionCantine;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Support\Facades\Log;

class CantineController extends Controller
{
    public function index()
{
    $classes = Classe::with('niveau')->orderBy('nom')->get();
    $anneesScolaires = AnneeScolaire::orderBy('est_active', 'desc')->orderBy('annee', 'desc')->get();
    $moisScolaires = MoisScolaire::orderBy('id')->get(); // ajouter la liste des mois

    return view('dashboard.pages.cantines.index', compact('classes', 'anneesScolaires', 'moisScolaires'));
}

    public function elevesByClasseCantine(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);
        
        try {
            $eleves = Inscription::with('eleve')
                ->where('classe_id', $request->classe_id)
                ->where('cantine_active', true) // Filtrer uniquement les élèves avec cantine active
                ->whereHas('anneeScolaire', function($query) {
                    $query->where('est_active', true);
                })
                ->get()
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                        'matricule' => $inscription->eleve->matricule,
                        'cantine_active' => $inscription->cantine_active // Inclure l'état de la cantine
                    ];
                });
                
            return response()->json($eleves);
            
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getEleveCantine(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id'
        ]);

        try {
            // Récupération de l'inscription avec relations
            $inscription = Inscription::with(['eleve', 'classe.niveau'])
                ->findOrFail($request->inscription_id);

            // Vérifier si l'élève a la cantine active
            if (!$inscription->cantine_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'a pas la cantine active'
                ]);
            }

            $anneeId = session('annee_scolaire_id'); 

            $ecoleId = $inscription->eleve->ecole_id;
            $niveauId = $inscription->classe->niveau->id;

            Log::info('Inscription trouvée', ['inscription_id' => $inscription->id]);
            Log::info('IDs récupérés', compact('anneeId', 'ecoleId', 'niveauId'));

            // Récupérer les types de frais pour la cantine
            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            Log::info('Type frais trouvés', [
                'cantine' => $typeCantine->id ?? null
            ]);

            // Récupérer le tarif de la cantine
            $tarifCantine = Tarif::where('annee_scolaire_id', $anneeId)
                ->where('niveau_id', $niveauId)
                ->where('ecole_id', $ecoleId)
                ->where('type_frais_id', $typeCantine->id ?? 0)
                ->first();

            Log::info('Tarifs trouvés', [
                'tarif_cantine' => $tarifCantine?->montant
            ]);

            $montantCantine = $tarifCantine ? $tarifCantine->montant : 0;

            // Récupérer tous les paiements pour la cantine
            $paiements = Paiement::where('inscription_id', $inscription->id)
                ->where('type_frais_id', $typeCantine->id ?? 0)
                ->get();
                
            Log::info('Paiements trouvés', ['count' => $paiements->count()]);

            // Total payé pour la cantine
            $totalPayeCantine = $paiements->sum('montant');
            $resteAPayerCantine = max(0, $montantCantine - $totalPayeCantine);

            Log::info('Totaux payés', [
                'cantine' => $totalPayeCantine
            ]);

            $tousPaiements = $paiements->sortByDesc('created_at')->values();

            return response()->json([
                'success' => true,
                'eleve' => [
                    'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                    'matricule' => $inscription->eleve->matricule,
                    'classe' => $inscription->classe->nom
                ],
                'frais' => [
                    'cantine' => $montantCantine
                ],
                'paiements' => $tousPaiements,
                'total_paye' => [
                    'cantine' => $totalPayeCantine
                ],
                'reste_a_payer' => [
                    'cantine' => $resteAPayerCantine
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur eleveData Cantine', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des données de cantine: ' . $e->getMessage()
            ]);
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
            $anneeId = session('annee_scolaire_id'); // 🔑 On force à utiliser la session
            if (!$anneeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active dans la session.'
                ], 422);
            }

            $inscription = Inscription::findOrFail($request->inscription_id);
            
            // Vérifier si l'élève a la cantine active
            if (!$inscription->cantine_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'a pas la cantine active'
                ]);
            }

            $ecoleId = $inscription->eleve->ecole_id;
            $niveauId = $inscription->classe->niveau->id;


            // Récupérer le type de frais pour la cantine
            $typeCantine = TypeFrais::where('nom', "cantine")->first();

            if (!$typeCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de frais "cantine" non trouvé'
                ]);
            }

            // Récupérer le tarif de la cantine
            $tarifCantine = Tarif::where('annee_scolaire_id', $anneeId)
                ->where('niveau_id', $niveauId)
                ->where('ecole_id', $ecoleId)
                ->where('type_frais_id', $typeCantine->id)
                ->first();

            if (!$tarifCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif de cantine non trouvé pour cette configuration'
                ]);
            }

            // Récupérer le total déjà payé pour la cantine
            $totalPayeCantine = Paiement::where('inscription_id', $request->inscription_id)
                ->where('type_frais_id', $typeCantine->id)
                ->sum('montant');

            $resteAPayer = max(0, $tarifCantine->montant - $totalPayeCantine);

            // Vérifier que le montant saisi ne dépasse pas le reste à payer
            if ($request->montant_cantine > $resteAPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant saisi dépasse le reste à payer (' . $resteAPayer . ' FCFA)'
                ]);
            }

            // Créer le paiement
            $paiement = Paiement::create([
                'user_id' => auth()->id(),
                'inscription_id' => $request->inscription_id,
                'annee_scolaire_id' => $anneeId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeCantine->id,
                'montant' => $request->montant_cantine,
                'mode_paiement' => $request->mode_paiement,
                'date_paiement' => $request->date_paiement,
                'description' => 'Paiement cantine'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'paiement_id' => $paiement->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur storePaiementCantine', ['message' => $e->getMessage()]);
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
        $typeScolarite = TypeFrais::where('nom', 'like', '%cantine%')->first();
        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id)
            ->where('niveau_id', $eleve->classe->niveau_id)
            ->first();

        $paiements = Paiement::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('type_frais_id', $typeScolarite->id)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $reduction = ReductionCantine::where('eleve_id', $eleve->id)
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
