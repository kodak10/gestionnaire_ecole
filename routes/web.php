<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CantineController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\CritereNotationController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\EcoleController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\FraisScolariteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JournalCaisseController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ParametrageScolariteController;
use App\Http\Controllers\PreInscriptionController;
use App\Http\Controllers\ReglementController;
use App\Http\Controllers\ReinscriptionController;
use App\Http\Controllers\RelanceController;
use App\Http\Controllers\ScolariteController;
use App\Http\Controllers\TarifMensuelController;
use App\Http\Controllers\TarifScolariteController;
use App\Http\Controllers\TransportController;
use App\Models\Eleve;
use Illuminate\Support\Facades\Route;






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

    // Auth::routes();

    // Route par défaut
    Route::get('/', function () {
        return redirect('/login');
    });

    // Routes protégées
    Route::middleware(['auth', 'EcoleAnnee.status'])->group(function () {
        Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
        

        Route::prefix('parametrages')->group(function() {
            Route::get('/ecole', [EcoleController::class, 'index'])->name('ecoles.index');
            Route::put('/ecole', [EcoleController::class, 'update'])->name('ecoles.update');

            Route::resource('classes', ClasseController::class);
            Route::get('classes/export/{type}', [ClasseController::class, 'export'])->name('classes.export');

            Route::resource('matieres', MatiereController::class);
            Route::post('/classes/assign-matieres', [MatiereController::class, 'assignMatieres'])->name('classes.matieres.assign');
            Route::get('/classes/{id}/matieres', [MatiereController::class, 'getMatieres'])->name('classes.matieres.get');

            Route::resource('mentions', MentionController::class);

        });

        // Pour récupérer les élèves d'une classe via AJAX
        Route::get('/eleves/by-classe', [ScolariteController::class, 'getElevesByClasse'])->name('eleves.by_classe');
        Route::get('/eleve_data', [ReglementController::class, 'eleveData'])->name('reglements.eleve_data');

        // Routes pour la gestion de la cantine
        Route::get('/eleves-by-classe-cantine', [CantineController::class, 'elevesByClasseCantine'])->name('eleves.by_classe_cantine');
        Route::get('/reglements/eleve-cantine-data', [CantineController::class, 'getEleveCantine'])->name('reglements.eleve_cantine_data');
        Route::post('/reglements/store-paiement-cantine', [CantineController::class, 'store'])->name('reglements.store_paiement_cantine');

        // Routes pour la gestion de la transport
        Route::get('/eleves-by-classe-transport', [TransportController::class, 'elevesByClasseTransport'])->name('eleves.by_classe_transport');
        Route::get('/reglements/eleve-transport-data', [TransportController::class, 'getEleveTransport'])->name('reglements.eleve_transport_data');
        Route::post('/reglements/store-paiement-transport', [TransportController::class, 'store'])->name('reglements.store_paiement_transport');




        Route::prefix('scolarite')->group(function() {
            Route::get('/', [ScolariteController::class, 'index'])->name('scolarite.index');

            Route::resource('tarifs', TarifScolariteController::class);
            Route::resource('tarifs-mensuels', TarifMensuelController::class)->except(['show']);

            Route::post('/tarifs-mensuels/check-existing', [TarifMensuelController::class, 'checkExistingTarif'])->name('tarifs-mensuels.check-existing');
            Route::get('/tarifs-mensuels/get-tarifs', [TarifMensuelController::class, 'getTarifsByTypeAndNiveau'])->name('tarifs-mensuels.get-tarifs');
            Route::get('/tarifs-mensuels/niveaux-by-type', [TarifMensuelController::class, 'getNiveauxByTypeFrais'])->name('tarifs-mensuels.niveaux-by-type');
            Route::post('/tarifs-mensuels/sync-filters', [TarifMensuelController::class, 'syncFilters'])->name('tarifs-mensuels.sync-filters');
            Route::get('/tarifs-mensuels/all-niveaux', [TarifMensuelController::class, 'getAllNiveauxWithTarifs'])->name('tarifs-mensuels.all-niveaux');
        

            Route::get('/eleve-paiements', [ScolariteController::class, 'getElevePaiements'])->name('paiements.eleve_data');
            Route::post('/store-paiement', [ScolariteController::class, 'storePaiement'])->name('paiements.store');
            Route::post('/apply-reduction', [ScolariteController::class, 'applyReduction'])->name('eleves.apply_reduction');
            Route::get('/print/{eleveId}/{anneeId}', [ScolariteController::class, 'printScolarite'])->name('scolarite.print');
            Route::get('/receipt/{paiementId}', [ScolariteController::class, 'generateReceipt'])->name('scolarite.receipt');
            Route::delete('/paiements/{paiement}', [ScolariteController::class, 'destroyPaiement'])->name('paiements.destroy');
            
        });


        

        Route::resource('eleves', EleveController::class);
        Route::get('reinscriptions/eleves-by-classe/{classe}', [ReinscriptionController::class, 'getElevesByClasse'])->name('reinscriptions.eleves-by-classe');

        Route::resource('reinscriptions', ReinscriptionController::class);




        // Pour réinscrire un élève spécifique enirdepuis sa fiche
        Route::get('eleves/{eleve}/reinscrire', [ReinscriptionController::class, 'create'])->name('eleves.reinscrire');


        Route::resource('preinscriptions', PreInscriptionController::class);
        Route::post('preinscriptions/{preinscription}/valider', [PreInscriptionController::class, 'valider'])->name('preinscriptions.valider');
        Route::post('preinscriptions/{preinscription}/refuser', [PreInscriptionController::class, 'refuser'])->name('preinscriptions.refuser');


        Route::resource('notes', NoteController::class);
        Route::get('/notes/inscriptions-by-classe', [NoteController::class, 'getInscriptionsByClasse'])->name('notes.inscriptions_by_classe');

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
            Route::get('/receipt/{paiementId}', [ReglementController::class, 'receipt'])->name('reglements.receipt');
            // Route::get('/eleve-data', [ReglementController::class, 'eleveData'])->name('reglements.eleve_data');
            Route::post('/store-paiement', [ReglementController::class, 'storePaiement'])->name('reglements.store_paiement');
            Route::delete('/delete-paiement', [ReglementController::class, 'deletePaiement'])->name('reglements.delete_paiement');
        });

        // Route pour charger les élèves par classe
        // Route::get('/eleves/by-classe', [ReglementController::class, 'elevesByClasse'])->name('eleves.by_classe');

        

                    // Routes pour la gestion des règlements 
        Route::prefix('cantine')->group(function() {

            Route::get('/', [CantineController::class, 'index'])->name('cantine.index');

            // Récupérer les élèves d'une classe
            // Route::get('/eleves/by-classe', [CantineController::class, 'getElevesByClasse'])->name('cantine.eleves.by_classe');

            // Récupérer les données de scolarité d'un élève
            Route::get('/eleve_data', [CantineController::class, 'getEleveScolarite'])->name('cantine.eleve_data');

            // Enregistrer un paiement
            Route::post('/store_paiement', [CantineController::class, 'storePaiement'])->name('cantine.store_paiement');

            // Appliquer une réduction
            Route::post('/apply_reduction', [CantineController::class, 'applyReduction'])->name('cantine.apply_reduction');

            // Supprimer un paiement
            Route::delete('/paiement/delete', [CantineController::class, 'delete'])->name('cantine.delete_paiement');

            // Générer le reçu
            Route::get('/receipt/{paiement}', [CantineController::class, 'generateReceipt'])->name('cantine.receipt');

            // Imprimer la scolarité d'un élève
            Route::get('/print/{eleve}/{annee}', [CantineController::class, 'printScolarite'])->name('cantine.print');

        });


        // Routes pour la gestion des règlements 
        Route::prefix('transport')->group(function() {

            Route::get('/', [TransportController::class, 'index'])->name('transport.index');

            // Récupérer les élèves d'une classe
            // Route::get('/eleves/by-classe', [TransportController::class, 'getElevesByClasse'])->name('transport.eleves.by_classe');

            // Récupérer les données de scolarité d'un élève
            Route::get('/eleve_data', [TransportController::class, 'getEleveScolarite'])->name('transport.eleve_data');

            // Enregistrer un paiement
            Route::post('/store_paiement', [TransportController::class, 'storePaiement'])->name('transport.store_paiement');

            // Appliquer une réduction
            Route::post('/apply_reduction', [TransportController::class, 'applyReduction'])->name('transport.apply_reduction');

            // Supprimer un paiement
            Route::delete('/paiement/delete', [TransportController::class, 'delete'])->name('transport.delete_paiement');

            // Générer le reçu
            Route::get('/receipt/{paiement}', [TransportController::class, 'generateReceipt'])->name('transport.receipt');

            // Imprimer la scolarité d'un élève
            Route::get('/print/{eleve}/{annee}', [TransportController::class, 'printScolarite'])->name('transport.print');

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
        });


    });


   