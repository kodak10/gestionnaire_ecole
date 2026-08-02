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
            'mode_paiement' => 'nullable|string',
            'frais_inscription' => 'nullable|numeric|min:0',
            'frais_scolarite' => 'nullable|numeric|min:0',
            'frais_transport' => 'nullable|numeric|min:0',
            'frais_cantine' => 'nullable|numeric|min:0',
            'date_paiement' => 'nullable|date',
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $matricule = $this->genererMatriculeEleve($ecoleId);

        $photoPath = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $photoPath = $request->file('photo')->store('eleves_photos', 'public');
        }

        $transportActive = $request->has('transport_active') && $request->input('transport_active') !== 'off';
        $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') !== 'off';

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

        DB::beginTransaction();

        try {
            // 1. Création de l'élève
            $id = DB::table($elevesTable)->insertGetId([
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'classe_id' => $request->classe_id,
                'matricule' => $matricule,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'code_national' => $request->code_national,
                'sexe' => $request->sexe,
                'naissance' => $request->naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'nationalite' => $request->nationalite ?? 'Ivoirienne',
                'num_extrait' => $request->num_extrait,
                'photo_path' => $photoPath,
                'infos_medicales' => $request->infos_medicales,
                'parent_nom' => $request->parent_nom ?? $request->pere_nom,
                'parent_telephone' => $request->parent_telephone ?? $request->pere_contact,
                'parent_telephone02' => $request->parent_telephone02 ?? $request->pere_contact02,
                'pere_nom' => $request->pere_nom,
                'pere_contact' => $request->pere_contact,
                'pere_contact02' => $request->pere_contact02,
                'mere_nom' => $request->mere_nom,
                'mere_contact' => $request->mere_contact,
                'mere_contact02' => $request->mere_contact02,
                'parent_adresse' => $request->parent_adresse,
                'transport_active' => $transportActive,
                'cantine_active' => $cantineActive,
                'statut' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Gestion du paiement (si nécessaire)
            $totalPaiement = floatval($request->frais_inscription ?? 0) + 
                           floatval($request->frais_scolarite ?? 0) + 
                           floatval($request->frais_transport ?? 0) + 
                           floatval($request->frais_cantine ?? 0);

            if ($totalPaiement > 0) {
                // Récupérer les types de frais
                $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
                $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
                $typeTransport = TypeFrais::where('nom', "Transport")->first();
                $typeCantine = TypeFrais::where('nom', "Cantine")->first();

                // Créer le paiement
                // ... logique de paiement similaire à l'ancienne
            }

            DB::commit();

            Log::info('✅ Élève créé', [
                'id' => $id,
                'matricule' => $matricule,
                'nom' => $request->nom,
                'prenom' => $request->prenom
            ]);

            return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur inscription élève', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de l\'inscription: ' . $e->getMessage());
        }
    }

    private function genererMatriculeEleve(int $ecoleId): string
    {
        $ecole = Ecole::findOrFail($ecoleId);
        $alias = strtoupper($ecole->sigle_ecole);

        do {
            $dernierEleve = DB::table('eleves')
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

        } while (DB::table('eleves')->where('matricule', $matricule)->exists());

        return $matricule;
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
}