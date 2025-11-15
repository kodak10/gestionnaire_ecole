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
        Schema::create('cantine_mensuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('mois_scolaire_id')->constrained()->onDelete('cascade');
            $table->decimal('montant', 10, 2)->default(0);
            $table->boolean('est_coche')->default(true);
            $table->boolean('est_paye')->default(false);
            $table->timestamps();

            $table->unique(['inscription_id', 'mois_scolaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cantine_mensuelles');
    }
};
