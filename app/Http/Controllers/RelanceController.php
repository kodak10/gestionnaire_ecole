<?php

namespace App\Http\Controllers;

use App\Exports\RelanceExport;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\PaiementDetail;
use App\Models\Reduction;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use App\Services\TableService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class RelanceController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur']);
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

        // Récupérer les tarifs depuis la table dynamique
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        $tarifs = DB::table($tarifsTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        $moisScolaires = MoisScolaire::orderBy('numero')->get();

        return view('dashboard.pages.comptabilites.relances', compact('classes', 'moisScolaires', 'tarifs'));
    }

    public function getTarifsByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
            $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

            $classe = DB::table($classesTable)
                ->where('id', $request->classe_id)
                ->first();

            if (!$classe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Classe non trouvée'
                ]);
            }

            $niveauId = $classe->niveau_id;

            $tarifs = DB::table($tarifsTable)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->orderBy('type_frais_id')
                ->get();

            $typeFraisMap = TypeFrais::pluck('nom', 'id')->toArray();

            $data = $tarifs->map(function($tarif) use ($typeFraisMap) {
                return [
                    'id' => $tarif->id,
                    'libelle' => $tarif->libelle,
                    'montant' => $tarif->montant,
                    'type_frais_nom' => $typeFraisMap[$tarif->type_frais_id] ?? null,
                    'type_frais_id' => $tarif->type_frais_id,
                    'niveau_id' => $tarif->niveau_id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur getTarifsByClasse: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

//     public function getRelanceData(Request $request)
// {
//     Log::info('=== getRelanceData START ===', [
//         'classe_id' => $request->classe_id,
//         'date_reference' => $request->date_reference,
//         'tarif_id' => $request->tarif_id,
//         'montant_min' => $request->montant_min,
//         'montant_max' => $request->montant_max,
//         'all_request' => $request->all()
//     ]);

//     $request->validate([
//         'classe_id' => 'required|exists:classes,id',
//         'date_reference' => 'required|exists:mois_scolaires,id',
//         'tarif_id' => 'nullable|numeric',
//         'montant_min' => 'nullable|numeric|min:0',
//         'montant_max' => 'nullable|numeric|min:0'
//     ]);

//     if ($request->montant_min && $request->montant_max && 
//         $request->montant_min > $request->montant_max) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Le montant minimum ne peut pas être supérieur au montant maximum'
//         ]);
//     }

//     try {
//         $ecoleId = session('current_ecole_id'); 
//         $anneeScolaireId = session('current_annee_scolaire_id');
//         $annee = session('current_annee_scolaire');

//         // Récupérer les tables dynamiques
//         $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
//         $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
//         $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
//         $tarifsMensuelsTable = $this->tableService->getTarifsMensuelsTableName($ecoleId, $annee);
//         $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
//         $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);
//         $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

//         $moisReference = MoisScolaire::find($request->date_reference);
//         if (!$moisReference) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Mois de référence invalide.'
//             ]);
//         }

//         // Récupérer le tarif spécifique
//         $tarif = null;
//         $typeFraisNom = null;
//         $typeFrais = null;

//         if ($request->tarif_id) {
//             $tarif = DB::table($tarifsTable)
//                 ->where('id', $request->tarif_id)
//                 ->where('ecole_id', $ecoleId)
//                 ->where('annee_scolaire_id', $anneeScolaireId)
//                 ->first();

//             if ($tarif) {
//                 $typeFrais = TypeFrais::find($tarif->type_frais_id);
//                 $typeFraisNom = $typeFrais ? $typeFrais->nom : '';
//             }
//         }

//         // Récupérer les élèves de la classe
//         $eleves = DB::table($elevesTable . ' as e')
//             ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
//             ->leftJoin($niveauxTable . ' as n', 'c.niveau_id', '=', 'n.id')
//             ->where('e.classe_id', $request->classe_id)
//             ->where('e.annee_scolaire_id', $anneeScolaireId)
//             ->where('e.ecole_id', $ecoleId)
//             ->where('e.is_active', 1)
//             ->select(
//                 'e.*',
//                 'c.nom as classe_nom',
//                 'c.niveau_id',
//                 'n.nom as niveau_nom'
//             )
//             ->orderBy('e.nom')
//             ->orderBy('e.prenom')
//             ->get();

//         if ($eleves->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'data' => [],
//                 'classe' => 'Classe',
//                 'mois_reference' => $moisReference->nom,
//                 'tarif_libelle' => $tarif->libelle ?? 'Tous les tarifs',
//                 'message' => 'Aucun élève trouvé dans cette classe'
//             ]);
//         }

//         // Récupérer tous les mois jusqu'au mois de référence
//         $moisScolaires = MoisScolaire::where('numero', '<=', $moisReference->numero)
//             ->orderBy('numero')
//             ->get();

//         $result = [];

//         foreach ($eleves as $eleve) {
//             // Vérifier si le tarif est pour le niveau de l'élève
//             if ($tarif && $tarif->niveau_id && $tarif->niveau_id != $eleve->niveau_id) {
//                 continue;
//             }

//             // Vérifier si le service est actif
//             if ($typeFraisNom == 'Cantine' && !$eleve->cantine_active) {
//                 continue;
//             }
//             if ($typeFraisNom == 'Transport' && !$eleve->transport_active) {
//                 continue;
//             }

//             // Récupérer les tarifs mensuels
//             $tarifsMensuels = DB::table($tarifsMensuelsTable)
//                 ->where('annee_scolaire_id', $anneeScolaireId)
//                 ->where('ecole_id', $ecoleId)
//                 ->where('tarif_id', $tarif->id ?? 0)
//                 ->where(function($q) use ($eleve) {
//                     $q->where('niveau_id', $eleve->niveau_id)
//                       ->orWhereNull('niveau_id');
//                 })
//                 ->get()
//                 ->keyBy('mois_id');

//             if ($tarifsMensuels->isEmpty() && $tarif) {
//                 continue;
//             }

//             if (!$tarif) {
//                 continue;
//             }

//             // === DÉTERMINER LE MOIS DE DÉBUT DU SERVICE ===
//             $moisDebutNumero = null;
//             $jourDebut = null;
//             $dateDebut = null;

//             if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
//                 $startDate = null;
//                 if ($typeFraisNom == 'Cantine' && $eleve->cantine_start_date) {
//                     $startDate = Carbon::parse($eleve->cantine_start_date);
//                 } elseif ($typeFraisNom == 'Transport' && $eleve->transport_start_date) {
//                     $startDate = Carbon::parse($eleve->transport_start_date);
//                 }

//                 if ($startDate) {
//                     $moisDebutNumero = (int) $startDate->format('n');
//                     $jourDebut = (int) $startDate->format('j');
//                     $dateDebut = $startDate->format('d/m/Y');
//                 } else {
//                     // Si pas de date de début, on prend le mois en cours
//                     $moisDebutNumero = (int) Carbon::now()->format('n');
//                     $jourDebut = 1;
//                     $dateDebut = Carbon::now()->format('d/m/Y');
//                 }
//             } else {
//                 // Pour Scolarité et Inscription: début à partir du premier mois scolaire (Août)
//                 $moisDebutNumero = 8;
//                 $jourDebut = 1;
//                 $dateDebut = 'Début année';
//             }

//             // ✅ VÉRIFICATION CRUCIALE: Si le mois de début est après le mois de référence, on ignore
//             if ($moisReference->numero < $moisDebutNumero) {
//                 Log::info('⏭️ Élève pas encore inscrit au service pour le mois de référence', [
//                     'eleve' => $eleve->nom . ' ' . $eleve->prenom,
//                     'service' => $typeFraisNom,
//                     'mois_debut' => $moisDebutNumero,
//                     'mois_reference' => $moisReference->numero
//                 ]);
//                 continue;
//             }

//             $moisDebut = MoisScolaire::where('numero', $moisDebutNumero)->first();

//             // === CALCUL DU MONTANT MENSUEL ===
//             $montantMensuel = 0;
//             $tarifMoisRef = $tarifsMensuels->get($moisReference->id);
//             if ($tarifMoisRef) {
//                 $montantMensuel = (float) $tarifMoisRef->montant;

//                 // Demi-tarif si le mois de début = mois de référence et jour > 15
//                 if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
//                     if ($moisReference->numero == $moisDebutNumero && $jourDebut > 15) {
//                         $montantMensuel = $montantMensuel / 2;
//                     }
//                 }
//             }

//             if ($montantMensuel <= 0) {
//                 continue;
//             }

//             // === CALCUL DU CUMUL ATTENDU ===
//             $cumulAttendu = 0;

//             foreach ($moisScolaires as $mois) {
//                 // Ignorer les mois avant le début du service
//                 if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
//                     if ($mois->numero < $moisDebutNumero) {
//                         continue;
//                     }
//                 }

//                 $tarifMensuel = $tarifsMensuels->get($mois->id);
//                 if (!$tarifMensuel) {
//                     continue;
//                 }

//                 $montant = (float) $tarifMensuel->montant;

//                 // Demi-tarif pour le mois de début si jour > 15
//                 if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
//                     if ($mois->numero == $moisDebutNumero && $jourDebut > 15) {
//                         $montant = $montant / 2;
//                     }
//                 }

//                 if ($mois->id <= $moisReference->id) {
//                     $cumulAttendu += $montant;
//                 }
//             }

//             // === RÉDUCTION POUR LA SCOLARITÉ ===
//             $reduction = 0;
//             if ($typeFraisNom == 'Scolarité') {
//                 $reduction = (float) DB::table($reductionsTable)
//                     ->where('eleve_id', $eleve->id)
//                     ->where('annee_scolaire_id', $anneeScolaireId)
//                     ->where('ecole_id', $ecoleId)
//                     ->where(function($q) use ($tarif) {
//                         $q->whereNull('tarif_id')
//                             ->orWhere('tarif_id', $tarif->id ?? 0);
//                     })
//                     ->sum('montant');
                
//                 if ($reduction > 0 && $cumulAttendu > 0) {
//                     $cumulAttendu = max(0, $cumulAttendu - $reduction);
//                 }
//             }

//             // === TOTAL PAYÉ ===
//             $totalPaye = (float) DB::table($paiementDetailsTable)
//                 ->where('eleve_id', $eleve->id)
//                 ->where('tarif_id', $tarif->id ?? 0)
//                 ->sum('montant');

//             // Payé avant le mois de référence
//             $payeAvant = (float) DB::table($paiementDetailsTable)
//                 ->where('eleve_id', $eleve->id)
//                 ->where('tarif_id', $tarif->id ?? 0)
//                 ->where('created_at', '<', $moisReference->created_at ?? Carbon::now())
//                 ->sum('montant');

//             // Payé pour le mois de référence
//             $payeMois = $totalPaye - $payeAvant;

//             // === CALCUL DES RESTES ===
//             $resteMois = max(0, $montantMensuel - $payeMois);
//             $resteCumul = max(0, $cumulAttendu - $totalPaye);
//             $statut = $resteCumul <= 0 ? 'A jour' : 'En retard';

//             // === FILTRER PAR MONTANT ===
//             if ($request->montant_min || $request->montant_max) {
//                 $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
//                 $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                
//                 if ($resteCumul < $montantMin || $resteCumul > $montantMax) {
//                     continue;
//                 }
//             }

//             $typeTarif = $typeFraisNom . ' - ' . ($tarif->libelle ?? '');
//             $dateDebutAffichage = $dateDebut ?? 'Début année';

//             $result[] = [
//                 'eleve' => $eleve->nom . ' ' . $eleve->prenom,
//                 'classe' => $eleve->classe_nom,
//                 'niveau' => $eleve->niveau_nom,
//                 'type_tarif' => $typeTarif,
//                 'date_debut' => $dateDebutAffichage,
//                 'mois_debut' => $moisDebut ? $moisDebut->nom : 'N/A',
//                 'montant_mois' => $montantMensuel,
//                 'cumul_attendu' => $cumulAttendu,
//                 'total_paye' => $totalPaye,
//                 'paye_mois' => $payeMois,
//                 'reste_mois' => $resteMois,
//                 'reste_cumul' => $resteCumul,
//                 'statut' => $statut,
//                 'mois_reference' => $moisReference->nom,
//                 'reduction' => $reduction,
//                 'telephone' => $eleve->telephone ?? '',
//                 'parent_telephone' => $eleve->parent_telephone ?? '',
//                 'parent_nom' => $eleve->parent_nom ?? '',
//                 'id' => $eleve->id
//             ];
//         }

//         Log::info('✅ Résultats finaux', ['count' => count($result)]);

//         return response()->json([
//             'success' => true,
//             'data' => $result,
//             'classe' => $eleves->first()->classe_nom ?? 'Classe',
//             'mois_reference' => $moisReference->nom,
//             'tarif_libelle' => $tarif->libelle ?? 'Tous les tarifs',
//             'montant_min' => $request->montant_min,
//             'montant_max' => $request->montant_max
//         ]);

//     } catch (\Exception $e) {
//         Log::error("❌ Erreur getRelanceData: " . $e->getMessage());
//         Log::error($e->getTraceAsString());
//         return response()->json([
//             'success' => false,
//             'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
//         ]);
//     }
// }


public function getRelanceData(Request $request)
{
    Log::info('=== getRelanceData START ===', $request->all());

    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'tarif_id' => 'nullable|numeric',
        'montant_min' => 'nullable|numeric|min:0',
        'montant_max' => 'nullable|numeric|min:0'
    ]);

    try {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les tables dynamiques
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        $tarifsMensuelsTable = $this->tableService->getTarifsMensuelsTableName($ecoleId, $annee);
        $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
        $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);

        $moisReference = MoisScolaire::find($request->date_reference);
        if (!$moisReference) {
            return response()->json([
                'success' => false,
                'message' => 'Mois de référence invalide.'
            ]);
        }

        // Récupérer la classe pour connaître le niveau
        $classe = DB::table($classesTable)->where('id', $request->classe_id)->first();
        if (!$classe) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée.'
            ]);
        }
        $niveauId = $classe->niveau_id;

        // === RÉCUPÉRER LE TARIF ===
        $tarif = null;
        $typeFraisNom = null;
        $typeFrais = null;

        if ($request->tarif_id) {
            $tarif = DB::table($tarifsTable)
                ->where('id', $request->tarif_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if ($tarif) {
                if ($tarif->niveau_id !== null && $tarif->niveau_id != $niveauId) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'classe' => $classe->nom ?? 'Classe',
                        'mois_reference' => $moisReference->nom,
                        'tarif_libelle' => $tarif->libelle ?? 'Tarif',
                        'message' => 'Ce tarif ne s\'applique pas au niveau de cette classe'
                    ]);
                }
                
                $typeFrais = TypeFrais::find($tarif->type_frais_id);
                $typeFraisNom = $typeFrais ? $typeFrais->nom : '';
            }
        }

        if (!$tarif) {
            return response()->json([
                'success' => true,
                'data' => [],
                'classe' => $classe->nom ?? 'Classe',
                'mois_reference' => $moisReference->nom,
                'tarif_libelle' => 'Aucun tarif sélectionné',
                'message' => 'Veuillez sélectionner un tarif'
            ]);
        }

        // Récupérer les élèves de la classe
        $eleves = DB::table($elevesTable . ' as e')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->leftJoin($niveauxTable . ' as n', 'c.niveau_id', '=', 'n.id')
            ->where('e.classe_id', $request->classe_id)
            ->where('e.annee_scolaire_id', $anneeScolaireId)
            ->where('e.ecole_id', $ecoleId)
            ->where('e.is_active', 1)
            ->select(
                'e.*',
                'c.nom as classe_nom',
                'c.niveau_id',
                'n.nom as niveau_nom'
            )
            ->orderBy('e.nom')
            ->orderBy('e.prenom')
            ->get();

        if ($eleves->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'classe' => $classe->nom ?? 'Classe',
                'mois_reference' => $moisReference->nom,
                'tarif_libelle' => $tarif->libelle ?? 'Tarif',
                'message' => 'Aucun élève trouvé dans cette classe'
            ]);
        }

        // Récupérer tous les mois jusqu'au mois de référence
        $moisScolaires = MoisScolaire::where('numero', '<=', $moisReference->numero)
            ->orderBy('numero')
            ->get();

        // Récupérer les tarifs mensuels pour ce tarif et ce niveau
        $tarifsMensuels = DB::table($tarifsMensuelsTable)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('tarif_id', $tarif->id)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->get()
            ->keyBy('mois_id');

        if ($tarifsMensuels->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'classe' => $classe->nom ?? 'Classe',
                'mois_reference' => $moisReference->nom,
                'tarif_libelle' => $tarif->libelle ?? 'Tarif',
                'message' => 'Aucun tarif mensuel trouvé pour ce tarif'
            ]);
        }

        $result = [];

        foreach ($eleves as $eleve) {
            // ======================================================
            // === FILTRAGE SPÉCIFIQUE PAR TYPE DE FRAIS ===
            // ======================================================
            
            // 1. POUR LA CANTINE
            if ($typeFraisNom == 'Cantine') {
                // L'élève doit être actif à la cantine
                if (!$eleve->cantine_active) {
                    Log::info('⏭️ Élève non actif à la cantine', [
                        'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                        'cantine_active' => $eleve->cantine_active
                    ]);
                    continue;
                }
                
                // Si l'élève a un tarif de cantine défini, il doit correspondre au tarif sélectionné
                if ($eleve->cantine_tarif_id !== null && $eleve->cantine_tarif_id != $tarif->id) {
                    Log::info('⏭️ Tarif cantine différent', [
                        'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                        'tarif_eleve' => $eleve->cantine_tarif_id,
                        'tarif_selectionne' => $tarif->id
                    ]);
                    continue;
                }
                
                // Si l'élève n'a pas de tarif défini (NULL), on le prend par défaut
                // mais on vérifie qu'il a une date de début
                if ($eleve->cantine_tarif_id === null && !$eleve->cantine_start_date) {
                    Log::info('⏭️ Élève sans date de début cantine', [
                        'eleve' => $eleve->nom . ' ' . $eleve->prenom
                    ]);
                    continue;
                }
            }
            
            // 2. POUR LE TRANSPORT
            if ($typeFraisNom == 'Transport') {
                if (!$eleve->transport_active) {
                    continue;
                }
                
                if ($eleve->transport_tarif_id !== null && $eleve->transport_tarif_id != $tarif->id) {
                    continue;
                }
                
                if ($eleve->transport_tarif_id === null && !$eleve->transport_start_date) {
                    continue;
                }
            }

            // === DÉTERMINER LE MOIS DE DÉBUT DU SERVICE ===
            $moisDebutNumero = null;
            $jourDebut = null;
            $dateDebut = null;

            if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                $startDate = null;
                if ($typeFraisNom == 'Cantine' && $eleve->cantine_start_date) {
                    $startDate = Carbon::parse($eleve->cantine_start_date);
                } elseif ($typeFraisNom == 'Transport' && $eleve->transport_start_date) {
                    $startDate = Carbon::parse($eleve->transport_start_date);
                }

                if ($startDate) {
                    $moisDebutNumero = (int) $startDate->format('n');
                    $jourDebut = (int) $startDate->format('j');
                    $dateDebut = $startDate->format('d/m/Y');
                } else {
                    // Si pas de date de début, on prend le mois en cours
                    $moisDebutNumero = (int) Carbon::now()->format('n');
                    $jourDebut = 1;
                    $dateDebut = Carbon::now()->format('d/m/Y');
                }
            } else {
                // Pour Scolarité et Inscription: début à partir du premier mois scolaire (Août)
                $moisDebutNumero = 8;
                $jourDebut = 1;
                $dateDebut = 'Début année';
            }

            // Si le mois de début est après le mois de référence, on ignore
            if ($moisReference->numero < $moisDebutNumero) {
                Log::info('⏭️ Service pas encore commencé', [
                    'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                    'mois_debut' => $moisDebutNumero,
                    'mois_reference' => $moisReference->numero
                ]);
                continue;
            }

            $moisDebut = MoisScolaire::where('numero', $moisDebutNumero)->first();

            // === CALCUL DU MONTANT MENSUEL ===
            $montantMensuel = 0;
            $tarifMoisRef = $tarifsMensuels->get($moisReference->id);
            if ($tarifMoisRef) {
                $montantMensuel = (float) $tarifMoisRef->montant;

                if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                    if ($moisReference->numero == $moisDebutNumero && $jourDebut > 15) {
                        $montantMensuel = $montantMensuel / 2;
                    }
                }
            }

            if ($montantMensuel <= 0) {
                continue;
            }

            // === CALCUL DU CUMUL ATTENDU ===
            $cumulAttendu = 0;

            foreach ($moisScolaires as $mois) {
                if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                    if ($mois->numero < $moisDebutNumero) {
                        continue;
                    }
                }

                $tarifMensuel = $tarifsMensuels->get($mois->id);
                if (!$tarifMensuel) {
                    continue;
                }

                $montant = (float) $tarifMensuel->montant;

                if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                    if ($mois->numero == $moisDebutNumero && $jourDebut > 15) {
                        $montant = $montant / 2;
                    }
                }

                if ($mois->id <= $moisReference->id) {
                    $cumulAttendu += $montant;
                }
            }

            // === RÉDUCTION ===
            $reduction = 0;
            if ($typeFraisNom == 'Scolarité') {
                $reduction = (float) DB::table($reductionsTable)
                    ->where('eleve_id', $eleve->id)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where(function($q) use ($tarif) {
                        $q->whereNull('tarif_id')
                            ->orWhere('tarif_id', $tarif->id);
                    })
                    ->sum('montant');
                
                if ($reduction > 0 && $cumulAttendu > 0) {
                    $cumulAttendu = max(0, $cumulAttendu - $reduction);
                }
            }

            // === TOTAL PAYÉ ===
            $totalPaye = (float) DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarif->id)
                ->sum('montant');

            $payeAvant = (float) DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarif->id)
                ->where('created_at', '<', $moisReference->created_at ?? Carbon::now())
                ->sum('montant');

            $payeMois = $totalPaye - $payeAvant;

            // === CALCUL DES RESTES ===
            $resteMois = max(0, $montantMensuel - $payeMois);
            $resteCumul = max(0, $cumulAttendu - $totalPaye);
            $statut = $resteCumul <= 0 ? 'A jour' : 'En retard';

            // === FILTRER PAR MONTANT ===
            if ($request->montant_min || $request->montant_max) {
                $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                
                if ($resteCumul < $montantMin || $resteCumul > $montantMax) {
                    continue;
                }
            }

            $typeTarif = $typeFraisNom . ' - ' . ($tarif->libelle ?? '');
            $dateDebutAffichage = $dateDebut ?? 'Début année';

            $result[] = [
                'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                'classe' => $eleve->classe_nom,
                'niveau' => $eleve->niveau_nom,
                'type_tarif' => $typeTarif,
                'date_debut' => $dateDebutAffichage,
                'mois_debut' => $moisDebut ? $moisDebut->nom : 'N/A',
                'montant_mois' => $montantMensuel,
                'cumul_attendu' => $cumulAttendu,
                'total_paye' => $totalPaye,
                'paye_mois' => $payeMois,
                'reste_mois' => $resteMois,
                'reste_cumul' => $resteCumul,
                'statut' => $statut,
                'mois_reference' => $moisReference->nom,
                'reduction' => $reduction,
                'telephone' => $eleve->telephone ?? '',
                'parent_telephone' => $eleve->parent_telephone ?? '',
                'parent_nom' => $eleve->parent_nom ?? '',
                'id' => $eleve->id
            ];
        }

        Log::info('✅ Résultats finaux', ['count' => count($result)]);

        return response()->json([
            'success' => true,
            'data' => $result,
            'classe' => $classe->nom ?? 'Classe',
            'mois_reference' => $moisReference->nom,
            'tarif_libelle' => $tarif->libelle ?? 'Tarif',
            'tarif_id' => $request->tarif_id,
            'montant_min' => $request->montant_min,
            'montant_max' => $request->montant_max
        ]);

    } catch (\Exception $e) {
        Log::error("❌ Erreur getRelanceData: " . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
        ]);
    }
}

