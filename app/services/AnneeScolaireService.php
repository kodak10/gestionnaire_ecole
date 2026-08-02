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
     * Récupérer le sigle de l'école depuis la base de données
     */
    private function getEcoleSigle(int $ecoleId): string
    {
        $ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
        
        if (!$ecole) {
            return 'ecole';
        }
        
        // Utiliser le sigle_ecole s'il existe
        if (!empty($ecole->sigle_ecole)) {
            $sigle = $this->cleanString($ecole->sigle_ecole);
            $sigle = strtoupper(trim($sigle));
            $sigle = str_replace(' ', '_', $sigle);
            return $sigle;
        }
        
        // Fallback : générer un sigle à partir du nom
        $nom = $this->cleanString($ecole->nom_ecole ?? 'ecole');
        $mots = explode(' ', $nom);
        $sigle = '';
        
        foreach ($mots as $mot) {
            if (!empty($mot)) {
                $sigle .= strtoupper(substr($mot, 0, 1));
            }
        }
        
        if (strlen($sigle) < 2) {
            $sigle = strtoupper(substr(str_replace(' ', '_', $nom), 0, 4));
        }
        
        return substr($sigle, 0, 10);
    }

    /**
     * Nettoyer une chaîne (enlever accents, caractères spéciaux)
     */
    private function cleanString(string $string): string
    {
        $string = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ö', 'û', 'ù', 'ç', 'É', 'È', 'Ê', 'Ë', 'À', 'Â', 'Î', 'Ï', 'Ô', 'Ö', 'Û', 'Ù', 'Ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'c', 'E', 'E', 'E', 'E', 'A', 'A', 'I', 'I', 'O', 'O', 'U', 'U', 'C'],
            $string
        );
        return preg_replace('/[^a-zA-Z0-9_ ]/', '', $string);
    }

    /**
     * Formater le suffixe pour les tables (année)
     */
    public function formatSuffix(string $annee): string
    {
        return str_replace('-', '_', $annee);
    }

    /**
     * Obtenir le nom de la table avec école et année
     * Format: table_sigle_annee
     */
    private function getTableName(string $base, string $sigle, string $suffix): string
    {
        return $base . '_' . $sigle . '_' . $suffix;
    }

    /**
     * Créer toutes les tables pour une année scolaire
     */
    public function createTablesForYear(string $annee, int $ecoleId): array
    {
        $suffix = $this->formatSuffix($annee);
        $sigle = $this->getEcoleSigle($ecoleId);
        $tablesCrees = [];
        
        try {
            Log::info('📦 Création des tables', [
                'annee' => $annee, 
                'ecole_id' => $ecoleId, 
                'sigle' => $sigle
            ]);
            
            $this->createNiveauxTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('niveaux', $sigle, $suffix);
            
            $this->createMatieresTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('matieres', $sigle, $suffix);
            
            $this->createTypeFraisTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('type_frais', $sigle, $suffix);
            
            $this->createClassesTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('classes', $sigle, $suffix);
            
            $this->createTarifsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('tarifs', $sigle, $suffix);
            
            $this->createNiveauMatiereTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('niveau_matiere', $sigle, $suffix);
            
            $this->createMentionsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('mentions', $sigle, $suffix);
            
            $this->createDepenseCategoriesTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('depense_categories', $sigle, $suffix);
            
            $this->createTarifsMensuelsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('tarifs_mensuels', $sigle, $suffix);
            
            $this->createElevesTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('eleves', $sigle, $suffix);
            
            $this->createPaiementsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('paiements', $sigle, $suffix);
            
            $this->createPaiementDetailsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('paiement_details', $sigle, $suffix);
            
            $this->createReductionsTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('reductions', $sigle, $suffix);
            
            $this->createNotesTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('notes', $sigle, $suffix);
            
            $this->createMoyenneGeneraleTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('moyenne_generale', $sigle, $suffix);
            
            $this->createMoyenneMoisTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('moyenne_mois', $sigle, $suffix);
            
            $this->createDepensesTable($suffix, $sigle);
            $tablesCrees[] = $this->getTableName('depenses', $sigle, $suffix);
            
            Log::info('✅ Tables créées', ['count' => count($tablesCrees)]);
            
            return [
                'success' => true,
                'message' => 'Tables créées avec succès',
                'suffix' => $suffix,
                'sigle' => $sigle,
                'tables' => $tablesCrees
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur création tables', [
                'annee' => $annee,
                'ecole_id' => $ecoleId,
                'sigle' => $sigle,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la création des tables: ' . $e->getMessage(),
                'suffix' => $suffix,
                'sigle' => $sigle,
                'tables_crees' => $tablesCrees
            ];
        }
    }

    /**
     * Supprimer les tables de force
     */
    public function forceDropTables(string $suffix, int $ecoleId): void
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        
        Log::warning('🧹 Suppression forcée', ['suffix' => $suffix, 'sigle' => $sigle]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = [
            $this->getTableName('depenses', $sigle, $suffix),
            $this->getTableName('moyenne_mois', $sigle, $suffix),
            $this->getTableName('moyenne_generale', $sigle, $suffix),
            $this->getTableName('notes', $sigle, $suffix),
            $this->getTableName('reductions', $sigle, $suffix),
            $this->getTableName('paiement_details', $sigle, $suffix),
            $this->getTableName('paiements', $sigle, $suffix),
            $this->getTableName('eleves', $sigle, $suffix),
            $this->getTableName('tarifs_mensuels', $sigle, $suffix),
            $this->getTableName('depense_categories', $sigle, $suffix),
            $this->getTableName('mentions', $sigle, $suffix),
            $this->getTableName('niveau_matiere', $sigle, $suffix),
            $this->getTableName('tarifs', $sigle, $suffix),
            $this->getTableName('classes', $sigle, $suffix),
            $this->getTableName('type_frais', $sigle, $suffix),
            $this->getTableName('matieres', $sigle, $suffix),
            $this->getTableName('niveaux', $sigle, $suffix),
        ];
        
        foreach (array_reverse($tables) as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                } catch (\Exception $e) {
                    // Ignorer
                }
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Récupérer la liste des tables créées
     */
    private function getCreatedTablesList(string $suffix, int $ecoleId): array
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        
        return [
            $this->getTableName('niveaux', $sigle, $suffix),
            $this->getTableName('matieres', $sigle, $suffix),
            $this->getTableName('type_frais', $sigle, $suffix),
            $this->getTableName('classes', $sigle, $suffix),
            $this->getTableName('tarifs', $sigle, $suffix),
            $this->getTableName('niveau_matiere', $sigle, $suffix),
            $this->getTableName('mentions', $sigle, $suffix),
            $this->getTableName('depense_categories', $sigle, $suffix),
            $this->getTableName('tarifs_mensuels', $sigle, $suffix),
            $this->getTableName('eleves', $sigle, $suffix),
            $this->getTableName('paiements', $sigle, $suffix),
            $this->getTableName('paiement_details', $sigle, $suffix),
            $this->getTableName('reductions', $sigle, $suffix),
            $this->getTableName('notes', $sigle, $suffix),
            $this->getTableName('moyenne_generale', $sigle, $suffix),
            $this->getTableName('moyenne_mois', $sigle, $suffix),
            $this->getTableName('depenses', $sigle, $suffix),
        ];
    }

    /**
     * Créer la table des niveaux
     */
    private function createNiveauxTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('niveaux', $sigle, $suffix);
        
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
    private function createMatieresTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('matieres', $sigle, $suffix);
        $niveauxTable = $this->getTableName('niveaux', $sigle, $suffix);
        
        Schema::create($tableName, function (Blueprint $table) use ($niveauxTable) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
           // $table->foreignId('niveau_id')->constrained($niveauxTable)->cascadeOnDelete();
            $table->string('nom');
            $table->timestamps();
            
            $table->index(['ecole_id'], 'idx_ecole');
           // $table->index(['niveau_id'], 'idx_niveau');
            $table->unique(['nom', 'ecole_id'], 'uq_nom_ecole_niveau');
        });
    }

    /**
     * Créer la table des types de frais
     */
    private function createTypeFraisTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('type_frais', $sigle, $suffix);
        
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
    private function createClassesTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('classes', $sigle, $suffix);
        $niveauxTable = $this->getTableName('niveaux', $sigle, $suffix);
        
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
    private function createTarifsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('tarifs', $sigle, $suffix);
        $typeFraisTable = 'type_frais';
        $niveauxTable = $this->getTableName('niveaux', $sigle, $suffix);
        
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
    private function createNiveauMatiereTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('niveau_matiere', $sigle, $suffix);
        $niveauxTable = $this->getTableName('niveaux', $sigle, $suffix);
        $matieresTable = $this->getTableName('matieres', $sigle, $suffix);
        
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
    private function createMentionsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('mentions', $sigle, $suffix);
        
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
    private function createDepenseCategoriesTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('depense_categories', $sigle, $suffix);
        
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
    private function createTarifsMensuelsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('tarifs_mensuels', $sigle, $suffix);
        $tarifsTable = $this->getTableName('tarifs', $sigle, $suffix);
        $niveauxTable = $this->getTableName('niveaux', $sigle, $suffix);
        
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
    private function createElevesTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('eleves', $sigle, $suffix);
        $tarifsTable = $this->getTableName('tarifs', $sigle, $suffix);
        $classesTable = $this->getTableName('classes', $sigle, $suffix);
        
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
    private function createPaiementsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('paiements', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        
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
    private function createPaiementDetailsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('paiement_details', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $paiementsTable = $this->getTableName('paiements', $sigle, $suffix);
        $tarifsTable = $this->getTableName('tarifs', $sigle, $suffix);
        
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
    private function createReductionsTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('reductions', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $tarifsTable = $this->getTableName('tarifs', $sigle, $suffix);
        
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
    private function createNotesTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('notes', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $classesTable = $this->getTableName('classes', $sigle, $suffix);
        $matieresTable = $this->getTableName('matieres', $sigle, $suffix);
        
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
    private function createMoyenneGeneraleTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('moyenne_generale', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $classesTable = $this->getTableName('classes', $sigle, $suffix);
        
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
    private function createMoyenneMoisTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('moyenne_mois', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $classesTable = $this->getTableName('classes', $sigle, $suffix);
        
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
    private function createDepensesTable(string $suffix, string $sigle): void
    {
        $tableName = $this->getTableName('depenses', $sigle, $suffix);
        $categoriesTable = $this->getTableName('depense_categories', $sigle, $suffix);
        
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

    // ============================================
    // MÉTHODES DE SUPPRESSION ET VÉRIFICATION
    // ============================================

    /**
     * Supprimer les tables d'une année scolaire
     */
    public function dropTablesForYear(string $annee, int $ecoleId): array
    {
        $suffix = $this->formatSuffix($annee);
        $sigle = $this->getEcoleSigle($ecoleId);
        $results = [];
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = array_reverse($this->getCreatedTablesList($suffix, $ecoleId));
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                    $results[$table] = 'supprimée';
                } catch (\Exception $e) {
                    $results[$table] = 'erreur: ' . $e->getMessage();
                }
            } else {
                $results[$table] = 'n\'existe pas';
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        return [
            'success' => true,
            'message' => 'Tables supprimées avec succès',
            'tables' => $results
        ];
    }

    /**
     * Vérifier si les tables existent pour une année
     */
    public function checkTablesExist(string $annee, int $ecoleId): array
    {
        $suffix = $this->formatSuffix($annee);
        $results = [];
        
        $tables = $this->getCreatedTablesList($suffix, $ecoleId);
        
        foreach ($tables as $table) {
            $results[$table] = Schema::hasTable($table);
        }
        
        return $results;
    }

    // ============================================
    // MÉTHODES DE MIGRATION DES DONNÉES
    // ============================================

    /**
     * MIGRER LES DONNÉES DEPUIS L'ANNÉE PRÉCÉDENTE
     */
    public function migrateInscriptionsToEleves(string $annee, int $ecoleId): array
    {
        try {
            Log::info('🔄 Migration des données', ['annee' => $annee, 'ecole_id' => $ecoleId]);
            
            $suffix = $this->formatSuffix($annee);
            $sigle = $this->getEcoleSigle($ecoleId);
            $results = [];
            
            // Récupérer l'ID de l'année scolaire créée
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

            // Récupérer l'ID de l'année précédente
            $anneePrecedente = $this->getAnneePrecedente($annee);
            $anneeScolairePrecedente = DB::table('annee_scolaires')
                ->where('annee', $anneePrecedente)
                ->where('ecole_id', $ecoleId)
                ->first();

            // Vérifier si l'année précédente existe
            if (!$anneeScolairePrecedente) {
                Log::info('ℹ️ Année précédente non trouvée, aucune donnée à migrer', [
                    'annee_precedente' => $anneePrecedente
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Aucune donnée à migrer (année précédente inexistante)',
                    'count' => 0
                ];
            }

            Log::info('📅 Migration depuis', [
                'source' => $anneePrecedente,
                'source_id' => $anneeScolairePrecedente->id,
                'destination' => $annee
            ]);

            // 1. MIGRATION DES NIVEAUX (toujours)
            $this->migrateNiveaux($suffix, $sigle, $ecoleId);
            $results['niveaux'] = 'ok';

            // 2. MIGRATION DES MATIERES (toujours)
            $this->migrateMatieres($suffix, $sigle, $ecoleId);
            $results['matieres'] = 'ok';

            // 3. MIGRATION DES TABLES AVEC ANNEE_SCOLAIRE_ID
            $this->migrateTypeFrais($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateClasses($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateTarifs($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateNiveauMatiere($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateMentions($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateDepenseCategories($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateTarifsMensuels($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $countEleves = $this->migrateEleves($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migratePaiements($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateReductions($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateNotes($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migrateDepenses($suffix, $sigle, $ecoleId, $anneeScolairePrecedente->id);
            $this->migratePaiementDetails($suffix, $sigle, $ecoleId);

            $results['eleves'] = $countEleves . ' élèves migrés';

            Log::info('✅ Migration terminée', ['details' => $results]);

            return [
                'success' => true,
                'message' => 'Données migrées avec succès depuis ' . $anneePrecedente,
                'count' => $countEleves,
                'details' => $results
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur migration', [
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

    /**
     * Récupérer l'année précédente
     */
    private function getAnneePrecedente(string $annee): string
    {
        $parts = explode('_', $annee);
        if (count($parts) == 2) {
            $debut = intval($parts[0]) - 1;
            $fin = intval($parts[1]) - 1;
            return $debut . '_' . $fin;
        }
        return $annee;
    }

    // ============================================
    // MÉTHODES DE MIGRATION PAR TABLE
    // ============================================

    private function migrateNiveaux(string $suffix, string $sigle, int $ecoleId): void
    {
        $tableName = $this->getTableName('niveaux', $sigle, $suffix);
        
        $niveaux = DB::table('niveaux')->where('ecole_id', $ecoleId)->get();
        
        foreach ($niveaux as $niveau) {
            DB::table($tableName)->insert([
                'id' => $niveau->id,
                'ecole_id' => $niveau->ecole_id,
                'nom' => $niveau->nom,
                'ordre' => $niveau->ordre ?? 0,
                'created_at' => $niveau->created_at,
                'updated_at' => $niveau->updated_at,
            ]);
        }
    }

    private function migrateMatieres(string $suffix, string $sigle, int $ecoleId): void
    {
        $tableName = $this->getTableName('matieres', $sigle, $suffix);
        
        $matieres = DB::table('matieres')->where('ecole_id', $ecoleId)->get();
        
        foreach ($matieres as $matiere) {
            DB::table($tableName)->insert([
                'id' => $matiere->id,
                'ecole_id' => $matiere->ecole_id,
                'niveau_id' => $matiere->niveau_id,
                'nom' => $matiere->nom,
                'created_at' => $matiere->created_at,
                'updated_at' => $matiere->updated_at,
            ]);
        }
    }

    private function migrateTypeFrais(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('type_frais', $sigle, $suffix);
        
        $typeFrais = DB::table('type_frais')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($typeFrais as $tf) {
            DB::table($tableName)->insert([
                'id' => $tf->id,
                'annee_scolaire_id' => $tf->annee_scolaire_id,
                'ecole_id' => $tf->ecole_id,
                'nom' => $tf->nom,
                'obligatoire' => $tf->obligatoire ?? false,
                'created_at' => $tf->created_at,
                'updated_at' => $tf->updated_at,
            ]);
        }
    }

    private function migrateClasses(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('classes', $sigle, $suffix);
        
        $classes = DB::table('classes')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($classes as $classe) {
            DB::table($tableName)->insert([
                'id' => $classe->id,
                'ecole_id' => $classe->ecole_id,
                'annee_scolaire_id' => $classe->annee_scolaire_id,
                'niveau_id' => $classe->niveau_id,
                'nom' => $classe->nom,
                'capacite' => $classe->capacite ?? 50,
                'moy_base' => $classe->moy_base ?? 20,
                'enseignant_id' => $classe->enseignant_id,
                'created_at' => $classe->created_at,
                'updated_at' => $classe->updated_at,
            ]);
        }
    }

    private function migrateTarifs(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('tarifs', $sigle, $suffix);
        
        $tarifs = DB::table('tarifs')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($tarifs as $tarif) {
            DB::table($tableName)->insert([
                'id' => $tarif->id,
                'annee_scolaire_id' => $tarif->annee_scolaire_id,
                'ecole_id' => $tarif->ecole_id,
                'type_frais_id' => $tarif->type_frais_id,
                'niveau_id' => $tarif->niveau_id,
                'libelle' => $tarif->libelle,
                'obligatoire' => $tarif->obligatoire ?? false,
                'montant' => $tarif->montant,
                'created_at' => $tarif->created_at,
                'updated_at' => $tarif->updated_at,
            ]);
        }
    }

    private function migrateNiveauMatiere(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('niveau_matiere', $sigle, $suffix);
        
        $niveauMatieres = DB::table('niveau_matiere')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($niveauMatieres as $nm) {
            DB::table($tableName)->insert([
                'id' => $nm->id,
                'annee_scolaire_id' => $nm->annee_scolaire_id,
                'ecole_id' => $nm->ecole_id,
                'niveau_id' => $nm->niveau_id,
                'matiere_id' => $nm->matiere_id,
                'coefficient' => $nm->coefficient ?? 1,
                'ordre' => $nm->ordre ?? 0,
                'denominateur' => $nm->denominateur ?? 20,
                'created_at' => $nm->created_at,
                'updated_at' => $nm->updated_at,
            ]);
        }
    }

    private function migrateMentions(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('mentions', $sigle, $suffix);
        
        $mentions = DB::table('mentions')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($mentions as $mention) {
            DB::table($tableName)->insert([
                'id' => $mention->id,
                'annee_scolaire_id' => $mention->annee_scolaire_id,
                'ecole_id' => $mention->ecole_id,
                'nom' => $mention->nom,
                'min_note' => $mention->min_note,
                'max_note' => $mention->max_note,
                'created_at' => $mention->created_at,
                'updated_at' => $mention->updated_at,
            ]);
        }
    }

    private function migrateDepenseCategories(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('depense_categories', $sigle, $suffix);
        
        $categories = DB::table('depense_categories')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($categories as $categorie) {
            DB::table($tableName)->insert([
                'id' => $categorie->id,
                'annee_scolaire_id' => $categorie->annee_scolaire_id,
                'ecole_id' => $categorie->ecole_id,
                'nom' => $categorie->nom,
                'created_at' => $categorie->created_at,
                'updated_at' => $categorie->updated_at,
            ]);
        }
    }

    private function migrateTarifsMensuels(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('tarifs_mensuels', $sigle, $suffix);
        
        $tarifsMensuels = DB::table('tarifs_mensuels')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($tarifsMensuels as $tm) {
            DB::table($tableName)->insert([
                'id' => $tm->id,
                'annee_scolaire_id' => $tm->annee_scolaire_id,
                'ecole_id' => $tm->ecole_id,
                'tarif_id' => $tm->tarif_id,
                'niveau_id' => $tm->niveau_id,
                'mois_id' => $tm->mois_id,
                'montant' => $tm->montant,
                'created_at' => $tm->created_at,
                'updated_at' => $tm->updated_at,
            ]);
        }
    }

    private function migrateEleves(string $suffix, string $sigle, int $ecoleId, int $sourceId): int
    {
        $tableName = $this->getTableName('eleves', $sigle, $suffix);
        $count = 0;
        
        $inscriptions = DB::table('inscriptions')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('inscriptions.annee_scolaire_id', $sourceId)
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
                'inscriptions.statut as inscription_statut'
            ])
            ->get();

        foreach ($inscriptions as $inscription) {
            $exists = DB::table($tableName)
                ->where('matricule', $inscription->matricule)
                ->where('ecole_id', $ecoleId)
                ->exists();

            if (!$exists) {
                DB::table($tableName)->insert([
                    'annee_scolaire_id' => $sourceId,
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
                    'created_at' => $inscription->created_at ?? now(),
                    'updated_at' => $inscription->updated_at ?? now(),
                ]);
                $count++;
            }
        }
        
        return $count;
    }

    private function migratePaiements(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('paiements', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        
        $paiements = DB::table('paiements')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($paiements as $paiement) {
            $eleveExists = DB::table($elevesTable)->where('id', $paiement->eleve_id)->exists();
                
            if ($eleveExists) {
                DB::table($tableName)->insert([
                    'id' => $paiement->id,
                    'annee_scolaire_id' => $paiement->annee_scolaire_id,
                    'ecole_id' => $paiement->ecole_id,
                    'eleve_id' => $paiement->eleve_id,
                    'montant' => $paiement->montant,
                    'mode_paiement' => $paiement->mode_paiement,
                    'reference' => $paiement->reference,
                    'user_id' => $paiement->user_id,
                    'created_at' => $paiement->created_at,
                    'updated_at' => $paiement->updated_at,
                ]);
            }
        }
    }

    private function migratePaiementDetails(string $suffix, string $sigle, int $ecoleId): void
    {
        $tableName = $this->getTableName('paiement_details', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        $paiementsTable = $this->getTableName('paiements', $sigle, $suffix);
        
        $paiementsIds = DB::table($paiementsTable)->pluck('id')->toArray();
            
        if (empty($paiementsIds)) {
            return;
        }
        
        $details = DB::table('paiement_details')->whereIn('paiement_id', $paiementsIds)->get();
        
        foreach ($details as $detail) {
            $eleveExists = DB::table($elevesTable)->where('id', $detail->eleve_id)->exists();
                
            if ($eleveExists) {
                DB::table($tableName)->insert([
                    'id' => $detail->id,
                    'paiement_id' => $detail->paiement_id,
                    'eleve_id' => $detail->eleve_id,
                    'tarif_id' => $detail->tarif_id,
                    'montant' => $detail->montant,
                    'created_at' => $detail->created_at,
                    'updated_at' => $detail->updated_at,
                ]);
            }
        }
    }

    private function migrateReductions(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('reductions', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        
        $reductions = DB::table('reductions')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($reductions as $reduction) {
            $eleveExists = DB::table($elevesTable)->where('id', $reduction->eleve_id)->exists();
                
            if ($eleveExists) {
                DB::table($tableName)->insert([
                    'id' => $reduction->id,
                    'annee_scolaire_id' => $reduction->annee_scolaire_id,
                    'ecole_id' => $reduction->ecole_id,
                    'eleve_id' => $reduction->eleve_id,
                    'tarif_id' => $reduction->tarif_id,
                    'montant' => $reduction->montant,
                    'raison' => $reduction->raison,
                    'user_id' => $reduction->user_id,
                    'created_at' => $reduction->created_at,
                    'updated_at' => $reduction->updated_at,
                ]);
            }
        }
    }

    private function migrateNotes(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('notes', $sigle, $suffix);
        $elevesTable = $this->getTableName('eleves', $sigle, $suffix);
        
        $notes = DB::table('notes')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($notes as $note) {
            $eleveExists = DB::table($elevesTable)->where('id', $note->eleve_id)->exists();
                
            if ($eleveExists) {
                DB::table($tableName)->insert([
                    'id' => $note->id,
                    'annee_scolaire_id' => $note->annee_scolaire_id,
                    'ecole_id' => $note->ecole_id,
                    'eleve_id' => $note->eleve_id,
                    'classe_id' => $note->classe_id,
                    'matiere_id' => $note->matiere_id,
                    'mois_id' => $note->mois_id,
                    'valeur' => $note->valeur,
                    'coefficient' => $note->coefficient ?? 1,
                    'appreciation' => $note->appreciation,
                    'user_id' => $note->user_id,
                    'created_at' => $note->created_at,
                    'updated_at' => $note->updated_at,
                ]);
            }
        }
    }

    private function migrateDepenses(string $suffix, string $sigle, int $ecoleId, int $sourceId): void
    {
        $tableName = $this->getTableName('depenses', $sigle, $suffix);
        
        $depenses = DB::table('depenses')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $sourceId)
            ->get();
        
        foreach ($depenses as $depense) {
            DB::table($tableName)->insert([
                'id' => $depense->id,
                'annee_scolaire_id' => $depense->annee_scolaire_id,
                'ecole_id' => $depense->ecole_id,
                'libelle' => $depense->libelle,
                'description' => $depense->description,
                'montant' => $depense->montant,
                'date_depense' => $depense->date_depense,
                'depense_category_id' => $depense->depense_category_id,
                'mode_paiement' => $depense->mode_paiement,
                'beneficiaire' => $depense->beneficiaire,
                'reference' => $depense->reference,
                'justificatif' => $depense->justificatif,
                'created_at' => $depense->created_at,
                'updated_at' => $depense->updated_at,
            ]);
        }
    }

    /**
 * Vérifier et afficher toutes les tables disponibles
 */
public function debugTables(int $ecoleId): array
{
    $sigle = $this->getEcoleSigle($ecoleId);
    $allTables = Schema::getAllTables();
    $tables = [];
    
    foreach ($allTables as $table) {
        $tableName = is_array($table) ? reset($table) : $table;
        if (str_contains($tableName, $sigle)) {
            $tables[] = $tableName;
        }
    }
    
    return $tables;
}
}