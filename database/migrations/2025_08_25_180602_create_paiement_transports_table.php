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
        Schema::create('paiement_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires');
            $table->foreignId('ecole_id')->constrained();
            $table->foreignId('inscription_id')->constrained();
            $table->foreignId('type_frais_id')->constrained();
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement'); // Espèce, Chèque, Mobile Money...
            $table->string('reference')->nullable(); // N° chèque ou transaction
            $table->foreignId('user_id')->constrained(); // Caissier qui a encaissé
            $table->timestamps();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_transports');
    }
};
