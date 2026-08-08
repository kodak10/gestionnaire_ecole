<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Document;
use App\Models\Eleve;
use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Services\TableService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // ✅ Import explicite du Facade DomPDF
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DocumentController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->tableService = $tableService;
        $this->middleware(['role:SuperAdministrateur|Administrateur'])->only([
            'templatesIndex', 'templatesCreate', 'templatesStore',
            'templatesEdit', 'templatesUpdate', 'templatesDestroy'
        ]);
    }

    // ==========================================
    // GESTION DES TEMPLATES DE DOCUMENTS
    // ==========================================

    public function templatesIndex()
    {
        $templates = Document::orderBy('type')->get();
        $types = $this->getDocumentTypes();

        return view('dashboard.pages.parametrage.documents.templates.index', compact('templates', 'types'));
    }

    public function templatesCreate(Request $request)
    {
        $type = $request->get('type', 'fiche_inscription');
        $document = Document::where('type', $type)->first();
        $variables = $this->getVariablesByType($type);

        return view('dashboard.pages.parametrage.documents.templates.create', compact('type', 'document', 'variables'));
    }

    public function templatesStore(Request $request)
    {
        $request->validate([
            'type' => 'required|string|unique:documents,type',
            'content' => 'required|string',
        ]);

        try {
            $document = Document::create([
                'type' => $request->type,
                'content' => $request->content,
            ]);

            Log::info('Modèle de document créé', [
                'type' => $document->type,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('documents.templates.index')
                ->with('success', 'Le modèle a été créé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur création modèle', [
                'error' => $e->getMessage(),
                'type' => $request->type
            ]);

            return back()->with('error', 'Erreur lors de la création du modèle.');
        }
    }

    public function templatesEdit($id)
    {
        $document = Document::findOrFail($id);
        $types = $this->getDocumentTypes();
        $variables = $this->getVariablesByType($document->type);

        return view('dashboard.pages.parametrage.documents.templates.edit', compact('document', 'types', 'variables'));
    }

    public function templatesUpdate(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|string|unique:documents,type,' . $id,
            'content' => 'required|string',
        ]);

        try {
            $document = Document::findOrFail($id);
            $document->update([
                'type' => $request->type,
                'content' => $request->content,
            ]);

            Log::info('Modèle de document mis à jour', [
                'type' => $document->type,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('documents.templates.index')
                ->with('success', 'Le modèle a été mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour modèle', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour du modèle.');
        }
    }

    public function templatesDestroy($id)
    {
        try {
            $document = Document::findOrFail($id);
            $document->delete();

            Log::info('Modèle de document supprimé', [
                'type' => $document->type,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('documents.templates.index')
                ->with('success', 'Le modèle a été supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur suppression modèle', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return back()->with('error', 'Erreur lors de la suppression du modèle.');
        }
    }

    private function getDocumentTypes()
    {
        return [
            'certificat_nationalite' => 'Certificat de Nationalité',
            'attestation_frequentation' => 'Certificat de Fréquentation',
            'fiche_inscription' => 'Fiche d\'Inscription',
            'certificat_scolaire' => 'Certificat de Scolarité',
        ];
    }

    private function getVariablesByType($type)
    {
        $entete = [
            '%DIRECTION_REGIONALE%' => 'Direction régionale',
            '%IEPP%' => 'I.E.P.P',
            '%SECTEUR_PEDAGOGIQUE%' => 'Secteur pédagogique',
            '%SIGLE_ECOLE%' => 'Sigle de l\'école (E.PV)',
            '%ANNEE%' => 'Année scolaire',
        ];

        $variables = [
            'certificat_nationalite' => array_merge($entete, [
                '%NOM%' => 'Nom de l\'élève',
                '%PRENOM%' => 'Prénom de l\'élève',
                '%MATRICULE%' => 'Matricule',
                '%SEXE%' => 'Sexe',
                '%NAISSANCE%' => 'Date de naissance',
                '%LIEU_NAISSANCE%' => 'Lieu de naissance',
                '%NATIONALITE%' => 'Nationalité',
                '%CLASSE%' => 'Classe',
                '%DATE_EDITION%' => 'Date d\'édition',
                '%ECOLE%' => 'Nom de l\'école',
                '%VILLE%' => 'Ville',
                '%DIRECTEUR%' => 'Nom du directeur',
                '%DATE_FR%' => 'Date en français',
                '%NUMERO_CERTIFICAT%' => 'Numéro du certificat'
            ]),
            'attestation_frequentation' => array_merge($entete, [
                '%NOM%' => 'Nom de l\'élève',
                '%PRENOM%' => 'Prénom de l\'élève',
                '%MATRICULE%' => 'Matricule',
                '%ACTE_NAISSANCE_NUM%' => 'N° acte de naissance',
                '%NAISSANCE%' => 'Date de naissance',
                '%LIEU_NAISSANCE%' => 'Lieu de naissance',
                '%CLASSE%' => 'Cours suivi / Classe',
                '%PERE_NOM%' => 'Nom du père',
                '%MERE_NOM%' => 'Nom de la mère',
                '%DATE_DEBUT_FREQUENTATION%' => 'Fréquente depuis le',
                '%DATE_FIN_FREQUENTATION%' => 'À ce jour',
                '%ECOLE%' => 'Nom de l\'école',
                '%VILLE%' => 'Ville',
                '%DIRECTEUR_ETUDES%' => 'Directeur des Études',
                '%DATE_FR%' => 'Date en français'
            ]),
            'fiche_inscription' => array_merge($entete, [
                '%NOM%' => 'Nom de l\'élève',
                '%PRENOM%' => 'Prénom de l\'élève',
                '%MATRICULE%' => 'Matricule',
                '%SEXE%' => 'Sexe',
                '%NAISSANCE%' => 'Date de naissance',
                '%LIEU_NAISSANCE%' => 'Lieu de naissance',
                '%NATIONALITE%' => 'Nationalité',
                '%CLASSE%' => 'Classe',
                '%DATE_INSCRIPTION%' => 'Date d\'inscription',
                '%PARENT_NOM%' => 'Nom du parent',
                '%PARENT_TELEPHONE%' => 'Téléphone du parent',
                '%PARENT_EMAIL%' => 'Email du parent',
                '%ADRESSE%' => 'Adresse',
                '%ECOLE%' => 'Nom de l\'école',
                '%VILLE%' => 'Ville',
                '%DIRECTEUR%' => 'Nom du directeur',
                '%DATE_FR%' => 'Date en français'
            ]),
            'certificat_scolaire' => array_merge($entete, [
                '%NOM%' => 'Nom de l\'élève',
                '%PRENOM%' => 'Prénom de l\'élève',
                '%MATRICULE%' => 'Matricule',
                '%NAISSANCE%' => 'Date de naissance',
                '%LIEU_NAISSANCE%' => 'Lieu de naissance',
                '%JUGEMENT_SUPPLETIF%' => 'Jugement supplétif',
                '%ACTE_NAISSANCE_NUM%' => 'N° acte de naissance / jugement',
                '%NUM_REGISTRE%' => 'N° registre établissement',
                '%SOUS_PREFECTURE%' => 'Sous-préfecture',
                '%CIRCONSCRIPTION%' => 'Circonscription primaire',
                '%DATE_DEBUT_FREQUENTATION%' => 'Fréquente depuis le',
                '%DATE_FIN_FREQUENTATION%' => 'À ce jour',
                '%CLASSE%' => 'Classe',
                '%MOYENNE_ANNUELLE%' => 'Moyenne annuelle',
                '%RANG%' => 'Rang',
                '%EFFECTIF%' => 'Effectif',
                '%OBSERVATION%' => 'Observation',
                '%ECOLE%' => 'Nom de l\'école',
                '%VILLE%' => 'Ville',
                '%DIRECTEUR%' => 'Nom du directeur',
                '%DATE_FR%' => 'Date en français'
            ])
        ];

        return $variables[$type] ?? $variables['fiche_inscription'];
    }

    // ==========================================
    // UPLOAD IMAGE POUR CKEDITOR
    // ==========================================
    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|max:2048',
        ]);

        $path = $request->file('upload')->store('public/uploads');
        $url = asset(str_replace('public/', 'storage/', $path));

        return response()->json(['url' => $url]);
    }

 

    // ==========================================
    // ROUTE: documents.inscriptions
    // FICHES D'INSCRIPTION (LISTE)
    // ==========================================
    public function inscriptions(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $query = DB::table($elevesTable . ' as eleves')
            ->leftJoin($classesTable . ' as classes', 'eleves.classe_id', '=', 'classes.id')
            ->where('eleves.ecole_id', $ecoleId)
            ->where('eleves.annee_scolaire_id', $anneeScolaireId)
            ->where('eleves.is_active', 1);

        $select = ['eleves.*'];
        if (Schema::hasColumn($classesTable, 'nom')) {
            $select[] = 'classes.nom as classe_nom';
        }
        if (Schema::hasColumn($classesTable, 'libelle')) {
            $select[] = 'classes.libelle as classe_libelle';
        }
        $query->select($select);

        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        if ($request->filled('classe_id')) {
            $query->where('eleves.classe_id', $request->classe_id);
        }

        $eleves = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->paginate(20);

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.documents.inscriptions', compact('eleves', 'classes'));
    }

public function genererFicheInscription($id)
{
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $annee = session('current_annee_scolaire');
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

    try {
        // Récupérer les noms des tables dynamiques
        $eleveTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        // Récupérer l'élève
        $eleve = DB::table($eleveTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('is_active', 1)
            ->first();

        if (!$eleve) {
            abort(404, 'Élève non trouvé');
        }

        // Récupérer la classe depuis la table dynamique des classes
        $classe = DB::table($classesTable)
            ->where('id', $eleve->classe_id)
            ->first();

        if (!$classe) {
            abort(404, 'Classe non trouvée pour cet élève');
        }

        // Récupérer le niveau si la colonne existe
        $niveau = null;
        if (isset($classe->niveau_id) && $classe->niveau_id) {
            $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
            $niveau = DB::table($niveauxTable)
                ->where('id', $classe->niveau_id)
                ->first();
        }

        $ecole = \App\Models\Ecole::find($ecoleId);
        if (!$ecole) {
            abort(404, 'École non trouvée');
        }

        // Ajouter le niveau à l'objet classe pour la vue
        if ($niveau) {
            $classe->niveau = $niveau;
        }

        $pdf = Pdf::loadView('dashboard.documents.scolaire.fiche-inscription', [
            'eleve'  => $eleve,
            'classe' => $classe,
            'ecole'  => $ecole,
            'annee'  => $annee,
            'anneeScolaire' => $anneeScolaire,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'fiche-inscription-' . ($eleve->nom ?? '') . '-' . ($eleve->prenom ?? '') . '.pdf';

        return $pdf->stream($filename);

    } catch (\Exception $e) {
        Log::error('Erreur genererFicheInscription: ' . $e->getMessage());
        abort(500, 'Erreur lors de la génération du PDF : ' . $e->getMessage());
    }
}

    // ==========================================
    // ROUTE: documents.certificats-scolarite
    // CERTIFICATS DE SCOLARITÉ (LISTE)
    // ==========================================
    public function certificatsScolarite(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $query = DB::table($elevesTable . ' as eleves')
            ->leftJoin($classesTable . ' as classes', 'eleves.classe_id', '=', 'classes.id')
            ->where('eleves.ecole_id', $ecoleId)
            ->where('eleves.annee_scolaire_id', $anneeScolaireId)
            ->where('eleves.is_active', 1);

        $select = ['eleves.*'];
        if (Schema::hasColumn($classesTable, 'nom')) {
            $select[] = 'classes.nom as classe_nom';
        }
        if (Schema::hasColumn($classesTable, 'libelle')) {
            $select[] = 'classes.libelle as classe_libelle';
        }
        $query->select($select);

        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        if ($request->filled('classe_id')) {
            $query->where('eleves.classe_id', $request->classe_id);
        }

        $eleves = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->paginate(20);

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.documents.certificats-scolarite', compact('eleves', 'classes'));
    }

    // ==========================================
    // ROUTE: documents.generer-certificat-scolarite
    // GÉNÉRATION PDF - CERTIFICAT DE SCOLARITÉ
    // ==========================================
    public function genererCertificatScolarite($id)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        try {
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleve = DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->first();

            if (!$eleve) {
                abort(404, 'Élève non trouvé');
            }

            $classe = Classe::find($eleve->classe_id);
            if (!$classe) {
                abort(404, 'Classe non trouvée pour cet élève');
            }

            $ecole = \App\Models\Ecole::find($ecoleId);
            if (!$ecole) {
                abort(404, 'École non trouvée');
            }

            $pdf = Pdf::loadView('dashboard.documents.scolaire.certificat-scolarite', [
                'eleve'  => $eleve,
                'classe' => $classe,
                'ecole'  => $ecole,
                'annee'  => $annee,
            ]);

            $pdf->setPaper('A4', 'portrait');

            $filename = 'certificat-scolarite-' . ($eleve->nom ?? '') . '-' . ($eleve->prenom ?? '') . '.pdf';

            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('Erreur genererCertificatScolarite: ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    // ==========================================
    // ROUTE: documents.attestations-frequentation
    // CERTIFICATS DE FRÉQUENTATION (LISTE)
    // ==========================================
    public function attestationsFrequentation(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $query = DB::table($elevesTable . ' as eleves')
            ->leftJoin($classesTable . ' as classes', 'eleves.classe_id', '=', 'classes.id')
            ->where('eleves.ecole_id', $ecoleId)
            ->where('eleves.annee_scolaire_id', $anneeScolaireId)
            ->where('eleves.is_active', 1);

        $select = ['eleves.*'];
        if (Schema::hasColumn($classesTable, 'nom')) {
            $select[] = 'classes.nom as classe_nom';
        }
        if (Schema::hasColumn($classesTable, 'libelle')) {
            $select[] = 'classes.libelle as classe_libelle';
        }
        $query->select($select);

        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        if ($request->filled('classe_id')) {
            $query->where('eleves.classe_id', $request->classe_id);
        }

        $eleves = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->paginate(20);

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.documents.attestations-frequentation', compact('eleves', 'classes'));
    }

    // ==========================================
    // ROUTE: documents.generer-attestation-frequentation
    // GÉNÉRATION PDF - CERTIFICAT DE FRÉQUENTATION
    // ==========================================
    public function genererAttestationFrequentation($id)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        try {
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleve = DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->first();

            if (!$eleve) {
                abort(404, 'Élève non trouvé');
            }

            $classe = Classe::find($eleve->classe_id);
            if (!$classe) {
                abort(404, 'Classe non trouvée pour cet élève');
            }

            $ecole = \App\Models\Ecole::find($ecoleId);
            if (!$ecole) {
                abort(404, 'École non trouvée');
            }

            $pdf = Pdf::loadView('dashboard.documents.scolaire.attestation-frequentation', [
                'eleve'  => $eleve,
                'classe' => $classe,
                'ecole'  => $ecole,
                'annee'  => $annee,
            ]);

            $pdf->setPaper('A4', 'portrait');

            $filename = 'certificat-frequentation-' . ($eleve->nom ?? '') . '-' . ($eleve->prenom ?? '') . '.pdf';

            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('Erreur genererAttestationFrequentation: ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    // ==========================================
    // ROUTE: documents.fiches-presence
    // FICHES DE PRÉSENCE (LISTE)
    // ==========================================
    public function fichesPresence(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $query = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($request->filled('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        $classes = $query->orderBy('nom')->get();

        $niveaux = Niveau::where('ecole_id', $ecoleId)
           // ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')
            ->get();

        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.documents.fiches-presence', compact('classes', 'niveaux', 'moisScolaire'));
    }

    // ==========================================
    // ROUTE: documents.generer-fiche-presence
    // GÉNÉRATION PDF - FICHE DE PRÉSENCE
    // ==========================================
    public function genererFichePresence($id)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        try {
            $classe = Classe::find($id);

            if (!$classe) {
                abort(404, 'Classe non trouvée');
            }

            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleves = DB::table($elevesTable)
                ->where('classe_id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();

            $pdf = Pdf::loadView('dashboard.documents.fiche-presence', [
                'classe' => $classe,
                'eleves' => $eleves,
                'anneeScolaire' => AnneeScolaire::find($anneeScolaireId)
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('fiche-presence-' . $classe->nom . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur genererFichePresence: ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    // ==========================================
    // ROUTE: documents.fiches-frequentation
    // FICHES DE FRÉQUENTATION (LISTE)
    // ==========================================
    public function fichesFrequentation(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);

        $query = DB::table($elevesTable . ' as eleves')
            ->leftJoin($classesTable . ' as classes', 'eleves.classe_id', '=', 'classes.id')
            ->where('eleves.ecole_id', $ecoleId)
            ->where('eleves.annee_scolaire_id', $anneeScolaireId)
            ->where('eleves.is_active', 1);

        $select = ['eleves.*'];
        if (Schema::hasColumn($classesTable, 'nom')) {
            $select[] = 'classes.nom as classe_nom';
        }
        if (Schema::hasColumn($classesTable, 'libelle')) {
            $select[] = 'classes.libelle as classe_libelle';
        }
        $query->select($select);

        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        if ($request->filled('classe_id')) {
            $query->where('eleves.classe_id', $request->classe_id);
        }

        $eleves = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->paginate(20);

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.documents.attestations-frequentation', compact('eleves', 'classes'));
    }

    // ==========================================
    // ROUTE: documents.generer-fiche-frequentation
    // GÉNÉRATION PDF - FICHE DE FRÉQUENTATION
    // ==========================================
    public function genererFicheFrequentation($id)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        try {
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleve = DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->first();

            if (!$eleve) {
                abort(404, 'Élève non trouvé');
            }

            $classe = Classe::find($eleve->classe_id);

            if (!$classe) {
                abort(404, 'Classe non trouvée pour cet élève');
            }

            $pdf = Pdf::loadView('dashboard.documents.fiche-frequentation', [
                'eleve' => $eleve,
                'classe' => $classe,
                'anneeScolaire' => $anneeScolaire,
                'ecoleId' => $ecoleId,
            ]);

            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('fiche-frequentation-' . ($eleve->nom ?? '') . '-' . ($eleve->prenom ?? '') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur genererFicheFrequentation: ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }



    public function cartesEleves(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        $eleves = collect();
        $classeSelectionnee = null;

        if ($request->filled('classe_id')) {
            $classeSelectionnee = Classe::find($request->classe_id);

            $annee = session('current_annee_scolaire');
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleves = DB::table($elevesTable)
                ->where('classe_id', $request->classe_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();
        }

        return view('dashboard.pages.documents.cartes-eleves', compact('classes', 'eleves', 'classeSelectionnee'));
    }


    public function genererParClasse($classeId)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        try {
            $classe = Classe::find($classeId);
            if (!$classe) {
                abort(404, 'Classe non trouvée');
            }

            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleves = DB::table($elevesTable)
                ->where('classe_id', $classeId)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();

            if ($eleves->isEmpty()) {
                abort(404, 'Aucun élève actif trouvé dans cette classe');
            }

            $ecole = \App\Models\Ecole::find($ecoleId);

            // Groupe les élèves par lot de 3 pour la grille (3 colonnes)
            $lignes = $eleves->chunk(3);

            $pdf = Pdf::loadView('dashboard.documents.scolaire.cartes-eleves-pdf', [
                'lignes' => $lignes,
                'classe' => $classe,
                'ecole'  => $ecole,
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('cartes-eleves-' . str_replace(' ', '-', $classe->nom) . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur genererParClasse (cartes élèves): ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération des cartes : ' . $e->getMessage());
        }
    }

    // ==========================================
    // ROUTE: documents.generer-carte-eleve
    // GÉNÉRATION PDF - CARTE D'UN SEUL ÉLÈVE
    // ==========================================
    public function genererCarteIndividuelle($id)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        try {
            $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);

            $eleve = DB::table($elevesTable)
                ->where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('is_active', 1)
                ->first();

            if (!$eleve) {
                abort(404, 'Élève non trouvé');
            }

            $ecole = \App\Models\Ecole::find($ecoleId);

            $pdf = Pdf::loadView('dashboard.documents.scolaire.cartes-eleves-pdf', [
                'lignes' => collect([$eleve])->chunk(1),
                'classe' => Classe::find($eleve->classe_id),
                'ecole'  => $ecole,
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('carte-eleve-' . ($eleve->nom ?? '') . '-' . ($eleve->prenom ?? '') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur genererCarteIndividuelle: ' . $e->getMessage());
            abort(500, 'Erreur lors de la génération de la carte : ' . $e->getMessage());
        }
    }
}