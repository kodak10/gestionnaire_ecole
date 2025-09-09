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
        Schema::create('reductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->foreignId('ecole_id')->constrained();
            $table->foreignId('inscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_frais_id')->nullable()->constrained('type_frais')->onDelete('set null');
            $table->decimal('montant', 10, 2);
            $table->string('raison')->nullable();
            $table->timestamps();
            
            $table->unique(['inscription_id', 'annee_scolaire_id',]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reductions');
    }
};