public function imprimerRelance(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'tarif_id' => 'nullable|numeric',
        'montant_min' => 'nullable|numeric|min:0',
        'montant_max' => 'nullable|numeric|min:0',
        'eleve_ids' => 'nullable|array',
        'eleve_ids.*' => 'numeric'
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer l'école
        $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();

        if (!$ecole) {
            abort(404, "École introuvable avec l'ID: " . $ecoleId);
        }

        // Récupérer les données de la relance
        $response = $this->getRelanceData($request);
        $relanceData = json_decode($response->getContent(), true);

        if (!$relanceData['success'] || empty($relanceData['data'])) {
            return redirect()->back()->with('error', 'Aucune donnée à imprimer');
        }

        // Filtrer par IDs des élèves sélectionnés
        if ($request->has('eleve_ids') && !empty($request->eleve_ids)) {
            $eleveIds = $request->eleve_ids;
            if (!is_array($eleveIds)) {
                $eleveIds = [$eleveIds];
            }
            
            $relanceData['data'] = array_filter($relanceData['data'], function($item) use ($eleveIds) {
                return in_array($item['id'] ?? 0, $eleveIds);
            });
            
            $relanceData['data'] = array_values($relanceData['data']);
            
            if (empty($relanceData['data'])) {
                return redirect()->back()->with('error', 'Aucun élève sélectionné trouvé');
            }
        }

        // Récupérer le template de relance papier
        $template = DB::table('document_templates')
            ->where('ecole_id', $ecoleId)
            ->where('type', 'relancePapier')
            ->where('is_active', 1)
            ->first();

        if (!$template) {
            $template = DB::table('document_templates')
                ->where('ecole_id', $ecoleId)
                ->where('type', 'relance')
                ->where('is_active', 1)
                ->first();
        }

        // Récupérer les détails
        $classeNom = $relanceData['classe'];
        $moisReference = $relanceData['mois_reference'];
        $tarifLibelle = $relanceData['tarif_libelle'];
        $eleves = $relanceData['data'];

        // PRÉPARER LES DONNÉES
        $ecoleData = [
            'nom_ecole' => $ecole->nom_ecole,
            'telephone' => $ecole->telephone,
            'adresse' => $ecole->adresse ?? '',
            'email' => $ecole->email ?? '',
            'logo' => $ecole->logo ?? '',
        ];

        // Préparer les élèves avec toutes les données nécessaires
        $elevesData = [];
        foreach ($eleves as $eleve) {
            $eleveData = DB::table($this->tableService->getElevesTableName($ecoleId, $annee))
                ->where('id', $eleve['id'] ?? 0)
                ->first();

            $nomComplet = $eleve['eleve'] ?? '';
            $nomParts = explode(' ', $nomComplet);
            $nom = $nomParts[0] ?? '';
            $prenom = implode(' ', array_slice($nomParts, 1)) ?? '';

            // Variables pour le template
            $variables = [
                '%NOM%' => $nom,
                '%PRENOM%' => $prenom,
                '%NOM_COMPLET%' => $nomComplet,
                '%NOM_RESPONSABLE%' => $eleve['parent_nom'] ?? 'Parent',
                '%MONTANT%' => number_format($eleve['reste_cumul'] ?? 0, 0, ',', ' ') . ' FCFA',
                '%MONTANT_MOIS%' => number_format($eleve['reste_mois'] ?? 0, 0, ',', ' ') . ' FCFA',
                '%CLASSE%' => $eleve['classe'] ?? '',
                '%MOIS%' => $eleve['mois_reference'] ?? '',
                '%DATE%' => now()->locale('fr')->translatedFormat('d F Y'),
                '%ANNEE%' => $annee,
                '%MATRICULE%' => $eleveData->matricule ?? '-',
                '%TELEPHONE%' => $eleve['telephone'] ?? '',
                '%TELEPHONE_PARENT%' => $eleve['parent_telephone'] ?? '',
                '%ECOLE%' => $ecole->nom_ecole,
                '%ADRESSE%' => $ecole->adresse ?? '',
                '%TEL_ECOLE%' => $ecole->telephone ?? '',
            ];

            $content = $template ? $template->content : '';
            $contentRempli = str_replace(array_keys($variables), array_values($variables), $content);

            $elevesData[] = [
                'id' => $eleve['id'] ?? 0,
                'nom' => $nom,
                'prenom' => $prenom,
                'nom_complet' => $nomComplet,
                'matricule' => $eleveData->matricule ?? '-',
                'classe' => $eleve['classe'] ?? '',
                'parent_nom' => $eleve['parent_nom'] ?? '',
                'parent_telephone' => $eleve['parent_telephone'] ?? $eleve['telephone'] ?? '',
                'telephone' => $eleve['telephone'] ?? '',
                'type_tarif' => $eleve['type_tarif'] ?? '',
                'montant_mois' => $eleve['montant_mois'] ?? 0,
                'cumul_attendu' => $eleve['cumul_attendu'] ?? 0,
                'total_paye' => $eleve['total_paye'] ?? 0,
                'reste_mois' => $eleve['reste_mois'] ?? 0,
                'reste_cumul' => $eleve['reste_cumul'] ?? 0,
                'statut' => $eleve['statut'] ?? 'En retard',
                'mois_reference' => $eleve['mois_reference'] ?? $moisReference,
                'content_rendu' => $contentRempli,
            ];
        }

        $pdfData = [
            'ecoleData' => $ecoleData,
            'classe_nom' => $classeNom,
            'mois_reference' => $moisReference,
            'tarif_libelle' => $tarifLibelle,
            'elevesData' => $elevesData,
            'date' => now()->locale('fr')->translatedFormat('d F Y'),
            'annee_scolaire' => $annee,
            'template_content' => $template ? $template->content : '',
            'template_used' => $template ? $template->nom : 'Template par défaut',
        ];

        $pdf = PDF::loadView('dashboard.documents.scolarite.relance_papier', $pdfData)
            ->setPaper('A4', 'portrait');

        return $pdf->stream("relance_{$classeNom}_{$moisReference}_{$annee}.pdf");

    } catch (\Exception $e) {
        Log::error('❌ Erreur imprimerRelance: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        return redirect()->back()->with('error', 'Erreur lors de l\'impression: ' . $e->getMessage());
    }
}

