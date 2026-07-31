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
        Schema::create('paiement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('type_frais_id')
                ->nullable()
                ->constrained('type_frais')
                ->nullOnDelete();

            $table->foreignId('tarif_id')
                ->nullable()
                ->constrained('tarifs')
                ->nullOnDelete();

            $table->decimal('montant', 10, 2);
            $table->timestamps();
            $table->unique(['paiement_id', 'inscription_id', 'tarif_id'], 'unique_paiement_tarif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_details');
    }
};