<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use App\Models\Eleve;
use App\Services\TableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReglementController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
        $this->tableService = $tableService;
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les classes depuis la table dynamique
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $classes = DB::table($classesTable . ' as c')
            ->join('niveaux', 'c.niveau_id', '=', 'niveaux.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('niveaux.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select('c.*', 'niveaux.nom as niveau_nom')
            ->get();

        return view('dashboard.pages.comptabilites.reglement', compact('classes'));
    }

    /**
     * Récupérer les élèves d'une classe (API)
     */
    public function elevesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les tables dynamiques
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $eleves = DB::table($elevesTable . ' as e')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->where('e.ecole_id', $ecoleId)
            ->where('e.annee_scolaire_id', $anneeScolaireId)
            ->where('e.classe_id', $request->classe_id)
            ->where('e.is_active', 1)
            ->select(
                'e.id as eleve_id',
                'e.nom',
                'e.prenom',
                'e.matricule',
                'c.nom as classe_nom'
            )
            ->orderBy('e.nom', 'asc')
            ->orderBy('e.prenom', 'asc')
            ->get()
            ->map(function($eleve) {
                return [
                    'id' => $eleve->eleve_id,
                    'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                    'matricule' => $eleve->matricule,
                    'classe' => $eleve->classe_nom
                ];
            });

        return response()->json($eleves);
    }

    /**
     * Récupérer les données d'un élève (API)
     */
    public function eleveData(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            // Récupérer les tables dynamiques
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
            $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
            $paiementsTable = $this->tableService->getPaiementsTableName($ecoleId, $annee);
            $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
            $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
            $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);

            // Récupérer l'élève avec sa classe
            $eleve = DB::table($elevesTable . ' as e')
                ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
                ->where('e.id', $request->eleve_id)
                ->where('e.ecole_id', $ecoleId)
                ->where('e.annee_scolaire_id', $anneeScolaireId)
                ->select(
                    'e.*',
                    'c.nom as classe_nom',
                    'c.niveau_id'
                )
                ->first();

            if (!$eleve) {
                throw new \Exception('Élève non trouvé');
            }

            $niveauId = $eleve->niveau_id;

            // Récupérer les types de frais
            $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
            $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
            $typeTransport = TypeFrais::where('nom', "Transport")->first();
            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            // Récupérer les tarifs
            $tarifInscription = DB::table($tarifsTable)
                ->where('type_frais_id', $typeInscription->id ?? 0)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->first();

            $tarifScolarite = DB::table($tarifsTable)
                ->where('type_frais_id', $typeScolarite->id ?? 0)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->first();

            $tarifTransport = DB::table($tarifsTable)
                ->where('type_frais_id', $typeTransport->id ?? 0)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->first();

            $tarifCantine = DB::table($tarifsTable)
                ->where('type_frais_id', $typeCantine->id ?? 0)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->first();

            $montantInscription = $tarifInscription->montant ?? 0;
            $montantScolarite = $tarifScolarite->montant ?? 0;
            $montantTransport = $tarifTransport->montant ?? 0;
            $montantCantine = $tarifCantine->montant ?? 0;

            // Appliquer les réductions sur la scolarité
            $reduction = DB::table($reductionsTable)
                ->where('eleve_id', $eleve->id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->sum('montant');
                
            $montantScolarite = max(0, $montantScolarite - $reduction);

            // Récupérer les paiements liés à cet élève
            $paiements = DB::table($paiementsTable . ' as p')
                ->leftJoin($paiementDetailsTable . ' as pd', 'p.id', '=', 'pd.paiement_id')
                ->leftJoin($tarifsTable . ' as t', 'pd.tarif_id', '=', 't.id')
                ->where('p.eleve_id', $eleve->id)
                ->where('p.ecole_id', $ecoleId)
                ->where('p.annee_scolaire_id', $anneeScolaireId)
                ->select(
                    'p.id as paiement_id',
                    'p.montant',
                    'p.mode_paiement',
                    'p.created_at',
                    'pd.id as detail_id',
                    'pd.montant as detail_montant',
                    't.libelle as tarif_libelle',
                    't.type_frais_id'
                )
                ->orderBy('p.created_at', 'desc')
                ->get();

            // Calculer les totaux payés par type (SANS ecole_id et annee_scolaire_id car ces colonnes n'existent pas dans paiement_details)
            $totalPayeInscription = DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarifInscription->id ?? 0)
                ->sum('montant');

            $totalPayeScolarite = DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarifScolarite->id ?? 0)
                ->sum('montant');

            $totalPayeTransport = DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarifTransport->id ?? 0)
                ->sum('montant');

            $totalPayeCantine = DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarifCantine->id ?? 0)
                ->sum('montant');

            $resteInscription = max(0, $montantInscription - $totalPayeInscription);
            $resteScolarite = max(0, $montantScolarite - $totalPayeScolarite);
            $resteTransport = max(0, $montantTransport - $totalPayeTransport);
            $resteCantine = max(0, $montantCantine - $totalPayeCantine);

            // Formater les paiements
            $paiementsFormatted = [];
            $groupedPaiements = $paiements->groupBy('paiement_id');
            
            foreach ($groupedPaiements as $paiementId => $items) {
                $first = $items->first();
                $details = $items->map(function($item) {
                    return [
                        'id' => $item->detail_id,
                        'montant' => $item->detail_montant,
                        'libelle' => $item->tarif_libelle ?? 'Inconnu',
                        'type_frais_id' => $item->type_frais_id
                    ];
                });

                $paiementsFormatted[] = [
                    'id' => $paiementId,
                    'montant' => $first->montant,
                    'mode_paiement' => $first->mode_paiement,
                    'created_at' => $first->created_at,
                    'details' => $details
                ];
            }

            return response()->json([
                'success' => true,
                'eleve' => [
                    'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                    'matricule' => $eleve->matricule,
                    'classe' => $eleve->classe_nom
                ],
                'frais' => [
                    'inscription' => $montantInscription,
                    'scolarite' => $montantScolarite,
                    'transport' => $montantTransport,
                    'cantine' => $montantCantine
                ],
                'total_paye' => [
                    'inscription' => $totalPayeInscription,
                    'scolarite' => $totalPayeScolarite,
                    'transport' => $totalPayeTransport,
                    'cantine' => $totalPayeCantine
                ],
                'reste_a_payer' => [
                    'inscription' => $resteInscription,
                    'scolarite' => $resteScolarite,
                    'transport' => $resteTransport,
                    'cantine' => $resteCantine
                ],
                'reduction' => [
                    'scolarite' => $reduction
                ],
                'paiements' => $paiementsFormatted
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur eleveData: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enregistrer un paiement
     */
public function storePaiement(Request $request)
{
    $request->validate([
        'eleve_id' => 'required|exists:eleves,id',
        'montant_inscription' => 'nullable|numeric|min:0',
        'montant_scolarite' => 'nullable|numeric|min:0',
        'montant_transport' => 'nullable|numeric|min:0',
        'montant_cantine' => 'nullable|numeric|min:0',
        'date_paiement' => 'required|date',
        'mode_paiement' => 'required|string',
        'reference' => 'nullable|string|max:255'
    ]);

    try {
        DB::beginTransaction();

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les tables dynamiques
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $paiementsTable = $this->tableService->getPaiementsTableName($ecoleId, $annee);
        $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);

        // Récupérer l'élève
        $eleve = DB::table($elevesTable)
            ->where('id', $request->eleve_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if (!$eleve) {
            throw new \Exception('Élève non trouvé');
        }

        $montantInscription = floatval($request->montant_inscription ?? 0);
        $montantScolarite = floatval($request->montant_scolarite ?? 0);
        $montantTransport = floatval($request->montant_transport ?? 0);
        $montantCantine = floatval($request->montant_cantine ?? 0);

        $total = $montantInscription + $montantScolarite + $montantTransport + $montantCantine;

        if ($total <= 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Aucun montant à payer'
            ]);
        }

        // ✅ Créer le paiement avec la date complète (heure actuelle)
        $now = now();
        $paiementDate = $request->date_paiement . ' ' . $now->format('H:i:s');

        $paiementId = DB::table($paiementsTable)->insertGetId([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'eleve_id' => $request->eleve_id,
            'montant' => $total,
            'mode_paiement' => $request->mode_paiement,
            'reference' => $request->reference,
            'user_id' => auth()->id(),
            'created_at' => $paiementDate,
            'updated_at' => $paiementDate
        ]);

        // Récupérer les types de frais
        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
        $typeTransport = TypeFrais::where('nom', "Transport")->first();
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();

        // Récupérer le niveau de l'élève
        $classe = DB::table($classesTable)
            ->where('id', $eleve->classe_id)
            ->first();
        $niveauId = $classe->niveau_id ?? null;

        // Récupérer les tarifs
        $tarifInscription = DB::table($tarifsTable)
            ->where('type_frais_id', $typeInscription->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifScolarite = DB::table($tarifsTable)
            ->where('type_frais_id', $typeScolarite->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifTransport = DB::table($tarifsTable)
            ->where('type_frais_id', $typeTransport->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifCantine = DB::table($tarifsTable)
            ->where('type_frais_id', $typeCantine->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        // Créer les détails
        if ($montantInscription > 0 && $tarifInscription) {
            DB::table($paiementDetailsTable)->insert([
                'paiement_id' => $paiementId,
                'eleve_id' => $request->eleve_id,
                'tarif_id' => $tarifInscription->id,
                'montant' => $montantInscription,
                'created_at' => $paiementDate,
                'updated_at' => $paiementDate
            ]);
        }

        if ($montantScolarite > 0 && $tarifScolarite) {
            DB::table($paiementDetailsTable)->insert([
                'paiement_id' => $paiementId,
                'eleve_id' => $request->eleve_id,
                'tarif_id' => $tarifScolarite->id,
                'montant' => $montantScolarite,
                'created_at' => $paiementDate,
                'updated_at' => $paiementDate
            ]);
        }

        if ($montantTransport > 0 && $tarifTransport) {
            DB::table($paiementDetailsTable)->insert([
                'paiement_id' => $paiementId,
                'eleve_id' => $request->eleve_id,
                'tarif_id' => $tarifTransport->id,
                'montant' => $montantTransport,
                'created_at' => $paiementDate,
                'updated_at' => $paiementDate
            ]);
        }

        if ($montantCantine > 0 && $tarifCantine) {
            DB::table($paiementDetailsTable)->insert([
                'paiement_id' => $paiementId,
                'eleve_id' => $request->eleve_id,
                'tarif_id' => $tarifCantine->id,
                'montant' => $montantCantine,
                'created_at' => $paiementDate,
                'updated_at' => $paiementDate
            ]);
        }

        DB::commit();
        
        return response()->json([
            'success' => true, 
            'paiement_id' => $paiementId,
            'message' => 'Paiement enregistré avec succès'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur lors de l\'enregistrement du paiement', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
}


/**
 * Générer un reçu de paiement
 */
public function generateReceipt($paiementId)
{    
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $annee = session('current_annee_scolaire');

    if (!$ecoleId) {
        abort(400, "Aucune école sélectionnée.");
    }

    // Récupérer les tables dynamiques
    $paiementsTable = $this->tableService->getPaiementsTableName($ecoleId, $annee);
    $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
    $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
    $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
    $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

    // Récupérer le paiement
    $paiement = DB::table($paiementsTable . ' as p')
        ->leftJoin('users', 'p.user_id', '=', 'users.id')
        ->where('p.id', $paiementId)
        ->where('p.ecole_id', $ecoleId)
        ->where('p.annee_scolaire_id', $anneeScolaireId)
        ->select('p.*', 'users.name as user_name')
        ->first();

    if (!$paiement) {
        abort(404, "Paiement introuvable.");
    }

    // Récupérer les détails
    $details = DB::table($paiementDetailsTable . ' as pd')
        ->leftJoin($tarifsTable . ' as t', 'pd.tarif_id', '=', 't.id')
        ->where('pd.paiement_id', $paiementId)
        ->select('pd.*', 't.libelle as tarif_libelle', 't.type_frais_id')
        ->get();

    // Récupérer l'élève
    $eleve = DB::table($elevesTable)
        ->where('id', $paiement->eleve_id)
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->first();

    if (!$eleve) {
        abort(404, "Élève introuvable.");
    }

    // Récupérer la classe
    $classe = DB::table($classesTable)
        ->where('id', $eleve->classe_id)
        ->first();

    // Récupérer l'école
    $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();

    if (!$ecole) {
        abort(404, "École introuvable avec l'ID: " . $ecoleId);
    }

    $montant_total = $details->sum('montant');
    $totalPaye = DB::table($paiementDetailsTable)
        ->where('eleve_id', $eleve->id)
        ->sum('montant');
    $reste_total = max(0, $montant_total - $totalPaye);

    // ✅ UTILISER LES MEMES NOMS DE VARIABLES QUE LA VUE
    $ecoleData = [
        'nom_ecole' => $ecole->nom_ecole,
        'telephone' => $ecole->telephone,
        'adresse' => $ecole->adresse ?? '',
        'email' => $ecole->email ?? '',
        'logo' => $ecole->logo ?? '',
    ];
    
    $paiementData = [
        'id' => $paiement->id,
        'created_at' => $paiement->created_at,
        'mode_paiement' => $paiement->mode_paiement,
        'user_name' => $paiement->user_name,
    ];
    
    $detailsData = $details->map(function($detail) {
        return [
            'tarif_libelle' => $detail->tarif_libelle,
            'montant' => $detail->montant,
        ];
    })->toArray();
    
    $eleveData = [
        'code_national' => $eleve->code_national ?? $eleve->matricule,
        'nom' => $eleve->nom,
        'prenom' => $eleve->prenom,
    ];
    
    $classeData = [
        'nom' => $classe ? $classe->nom : '',
    ];

    // ✅ PASSER LES VARIABLES AVEC LES BONS NOMS
    $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', [
        'ecoleData' => $ecoleData,
        'paiementData' => $paiementData,
        'detailsData' => $detailsData,
        'eleveData' => $eleveData,
        'classeData' => $classeData,
        'montant_total' => $montant_total,
        'reste_total' => $reste_total,
    ])->setPaper('A4', 'portrait');
            
    return $pdf->stream("recu_paiement_{$paiementId}.pdf");
}

    /**
     * Supprimer un paiement
     */
    public function deletePaiement(Request $request)
    {
        $request->validate(['paiement_id' => 'required']);

        try {
            DB::beginTransaction();

            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $paiementsTable = $this->tableService->getPaiementsTableName($ecoleId, $annee);
            $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);

            // Supprimer les détails
            DB::table($paiementDetailsTable)
                ->where('paiement_id', $request->paiement_id)
                ->delete();

            // Supprimer le paiement
            DB::table($paiementsTable)
                ->where('id', $request->paiement_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Paiement supprimé avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur deletePaiement: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Générer un reçu (alias)
     */
    public function receipt($paiementId)
    {
        return $this->generateReceipt($paiementId);
    }
}