public function export(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'tarif_id' => 'nullable|numeric',
        'montant_min' => 'nullable|numeric|min:0',
        'montant_max' => 'nullable|numeric|min:0',
        'format' => 'required|in:pdf,excel',
        'eleve_ids' => 'nullable|array',
        'eleve_ids.*' => 'numeric'
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les données de la relance
        $response = $this->getRelanceData($request);
        $relanceData = json_decode($response->getContent(), true);

        if (!$relanceData['success'] || empty($relanceData['data'])) {
            return redirect()->back()->with('error', 'Aucune donnée à exporter');
        }

        // Filtrer par IDs des élèves sélectionnés
        if ($request->has('eleve_ids') && !empty($request->eleve_ids)) {
            $eleveIds = $request->eleve_ids;
            if (!is_array($eleveIds)) {
                $eleveIds = [$eleveIds];
            }
            
            $relanceData['data'] = array_filter($relanceData['data'], function($item) use ($eleveIds) {
                return in_array($item['id'] ?? 0, $eleveIds);
            });
            
            $relanceData['data'] = array_values($relanceData['data']);
            
            if (empty($relanceData['data'])) {
                return redirect()->back()->with('error', 'Aucun élève sélectionné trouvé');
            }
        }

        $classeNom = $relanceData['classe'];
        $moisReference = $relanceData['mois_reference'];
        $eleves = $relanceData['data'];

        // Préparer les données pour l'export
        $exportData = [];
        foreach ($eleves as $eleve) {
            $exportData[] = [
                'Élève' => $eleve['eleve'] ?? '',
                'Classe' => $eleve['classe'] ?? '',
                'Montant Mois' => $eleve['montant_mois'] ?? 0,
                'Cumul Attendu' => $eleve['cumul_attendu'] ?? 0,
                'Total Payé' => $eleve['total_paye'] ?? 0,
                'Reste Mois' => $eleve['reste_mois'] ?? 0,
                'Reste Cumulé' => $eleve['reste_cumul'] ?? 0,
                'Statut' => $eleve['statut'] ?? 'En retard',
                'Téléphone' => $eleve['telephone'] ?? '',
                'Parent' => $eleve['parent_nom'] ?? '',
                'Téléphone Parent' => $eleve['parent_telephone'] ?? '',
            ];
        }

        $fileName = "relance_{$classeNom}_{$moisReference}_{$annee}";

        if ($request->format === 'excel') {
            return Excel::download(new RelanceExport($exportData), $fileName . '.xlsx');
        } else {
            $pdf = PDF::loadView('dashboard.exports.relance_pdf', [
                'data' => $exportData,
                'classe' => $classeNom,
                'mois' => $moisReference,
                'date' => now()->locale('fr')->translatedFormat('d F Y')
            ]);
            return $pdf->download($fileName . '.pdf');
        }

    } catch (\Exception $e) {
        Log::error('❌ Erreur export: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
    }
}

