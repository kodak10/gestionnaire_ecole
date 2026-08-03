<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BilanFinancierController;
use App\Http\Controllers\BilanScolaireController;
use App\Http\Controllers\CantineController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\CritereNotationController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\EcoleController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\FraisScolariteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JournalCaisseController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ParametrageScolariteController;
use App\Http\Controllers\ParcheminController;
use App\Http\Controllers\PreInscriptionController;
use App\Http\Controllers\ReductionController;
use App\Http\Controllers\ReglementController;
use App\Http\Controllers\ReinscriptionController;
use App\Http\Controllers\RelanceController;
use App\Http\Controllers\ScolariteController;
use App\Http\Controllers\TableauHonneurController;
use App\Http\Controllers\TarifMensuelController;
use App\Http\Controllers\TarifScolariteController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AnneeScolaireController;
use App\Models\Eleve;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\Route;

// ============================================
// ROUTES PUBLIQUES (SANS AUTHENTIFICATION)
// ============================================

// Redirection par défaut
Route::get('/', function () {
    return redirect('/login');
});

// Routes d'authentification
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Routes API publiques
Route::get('/ecoles/{ecoleId}/annees-scolaires', [EcoleController::class, 'getAnneesScolaires']);

// ============================================
// ROUTES ADMINISTRATION (Authentification requise)
// ============================================
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AnneeScolaireController::class, 'dashboard'])->name('dashboard');
    
    // Gestion des écoles
    Route::post('/ecoles', [AnneeScolaireController::class, 'createEcole'])->name('ecoles.store');
    Route::get('/ecoles/{id}/classes', [AnneeScolaireController::class, 'getClassesByEcole'])->name('ecoles.classes');
    Route::get('/ecoles/{id}/niveaux', [AnneeScolaireController::class, 'getNiveauxByEcole'])->name('ecoles.niveaux');
    Route::get('/ecoles/{id}/matieres', [AnneeScolaireController::class, 'getMatieresByEcole'])->name('ecoles.matieres');
    
    // Gestion des années scolaires
    Route::post('/annees-scolaires', [AnneeScolaireController::class, 'createAnneeScolaire'])->name('annees.store');
    Route::delete('/annees-scolaires/{id}', [AnneeScolaireController::class, 'deleteAnneeScolaire'])->name('annees.delete');
    Route::patch('/annees-scolaires/{id}/toggle', [AnneeScolaireController::class, 'toggleAnneeScolaire'])->name('annees.toggle');
    Route::post('/annees-scolaires/{id}/regenerate', [AnneeScolaireController::class, 'regenerateTables'])->name('annees.regenerate');
    Route::get('/annees-scolaires/{id}/check-tables', [AnneeScolaireController::class, 'checkTables'])->name('annees.check-tables');
});

