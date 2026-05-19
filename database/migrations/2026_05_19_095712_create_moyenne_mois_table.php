<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moyenne_mois', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->foreignId('eleve_id')->constrained()->onDelete('restrict');
            $table->foreignId('classe_id')->constrained()->onDelete('restrict');
            $table->foreignId('mois_id')->constrained('mois_scolaires')->onDelete('restrict');
            $table->foreignId('annee_scolaire_id')->constrained()->onDelete('restrict');
            $table->foreignId('ecole_id')->constrained()->onDelete('restrict');
            
            // Moyenne du mois
            $table->decimal('moyenne', 10, 2)->nullable();
            
            // Rang dans la classe pour ce mois
            $table->integer('rang')->nullable();
            $table->boolean('exaequo')->default(false);
            
            // Appréciation générale du mois
            $table->text('appreciation')->nullable();
            
            // Détails des notes par matière pour ce mois (JSON)
            $table->json('details_notes')->nullable();
            
            // Statistiques de la classe pour ce mois
            $table->decimal('moyenne_classe', 10, 2)->nullable();
            $table->decimal('moyenne_min', 10, 2)->nullable();
            $table->decimal('moyenne_max', 10, 2)->nullable();
            $table->integer('effectif_classe')->nullable();
            
            // Métadonnées
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('date_generation')->nullable();
            
            $table->timestamps();
            
            // Index pour optimiser les recherches
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id', 'mois_id']);
            $table->index(['eleve_id', 'mois_id']);
            $table->index(['classe_id', 'mois_id', 'moyenne']);
            
            // Un seul enregistrement par élève/classe/mois/année
            $table->unique(['eleve_id', 'classe_id', 'mois_id', 'annee_scolaire_id'], 'unique_moyenne_eleve_classe_mois');
        });
    }

    public function down()
    {
        Schema::table('moyenne_mois', function (Blueprint $table) {
            $table->dropForeign(['eleve_id']);
            $table->dropForeign(['classe_id']);
            $table->dropForeign(['mois_id']);
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropForeign(['ecole_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('moyenne_mois');
    }
};