/**
 * Envoyer des SMS de relance en utilisant le template de la base de données
 */
// public function sendSms(Request $request)
// {
//     try {
//         $ecoleId = session('current_ecole_id');
//         $annee = session('current_annee_scolaire');

        
        
//         // Validation des paramètres (sans 'message' car il sera généré)
//         $request->validate([
//             'phone' => 'required|string',
//             'eleve_id' => 'required|numeric',
//             'classe' => 'nullable|string',
//             'mois' => 'nullable|string',
//             'montant' => 'nullable|numeric',
//             'eleve_nom' => 'nullable|string',
//             'parent_nom' => 'nullable|string',
//             '_token' => 'required|string'
//         ]);

//         // Récupérer le template SMS de la base
//         $template = DB::table('document_templates')
//             ->where('ecole_id', $ecoleId)
//             ->where('type', 'relanceSms')
//             ->where('is_active', 1)
//             ->first();

//         if (!$template) {
//             Log::error('Template SMS non trouvé', ['ecole_id' => $ecoleId]);
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Aucun template SMS trouvé dans la base de données'
//             ]);
//         }

//         // Préparer les variables
//         $nomComplet = $request->eleve_nom ?? '';
//         $nomParts = explode(' ', $nomComplet);
//         $nom = $nomParts[0] ?? '';
//         $prenom = implode(' ', array_slice($nomParts, 1)) ?? '';

