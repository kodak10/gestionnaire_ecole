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

public function getRelanceData(Request $request)
{
    Log::info('=== getRelanceData START ===', [
        'classe_id' => $request->classe_id,
        'date_reference' => $request->date_reference,
        'tarif_id' => $request->tarif_id,
        'montant_min' => $request->montant_min,
        'montant_max' => $request->montant_max,
        'all_request' => $request->all()
    ]);

    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'tarif_id' => 'nullable|numeric',
        'montant_min' => 'nullable|numeric|min:0',
        'montant_max' => 'nullable|numeric|min:0'
    ]);

    if ($request->montant_min && $request->montant_max && 
        $request->montant_min > $request->montant_max) {
        return response()->json([
            'success' => false,
            'message' => 'Le montant minimum ne peut pas être supérieur au montant maximum'
        ]);
    }

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

        // Récupérer le tarif spécifique
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
                $typeFrais = TypeFrais::find($tarif->type_frais_id);
                $typeFraisNom = $typeFrais ? $typeFrais->nom : '';
            }
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
                'classe' => 'Classe',
                'mois_reference' => $moisReference->nom,
                'tarif_libelle' => $tarif->libelle ?? 'Tous les tarifs',
                'message' => 'Aucun élève trouvé dans cette classe'
            ]);
        }

        // Récupérer tous les mois jusqu'au mois de référence
        $moisScolaires = MoisScolaire::where('numero', '<=', $moisReference->numero)
            ->orderBy('numero')
            ->get();

        $result = [];

        foreach ($eleves as $eleve) {
            // Vérifier si le tarif est pour le niveau de l'élève
            if ($tarif && $tarif->niveau_id && $tarif->niveau_id != $eleve->niveau_id) {
                continue;
            }

            // Vérifier si le service est actif
            if ($typeFraisNom == 'Cantine' && !$eleve->cantine_active) {
                continue;
            }
            if ($typeFraisNom == 'Transport' && !$eleve->transport_active) {
                continue;
            }

            // Récupérer les tarifs mensuels
            $tarifsMensuels = DB::table($tarifsMensuelsTable)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('tarif_id', $tarif->id ?? 0)
                ->where(function($q) use ($eleve) {
                    $q->where('niveau_id', $eleve->niveau_id)
                      ->orWhereNull('niveau_id');
                })
                ->get()
                ->keyBy('mois_id');

            if ($tarifsMensuels->isEmpty() && $tarif) {
                continue;
            }

            if (!$tarif) {
                continue;
            }

            // === DÉTERMINER LE MOIS DE DÉBUT ===
            $moisDebutNumero = null;
            $jourDebut = null;

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
                } else {
                    $moisDebutNumero = (int) Carbon::parse($eleve->created_at)->format('n');
                    $jourDebut = (int) Carbon::parse($eleve->created_at)->format('j');
                }
            } else {
                $moisDebutNumero = 8;
                $jourDebut = 1;
            }

            $moisDebut = MoisScolaire::where('numero', $moisDebutNumero)->first();

            // Calcul du montant pour le mois de référence
            $montantMensuel = 0;
            $tarifMoisRef = $tarifsMensuels->get($moisReference->id);
            if ($tarifMoisRef) {
                $montantMensuel = (float) $tarifMoisRef->montant;

                if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                    if ($moisReference->numero == $moisDebutNumero && $jourDebut > 15) {
                        $montantMensuel = $montantMensuel / 2;
                    }
                    if ($moisReference->numero < $moisDebutNumero) {
                        $montantMensuel = 0;
                    }
                }
            }

            if ($montantMensuel <= 0) {
                continue;
            }

            // Calcul du cumul attendu
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

            // Réduction pour la Scolarité
            $reduction = 0;
            if ($typeFraisNom == 'Scolarité') {
                $reduction = (float) DB::table($reductionsTable)
                    ->where('eleve_id', $eleve->id)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where(function($q) use ($tarif) {
                        $q->whereNull('tarif_id')
                            ->orWhere('tarif_id', $tarif->id ?? 0);
                    })
                    ->sum('montant');
                
                if ($reduction > 0 && $cumulAttendu > 0) {
                    $cumulAttendu = max(0, $cumulAttendu - $reduction);
                }
            }

            // Total payé
            $totalPaye = (float) DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarif->id ?? 0)
                ->sum('montant');

            // Payé avant le mois de référence
            $payeAvant = (float) DB::table($paiementDetailsTable)
                ->where('eleve_id', $eleve->id)
                ->where('tarif_id', $tarif->id ?? 0)
                ->where('created_at', '<', $moisReference->created_at ?? Carbon::now())
                ->sum('montant');

            // Payé pour le mois de référence
            $payeMois = $totalPaye - $payeAvant;

            // Calcul des restes
            $resteMois = max(0, $montantMensuel - $payeMois);
            $resteCumul = max(0, $cumulAttendu - $totalPaye);
            $statut = $resteCumul <= 0 ? 'À jour' : 'En retard';

            // Filtrer par montant
            if ($request->montant_min || $request->montant_max) {
                $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                
                if ($resteCumul < $montantMin || $resteCumul > $montantMax) {
                    continue;
                }
            }

            $typeTarif = $typeFraisNom . ' - ' . ($tarif->libelle ?? '');

            $dateDebut = null;
            if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                $startDate = null;
                if ($typeFraisNom == 'Cantine' && $eleve->cantine_start_date) {
                    $startDate = Carbon::parse($eleve->cantine_start_date);
                } elseif ($typeFraisNom == 'Transport' && $eleve->transport_start_date) {
                    $startDate = Carbon::parse($eleve->transport_start_date);
                }
                if ($startDate) {
                    $dateDebut = $startDate->format('d/m/Y');
                } else {
                    $dateDebut = Carbon::parse($eleve->created_at)->format('d/m/Y');
                }
            } else {
                $dateDebut = 'Début année';
            }

            $result[] = [
                'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                'classe' => $eleve->classe_nom,
                'niveau' => $eleve->niveau_nom,
                'type_tarif' => $typeTarif,
                'date_debut' => $dateDebut,
                'mois_debut' => $moisDebut ? $moisDebut->nom : 'N/A',
                'montant_mois' => $montantMensuel,
                'cumul_attendu' => $cumulAttendu,
                'total_paye' => $totalPaye,
                'paye_mois' => $payeMois,  // Ajouté pour déboguer
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

        return response()->json([
            'success' => true,
            'data' => $result,
            'classe' => $eleves->first()->classe_nom ?? 'Classe',
            'mois_reference' => $moisReference->nom,
            'tarif_libelle' => $tarif->libelle ?? 'Tous les tarifs',
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
        // ... à implémenter
    }

    public function export(Request $request)
    {
        // ... à implémenter
    }

    public function sendSms(Request $request)
    {
        // ... à implémenter
    }
}