// ============================================
// ROUTES PROTÉGÉES (Authentification + EcoleAnnee.status)
// ============================================
Route::middleware(['auth', 'EcoleAnnee.status'])->group(function () {

    // ============================================
    // DASHBOARD
    // ============================================
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // ============================================
    // GESTION DU PROFIL UTILISATEUR
    // ============================================
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::resource('users', UserController::class);

    // ============================================
    // PARAMÉTRAGES
    // ============================================
    Route::prefix('parametrages')->group(function() {
        // École
        Route::get('/ecole', [EcoleController::class, 'index'])->name('ecoles.index');
        Route::put('/ecole', [EcoleController::class, 'update'])->name('ecoles.update');

        // Niveaux
        Route::resource('niveaux', NiveauController::class);

        // Classes
        Route::resource('classes', ClasseController::class);
        Route::get('classes/export/{type}', [ClasseController::class, 'export'])->name('classes.export');

        // Matières
        Route::resource('matieres', MatiereController::class);
        Route::post('/classes/assign-matieres', [MatiereController::class, 'assignMatieres'])->name('classes.matieres.assign');
        Route::get('/classes/{id}/matieres', [MatiereController::class, 'getMatieres'])->name('classes.matieres.get');

        // Mentions
        Route::resource('mentions', MentionController::class);

        // Enseignants
        Route::resource('enseignants', EnseignantController::class);

        // Documents
        Route::get('/documents/inscriptions/model', [DocumentController::class, 'inscriptionsModel'])->name('documents.inscriptions.model');
        Route::post('/documents/inscriptions/save', [DocumentController::class, 'inscriptionsModelSave'])->name('documents.inscriptions.save');
        Route::post('/documents/upload-image', [DocumentController::class, 'uploadImage'])->name('documents.upload-image');
    });

    // ============================================
    // TEMPLATES DE DOCUMENTS
    // ============================================
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/active-sms', [DocumentTemplateController::class, 'getActiveSms'])->name('getActiveSms');
        Route::get('/', [DocumentTemplateController::class, 'index'])->name('index');
        Route::get('/create', [DocumentTemplateController::class, 'create'])->name('create');
        Route::post('/', [DocumentTemplateController::class, 'store'])->name('store');
        Route::get('/{id}', [DocumentTemplateController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DocumentTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DocumentTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentTemplateController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // GESTION DES ÉLÈVES
    // ============================================
    Route::prefix('eleves')->name('eleves.')->group(function () {
        Route::get('/export', [EleveController::class, 'export'])->name('export');
        Route::get('/by-classe', [EleveController::class, 'getByClasse'])->name('by-classe');
        Route::get('/{eleve}/reinscrire', [ReinscriptionController::class, 'create'])->name('reinscrire');
    });
    Route::resource('eleves', EleveController::class);

    // ============================================
    // GESTION DES INSCRIPTIONS
    // ============================================
    Route::resource('preinscriptions', PreInscriptionController::class);
    Route::post('preinscriptions/{preinscription}/valider', [PreInscriptionController::class, 'valider'])->name('preinscriptions.valider');
    Route::post('preinscriptions/{preinscription}/refuser', [PreInscriptionController::class, 'refuser'])->name('preinscriptions.refuser');

    // ============================================
    // GESTION DES RÉINSCRIPTIONS
    // ============================================
    Route::prefix('reinscriptions')->name('reinscriptions.')->group(function () {
        Route::get('/get-classes-by-annee', [ReinscriptionController::class, 'getClassesByAnnee'])->name('getClassesByAnnee');
        Route::get('/eleves-by-classe/{classe}', [ReinscriptionController::class, 'getElevesByClasse'])->name('elevesByClasse');
        Route::get('/', [ReinscriptionController::class, 'index'])->name('index');
        Route::post('/', [ReinscriptionController::class, 'store'])->name('store');
        Route::get('/{id}', [ReinscriptionController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReinscriptionController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReinscriptionController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReinscriptionController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // GESTION DE LA SCOLARITÉ
    // ============================================
    Route::prefix('scolarite')->name('scolarite.')->group(function() {
        Route::get('/', [ScolariteController::class, 'index'])->name('index');
        Route::get('/eleves/by-classe', [ScolariteController::class, 'getElevesByClasse'])->name('eleves.by_classe');

        // Tarifs annuels
        Route::resource('tarifs', TarifScolariteController::class);

        // Tarifs mensuels
        Route::resource('tarifs-mensuels', TarifMensuelController::class)->except(['show']);
        Route::get('/tarifs-mensuels/get-tarifs', [TarifMensuelController::class, 'getTarifsMensuels'])->name('tarifs-mensuels.get-tarifs');
    });

    // ============================================
    // GESTION DES RÈGLEMENTS (Scolarité)
    // ============================================
    Route::prefix('reglements')->name('reglements.')->group(function () {
        Route::get('/', [ReglementController::class, 'index'])->name('index');
        Route::get('/eleve-data', [ReglementController::class, 'eleveData'])->name('eleve_data');
        Route::post('/store-paiement', [ReglementController::class, 'storePaiement'])->name('store_paiement');
        Route::delete('/delete-paiement', [ReglementController::class, 'deletePaiement'])->name('delete_paiement');
        Route::get('/receipt/{paiement}', [ReglementController::class, 'generateReceipt'])->name('receipt');
    });

    // ============================================
    // GESTION DE LA CANTINE
    // ============================================
    Route::prefix('cantine')->name('cantine.')->group(function () {
        Route::get('/', [CantineController::class, 'index'])->name('index');
        Route::get('/eleves-by-classe', [CantineController::class, 'elevesByClasseCantine'])->name('eleves.by_classe');
        Route::get('/mois-a-payer', [CantineController::class, 'getMoisAPayer'])->name('mois_a_payer');
        Route::post('/paiement-mensuel', [CantineController::class, 'storePaiementMensuel'])->name('store_paiement_mensuel');
        Route::get('/receipt/{id}', [CantineController::class, 'generateReceipt'])->name('receipt');
        Route::delete('/paiement', [CantineController::class, 'deletePaiement'])->name('delete_paiement');
        Route::get('/eleve-data', [CantineController::class, 'getEleveCantine'])->name('eleve_data');
        Route::post('/store-paiement', [CantineController::class, 'store'])->name('store_paiement');
    });

    // ============================================
    // GESTION DU TRANSPORT
    // ============================================
    Route::prefix('transport')->name('transport.')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');
        Route::get('/gestion', [TransportController::class, 'GestionTransport'])->name('gestion');
        Route::get('/eleves-by-classe', [TransportController::class, 'elevesByClasseTransport'])->name('eleves.by_classe');
        Route::get('/mois-a-payer', [TransportController::class, 'getMoisAPayer'])->name('mois_a_payer');
        Route::post('/paiement-mensuel', [TransportController::class, 'storePaiementMensuel'])->name('store_paiement_mensuel');
        Route::get('/receipt/{id}', [TransportController::class, 'generateReceipt'])->name('receipt');
        Route::delete('/paiement', [TransportController::class, 'deletePaiement'])->name('delete_paiement');
        Route::get('/eleve-data', [TransportController::class, 'getEleveTransport'])->name('eleve_data');
        Route::post('/store-paiement', [TransportController::class, 'store'])->name('store_paiement');
        Route::post('/update-transport-type', [TransportController::class, 'updateTransportType'])->name('update_transport_type');
    });

    // ============================================
    // GESTION DES RÉDUCTIONS
    // ============================================
    Route::prefix('reductions')->name('reductions.')->group(function () {
        Route::get('/', [ReductionController::class, 'index'])->name('index');
        Route::get('/get-eleves', [ReductionController::class, 'getEleves'])->name('get_eleves');
        Route::get('/get-eleve-data', [ReductionController::class, 'getEleveData'])->name('get_eleve_data');
        Route::post('/store', [ReductionController::class, 'store'])->name('store');
        Route::delete('/{id}', [ReductionController::class, 'destroy'])->name('destroy');
        Route::post('/update-transport-tarif', [ReductionController::class, 'updateTransportTarif'])->name('update_transport_tarif');
        Route::post('/update-cantine-tarif', [ReductionController::class, 'updateCantineTarif'])->name('update_cantine_tarif');
    });

    // ============================================
    // GESTION DES NOTES
    // ============================================
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/inscriptions-by-classe', [NoteController::class, 'getInscriptionsByClasse'])->name('inscriptions_by_classe');
        Route::get('/matieres-by-classe', [NoteController::class, 'getMatieresByClasse'])->name('matieres_by_classe');
        Route::get('/by-classe', [NoteController::class, 'getNotesByClasse'])->name('byClasse');
        Route::get('/generate-bulletin', [NoteController::class, 'generateBulletin'])->name('generateBulletin');
        Route::get('/filter-by-classe', [NoteController::class, 'filterByClasse'])->name('filterByClasse');
        Route::get('/generate-fiches-moyennes', [NoteController::class, 'generateFichesMoyennes'])->name('generateFichesMoyennes');
        Route::get('/recap/pdf', [NoteController::class, 'generateRecapMoyennes'])->name('recap.pdf');
        Route::get('/generate-bulletin-annuel', [NoteController::class, 'generateBulletinAnnuel'])->name('generateBulletinAnnuel');
        Route::get('/check-existing-mois-moyenne', [NoteController::class, 'checkExistingMoisMoyenne'])->name('checkExistingMoisMoyenne');
    });
    Route::resource('notes', NoteController::class);

    // ============================================
    // GESTION DES DOCUMENTS
    // ============================================
    Route::prefix('documents')->name('documents.')->group(function () {
        // Listes
        Route::get('/inscriptions', [DocumentController::class, 'inscriptions'])->name('inscriptions');
        Route::get('/certificats-scolarite', [DocumentController::class, 'certificatsScolarite'])->name('certificats-scolarite');
        Route::get('/fiches-presence', [DocumentController::class, 'fichesPresence'])->name('fiches-presence');
        Route::get('/fiches-frequentation', [DocumentController::class, 'fichesFrequentation'])->name('fiches-frequentation');

        // Génération
        Route::get('/generer-fiche-inscription/{eleve}', [DocumentController::class, 'genererFicheInscription'])->name('generer-fiche-inscription');
        Route::get('/generer-certificat-scolarite/{eleve}', [DocumentController::class, 'genererCertificatScolarite'])->name('generer-certificat-scolarite');
        Route::get('/generer-fiche-presence/{classe}', [DocumentController::class, 'genererFichePresence'])->name('generer-fiche-presence');
        Route::get('/generer-fiche-frequentation/{eleve}', [DocumentController::class, 'genererFicheFrequentation'])->name('generer-fiche-frequentation');
    });

    // ============================================
    // TABLEAUX D'HONNEUR
    // ============================================
    Route::prefix('tableaux-honneur')->name('tableaux-honneur.')->group(function () {
        Route::get('/', [TableauHonneurController::class, 'index'])->name('index');
        Route::get('/generer-mensuel', [TableauHonneurController::class, 'genererMensuel'])->name('generer-mensuel');
        Route::get('/generer-annuel', [TableauHonneurController::class, 'genererAnnuel'])->name('generer-annuel');
        Route::get('/generer-major', [TableauHonneurController::class, 'genererMajor'])->name('generer-major');
    });

    // ============================================
    // PARCHEMINS
    // ============================================
    Route::prefix('parchemin')->name('parchemin.')->group(function () {
        Route::get('/', [ParcheminController::class, 'index'])->name('index');
        Route::get('/generer', [ParcheminController::class, 'generer'])->name('generer');
    });

    // ============================================
    // JOURNAL DES PAIEMENTS
    // ============================================
    Route::prefix('journal-paiements')->name('journal-paiements.')->group(function () {
        Route::get('/', [JournalCaisseController::class, 'index'])->name('index');
        Route::get('/data', [JournalCaisseController::class, 'getData'])->name('data');
        Route::get('/inscriptions-by-classe', [JournalCaisseController::class, 'getInscriptionsByClasse'])->name('inscriptions-by-classe');
        Route::post('/', [JournalCaisseController::class, 'store'])->name('store');
        Route::put('/{paiement}', [JournalCaisseController::class, 'update'])->name('update');
        Route::delete('/{paiement}', [JournalCaisseController::class, 'destroy'])->name('destroy');
        Route::get('/{paiement}', [JournalCaisseController::class, 'show'])->name('show');
    });

    // ============================================
    // GESTION DES DÉPENSES
    // ============================================
    Route::prefix('depenses')->name('depenses.')->group(function () {
        Route::get('/', [DepenseController::class, 'index'])->name('index');
        Route::get('/data', [DepenseController::class, 'getDepensesData'])->name('data');
        Route::post('/', [DepenseController::class, 'store'])->name('store');
        Route::get('/{id}', [DepenseController::class, 'show'])->name('show');
        Route::put('/{id}', [DepenseController::class, 'update'])->name('update');
        Route::delete('/{id}', [DepenseController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // RELANCE
    // ============================================
    Route::prefix('relance')->name('relance.')->group(function () {
        Route::get('/', [RelanceController::class, 'index'])->name('index');
        Route::get('/data', [RelanceController::class, 'getRelanceData'])->name('data');
        Route::get('/imprimer', [RelanceController::class, 'imprimerRelance'])->name('imprimer');
        Route::get('/export', [RelanceController::class, 'export'])->name('export');
        Route::post('/send-sms', [RelanceController::class, 'sendSms'])->name('send.sms');
    });

    // ============================================
    // BILANS
    // ============================================
    Route::prefix('bilans')->name('bilans.')->group(function () {
        Route::get('/scolaire', [BilanScolaireController::class, 'index'])->name('scolaire');
        Route::get('/financier', [BilanFinancierController::class, 'index'])->name('financier');
        Route::post('/scolaire/export', [BilanScolaireController::class, 'export'])->name('scolaire.export');
        Route::post('/financier/export', [BilanFinancierController::class, 'export'])->name('financier.export');
    });

});

// ============================================
// ROUTES API PUBLIQUES (SANS MIDDLEWARE EcoleAnnee.status)
// ============================================
Route::middleware(['auth'])->group(function () {
    Route::get('/api/eleves-par-classe/{classe}', function($classeId) {
        $eleves = Eleve::where('classe_id', $classeId)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom', 'matricule']);
            
        return response()->json($eleves->map(function($eleve) {
            return [
                'id' => $eleve->id,
                'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                'matricule' => $eleve->matricule
            ];
        }));
    });
});