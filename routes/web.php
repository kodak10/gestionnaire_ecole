<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CantineController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\CritereNotationController;
use App\Http\Controllers\DocumentTemplateController;

use App\Http\Controllers\DepenseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EcoleController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\FraisScolariteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JournalCaisseController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ParametrageScolariteController;
use App\Http\Controllers\ParcheminController;
use App\Http\Controllers\PreInscriptionController;
use App\Http\Controllers\ReglementController;
use App\Http\Controllers\ReinscriptionController;
use App\Http\Controllers\RelanceController;
use App\Http\Controllers\ScolariteController;
use App\Http\Controllers\TableauHonneurController;
use App\Http\Controllers\TarifMensuelController;
use App\Http\Controllers\TarifScolariteController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReductionController;
use App\Http\Controllers\BilanScolaireController;
use App\Http\Controllers\BilanFinancierController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\Admin\AnneeScolaireController;
use App\Models\Eleve;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\Route;


    // Route par défaut
    Route::get('/', function () {
        return redirect('/login');
    });


    // Routes d'authentification personnalisées
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);

    Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::get('/ecoles/{ecoleId}/annees-scolaires', [EcoleController::class, 'getAnneesScolaires']);


    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    
    // Dashboard
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


    // Routes protégées
    Route::middleware(['auth', 'EcoleAnnee.status'])->group(function () {
        Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
        

        Route::prefix('parametrages')->group(function() {
            Route::get('/ecole', [EcoleController::class, 'index'])->name('ecoles.index');
            Route::put('/ecole', [EcoleController::class, 'update'])->name('ecoles.update');

            Route::resource('niveaux', NiveauController::class);

            Route::resource('classes', ClasseController::class);
            Route::get('classes/export/{type}', [ClasseController::class, 'export'])->name('classes.export');

            Route::resource('matieres', MatiereController::class);
            Route::post('/classes/assign-matieres', [MatiereController::class, 'assignMatieres'])->name('classes.matieres.assign');
            Route::get('/classes/{id}/matieres', [MatiereController::class, 'getMatieres'])->name('classes.matieres.get');

            Route::resource('mentions', MentionController::class);

            Route::resource('enseignants', EnseignantController::class);


            Route::get('/documents/inscriptions/model', [DocumentController::class, 'inscriptionsModel'])->name('documents.inscriptions.model');
            Route::post('/documents/inscriptions/save', [DocumentController::class, 'inscriptionsModelSave'])->name('documents.inscriptions.save');
            Route::post('/documents/upload-image', [DocumentController::class, 'uploadImage'])->name('documents.upload-image');

        });


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

        

        // Pour récupérer les élèves d'une classe via AJAX
        Route::get('/eleves/by-classe', [ScolariteController::class, 'getElevesByClasse'])->name('eleves.by_classe');
        Route::get('/eleve_data', [ReglementController::class, 'eleveData'])->name('reglements.eleve_data');

        // // Routes pour la gestion de la cantine
        // Route::get('/eleves-by-classe-cantine', [CantineController::class, 'elevesByClasseCantine'])->name('eleves.by_classe_cantine');
        // Route::get('/reglements/eleve-cantine-data', [CantineController::class, 'getEleveCantine'])->name('reglements.eleve_cantine_data');
        // Route::post('/reglements/store-paiement-cantine', [CantineController::class, 'store'])->name('reglements.store_paiement_cantine');

        // // Routes pour la gestion de la transport
        // Route::get('/eleves-by-classe-transport', [TransportController::class, 'elevesByClasseTransport'])->name('eleves.by_classe_transport');
        // Route::get('/reglements/eleve-transport-data', [TransportController::class, 'getEleveTransport'])->name('reglements.eleve_transport_data');
        // Route::post('/reglements/store-paiement-transport', [TransportController::class, 'store'])->name('reglements.store_paiement_transport');


        // Routes pour les élèves par classe
Route::get('/eleves-by-classe-cantine', [CantineController::class, 'elevesByClasseCantine'])->name('eleves.by_classe_cantine');

// Routes pour les règlements (cantine et transport)
Route::prefix('reglements')->group(function () {
    // Cantine
    Route::get('/eleve-cantine-data', [CantineController::class, 'getEleveCantine'])->name('reglements.eleve_cantine_data');
    Route::post('/store-paiement-cantine', [CantineController::class, 'store'])->name('reglements.store_paiement_cantine');
    
    // Transport
    Route::get('/eleve-transport-data', [TransportController::class, 'getEleveTransport'])->name('reglements.eleve_transport_data');
    Route::post('/store-paiement-transport', [TransportController::class, 'store'])->name('reglements.store_paiement_transport');
    
    // Suppression (commune)
    Route::delete('/delete-paiement', [CantineController::class, 'deletePaiement'])->name('reglements.delete_paiement');
});

// Routes pour Cantine
Route::prefix('cantine')->group(function () {
    Route::get('/', [CantineController::class, 'index'])->name('cantine.index');
    Route::get('/mois-a-payer', [CantineController::class, 'getMoisAPayer'])->name('cantine.mois_a_payer');
    Route::post('/paiement-mensuel', [CantineController::class, 'storePaiementMensuel'])->name('cantine.store_paiement_mensuel');
    Route::get('/receipt/{id}', [CantineController::class, 'generateReceipt'])->name('cantine.receipt');
    Route::delete('/paiement', [CantineController::class, 'deletePaiement'])->name('cantine.delete_paiement');
});

// Routes pour Transport
Route::prefix('transport')->group(function () {
    Route::get('/', [TransportController::class, 'index'])->name('transport.index');
    //Route::post('/reglements/store-paiement-transport', [TransportController::class, 'store'])->name('reglements.store_paiement_transport');
    Route::get('/eleves-by-classe-transport', [TransportController::class, 'elevesByClasseTransport'])->name('eleves.by_classe_transport');
    Route::get('/mois-a-payer', [TransportController::class, 'getMoisAPayer'])->name('transport.mois_a_payer');
    Route::post('/paiement-mensuel', [TransportController::class, 'storePaiementMensuel'])->name('transport.store_paiement_mensuel');
    Route::get('/receipt/{id}', [TransportController::class, 'generateReceipt'])->name('transport.receipt');
    Route::post('/update-transport-type', [TransportController::class, 'updateTransportType'])->name('transport.update_transport_type');
    Route::delete('/paiement', [TransportController::class, 'deletePaiement'])->name('transport.delete_paiement');
});

Route::prefix('scolarite')->group(function() {
    Route::get('/', [ScolariteController::class, 'index'])->name('scolarite.index');

    // Route resource pour les tarifs annuels
    Route::resource('tarifs', TarifScolariteController::class);

    // Route resource pour les tarifs mensuels (sans show)
    Route::resource('tarifs-mensuels', TarifMensuelController::class)->except(['show']);

    // Routes personnalisées pour les tarifs mensuels
    Route::get('/tarifs-mensuels/get-tarifs', [TarifMensuelController::class, 'getTarifsMensuels'])->name('tarifs-mensuels.get-tarifs');
    
});
        
        Route::get('/eleves/export', [EleveController::class, 'export'])->name('eleves.export');

        Route::resource('eleves', EleveController::class);

        // Routes pour les réinscriptions - CORRIGÉES
        Route::prefix('reinscriptions')->group(function () {
            // Routes AJAX - doivent être AVANT la route resource
            Route::get('/get-classes-by-annee', [ReinscriptionController::class, 'getClassesByAnnee'])->name('reinscriptions.getClassesByAnnee');
            Route::get('/eleves-by-classe/{classe}', [ReinscriptionController::class, 'getElevesByClasse'])->name('reinscriptions.elevesByClasse');
            
            // Route resource (gère index, store, show, edit, update, destroy)
            Route::get('/', [ReinscriptionController::class, 'index'])->name('reinscriptions.index');
            Route::post('/', [ReinscriptionController::class, 'store'])->name('reinscriptions.store');
            Route::get('/{id}', [ReinscriptionController::class, 'show'])->name('reinscriptions.show');
            Route::get('/{id}/edit', [ReinscriptionController::class, 'edit'])->name('reinscriptions.edit');
            Route::put('/{id}', [ReinscriptionController::class, 'update'])->name('reinscriptions.update');
            Route::delete('/{id}', [ReinscriptionController::class, 'destroy'])->name('reinscriptions.destroy');
        });
    
        // Pour réinscrire un élève spécifique enirdepuis sa fiche
        Route::get('eleves/{eleve}/reinscrire', [ReinscriptionController::class, 'create'])->name('eleves.reinscrire');

        Route::resource('preinscriptions', PreInscriptionController::class);
        Route::post('preinscriptions/{preinscription}/valider', [PreInscriptionController::class, 'valider'])->name('preinscriptions.valider');
        Route::post('preinscriptions/{preinscription}/refuser', [PreInscriptionController::class, 'refuser'])->name('preinscriptions.refuser');

        Route::get('/notes/inscriptions-by-classe', [NoteController::class, 'getInscriptionsByClasse'])->name('notes.inscriptions_by_classe');
        Route::get('/notes/matieres-by-classe', [NoteController::class, 'getMatieresByClasse'])->name('notes.matieres_by_classe');
        Route::get('/notes/by-classe', [NoteController::class, 'getNotesByClasse'])->name('notes.byClasse');
        Route::get('/notes/generate-bulletin', [NoteController::class, 'generateBulletin'])->name('notes.generateBulletin');
        Route::get('/notes/filter-by-classe', [NoteController::class, 'filterByClasse'])->name('notes.filterByClasse');


        Route::get('/notes/generate-fiches-moyennes', [NoteController::class, 'generateFichesMoyennes'])->name('notes.generateFichesMoyennes');
        Route::get('/notes/recap/pdf', [NoteController::class, 'generateRecapMoyennes'])->name('notes.recap.pdf');


        Route::get('notes/generate-bulletin-annuel', [NoteController::class, 'generateBulletinAnnuel'])->name('notes.generateBulletinAnnuel');

        Route::resource('notes', NoteController::class);

        Route::get('/notes/check-existing-mois-moyenne', [NoteController::class, 'checkExistingMoisMoyenne'])->name('notes.checkExistingMoisMoyenne');


        // Routes pour les documents
        Route::get('/documents/inscriptions', [DocumentController::class, 'inscriptions'])->name('documents.inscriptions');
        Route::get('/documents/certificats-scolarite', [DocumentController::class, 'certificatsScolarite'])->name('documents.certificats-scolarite');
        Route::get('/documents/fiches-presence', [DocumentController::class, 'fichesPresence'])->name('documents.fiches-presence');
        Route::get('/documents/fiches-frequentation', [DocumentController::class, 'fichesFrequentation'])->name('documents.fiches-frequentation');

        Route::get('/documents/generer-fiche-inscription/{eleve}', [DocumentController::class, 'genererFicheInscription'])->name('documents.generer-fiche-inscription');
        Route::get('/documents/generer-certificat-scolarite/{eleve}', [DocumentController::class, 'genererCertificatScolarite'])->name('documents.generer-certificat-scolarite');
        Route::get('/documents/generer-fiche-presence/{classe}', [DocumentController::class, 'genererFichePresence'])->name('documents.generer-fiche-presence');
        Route::get('/documents/generer-fiche-frequentation/{eleve}', [DocumentController::class, 'genererFicheFrequentation'])->name('documents.generer-fiche-frequentation');
        
        // Routes pour les tableaux d'honneur
        Route::get('/tableaux-honneur', [TableauHonneurController::class, 'index'])->name('tableaux-honneur.index');
        Route::get('/tableaux-honneur/generer-mensuel', [TableauHonneurController::class, 'genererMensuel'])->name('tableaux-honneur.generer-mensuel');
        Route::get('/tableaux-honneur/generer-annuel', [TableauHonneurController::class, 'genererAnnuel'])->name('tableaux-honneur.generer-annuel');
        Route::get('/tableaux-honneur/generer-major', [TableauHonneurController::class, 'genererMajor'])->name('tableaux-honneur.generer-major');

        // Routes pour les parchemins
        Route::get('/parchemin', [ParcheminController::class, 'index'])->name('parchemin.index');
        Route::get('/parchemin/generer', [ParcheminController::class, 'generer'])->name('parchemin.generer');



        // Routes pour le journal des paiements
        Route::prefix('journal-paiements')->group(function () {
            Route::get('/', [JournalCaisseController::class, 'index'])->name('journal-paiements.index');
            Route::get('/data', [JournalCaisseController::class, 'getData'])->name('journal-paiements.data');
            Route::post('/', [JournalCaisseController::class, 'store'])->name('journal-paiements.store');
            Route::put('/{paiement}', [JournalCaisseController::class, 'update'])->name('journal-paiements.update');
            Route::delete('/{paiement}', [JournalCaisseController::class, 'destroy'])->name('journal-paiements.destroy');
            Route::get('/{paiement}', [JournalCaisseController::class, 'show'])->name('journal-paiements.show');
            Route::get('/inscriptions-by-classe', [JournalCaisseController::class, 'getInscriptionsByClasse'])->name('journal-paiements.inscriptions-by-classe');
        });

        // Route API pour récupérer les élèves par classe
        Route::get('/api/eleves-par-classe/{classe}', function($classeId) {
            $eleves = Eleve::where('classe_id', $classeId)
                ->orderBy('nom')
                    >get(['id', 'nom', 'prenom', 'matricule']);
                
            return response()->json($eleves->map(function($eleve) {
                return [
                    'id' => $eleve->id,
                    'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                    'matricule' => $eleve->matricule
                ];
            }));
        });

            

       // Routes pour les règlements
        Route::prefix('reglements')->group(function () {
            Route::get('/', [ReglementController::class, 'index'])->name('reglements.index');
            Route::post('/store-paiement', [ReglementController::class, 'storePaiement'])->name('reglements.store_paiement');
            Route::delete('/delete-paiement', [ReglementController::class, 'deletePaiement'])->name('reglements.delete_paiement');

            Route::get('/receipt/{paiement}', [ReglementController::class, 'generateReceipt']) ->name('reglements.receipt');
        });

        // Routes pour les bilans
Route::prefix('bilans')->name('bilans.')->middleware(['auth'])->group(function () {
    Route::get('/scolaire', [BilanScolaireController::class, 'index'])->name('scolaire');
    Route::get('/financier', [BilanFinancierController::class, 'index'])->name('financier');
    Route::post('/scolaire/export', [BilanScolaireController::class, 'export'])->name('scolaire.export');
    Route::post('/financier/export', [BilanFinancierController::class, 'export'])->name('financier.export');
});


Route::prefix('reductions')->group(function () {
    Route::get('/', [ReductionController::class, 'index'])->name('reductions.index');
    Route::get('/get-eleve-data', [ReductionController::class, 'getEleveData'])->name('reductions.get_eleve_data');
    Route::post('/store', [ReductionController::class, 'store'])->name('reductions.store');
    Route::delete('/{id}', [ReductionController::class, 'destroy'])->name('reductions.destroy');
    Route::post('/update-transport-tarif', [ReductionController::class, 'updateTransportTarif'])->name('reductions.update_transport_tarif');
    Route::post('/update-cantine-tarif', [ReductionController::class, 'updateCantineTarif'])->name('reductions.update_cantine_tarif');
});

// Routes pour Cantine
Route::prefix('cantine')->group(function () {
    Route::get('/', [CantineController::class, 'index'])->name('cantine.index');
    //Route::get('/eleves', [CantineController::class, 'elevesByClasseCantine'])->name('eleves.by_classe_cantine');
    Route::get('/mois-a-payer', [CantineController::class, 'getMoisAPayer'])->name('cantine.mois_a_payer');
    Route::post('/paiement-mensuel', [CantineController::class, 'storePaiementMensuel'])->name('cantine.store_paiement_mensuel');
    Route::get('/receipt/{id}', [CantineController::class, 'generateReceipt'])->name('cantine.receipt');
    Route::delete('/paiement', [CantineController::class, 'deletePaiement'])->name('cantine.delete_paiement');
});

// Routes pour Transport
Route::prefix('transport')->group(function () {
    Route::get('/', [TransportController::class, 'index'])->name('transport.index');

    Route::get('/gestion', [TransportController::class, 'GestionTransport'])->name('transport.gestion');
   // Route::get('/eleves', [TransportController::class, 'elevesByClasseTransport'])->name('eleves.by_classe_transport');
    Route::get('/mois-a-payer', [TransportController::class, 'getMoisAPayer'])->name('transport.mois_a_payer');
    Route::post('/paiement-mensuel', [TransportController::class, 'storePaiementMensuel'])->name('transport.store_paiement_mensuel');
    Route::get('/receipt/{id}', [TransportController::class, 'generateReceipt'])->name('transport.receipt');
    Route::delete('/paiement', [TransportController::class, 'deletePaiement'])->name('transport.delete_paiement');
});
        // Routes pour la gestion des dépenses de scolarité
        Route::prefix('depenses')->group(function () {
            Route::get('/', [DepenseController::class, 'index'])->name('depenses.index');
            Route::get('/data', [DepenseController::class, 'getDepensesData'])->name('depenses.data');
            Route::post('/', [DepenseController::class, 'store'])->name('depenses.store');
            Route::get('/{id}', [DepenseController::class, 'show'])->name('depenses.show');

            Route::put('/{id}', [DepenseController::class, 'update'])->name('depenses.update');
            Route::delete('/{id}', [DepenseController::class, 'destroy'])->name('depenses.destroy');
        });
        

        // Routes pour la relance
        Route::prefix('relance')->group(function () {
            Route::get('/', [RelanceController::class, 'index'])->name('relance.index');
            Route::get('/data', [RelanceController::class, 'getRelanceData'])->name('relance.data');
            Route::get('/imprimer', [RelanceController::class, 'imprimerRelance'])->name('relance.imprimer');
            Route::get('/export', [RelanceController::class, 'export'])->name('relance.export');
            Route::post('/send-sms', [RelanceController::class, 'sendSms'])->name('relance.send.sms');

        });


        // Routes pour le profil utilisateur
Route::get('/profile', [UserController::class, 'profile'])->name('profile');
Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

// Routes pour la gestion des utilisateurs
Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
Route::resource('users', UserController::class);




    });


   