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
        // Modifier la colonne niveau_id pour accepter NULL
        Schema::table('tarifs_mensuels', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['niveau_id']);
            
            // Modifier la colonne pour accepter NULL
            $table->foreignId('niveau_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère
            $table->foreign('niveau_id')->references('id')->on('niveaux')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarifs_mensuels', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['niveau_id']);
            
            // Revenir à NOT NULL
            $table->foreignId('niveau_id')->nullable(false)->change();
            
            // Recréer la contrainte de clé étrangère
            $table->foreign('niveau_id')->references('id')->on('niveaux')->onDelete('cascade');
        });
    }
};