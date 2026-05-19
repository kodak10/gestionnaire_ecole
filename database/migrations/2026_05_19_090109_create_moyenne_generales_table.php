<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moyenne_generale', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->foreignId('eleve_id')->constrained()->onDelete('restrict');
            $table->foreignId('classe_id')->constrained()->onDelete('restrict');
            $table->foreignId('annee_scolaire_id')->constrained()->onDelete('restrict');
            $table->foreignId('ecole_id')->constrained()->onDelete('restrict');
            
            // Stocker les moyennes par mois (JSON)
            $table->json('moyennes_par_mois')->nullable();
            $table->json('rangs_par_mois')->nullable();
            
            // Stocker les moyennes par matière (JSON)
            $table->json('moyennes_par_matiere')->nullable();
            $table->json('rangs_par_matiere')->nullable();
            
            // Stocker les détails complets des notes par matière et par mois (JSON)
            $table->json('details_notes')->nullable();
            
            // Moyenne annuelle
            $table->decimal('moyenne_annuelle', 10, 2)->nullable();
            
            // Rang général
            $table->integer('rang_general')->nullable();
            $table->boolean('exaequo')->default(false);
            
            // Appréciation générale
            $table->text('appreciation_generale')->nullable();
            
            // Décision
            $table->string('decision')->nullable();
            
            // Distinctions et sanctions
            $table->json('distinctions')->nullable();
            $table->json('sanctions')->nullable();
            
            // Métadonnées
            $table->json('mois_selectionnes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('date_cloture')->nullable();
            
            $table->timestamps();
            
            // Index pour optimiser les recherches
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id']);
            $table->index(['eleve_id']);
            $table->index(['classe_id', 'moyenne_annuelle']);
            
            // Un seul enregistrement par élève/classe/année
            $table->unique(['eleve_id', 'classe_id', 'annee_scolaire_id'], 'unique_moyenne_eleve_classe_annee');
        });
    }

    public function down()
    {
        Schema::table('moyenne_generale', function (Blueprint $table) {
            $table->dropForeign(['eleve_id']);
            $table->dropForeign(['classe_id']);
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropForeign(['ecole_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('moyenne_generale');
    }
};