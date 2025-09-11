<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\PaiementCantine;
use App\Models\PaiementDetail;
use App\Models\Reduction;
use App\Models\ReductionCantine;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

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
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->findOrFail($request->inscription_id);

        $anneeId = session('annee_scolaire_id');
        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        // Type de frais Cantine
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();

        $tarifCantine = Tarif::where([
            'annee_scolaire_id' => $anneeId,
            'niveau_id' => $niveauId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeCantine->id ?? 0
        ])->first();

        $montantCantine = $tarifCantine->montant ?? 0;

        // Récupérer les paiements liés à la Cantine
        $paiements = Paiement::with('details.typeFrais')
            ->whereHas('details', function($q) use ($inscription, $typeCantine) {
                $q->where('inscription_id', $inscription->id)
                  ->where('type_frais_id', $typeCantine->id ?? 0);
            })
            ->orderByDesc('created_at')
            ->get();

        // Calcul du total payé pour la Cantine
        $totalPayeCantine = 0;
        foreach ($paiements as $paiement) {
            foreach ($paiement->details as $detail) {
                if ($detail->type_frais_id == ($typeCantine->id ?? 0)) {
                    $totalPayeCantine += $detail->montant;
                }
            }
        }

        $resteCantine = max(0, $montantCantine - $totalPayeCantine);

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
            'total_paye' => [
                'cantine' => $totalPayeCantine
            ],
            'reste_a_payer' => [
                'cantine' => $resteCantine
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
        'montant_cantine' => 'required|numeric|min:0',
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

        if (!$inscription->cantine_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'a pas la cantine active.'
            ]);
        }

        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        $typeCantine = TypeFrais::where('nom', 'Cantine')->first();
        if (!$typeCantine) {
            return response()->json([
                'success' => false,
                'message' => 'Type de frais "Cantine" non trouvé.'
            ]);
        }

        $tarifCantine = Tarif::where([
            'annee_scolaire_id' => $anneeId,
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

        $totalPayeCantine = PaiementDetail::where('inscription_id', $request->inscription_id)
            ->where('type_frais_id', $typeCantine->id)
            ->sum('montant');

        $resteAPayer = max(0, $tarifCantine->montant - $totalPayeCantine);

        if ($request->montant_cantine > $resteAPayer) {
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
            'montant' => $request->montant_cantine,
            'mode_paiement' => $request->mode_paiement,
            'reference' => null,
            'description' => 'Paiement Cantine',
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
        ]);


        // Paiement détail
        PaiementDetail::create([
            'paiement_id' => $paiement->id,
            'inscription_id' => $request->inscription_id,
            'annee_scolaire_id' => $anneeId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeCantine->id,
            'montant' => $request->montant_cantine,
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
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
