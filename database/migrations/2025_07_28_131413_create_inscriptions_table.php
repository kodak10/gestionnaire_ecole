<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->boolean('cantine_active')->default(false);
            $table->foreignId('cantine_tarif_id')->nullable()->constrained('tarifs')->nullOnDelete();
            $table->date('cantine_start_date')->nullable();
            $table->boolean('transport_active')->default(false);
            $table->foreignId('transport_tarif_id')->nullable()->constrained('tarifs')->nullOnDelete();
            $table->date('transport_start_date')->nullable();
            $table->string('statut')->default('active');
            $table->timestamps();
            $table->unique(['eleve_id', 'annee_scolaire_id'], 'unique_inscription_eleve_annee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};