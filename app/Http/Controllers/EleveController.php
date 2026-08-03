<?php
// app/Http/Controllers/EleveController.php

namespace App\Http\Controllers;

use App\Exports\ElevesExport;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\TypeFrais;
use App\Models\Tarif;
use App\Services\TableService;

use PDF;
use Illuminate\Support\Facades\Auth;

class EleveController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur|Administrateur')->except(['index', 'export', 'edit', 'update']);
        $this->tableService = $tableService;
    }

    public function index(Request $request)
    {
        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        // Récupérer le nom de la table des élèves dynamique
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        Log::info('📋 CHARGEMENT ELEVES', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'eleves_table' => $elevesTable,
            'classes_table' => $classesTable
        ]);

        // Récupérer les classes pour le filtre
        $classes = DB::table($classesTable . ' as c')
            ->join('niveaux', 'c.niveau_id', '=', 'niveaux.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('niveaux.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select('c.*', 'niveaux.nom as niveau_nom')
            ->get();

        // Requête sur la table des élèves
        $query = DB::table($elevesTable . ' as e')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->where('e.ecole_id', $ecoleId)
            ->where('e.annee_scolaire_id', $anneeScolaireId)
            ->where('e.is_active', 1)
            ->select('e.*', 'c.nom as classe_nom');

        // Filtres
        if ($request->filled('classe_id')) {
            $query->where('e.classe_id', $request->classe_id);
        }

        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('e.nom', 'like', '%'.$request->nom.'%')
                  ->orWhere('e.prenom', 'like', '%'.$request->nom.'%');
            });
        }

        if ($request->filled('sexe')) {
            $query->where('e.sexe', $request->sexe);
        }

        if ($request->filled('cantine')) {
            $query->where('e.cantine_active', $request->cantine == '1');
        }

        if ($request->filled('transport')) {
            $query->where('e.transport_active', $request->transport == '1');
        }

        // Tri
        $sort = $request->get('sort', 'asc');
        if ($request->filled('sort_by')) {
            $query->orderBy('e.' . $request->sort_by, $sort);
        } else {
            $query->orderBy('e.nom', 'asc')->orderBy('e.prenom', 'asc');
        }

        $eleves = $query->paginate(12);

        // Ajouter les infos manquantes (photo, nom_complet, etc.)
        foreach ($eleves as $eleve) {
            $eleve->nom_complet = $eleve->nom . ' ' . $eleve->prenom;
            $eleve->photo_url = $this->getPhotoUrl($eleve);
            $eleve->naissance_formattee = $eleve->naissance ? date('d/m/Y', strtotime($eleve->naissance)) : '-';
        }

        $viewMode = $request->get('view_mode', 'grid');

        Log::info('📊 Élèves trouvés', ['count' => $eleves->count()]);

        return view('dashboard.pages.eleves.index', compact('eleves', 'classes', 'viewMode'));
    }

    private function getPhotoUrl($eleve)
    {
        if (!empty($eleve->photo_path)) {
            return asset('storage/' . $eleve->photo_path);
        }
        // Image par défaut selon le sexe
        if ($eleve->sexe === 'Masculin') {
            return asset('assets/img/profiles/avatar-01.jpg');
        }
        return asset('assets/img/profiles/avatar-02.jpg');
    }

    public function refresh()
    {
        return redirect()->route('eleves.index')->with('success', 'Liste actualisée');
    }

    public function export(Request $request)
{
    if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
        abort(403, 'Vous n\'avez pas la permission d\'exporter la liste des élèves.');
    }

    $format = $request->format;
    $ecoleId = session('current_ecole_id');
    $annee = session('current_annee_scolaire');
    $anneeScolaireId = session('current_annee_scolaire_id');

    // Récupérer les infos de l'école
    $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
    

    $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
    $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

    $query = DB::table($elevesTable . ' as e')
        ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
        ->where('e.ecole_id', $ecoleId)
        ->where('e.annee_scolaire_id', $anneeScolaireId)
        ->where('e.is_active', 1)
        ->select('e.*', 'c.nom as classe_nom');

    // Appliquer les filtres
    if ($request->filled('classe_id')) {
        $query->where('e.classe_id', $request->classe_id);
    }

    if ($request->filled('nom')) {
        $query->where(function($q) use ($request) {
            $q->where('e.nom', 'like', '%'.$request->nom.'%')
              ->orWhere('e.prenom', 'like', '%'.$request->nom.'%');
        });
    }

    if ($request->filled('sexe')) {
        $query->where('e.sexe', $request->sexe);
    }

    if ($request->filled('cantine')) {
        $query->where('e.cantine_active', $request->cantine == '1');
    }

    if ($request->filled('transport')) {
        $query->where('e.transport_active', $request->transport == '1');
    }

    $query->orderBy('e.nom', 'asc')->orderBy('e.prenom', 'asc');
    $eleves = $query->get();

    $filters = [
        'classe' => $request->classe_id ? DB::table($classesTable)->where('id', $request->classe_id)->value('nom') : 'Toutes',
        'nom'    => $request->nom ?: 'Tous',
        'sexe'   => $request->sexe ?: 'Tous',
        'cantine' => $request->filled('cantine') ? ($request->cantine == '1' ? 'Oui' : 'Non') : null,
        'transport' => $request->filled('transport') ? ($request->transport == '1' ? 'Oui' : 'Non') : null,
    ];

    if ($format === 'excel') {
        return Excel::download(new ElevesExport($eleves, $filters, $ecole), 'liste_eleves_' . date('Y-m-d') . '.xlsx');
    }

    if ($format === 'pdf') {
        $data = [
            'eleves'  => $eleves,
            'title'   => 'Liste des Élèves',
            'date'    => now()->locale('fr')->translatedFormat('d F Y'),
            'filters' => $filters,
            'ecole'   => $ecole
        ];
        
        $pdf = PDF::loadView('dashboard.documents.liste', $data)
                ->setPaper('a4', 'landscape');

        return $pdf->stream('liste_eleves_' . date('Y-m-d') . '.pdf');
    }

    return redirect()->back()->with('error', 'Format non supporté');
}

    public function create(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $classes = DB::table($classesTable . ' as c')
            ->join('niveaux', 'c.niveau_id', '=', 'niveaux.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('niveaux.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select('c.*', 'niveaux.nom as niveau_nom')
            ->get();

        // Récupérer les types de frais
        $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
        $scolarite = TypeFrais::where('nom', 'Scolarité')->first();
        $transports = TypeFrais::where('nom', 'Transport')->first();
        $cantines = TypeFrais::where('nom', 'Cantine')->first();
        
        // Récupérer TOUS les tarifs
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        $tarifs = DB::table($tarifsTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get()
            ->groupBy('type_frais_id');

        return view('dashboard.pages.eleves.create', compact(
            'classes',
            'fraisInscription',
            'scolarite',
            'transports',
            'cantines',
            'tarifs'
        ));
    }

    public function store(Request $request)
{
    Log::info('📝 CRÉATION ÉLÈVE - Début', ['data' => $request->all()]);

    // ============================================
    // 1. VALIDATION DES DONNÉES
    // ============================================
    $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'num_extrait' => 'nullable|string|max:255',
        'naissance' => 'required|date',
        'lieu_naissance' => 'nullable|string|max:255',
        'sexe' => 'required|in:Masculin,Féminin',
        'nationalite' => 'nullable|string|max:255',
        'code_national' => 'nullable|string|max:255',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        'infos_medicales' => 'nullable|string',
        'pere_nom' => 'required|string|max:255',
        'pere_contact' => 'required|string|max:20',
        'pere_contact02' => 'nullable|string|max:20',
        'mere_nom' => 'nullable|string|max:255',
        'mere_contact' => 'nullable|string|max:20',
        'mere_contact02' => 'nullable|string|max:20',
        'parent_adresse' => 'nullable|string|max:255',
        'classe_id' => 'required|exists:classes,id',
        'transport_active' => 'nullable|boolean',
        'cantine_active' => 'nullable|boolean',
        'parent_nom' => 'nullable|string|max:255',
        'parent_telephone' => 'nullable|string|max:20',
        'parent_telephone02' => 'nullable|string|max:20',
        'mode_paiement' => 'nullable|string|in:especes,cheque,virement,mobile_money,carte',
        'frais_inscription' => 'nullable|numeric|min:0',
        'frais_scolarite' => 'nullable|numeric|min:0',
        'frais_transport' => 'nullable|numeric|min:0',
        'frais_cantine' => 'nullable|numeric|min:0',
        'reference' => 'nullable|string|max:255',
    ]);

    // ============================================
    // 2. RÉCUPÉRATION DES INFORMATIONS DE SESSION
    // ============================================
    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');
    $annee = session('current_annee_scolaire');

    // Vérification des données de session
    if (!$ecoleId || !$anneeScolaireId || !$annee) {
        Log::error('❌ Données de session manquantes', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'annee' => $annee
        ]);
        return redirect()->back()
            ->with('error', 'Données de session manquantes. Veuillez vous reconnecter.')
            ->withInput();
    }

    // ============================================
    // 3. GÉNÉRATION DU MATRICULE
    // ============================================
    $matricule = $this->genererMatriculeEleve($ecoleId);

    // ============================================
    // 4. GESTION DE LA PHOTO
    // ============================================
    $photoPath = null;
    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
        try {
            $photoPath = $request->file('photo')->store('eleves_photos', 'public');
            Log::info('📸 Photo téléchargée', ['path' => $photoPath]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors du téléchargement de la photo', [
                'error' => $e->getMessage()
            ]);
            // Continuer sans photo
        }
    }

    // ============================================
    // 5. OPTIONS TRANSPORT & CANTINE
    // ============================================
    $transportActive = $request->has('transport_active') && $request->input('transport_active') !== 'off';
    $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') !== 'off';

    // ============================================
    // 6. NOMS DES TABLES DYNAMIQUES
    // ============================================
    try {
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        
        Log::info('📋 Tables dynamiques', [
            'eleves' => $elevesTable,
            'classes' => $classesTable,
            'tarifs' => $tarifsTable
        ]);
    } catch (\Exception $e) {
        Log::error('❌ Erreur lors de la récupération des noms de tables', [
            'error' => $e->getMessage()
        ]);
        return redirect()->back()
            ->with('error', 'Erreur de configuration: ' . $e->getMessage())
            ->withInput();
    }

    // ============================================
    // 7. TRANSACTION DB
    // ============================================
    DB::beginTransaction();

    try {
        // ============================================
        // 7.1 CRÉATION DE L'ÉLÈVE (SANS INSCRIPTION)
        // ============================================
        $eleveId = DB::table($elevesTable)->insertGetId([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'classe_id' => $request->classe_id,
            'matricule' => $matricule,
            'nom' => strtoupper($request->nom),
            'prenom' => strtoupper($request->prenom),
            'code_national' => strtoupper($request->code_national ?? ''),
            'sexe' => $request->sexe,
            'naissance' => $request->naissance,
            'lieu_naissance' => strtoupper($request->lieu_naissance ?? ''),
            'nationalite' => $request->nationalite ?? 'Ivoirienne',
            'num_extrait' => strtoupper($request->num_extrait ?? ''),
            'photo_path' => $photoPath,
            'infos_medicales' => $request->infos_medicales,
            'parent_nom' => strtoupper($request->parent_nom ?? $request->pere_nom ?? ''),
            'parent_telephone' => $request->parent_telephone ?? $request->pere_contact ?? '',
            'parent_telephone02' => $request->parent_telephone02 ?? $request->pere_contact02 ?? '',
            'pere_nom' => strtoupper($request->pere_nom ?? ''),
            'pere_contact' => $request->pere_contact ?? '',
            'pere_contact02' => $request->pere_contact02 ?? '',
            'mere_nom' => strtoupper($request->mere_nom ?? ''),
            'mere_contact' => $request->mere_contact ?? '',
            'mere_contact02' => $request->mere_contact02 ?? '',
            'parent_adresse' => $request->parent_adresse ?? '',
            'transport_active' => $transportActive,
            'cantine_active' => $cantineActive,
            'statut' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('✅ Élève créé', [
            'eleve_id' => $eleveId,
            'matricule' => $matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom
        ]);

        // ============================================
        // 7.2 RÉCUPÉRATION DU NIVEAU DE LA CLASSE
        // ============================================
        $classe = DB::table($classesTable)
            ->where('id', $request->classe_id)
            ->first();

        if (!$classe) {
            throw new \Exception('Classe non trouvée avec l\'ID: ' . $request->classe_id);
        }

        $niveauId = $classe->niveau_id ?? null;

        Log::info('📚 Classe et niveau', [
            'classe_id' => $request->classe_id,
            'classe_nom' => $classe->nom ?? 'N/A',
            'niveau_id' => $niveauId
        ]);

        // ============================================
        // 7.3 GESTION DU PAIEMENT
        // ============================================
        $fraisInscription = floatval($request->frais_inscription ?? 0);
        $fraisScolarite = floatval($request->frais_scolarite ?? 0);
        $fraisTransport = floatval($request->frais_transport ?? 0);
        $fraisCantine = floatval($request->frais_cantine ?? 0);
        
        $totalPaiement = $fraisInscription + $fraisScolarite + $fraisTransport + $fraisCantine;

        $paiementId = null;

        if ($totalPaiement > 0) {
            $modePaiement = $request->mode_paiement ?? 'especes';

            Log::info('💰 Traitement du paiement', [
                'total' => $totalPaiement,
                'mode' => $modePaiement,
                'details' => [
                    'inscription' => $fraisInscription,
                    'scolarite' => $fraisScolarite,
                    'transport' => $fraisTransport,
                    'cantine' => $fraisCantine
                ]
            ]);

            // CRÉATION DU PAIEMENT D'ABORD
            $paiementId = $this->enregistrerPaiement(
                $eleveId,
                $totalPaiement,
                $modePaiement,
                $request->reference
            );

            // RÉCUPÉRATION DES TYPES DE FRAIS
            $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
            $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
            $typeTransport = TypeFrais::where('nom', "Transport")->first();
            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            // CRÉATION DES DÉTAILS DE PAIEMENT
            if ($fraisInscription > 0 && $typeInscription) {
                $tarif = $this->getTarif($typeInscription->id, $ecoleId, $anneeScolaireId, $niveauId);
                if ($tarif) {
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, $tarif->id, $fraisInscription);
                    Log::info('✅ Détail inscription enregistré', ['tarif_id' => $tarif->id]);
                } else {
                    Log::warning('⚠️ Aucun tarif trouvé pour l\'inscription', [
                        'type_frais_id' => $typeInscription->id,
                        'niveau_id' => $niveauId
                    ]);
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, null, $fraisInscription);
                }
            }

            if ($fraisScolarite > 0 && $typeScolarite) {
                $tarif = $this->getTarif($typeScolarite->id, $ecoleId, $anneeScolaireId, $niveauId);
                if ($tarif) {
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, $tarif->id, $fraisScolarite);
                    Log::info('✅ Détail scolarité enregistré', ['tarif_id' => $tarif->id]);
                } else {
                    Log::warning('⚠️ Aucun tarif trouvé pour la scolarité', [
                        'type_frais_id' => $typeScolarite->id,
                        'niveau_id' => $niveauId
                    ]);
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, null, $fraisScolarite);
                }
            }

            if ($fraisTransport > 0 && $typeTransport) {
                $tarif = $this->getTarif($typeTransport->id, $ecoleId, $anneeScolaireId, $niveauId);
                if ($tarif) {
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, $tarif->id, $fraisTransport);
                    Log::info('✅ Détail transport enregistré', ['tarif_id' => $tarif->id]);
                } else {
                    Log::warning('⚠️ Aucun tarif trouvé pour le transport', [
                        'type_frais_id' => $typeTransport->id,
                        'niveau_id' => $niveauId
                    ]);
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, null, $fraisTransport);
                }
            }

            if ($fraisCantine > 0 && $typeCantine) {
                $tarif = $this->getTarif($typeCantine->id, $ecoleId, $anneeScolaireId, $niveauId);
                if ($tarif) {
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, $tarif->id, $fraisCantine);
                    Log::info('✅ Détail cantine enregistré', ['tarif_id' => $tarif->id]);
                } else {
                    Log::warning('⚠️ Aucun tarif trouvé pour la cantine', [
                        'type_frais_id' => $typeCantine->id,
                        'niveau_id' => $niveauId
                    ]);
                    $this->enregistrerDetailPaiement($paiementId, $eleveId, null, $fraisCantine);
                }
            }

            Log::info('💰 Paiement enregistré avec succès', [
                'eleve_id' => $eleveId,
                'paiement_id' => $paiementId,
                'total' => $totalPaiement
            ]);
        } else {
            Log::info('ℹ️ Aucun paiement à enregistrer', [
                'eleve_id' => $eleveId
            ]);
        }

        // ============================================
        // 8. COMMIT DE LA TRANSACTION
        // ============================================
        DB::commit();

        // ============================================
        // 9. MESSAGE DE SUCCÈS
        // ============================================
        $message = '🎉 Élève inscrit avec succès! Matricule: ' . $matricule;
        if ($totalPaiement > 0) {
            $message .= ' Paiement de ' . number_format($totalPaiement, 0, ',', ' ') . ' FCFA enregistré.';
        }

        Log::info('✅ Inscription terminée avec succès', [
            'eleve_id' => $eleveId,
            'matricule' => $matricule,
            'total_paiement' => $totalPaiement
        ]);

        return redirect()->route('eleves.index')->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ ERREUR INSCRIPTION ÉLÈVE', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);

        return redirect()->back()
            ->with('error', 'Erreur lors de l\'inscription: ' . $e->getMessage())
            ->withInput();
    }
}

