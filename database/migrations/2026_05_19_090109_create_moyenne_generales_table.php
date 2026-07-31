<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moyenne_generale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained()->restrictOnDelete();
            $table->foreignId('classe_id')->constrained()->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained()->restrictOnDelete();
            $table->foreignId('ecole_id')->constrained()->restrictOnDelete();
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
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamps();
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id']);
            $table->index('eleve_id');
            $table->index(['classe_id', 'moyenne_annuelle']);
            $table->unique(['eleve_id', 'classe_id', 'annee_scolaire_id'], 'unique_moyenne_eleve_classe_annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyenne_generale');
    }
};