//         $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
//         $ecoleNom = $ecole ? $ecole->nom_ecole : 'Notre école';

//         $montant = (float) $request->montant;
//         $montantFormatted = number_format($montant, 0, ',', ' ') . ' FCFA';

//         $variables = [
//             '%NOM%' => $nom,
//             '%PRENOM%' => $prenom,
//             '%NOM_COMPLET%' => $nomComplet,
//             '%NOM_RESPONSABLE%' => $request->parent_nom ?? 'Parent',
//             '%MONTANT%' => $montantFormatted,
//             '%CLASSE%' => $request->classe ?? '',
//             '%MOIS%' => $request->mois ?? 'ce mois',
//             '%DATE%' => now()->locale('fr')->translatedFormat('d F Y'),
//             '%ANNEE%' => $annee,
//             '%ECOLE%' => $ecoleNom,
//         ];

//         // Remplacer les variables dans le template
//         $message = str_replace(array_keys($variables), array_values($variables), $template->content);

//         Log::info('📱 Message généré depuis le template', [
//             'template_id' => $template->id,
//             'template_nom' => $template->nom,
//             'phone' => $request->phone,
//             'message_length' => strlen($message)
//         ]);

//         // Initialiser le service SMS
//         $smsService = new \App\Services\SmsService($ecoleId);
        