/**
 * Récupérer un tarif par type de frais
 */
private function getTarif($typeFraisId, $ecoleId, $anneeScolaireId, $niveauId)
{
    // Récupérer l'année depuis la session
    $annee = session('current_annee_scolaire');
    
    // Vérifier que l'année existe
    if (!$annee) {
        Log::warning('⚠️ Aucune année scolaire trouvée dans la session');
        return null;
    }
    
    try {
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        
        // Vérifier que la table existe
        if (!$this->tableService->tableExistsExact($tarifsTable)) {
            Log::warning('⚠️ Table des tarifs non trouvée', ['table' => $tarifsTable]);
            return null;
        }
        
        return DB::table($tarifsTable)
            ->where('type_frais_id', $typeFraisId)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();
            
    } catch (\Exception $e) {
        Log::error('❌ Erreur lors de la récupération du tarif', [
            'error' => $e->getMessage(),
            'type_frais_id' => $typeFraisId,
            'niveau_id' => $niveauId
        ]);
        return null;
    }
}

/**
 * Enregistrer un paiement (SANS INSCRIPTION_ID)
 */
private function enregistrerPaiement($eleveId, $montant, $modePaiement, $reference = null)
{
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $annee = session('current_annee_scolaire');

    try {
        $paiementsTable = $this->tableService->getPaiementsTableName($ecoleId, $annee);
        
        // Vérifier que la table existe
        if (!$this->tableService->tableExistsExact($paiementsTable)) {
            Log::error('❌ Table des paiements non trouvée', ['table' => $paiementsTable]);
            throw new \Exception('La table des paiements n\'existe pas pour cette année scolaire.');
        }

        $paiementId = DB::table($paiementsTable)->insertGetId([
            'eleve_id' => $eleveId,
            'montant' => $montant,
            'mode_paiement' => $modePaiement,
            'reference' => $reference,
            'user_id' => auth()->id(),
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('✅ Paiement créé', [
            'paiement_id' => $paiementId,
            'eleve_id' => $eleveId,
            'montant' => $montant
        ]);

        return $paiementId;
        
    } catch (\Exception $e) {
        Log::error('❌ Erreur lors de l\'enregistrement du paiement', [
            'error' => $e->getMessage(),
            'eleve_id' => $eleveId,
            'montant' => $montant
        ]);
        throw $e;
    }
}

/**
 * Enregistrer un détail de paiement (SANS INSCRIPTION_ID)
 */
private function enregistrerDetailPaiement($paiementId, $eleveId, $tarifId, $montant, $moisId = null)
{
    $ecoleId = session('current_ecole_id');
    $annee = session('current_annee_scolaire');

    try {
        $paiementDetailsTable = $this->tableService->getPaiementDetailsTableName($ecoleId, $annee);
        
        // Vérifier que la table existe
        if (!$this->tableService->tableExistsExact($paiementDetailsTable)) {
            Log::error('❌ Table des détails de paiement non trouvée', ['table' => $paiementDetailsTable]);
            throw new \Exception('La table des détails de paiement n\'existe pas pour cette année scolaire.');
        }

        // Construction des données avec uniquement les colonnes qui existent
        $data = [
            'paiement_id' => $paiementId,
            'eleve_id' => $eleveId,
            'tarif_id' => $tarifId,
            'montant' => $montant,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Ajouter le mois si présent (si la colonne existe)
        if ($moisId) {
            $data['mois_id'] = $moisId;
        }

        $detailId = DB::table($paiementDetailsTable)->insertGetId($data);

        Log::info('✅ Détail paiement créé', [
            'detail_id' => $detailId,
            'paiement_id' => $paiementId,
            'montant' => $montant
        ]);

        return $detailId;
        
    } catch (\Exception $e) {
        Log::error('❌ Erreur lors de l\'enregistrement du détail de paiement', [
            'error' => $e->getMessage(),
            'paiement_id' => $paiementId,
            'montant' => $montant
        ]);
        throw $e;
    }
}



private function genererMatriculeEleve(int $ecoleId): string
{
    try {
        $ecole = Ecole::findOrFail($ecoleId);
        $alias = strtoupper($ecole->sigle_ecole ?? 'ELEV');
        
        // Récupérer l'année scolaire en cours
        $annee = session('current_annee_scolaire');
        
        if (!$annee) {
            Log::warning('⚠️ Aucune année scolaire trouvée pour la génération du matricule');
            $annee = date('Y') . '-' . (date('Y') + 1);
        }
        
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        
        // Vérifier que la table existe
        if (!$this->tableService->tableExistsExact($elevesTable)) {
            Log::warning('⚠️ Table des élèves non trouvée pour la génération du matricule', ['table' => $elevesTable]);
            // Retourner un matricule par défaut
            return $alias . '-' . date('Y') . '00001';
        }

        do {
            // Récupérer le dernier matricule dans la table dynamique
            $dernierEleve = DB::table($elevesTable)
                ->where('ecole_id', $ecoleId)
                ->where('matricule', 'like', $alias . '-%')
                ->orderByDesc('id')
                ->first();

            $dernierNumero = 0;
            if ($dernierEleve && preg_match('/-(\d+)$/', $dernierEleve->matricule, $matches)) {
                $dernierNumero = intval($matches[1]);
            }

            $nouveauNumero = $dernierNumero + 1;
            $numeroFormate = str_pad($nouveauNumero, 5, '0', STR_PAD_LEFT);
            $matricule = $alias . '-' . $numeroFormate;

        } while (DB::table($elevesTable)->where('matricule', $matricule)->exists());

        Log::info('✅ Matricule généré', [
            'eleve_id' => $ecoleId,
            'matricule' => $matricule
        ]);

        return $matricule;
        
    } catch (\Exception $e) {
        Log::error('❌ Erreur lors de la génération du matricule', [
            'error' => $e->getMessage(),
            'ecole_id' => $ecoleId
        ]);
        // Retourner un matricule de secours
        return 'ELEV-' . date('Y') . rand(10000, 99999);
    }
}

    public function show($id)
    {
        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');
        
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $eleve = DB::table($elevesTable . ' as e')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->where('e.id', $id)
            ->where('e.ecole_id', $ecoleId)
            ->select('e.*', 'c.nom as classe_nom')
            ->first();

        if (!$eleve) {
            return redirect()->route('eleves.index')->with('error', 'Élève non trouvé.');
        }

        return view('dashboard.pages.eleves.show', compact('eleve'));
    }

    public function edit($id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
            abort(403, 'Vous n\'avez pas la permission d\'éditer cet élève.');
        }

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $eleve = DB::table($elevesTable . ' as e')
            ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
            ->where('e.id', $id)
            ->where('e.ecole_id', $ecoleId)
            ->where('e.annee_scolaire_id', $anneeScolaireId)
            ->select('e.*', 'c.nom as classe_nom')
            ->first();

        if (!$eleve) {
            return redirect()->route('eleves.index')->with('error', 'Élève non trouvé.');
        }

        $classes = DB::table($classesTable . ' as c')
            ->join('niveaux', 'c.niveau_id', '=', 'niveaux.id')
            ->where('c.ecole_id', $ecoleId)
            ->where('c.annee_scolaire_id', $anneeScolaireId)
            ->orderBy('niveaux.ordre', 'asc')
            ->orderBy('c.nom', 'asc')
            ->select('c.*', 'niveaux.nom as niveau_nom')
            ->get();

        // Récupérer les types de frais
        $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
        $scolarite = TypeFrais::where('nom', 'Scolarité')->first();

        return view('dashboard.pages.eleves.edit', compact('eleve', 'classes', 'fraisInscription', 'scolarite'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
            abort(403, 'Vous n\'avez pas la permission d\'éditer cet élève.');
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'num_extrait' => 'nullable|string|max:255',
            'naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'nationalite' => 'nullable|string|max:255',
            'code_national' => 'nullable|string|max:255',
            'photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'infos_medicales' => 'nullable|string',
            'pere_nom' => 'nullable|string|max:255',
            'pere_contact' => 'nullable|string|max:20',
            'pere_contact02' => 'nullable|string|max:20',
            'mere_nom' => 'nullable|string|max:255',
            'mere_contact' => 'nullable|string|max:20',
            'mere_contact02' => 'nullable|string|max:20',
            'parent_adresse' => 'nullable|string|max:255',
            'classe_id' => 'required|exists:classes,id',
            'parent_nom' => 'nullable|string|max:255',
            'parent_telephone' => 'nullable|string|max:20',
            'parent_telephone02' => 'nullable|string|max:20',
        ]);

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

        try {
            $transportActive = $request->has('transport_active') && $request->input('transport_active') == '1';
            $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') == '1';

            $updateData = [
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'num_extrait' => $request->num_extrait,
                'sexe' => $request->sexe,
                'naissance' => $request->naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'nationalite' => $request->nationalite ?? 'Ivoirienne',
                'infos_medicales' => $request->infos_medicales,
                'code_national' => $request->code_national,
                'pere_nom' => $request->pere_nom,
                'pere_contact' => $request->pere_contact,
                'pere_contact02' => $request->pere_contact02,
                'mere_nom' => $request->mere_nom,
                'mere_contact' => $request->mere_contact,
                'mere_contact02' => $request->mere_contact02,
                'parent_adresse' => $request->parent_adresse,
                'classe_id' => $request->classe_id,
                'parent_nom' => $request->parent_nom ?? $request->pere_nom,
                'parent_telephone' => $request->parent_telephone ?? $request->pere_contact,
                'parent_telephone02' => $request->parent_telephone02 ?? $request->pere_contact02,
                'transport_active' => $transportActive,
                'cantine_active' => $cantineActive,
                'updated_at' => now(),
            ];

            // Gestion de la photo
            if ($request->hasFile('photo_path') && $request->file('photo_path')->isValid()) {
                $path = $request->file('photo_path')->store('eleves_photos', 'public');
                $updateData['photo_path'] = $path;
            }

            DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->update($updateData);

            Log::info('✅ Élève modifié', ['id' => $id]);

            return redirect()->route('eleves.index')->with('success', 'Élève modifié avec succès!');

        } catch (\Exception $e) {
            Log::error('❌ Erreur modification élève', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur'])) {
            abort(403, 'Vous n\'avez pas la permission de supprimer un élève.');
        }

        $ecoleId = session('current_ecole_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

        try {
            DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->delete();

            Log::info('🗑️ Élève supprimé', ['id' => $id]);

            return redirect()->route('eleves.index')->with('success', 'Élève supprimé avec succès');

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression élève', ['error' => $e->getMessage()]);
            return redirect()->route('eleves.index')
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }



    /**
     * Récupérer les élèves d'une classe spécifique (API)
     */
    public function getByClasse(Request $request)
    {
        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');
            $annee = session('current_annee_scolaire');

            $classeId = $request->query('classe_id');

            if (!$classeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paramètre classe_id est requis'
                ], 400);
            }

            Log::info('📋 Récupération des élèves par classe', [
                'classe_id' => $classeId,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId
            ]);

            // Récupérer les noms des tables dynamiques
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
            $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

            // Récupérer les élèves de la classe
            $eleves = DB::table($elevesTable . ' as e')
                ->leftJoin($classesTable . ' as c', 'e.classe_id', '=', 'c.id')
                ->where('e.ecole_id', $ecoleId)
                ->where('e.annee_scolaire_id', $anneeScolaireId)
                ->where('e.classe_id', $classeId)
                ->where('e.is_active', 1)
                ->select(
                    'e.id',
                    'e.nom',
                    'e.prenom',
                    'e.matricule',
                    'e.sexe',
                    'e.naissance',
                    'e.cantine_active',
                    'e.transport_active',
                    'c.nom as classe_nom'
                )
                ->orderBy('e.nom', 'asc')
                ->orderBy('e.prenom', 'asc')
                ->get();

            // Formater les données
            $eleves->map(function($eleve) {
                $eleve->nom_complet = $eleve->nom . ' ' . $eleve->prenom;
                $eleve->naissance_formattee = $eleve->naissance ? date('d/m/Y', strtotime($eleve->naissance)) : '-';
                return $eleve;
            });

            Log::info('📊 Élèves trouvés pour la classe', [
                'classe_id' => $classeId,
                'count' => $eleves->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $eleves
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la récupération des élèves par classe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


}