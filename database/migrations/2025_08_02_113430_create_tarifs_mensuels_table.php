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
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->foreignId('ecole_id')->constrained();
            $table->foreignId('type_frais_id')->constrained();
            $table->foreignId('niveau_id')->constrained();
            $table->foreignId('mois_id')->constrained('mois_scolaires');
            $table->decimal('montant', 10, 2);
            $table->timestamps();

            $table->unique(['type_frais_id', 'niveau_id', 'mois_id', 'ecole_id'], 'unique_tarif_mensuel');
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
