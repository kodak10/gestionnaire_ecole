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
        Schema::table('classes', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte (vers users)
            $table->dropForeign(['enseignant_id']);

            // Recréer la contrainte vers la table enseignants
            $table->foreign('enseignant_id')
                  ->references('id')
                  ->on('enseignants')
                  ->nullOnDelete(); // ou ->onDelete('set null')
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Supprimer la contrainte vers enseignants
            $table->dropForeign(['enseignant_id']);

            // Revenir à la contrainte originale vers users
            $table->foreign('enseignant_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }
};
