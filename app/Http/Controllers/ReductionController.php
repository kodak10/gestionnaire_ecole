<?php

namespace App\Http\Controllers;

use App\Models\Reduction;
use App\Models\Tarif;
use App\Models\TypeFrais;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReductionController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
        $this->tableService = $tableService;
    }

    /**
     * Afficher la liste des réductions
     */
    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les noms des tables dynamiques
        $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

        // Requête avec jointures dynamiques
        $reductions = DB::table($reductionsTable . ' as r')
            ->leftJoin($elevesTable . ' as e', 'r.eleve_id', '=', 'e.id')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->leftJoin($tarifsTable . ' as t', 'r.tarif_id', '=', 't.id')
            ->where('r.ecole_id', $ecoleId)
            ->where('r.annee_scolaire_id', $anneeScolaireId)
            ->select(
                'r.*',
                'e.nom as eleve_nom',
                'e.prenom as eleve_prenom',
                'e.matricule',
                'c.nom as classe_nom',
                't.libelle as tarif_libelle',
                't.montant as tarif_montant'
            )
            ->orderBy('r.created_at', 'desc')
            ->paginate(20);

        $typeFrais = TypeFrais::orderBy('nom')->get();

        return view('dashboard.pages.comptabilites.reductions.index', compact('reductions', 'typeFrais'));
    }

    /**
     * Récupérer la liste des élèves par classe (pour le select)
     */
    public function getEleves(Request $request)
    {
        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $search = $request->get('search', '');
            $classeId = $request->get('classe_id');

            // Récupérer les noms des tables dynamiques
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
            $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

            // Requête pour récupérer les élèves
            $query = DB::table($elevesTable . ' as e')
                ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
                ->where('e.ecole_id', $ecoleId)
                ->where('e.annee_scolaire_id', $anneeScolaireId)
                ->where('e.is_active', 1)
                ->select(
                    'e.id as eleve_id',
                    'e.nom',
                    'e.prenom',
                    'e.matricule',
                    'c.nom as classe_nom'
                );

            // Filtre par recherche (nom, prénom ou matricule)
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('e.nom', 'LIKE', '%' . $search . '%')
                      ->orWhere('e.prenom', 'LIKE', '%' . $search . '%')
                      ->orWhere('e.matricule', 'LIKE', '%' . $search . '%');
                });
            }

            // Filtre par classe
            if (!empty($classeId)) {
                $query->where('e.classe_id', $classeId);
            }

            // Récupérer les résultats
            $eleves = $query->orderBy('e.nom', 'asc')
                ->orderBy('e.prenom', 'asc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $eleves->map(function($eleve) {
                    return [
                        'id' => $eleve->eleve_id,
                        'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                        'matricule' => $eleve->matricule,
                        'classe' => $eleve->classe_nom ?? 'Non inscrit'
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR getEleves: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Récupérer les données d'un élève pour les réductions
 */
public function getEleveData(Request $request)
{
    $request->validate([
        'eleve_id' => 'required|exists:eleves,id'
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer les noms des tables dynamiques
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

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

        // Récupérer les réductions existantes pour cet élève
        $reductions = DB::table($reductionsTable)
            ->where('eleve_id', $eleve->id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get()
            ->keyBy('tarif_id');

        // Récupérer les types de frais pour identifier transport et cantine
        $transportTypeIds = TypeFrais::where('nom', 'LIKE', '%Transport%')
            ->orWhere('nom', 'LIKE', '%transport%')
            ->pluck('id')
            ->toArray();
            
        $cantineTypeIds = TypeFrais::where('nom', 'LIKE', '%Cantine%')
            ->orWhere('nom', 'LIKE', '%cantine%')
            ->pluck('id')
            ->toArray();

        // Récupérer TOUS les tarifs pour le niveau de l'élève OU NULL (tous les niveaux)
        $tarifs = DB::table($tarifsTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->get();

        // Récupérer les types de frais pour les noms
        $typeFraisMap = TypeFrais::pluck('nom', 'id')->toArray();

        // Séparer les tarifs par type
        $fraisData = [];                    // Uniquement les frais à afficher (Scolarité, Inscription, etc.)
        $transportTarifsForSelect = [];     // Tous les tarifs de transport (pour le select)
        $cantineTarifsForSelect = [];       // Tous les tarifs de cantine (pour le select)

        foreach ($tarifs as $tarif) {
            $typeNom = $typeFraisMap[$tarif->type_frais_id] ?? 'Inconnu';
            $reduction = $reductions->get($tarif->id);

            $item = [
                'tarif_id' => $tarif->id,
                'type_frais_id' => $tarif->type_frais_id,
                'type_frais_nom' => $typeNom,
                'montant_total' => $tarif->montant,
                'reduction_actuelle' => $reduction->montant ?? 0,
                'reduction_id' => $reduction->id ?? null,
                'libelle' => $tarif->libelle ?? '',
                'obligatoire' => (bool) $tarif->obligatoire,
                'niveau_id' => $tarif->niveau_id
            ];

            // Vérifier si c'est un tarif de Transport ou Cantine
            $isTransport = in_array($tarif->type_frais_id, $transportTypeIds);
            $isCantine = in_array($tarif->type_frais_id, $cantineTypeIds);

            // GESTION TRANSPORT
            if ($isTransport) {
                // Ajouter au select pour que l'utilisateur puisse choisir
                $transportTarifsForSelect[] = $item;
                
                // Afficher dans la liste DES SEULEMENT SI :
                // 1. L'élève a le transport actif ET
                // 2. Le tarif est obligatoire OU c'est le tarif sélectionné
                if ($eleve->transport_active == 1 && 
                    ($tarif->obligatoire == 1 || $tarif->id == $eleve->transport_tarif_id)) {
                    $fraisData[] = $item;
                }
            } 
            // GESTION CANTINE
            elseif ($isCantine) {
                // Ajouter au select pour que l'utilisateur puisse choisir
                $cantineTarifsForSelect[] = $item;
                
                // Afficher dans la liste DES SEULEMENT SI :
                // 1. L'élève a la cantine active ET
                // 2. Le tarif est obligatoire OU c'est le tarif sélectionné
                if ($eleve->cantine_active == 1 && 
                    ($tarif->obligatoire == 1 || $tarif->id == $eleve->cantine_tarif_id)) {
                    $fraisData[] = $item;
                }
            } 
            // AUTRES FRAIS (Scolarité, Inscription, etc.) - toujours affichés
            else {
                $fraisData[] = $item;
            }
        }

        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                'matricule' => $eleve->matricule,
                'classe' => $eleve->classe_nom
            ],
            'frais' => $fraisData,
            'transport_tarifs' => $transportTarifsForSelect,
            'cantine_tarifs' => $cantineTarifsForSelect,
            'selected_transport_tarif' => $eleve->transport_tarif_id ?? null,
            'selected_cantine_tarif' => $eleve->cantine_tarif_id ?? null,
            'transport_active' => $eleve->transport_active ?? 0,
            'cantine_active' => $eleve->cantine_active ?? 0
        ]);

    } catch (\Exception $e) {
        Log::error('ERREUR getEleveData: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Mettre à jour le tarif de transport d'un élève
     */
    public function updateTransportTarif(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'tarif_id' => 'required'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            // Récupérer la table dynamique
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            // Vérifier si c'est la désactivation (tarif_id = 0)
            if ($request->tarif_id == 0) {
                DB::table($elevesTable)
                    ->where('id', $request->eleve_id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->update([
                        'transport_tarif_id' => null,
                        'transport_active' => 0,
                        'transport_start_date' => null,
                        'updated_at' => now()
                    ]);
                
                $message = 'Transport désactivé avec succès';
            } else {
                DB::table($elevesTable)
                    ->where('id', $request->eleve_id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->update([
                        'transport_tarif_id' => $request->tarif_id,
                        'transport_active' => 1,
                        'transport_start_date' => now(),
                        'updated_at' => now()
                    ]);
                
                $message = 'Tarif de transport sélectionné avec succès';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR updateTransportTarif: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le tarif de cantine d'un élève
     */
    public function updateCantineTarif(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'tarif_id' => 'required'
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            // Récupérer la table dynamique
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            // Vérifier si c'est la désactivation (tarif_id = 0)
            if ($request->tarif_id == 0) {
                DB::table($elevesTable)
                    ->where('id', $request->eleve_id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->update([
                        'cantine_tarif_id' => null,
                        'cantine_active' => 0,
                        'cantine_start_date' => null,
                        'updated_at' => now()
                    ]);
                
                $message = 'Cantine désactivée avec succès';
            } else {
                DB::table($elevesTable)
                    ->where('id', $request->eleve_id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->update([
                        'cantine_tarif_id' => $request->tarif_id,
                        'cantine_active' => 1,
                        'cantine_start_date' => now(),
                        'updated_at' => now()
                    ]);
                
                $message = 'Tarif de cantine sélectionné avec succès';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR updateCantineTarif: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Enregistrer une nouvelle réduction
 */
public function store(Request $request)
{
    $request->validate([
        'eleve_id' => 'required|exists:eleves,id',
        'tarif_id' => 'required',
        'montant' => 'required|numeric|min:0',
        'raison' => 'nullable|string|max:255'
    ]);

    try {
        DB::beginTransaction();

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        // Récupérer la table dynamique
        $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

        // Vérifier que le tarif existe dans la table dynamique
        $tarifExists = DB::table($tarifsTable)
            ->where('id', $request->tarif_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->exists();

        if (!$tarifExists) {
            throw new \Exception('Le tarif sélectionné n\'existe pas pour cette année scolaire');
        }

        // Vérifier si la réduction existe déjà
        $existingReduction = DB::table($reductionsTable)
            ->where('eleve_id', $request->eleve_id)
            ->where('tarif_id', $request->tarif_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if ($existingReduction) {
            // Mettre à jour
            DB::table($reductionsTable)
                ->where('id', $existingReduction->id)
                ->update([
                    'montant' => $request->montant,
                    'raison' => $request->raison,
                    'user_id' => auth()->id(),
                    'updated_at' => now()
                ]);
            $message = 'Réduction mise à jour avec succès';
        } else {
            // Créer une nouvelle réduction
            DB::table($reductionsTable)->insert([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'eleve_id' => $request->eleve_id,
                'tarif_id' => $request->tarif_id,
                'user_id' => auth()->id(),
                'montant' => $request->montant,
                'raison' => $request->raison,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $message = 'Réduction ajoutée avec succès';
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ERREUR store reduction: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Supprimer une réduction
     */
    public function destroy($id)
    {
        try {
            $ecoleId = session('current_ecole_id');
            $annee = session('current_annee_scolaire');

            $reductionsTable = $this->tableService->getReductionsTableName($ecoleId, $annee);

            $reduction = DB::table($reductionsTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->first();

            if (!$reduction) {
                throw new \Exception('Réduction non trouvée');
            }

            DB::table($reductionsTable)
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réduction supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR delete reduction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}