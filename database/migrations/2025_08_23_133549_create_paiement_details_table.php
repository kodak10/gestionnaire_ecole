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
            $table->foreignId('inscription_id')->constrained();
            $table->foreignId('type_frais_id')->constrained();
            $table->foreignId('paiement_id')->constrained()->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->timestamps();
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
