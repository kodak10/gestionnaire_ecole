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
        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->foreignId('ecole_id')->constrained();
            $table->string('nom')->unique(); // Exemple : Passable, Bien, Excellent
            $table->text('description')->nullable(); // Explication optionnelle
            $table->integer('min_note')->nullable(); // Note minimale (optionnelle)
            $table->integer('max_note')->nullable(); // Note maximale (optionnelle)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