//         // Vérifier si l'école peut envoyer des SMS
//         $balance = $smsService->checkSmsBalance($ecoleId);
//         if (!$balance['can_send_sms']) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'L\'école n\'a pas assez de crédits SMS ou les notifications sont désactivées',
//                 'balance' => $balance
//             ]);
//         }

//         // Nettoyer le numéro
//         $phone = $request->phone;
        
//         // Envoyer le SMS
//         $result = $smsService->sendSms($phone, $message, $ecoleId);
        
//         if ($result['success']) {
//             return response()->json([
//                 'success' => true,
//                 'message' => 'SMS envoyé avec succès',
//                 'phone' => $phone,
//                 'sms_restant' => $result['sms_restant'] ?? null,
//                 'template_used' => $template->nom,
//                 'message_preview' => $message
//             ]);
//         } else {
//             return response()->json([
//                 'success' => false,
//                 'message' => $result['message'] ?? 'Erreur lors de l\'envoi du SMS'
//             ]);
//         }

//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Validation échouée: ' . implode(', ', $e->errors())
//         ]);
//     } catch (\Exception $e) {
//         Log::error('❌ Erreur sendSms: ' . $e->getMessage());
//         Log::error($e->getTraceAsString());
//         return response()->json([
//             'success' => false,
//             'message' => 'Erreur: ' . $e->getMessage()
//         ]);
//     }
// }


