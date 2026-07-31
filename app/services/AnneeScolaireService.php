<?php
// app/Services/AnneeScolaireService.php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnneeScolaireService
{

     /**
     * Formater le suffixe pour les tables (uniquement l'année)
     */
    public function formatSuffix(string $annee): string
    {
        return str_replace('-', '_', $annee);
    }

    /**
     * Créer toutes les tables pour une année scolaire
     * SANS TRANSACTION - La transaction est gérée par le contrôleur
     */
    public function createTablesForYear(string $annee): array
    {
        $suffix = $this->formatSuffix($annee);
        $tablesCrees = [];
        
        try {
            Log::info('🚀 DÉBUT CRÉATION TABLES', ['annee' => $annee, 'suffix' => $suffix]);
            
            // 1. Tables de référence
            Log::info('📋 1/5 Création des tables de référence...');
            $this->createNiveauxTable($suffix);
            $tablesCrees[] = 'niveaux_' . $suffix;
            
            $this->createMatieresTable($suffix);
            $tablesCrees[] = 'matieres_' . $suffix;
                        
            // 2. Tables de liaison
            Log::info('📋 2/5 Création des tables de liaison...');
            $this->createClassesTable($suffix);
            $tablesCrees[] = 'classes_' . $suffix;
            
            $this->createTarifsTable($suffix);
            $tablesCrees[] = 'tarifs_' . $suffix;
            
            $this->createNiveauMatiereTable($suffix);
            $tablesCrees[] = 'niveau_matiere_' . $suffix;
            
            $this->createMentionsTable($suffix);
            $tablesCrees[] = 'mentions_' . $suffix;
            
            $this->createDepenseCategoriesTable($suffix);
            $tablesCrees[] = 'depense_categories_' . $suffix;
            
            // 3. Tables principales
            Log::info('📋 3/5 Création des tables principales...');
            $this->createTarifsMensuelsTable($suffix);
            $tablesCrees[] = 'tarifs_mensuels_' . $suffix;
            
            $this->createElevesTable($suffix);
            $tablesCrees[] = 'eleves_' . $suffix;
            
            // 4. Tables de paiement
            Log::info('📋 4/5 Création des tables de paiement...');
            $this->createPaiementsTable($suffix);
            $tablesCrees[] = 'paiements_' . $suffix;
            
            $this->createPaiementDetailsTable($suffix);
            $tablesCrees[] = 'paiement_details_' . $suffix;
            
            $this->createReductionsTable($suffix);
            $tablesCrees[] = 'reductions_' . $suffix;
            
            // 5. Tables de notes et moyennes
            Log::info('📋 5/5 Création des tables de notes et moyennes...');
            $this->createNotesTable($suffix);
            $tablesCrees[] = 'notes_' . $suffix;
            
            $this->createMoyenneGeneraleTable($suffix);
            $tablesCrees[] = 'moyenne_generale_' . $suffix;
            
            $this->createMoyenneMoisTable($suffix);
            $tablesCrees[] = 'moyenne_mois_' . $suffix;
            
            $this->createDepensesTable($suffix);
            $tablesCrees[] = 'depenses_' . $suffix;
            
            Log::info('✅ SUCCÈS: Toutes les tables ont été créées', [
                'annee' => $annee,
                'suffix' => $suffix,
                'tables_crees' => $tablesCrees
            ]);
            
            return [
                'success' => true,
                'message' => 'Tables créées avec succès',
                'suffix' => $suffix,
                'tables' => $tablesCrees
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRÉATION TABLES', [
                'annee' => $annee,
                'suffix' => $suffix,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tables_deja_crees' => $tablesCrees
            ]);
            
            // On retourne l'erreur pour que le contrôleur gère le rollback
            return [
                'success' => false,
                'message' => 'Erreur lors de la création des tables: ' . $e->getMessage(),
                'suffix' => $suffix,
                'tables_crees' => $tablesCrees
            ];
        }
    }

    /**
     * Supprimer les tables de force
     */
    public function forceDropTables(string $suffix): void
    {
        Log::warning('🧹 Suppression forcée des tables', ['suffix' => $suffix]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = [
            'depenses_' . $suffix,
            'moyenne_mois_' . $suffix,
            'moyenne_generale_' . $suffix,
            'notes_' . $suffix,
            'reductions_' . $suffix,
            'paiement_details_' . $suffix,
            'paiements_' . $suffix,
            'eleves_' . $suffix,
            'tarifs_mensuels_' . $suffix,
            'depense_categories_' . $suffix,
            'mentions_' . $suffix,
            'niveau_matiere_' . $suffix,
            'tarifs_' . $suffix,
            'classes_' . $suffix,
            'type_frais_' . $suffix,
            'matieres_' . $suffix,
            'niveaux_' . $suffix,
        ];
        
        foreach (array_reverse($tables) as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                    Log::debug('Table supprimée', ['table' => $table]);
                } catch (\Exception $e) {
                    Log::warning('Erreur suppression table', [
                        'table' => $table,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Récupérer la liste des tables créées
     */
    private function getCreatedTablesList(string $suffix): array
    {
        return [
            'niveaux_' . $suffix,
            'matieres_' . $suffix,
            'type_frais_' . $suffix,
            'classes_' . $suffix,
            'tarifs_' . $suffix,
            'niveau_matiere_' . $suffix,
            'mentions_' . $suffix,
            'depense_categories_' . $suffix,
            'tarifs_mensuels_' . $suffix,
            'eleves_' . $suffix,
            'paiements_' . $suffix,
            'paiement_details_' . $suffix,
            'reductions_' . $suffix,
            'notes_' . $suffix,
            'moyenne_generale_' . $suffix,
            'moyenne_mois_' . $suffix,
            'depenses_' . $suffix,
        ];
    }

    
    /**
     * Créer la table des niveaux
     */
    private function createNiveauxTable(string $suffix): void
    {
        $tableName = 'niveaux_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
        });
    }

    /**
     * Créer la table des matières
     */
    private function createMatieresTable(string $suffix): void
    {
        $tableName = 'matieres_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($suffix) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux_' . $suffix)->cascadeOnDelete();
            $table->string('nom');
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
            $table->index(['niveau_id'], 'idx_niveau');
            $table->unique(['nom', 'ecole_id', 'niveau_id'], 'uq_nom_ecole_niveau');
        });
    }

    /**
     * Créer la table des types de frais
     */
    private function createTypeFraisTable(string $suffix): void
    {
        $tableName = 'type_frais_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->boolean('obligatoire')->default(false);
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
            $table->unique(['nom', 'ecole_id', 'annee_scolaire_id'], 'uq_nom_ecole_annee');
        });
    }

    /**
     * Créer la table des classes
     */
    private function createClassesTable(string $suffix): void
    {
        $tableName = 'classes_' . $suffix;
        $niveauxTable = 'niveaux_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($niveauxTable) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained($niveauxTable)->cascadeOnDelete();
            $table->string('nom');
            $table->unsignedInteger('capacite')->default(50);
            $table->decimal('moy_base', 10, 2)->default(20.00);
            $table->foreignId('enseignant_id')->nullable()->constrained('enseignants')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
            $table->index(['niveau_id'], 'idx_niveau');
        });
    }

    /**
     * Créer la table des tarifs
     */
    private function createTarifsTable(string $suffix): void
    {
        $tableName = 'tarifs_' . $suffix;
        $typeFraisTable = 'type_frais';
        $niveauxTable = 'niveaux_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($typeFraisTable, $niveauxTable) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('type_frais_id')->constrained($typeFraisTable)->cascadeOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained($niveauxTable)->nullOnDelete();
            $table->string('libelle')->nullable();
            $table->boolean('obligatoire')->default(false);
            $table->decimal('montant', 10, 2);
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
            $table->index(['type_frais_id'], 'idx_type_frais');
            $table->index(['niveau_id'], 'idx_niveau');
        });
    }

    /**
     * Créer la table niveau_matiere
     */
    private function createNiveauMatiereTable(string $suffix): void
    {
        $tableName = 'niveau_matiere_' . $suffix;
        $niveauxTable = 'niveaux_' . $suffix;
        $matieresTable = 'matieres_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($niveauxTable, $matieresTable) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained($niveauxTable)->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained($matieresTable)->cascadeOnDelete();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->integer('ordre')->default(0);
            $table->integer('denominateur')->default(20);
            $table->timestamps();
            
            $table->unique(['niveau_id', 'matiere_id'], 'uq_niveau_matiere');
            $table->index(['ecole_id'], 'idx_ecole');
            $table->index(['annee_scolaire_id'], 'idx_annee');
        });
    }

    /**
     * Créer la table des mentions
     */
    private function createMentionsTable(string $suffix): void
    {
        $tableName = 'mentions_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->integer('min_note')->nullable();
            $table->integer('max_note')->nullable();
            $table->timestamps();
            
            $table->unique(['nom', 'ecole_id', 'annee_scolaire_id'], 'uq_nom_ecole_annee');
            $table->index(['ecole_id'], 'idx_ecole');
        });
    }

    /**
     * Créer la table des catégories de dépenses
     */
    private function createDepenseCategoriesTable(string $suffix): void
    {
        $tableName = 'depense_categories_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->timestamps();
            
            $table->unique(['nom', 'ecole_id', 'annee_scolaire_id'], 'uq_nom_ecole_annee');
            $table->index(['ecole_id'], 'idx_ecole');
        });
    }

    /**
     * Créer la table des tarifs mensuels
     */
    private function createTarifsMensuelsTable(string $suffix): void
    {
        $tableName = 'tarifs_mensuels_' . $suffix;
        $tarifsTable = 'tarifs_' . $suffix;
        $niveauxTable = 'niveaux_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($tarifsTable, $niveauxTable) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('tarif_id')->nullable()->constrained($tarifsTable)->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained($niveauxTable)->nullOnDelete();
            $table->foreignId('mois_id')->constrained('mois_scolaires')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->timestamps();
            
            $table->unique(['tarif_id', 'niveau_id', 'mois_id', 'ecole_id'], 'uq_tarif_niveau_mois_ecole');
            $table->index(['ecole_id'], 'idx_ecole');
        });
    }

    /**
     * Créer la table des élèves
     */
    private function createElevesTable(string $suffix): void
    {
        $tableName = 'eleves_' . $suffix;
        $tarifsTable = 'tarifs_' . $suffix;
        $classesTable = 'classes_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($tarifsTable, $classesTable) {
            $table->id();
            
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained($classesTable)->nullOnDelete();
            
            $table->string('matricule')->unique();
            $table->string('code_national')->nullable()->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['Masculin', 'Féminin']);
            $table->date('naissance');
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable()->default('Ivoirienne');
            $table->string('num_extrait')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('infos_medicales')->nullable();
            
            $table->string('parent_nom');
            $table->string('parent_telephone');
            $table->string('parent_telephone02')->nullable();
            $table->string('parent_email')->nullable();
            
            $table->string('pere_nom')->nullable();
            $table->string('pere_contact')->nullable();
            $table->string('pere_contact02')->nullable();
            
            $table->string('mere_nom')->nullable();
            $table->string('mere_contact')->nullable();
            $table->string('mere_contact02')->nullable();
            
            $table->string('parent_adresse')->nullable();
            
            $table->boolean('transport_active')->default(false);
            $table->foreignId('transport_tarif_id')->nullable()->constrained($tarifsTable)->nullOnDelete();
            $table->date('transport_start_date')->nullable();
            
            $table->boolean('cantine_active')->default(false);
            $table->foreignId('cantine_tarif_id')->nullable()->constrained($tarifsTable)->nullOnDelete();
            $table->date('cantine_start_date')->nullable();
            
            $table->string('statut')->default('active');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['classe_id'], 'idx_classe');
            $table->index(['nom', 'prenom'], 'idx_nom_prenom');
            $table->index(['statut'], 'idx_statut');
            $table->index(['is_active'], 'idx_active');
            
            $table->unique(['ecole_id', 'matricule'], 'uq_matricule_ecole');
        });
    }

    /**
     * Créer la table des paiements
     */
    private function createPaiementsTable(string $suffix): void
    {
        $tableName = 'paiements_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained($elevesTable)->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement');
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['eleve_id'], 'idx_eleve');
            $table->index(['created_at'], 'idx_created');
        });
    }

    /**
     * Créer la table des détails de paiement
     */
    private function createPaiementDetailsTable(string $suffix): void
    {
        $tableName = 'paiement_details_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        $paiementsTable = 'paiements_' . $suffix;
        $tarifsTable = 'tarifs_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable, $paiementsTable, $tarifsTable) {
            $table->id();
            $table->foreignId('paiement_id')->constrained($paiementsTable)->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained($elevesTable)->cascadeOnDelete();
            $table->foreignId('tarif_id')->nullable()->constrained($tarifsTable)->nullOnDelete();
            $table->decimal('montant', 10, 2);
            $table->timestamps();
            
            $table->unique(['paiement_id', 'eleve_id', 'tarif_id'], 'uq_paiement_eleve_tarif');
            $table->index(['eleve_id'], 'idx_eleve');
        });
    }

    /**
     * Créer la table des réductions
     */
    private function createReductionsTable(string $suffix): void
    {
        $tableName = 'reductions_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        $tarifsTable = 'tarifs_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable, $tarifsTable) {
            $table->id();
            
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained($elevesTable)->cascadeOnDelete();
            $table->foreignId('tarif_id')->nullable()->constrained($tarifsTable)->nullOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('raison')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['eleve_id', 'tarif_id', 'annee_scolaire_id'], 'uq_eleve_tarif_annee');
            $table->index(['eleve_id'], 'idx_eleve');
        });
    }

    /**
     * Créer la table des notes
     */
    private function createNotesTable(string $suffix): void
    {
        $tableName = 'notes_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        $classesTable = 'classes_' . $suffix;
        $matieresTable = 'matieres_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable, $classesTable, $matieresTable) {
            $table->id();
            
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained($elevesTable)->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained($classesTable)->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained($matieresTable)->cascadeOnDelete();
            $table->foreignId('mois_id')->constrained('mois_scolaires')->cascadeOnDelete();
            
            $table->decimal('valeur', 5, 2)->nullable();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->text('appreciation')->nullable();
            
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(
                ['eleve_id', 'matiere_id', 'mois_id', 'annee_scolaire_id', 'ecole_id'],
                'uq_note_unique'
            );
            
            $table->index(['eleve_id', 'mois_id'], 'idx_eleve_mois');
        });
    }

    /**
     * Créer la table des moyennes générales
     */
    private function createMoyenneGeneraleTable(string $suffix): void
    {
        $tableName = 'moyenne_generale_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        $classesTable = 'classes_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable, $classesTable) {
            $table->id();
            $table->foreignId('eleve_id')->constrained($elevesTable)->restrictOnDelete();
            $table->foreignId('classe_id')->constrained($classesTable)->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->restrictOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->restrictOnDelete();
            
            $table->json('moyennes_par_mois')->nullable();
            $table->json('rangs_par_mois')->nullable();
            $table->json('moyennes_par_matiere')->nullable();
            $table->json('rangs_par_matiere')->nullable();
            $table->json('details_notes')->nullable();
            $table->json('mois_selectionnes')->nullable();
            $table->json('mois_coefficients')->nullable();
            
            $table->decimal('moyenne_annuelle', 10, 2)->nullable();
            $table->integer('rang_general')->nullable();
            $table->boolean('exaequo')->default(false);
            $table->text('appreciation_generale')->nullable();
            $table->string('decision')->nullable();
            $table->json('distinctions')->nullable();
            $table->json('sanctions')->nullable();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamps();
            
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id'], 'idx_ecole_annee_classe');
            $table->index(['eleve_id'], 'idx_eleve');
            $table->index(['classe_id', 'moyenne_annuelle'], 'idx_classe_moyenne');
            
            $table->unique(
                ['eleve_id', 'classe_id', 'annee_scolaire_id'], 
                'uq_eleve_classe_annee'
            );
        });
    }

    /**
     * Créer la table des moyennes par mois
     */
    private function createMoyenneMoisTable(string $suffix): void
    {
        $tableName = 'moyenne_mois_' . $suffix;
        $elevesTable = 'eleves_' . $suffix;
        $classesTable = 'classes_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($elevesTable, $classesTable) {
            $table->id();
            $table->foreignId('eleve_id')->constrained($elevesTable)->restrictOnDelete();
            $table->foreignId('classe_id')->constrained($classesTable)->restrictOnDelete();
            $table->foreignId('mois_id')->constrained('mois_scolaires')->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->restrictOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->restrictOnDelete();
            
            $table->decimal('moyenne', 10, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->boolean('exaequo')->default(false);
            $table->text('appreciation')->nullable();
            $table->json('details_notes')->nullable();
            $table->decimal('moyenne_classe', 10, 2)->nullable();
            $table->decimal('moyenne_min', 10, 2)->nullable();
            $table->decimal('moyenne_max', 10, 2)->nullable();
            $table->integer('effectif_classe')->nullable();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_generation')->nullable();
            $table->timestamps();
            
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id', 'mois_id'], 'idx_ecole_annee_classe_mois');
            $table->index(['eleve_id', 'mois_id'], 'idx_eleve_mois');
            $table->index(['classe_id', 'mois_id', 'moyenne'], 'idx_classe_mois_moyenne');
            
            $table->unique(
                ['eleve_id', 'classe_id', 'mois_id', 'annee_scolaire_id'], 
                'uq_eleve_classe_mois_annee'
            );
        });
    }

    /**
     * Créer la table des dépenses
     */
    private function createDepensesTable(string $suffix): void
    {
        $tableName = 'depenses_' . $suffix;
        $categoriesTable = 'depense_categories_' . $suffix;
        Log::debug('Création table: ' . $tableName);
        
        Schema::create($tableName, function (Blueprint $table) use ($categoriesTable) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->decimal('montant', 10, 2);
            $table->date('date_depense');
            $table->foreignId('depense_category_id')->constrained($categoriesTable)->cascadeOnDelete();
            $table->string('mode_paiement')->nullable();
            $table->string('beneficiaire')->nullable();
            $table->string('reference')->nullable();
            $table->string('justificatif')->nullable();
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
            $table->index(['date_depense'], 'idx_date');
            $table->index(['depense_category_id'], 'idx_category');
        });
    }

    /**
     * Supprimer les tables d'une année scolaire
     */
    public function dropTablesForYear(string $annee): array
    {
        $suffix = $this->formatSuffix($annee);
        $results = [];
        
        Log::info('🗑️ Suppression des tables', ['suffix' => $suffix]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = array_reverse($this->getCreatedTablesList($suffix));
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                    $results[$table] = '✅ supprimée';
                    Log::debug('Table supprimée', ['table' => $table]);
                } catch (\Exception $e) {
                    $results[$table] = '❌ erreur: ' . $e->getMessage();
                    Log::warning('Erreur suppression table', [
                        'table' => $table,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $results[$table] = '⏭️ n\'existe pas';
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        Log::info('✅ Suppression terminée', ['suffix' => $suffix]);
        
        return [
            'success' => true,
            'message' => 'Tables supprimées avec succès',
            'tables' => $results
        ];
    }

    /**
     * Vérifier si les tables existent pour une année
     */
    public function checkTablesExist(string $annee): array
    {
        $suffix = $this->formatSuffix($annee);
        $results = [];
        
        $tables = $this->getCreatedTablesList($suffix);
        
        foreach ($tables as $table) {
            $results[$table] = Schema::hasTable($table);
        }
        
        return $results;
    }

    /**
 * Migrer les données de la table inscriptions vers eleves_XXXX_XXXX
 */
public function migrateInscriptionsToEleves(string $annee, int $ecoleId): array
{
    try {
        // Récupérer l'ID de l'année scolaire
        $anneeScolaire = DB::table('annee_scolaires')
            ->where('annee', $annee)
            ->where('ecole_id', $ecoleId)
            ->first();

        if (!$anneeScolaire) {
            return [
                'success' => false,
                'message' => 'Année scolaire non trouvée'
            ];
        }

        // Récupérer les inscriptions de l'année
        $inscriptions = DB::table('inscriptions')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaire->id)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->select([
                'eleves.*',
                'inscriptions.classe_id',
                'inscriptions.cantine_active',
                'inscriptions.cantine_tarif_id',
                'inscriptions.cantine_start_date',
                'inscriptions.transport_active',
                'inscriptions.transport_tarif_id',
                'inscriptions.transport_start_date',
                'inscriptions.statut as inscription_statut',
                'inscriptions.id as inscription_id'
            ])
            ->get();

        $suffix = $this->formatSuffix($annee);
        $tableName = 'eleves_' . $suffix;

        if (!Schema::hasTable($tableName)) {
            return [
                'success' => false,
                'message' => "La table {$tableName} n'existe pas"
            ];
        }

        $count = 0;
        foreach ($inscriptions as $inscription) {
            $exists = DB::table($tableName)
                ->where('matricule', $inscription->matricule)
                ->where('ecole_id', $ecoleId)
                ->exists();

            if (!$exists) {
                DB::table($tableName)->insert([
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'ecole_id' => $inscription->ecole_id,
                    'classe_id' => $inscription->classe_id,
                    'matricule' => $inscription->matricule,
                    'code_national' => $inscription->code_national,
                    'nom' => $inscription->nom,
                    'prenom' => $inscription->prenom,
                    'sexe' => $inscription->sexe,
                    'naissance' => $inscription->naissance,
                    'lieu_naissance' => $inscription->lieu_naissance,
                    'nationalite' => $inscription->nationalite ?? 'Ivoirienne',
                    'num_extrait' => $inscription->num_extrait,
                    'photo_path' => $inscription->photo_path,
                    'infos_medicales' => $inscription->infos_medicales,
                    'parent_nom' => $inscription->parent_nom,
                    'parent_telephone' => $inscription->parent_telephone,
                    'parent_telephone02' => $inscription->parent_telephone02,
                    'parent_email' => $inscription->parent_email,
                    'pere_nom' => $inscription->pere_nom,
                    'pere_contact' => $inscription->pere_contact,
                    'pere_contact02' => $inscription->pere_contact02,
                    'mere_nom' => $inscription->mere_nom,
                    'mere_contact' => $inscription->mere_contact,
                    'mere_contact02' => $inscription->mere_contact02,
                    'parent_adresse' => $inscription->parent_adresse,
                    'transport_active' => $inscription->transport_active ?? false,
                    'transport_tarif_id' => $inscription->transport_tarif_id,
                    'transport_start_date' => $inscription->transport_start_date,
                    'cantine_active' => $inscription->cantine_active ?? false,
                    'cantine_tarif_id' => $inscription->cantine_tarif_id,
                    'cantine_start_date' => $inscription->cantine_start_date,
                    'statut' => $inscription->inscription_statut ?? 'active',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        Log::info('Migration inscriptions terminée', [
            'annee' => $annee,
            'ecole_id' => $ecoleId,
            'count' => $count
        ]);

        return [
            'success' => true,
            'message' => "{$count} élèves migrés avec succès",
            'count' => $count
        ];

    } catch (\Exception $e) {
        Log::error('Erreur migration inscriptions', [
            'annee' => $annee,
            'ecole_id' => $ecoleId,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'message' => 'Erreur lors de la migration: ' . $e->getMessage()
        ];
    }
}
}