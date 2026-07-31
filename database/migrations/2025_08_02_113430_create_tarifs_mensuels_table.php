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
        Schema::create('tarifs_mensuels', function (Blueprint $table) {
    $table->id();

    $table->foreignId('annee_scolaire_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('ecole_id')
        ->constrained()
        ->cascadeOnDelete();

    // Ancienne colonne pour import
    $table->foreignId('type_frais_id')
        ->nullable()
        ->constrained('type_frais')
        ->nullOnDelete();

    // Nouvelle colonne
    $table->foreignId('tarif_id')
        ->nullable()
        ->constrained('tarifs')
        ->nullOnDelete();

    $table->foreignId('niveau_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->foreignId('mois_id')
        ->constrained('mois_scolaires')
        ->cascadeOnDelete();

    $table->decimal('montant', 10, 2);

    $table->timestamps();

    $table->unique(
        [
            'tarif_id',
            'niveau_id',
            'mois_id',
            'ecole_id'
        ],
        'unique_tarif_mensuel'
    );
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifs_mensuels');
    }
};