/**
 * Envoyer des SMS de relance en utilisant le template de la base de données
 */
public function sendSms(Request $request)
{
    try {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $request->validate([
            'phone' => 'required|string',
            'eleve_id' => 'required|numeric',
            'classe' => 'nullable|string',
            'mois' => 'nullable|string',
            'montant' => 'nullable|numeric',
            'eleve_nom' => 'nullable|string',
            'parent_nom' => 'nullable|string',
            '_token' => 'required|string'
        ]);

        // Récupérer le template SMS de la base
        $template = DB::table('document_templates')
            ->where('ecole_id', $ecoleId)
            ->where('type', 'relanceSms')
            ->where('is_active', 1)
            ->first();

        if (!$template) {
            Log::error('Template SMS non trouvé', ['ecole_id' => $ecoleId]);
            return response()->json([
                'success' => false,
                'message' => 'Aucun template SMS trouvé dans la base de données'
            ]);
        }

        // Préparer les variables avec troncature
        $nomComplet = $request->eleve_nom ?? '';
        $nomParts = explode(' ', $nomComplet);
        $nom = $this->truncateText($nomParts[0] ?? '', 20);
        $prenom = $this->truncateText(implode(' ', array_slice($nomParts, 1)) ?? '', 30);
        
        // Tronquer le nom du responsable
        $parentNom = $this->truncateText($request->parent_nom ?? 'Parent', 30);

        $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
        $ecoleNom = $ecole ? $this->truncateText($ecole->nom_ecole, 30) : 'Notre école';

        $montant = (float) $request->montant;
        $montantFormatted = number_format($montant, 0, ',', ' ') . ' FCFA';

        $variables = [
            '%NOM%' => $nom,
            '%PRENOM%' => $prenom,
            '%NOM_COMPLET%' => $this->truncateText($nomComplet, 40),
            '%NOM_RESPONSABLE%' => $parentNom,
            '%MONTANT%' => $montantFormatted,
            '%CLASSE%' => $this->truncateText($request->classe ?? '', 20),
            '%MOIS%' => $this->truncateText($request->mois ?? 'ce mois', 15),
            '%DATE%' => now()->locale('fr')->translatedFormat('d F Y'),
            '%ANNEE%' => $annee,
            '%ECOLE%' => $ecoleNom,
        ];

        // Remplacer les variables dans le template
        $message = str_replace(array_keys($variables), array_values($variables), $template->content);

        // === GESTION DE LA LONGUEUR DU SMS ===
        $smsInfo = $this->getSmsLengthInfo($message);
        
        // Tronquer le message si trop long
        if ($smsInfo['char_count'] > 160) {
            // Limiter à 160 caractères pour un SMS standard
            $message = $this->truncateSmsMessage($message, 160);
            $smsInfo = $this->getSmsLengthInfo($message);
        }

        Log::info('📱 Message généré depuis le template', [
            'template_id' => $template->id,
            'template_nom' => $template->nom,
            'phone' => $request->phone,
            'message_length' => $smsInfo['char_count'],
            'sms_count' => $smsInfo['sms_count'],
            'encoding' => $smsInfo['encoding']
        ]);

        // Initialiser le service SMS
        $smsService = new \App\Services\SmsService($ecoleId);
        
        // Vérifier si l'école peut envoyer des SMS
        $balance = $smsService->checkSmsBalance($ecoleId);
        if (!$balance['can_send_sms']) {
            return response()->json([
                'success' => false,
                'message' => 'L\'école n\'a pas assez de crédits SMS ou les notifications sont désactivées',
                'balance' => $balance
            ]);
        }

        // Nettoyer le numéro
        $phone = $request->phone;
        
        // Envoyer le SMS
        $result = $smsService->sendSms($phone, $message, $ecoleId);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'SMS envoyé avec succès',
                'phone' => $phone,
                'sms_restant' => $result['sms_restant'] ?? null,
                'template_used' => $template->nom,
                'message_preview' => $message,
                'sms_info' => $smsInfo
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'envoi du SMS'
            ]);
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation échouée: ' . implode(', ', $e->errors())
        ]);
    } catch (\Exception $e) {
        Log::error('❌ Erreur sendSms: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
}

/**
 * Tronquer un texte à une longueur maximale
 */
private function truncateText($text, $maxLength = 30)
{
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

/**
 * Tronquer un message SMS à une longueur maximale
 */
private function truncateSmsMessage($message, $maxLength = 160)
{
    if (strlen($message) <= $maxLength) {
        return $message;
    }
    
    // Chercher le dernier espace avant la limite
    $truncated = substr($message, 0, $maxLength - 3);
    $lastSpace = strrpos($truncated, ' ');
    
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }
    
    return $truncated . '...';
}

/**
 * Calculer les informations de longueur du SMS
 */
private function getSmsLengthInfo($message)
{
    // Détecter si le message contient des caractères spéciaux
    $hasSpecialChars = preg_match('/[^A-Za-z0-9 .,!?\'"]/', $message);
    
    // GSM 7-bit: 160 caractères, Unicode: 70 caractères
    $maxPerSms = $hasSpecialChars ? 70 : 160;
    $charCount = strlen($message);
    $smsCount = ceil($charCount / $maxPerSms);
    
    return [
        'char_count' => $charCount,
        'sms_count' => $smsCount,
        'max_per_sms' => $maxPerSms,
        'encoding' => $hasSpecialChars ? 'Unicode' : 'GSM-7',
        'has_special_chars' => $hasSpecialChars
    ];
}
}