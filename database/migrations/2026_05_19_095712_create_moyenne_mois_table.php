<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moyenne_mois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained()->restrictOnDelete();
            $table->foreignId('classe_id')->constrained()->restrictOnDelete();
            $table->foreignId('mois_id')->constrained('mois_scolaires')->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained()->restrictOnDelete();
            $table->foreignId('ecole_id')->constrained()->restrictOnDelete();
            $table->decimal('moyenne', 10, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->boolean('exaequo')->default(false);
            $table->text('appreciation')->nullable();
            $table->json('details_notes')->nullable();
            $table->decimal('moyenne_classe', 10, 2)->nullable();
            $table->decimal('moyenne_min', 10, 2)->nullable();
            $table->decimal('moyenne_max', 10, 2)->nullable();
            $table->integer('effectif_classe')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('date_generation')->nullable();
            $table->timestamps();
            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id', 'mois_id']);
            $table->index(['eleve_id', 'mois_id']);
            $table->index(['classe_id', 'mois_id', 'moyenne']);
            $table->unique(['eleve_id', 'classe_id', 'mois_id', 'annee_scolaire_id'], 'unique_moyenne_eleve_classe_mois');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyenne_mois');
    }
};