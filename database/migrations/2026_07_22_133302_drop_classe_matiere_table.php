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
        // Supprimer la table classe_matiere
        Schema::dropIfExists('classe_matiere');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer la table classe_matiere en cas de rollback
        Schema::create('classe_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained()->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained()->onDelete('cascade');
            $table->float('coefficient')->default(1);
            $table->integer('ordre')->default(0);
            $table->timestamps();
            
            // Empêcher les doublons
            $table->unique(['classe_id', 'matiere_id']);
        });
    }
};