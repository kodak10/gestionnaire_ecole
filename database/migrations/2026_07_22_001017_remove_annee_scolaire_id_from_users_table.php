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
        Schema::table('users', function (Blueprint $table) {
            // Vérifier si la colonne existe avant de la supprimer
            if (Schema::hasColumn('users', 'annee_scolaire_id')) {
                // Supprimer d'abord la clé étrangère
                $table->dropForeign(['annee_scolaire_id']);
                // Puis supprimer la colonne
                $table->dropColumn('annee_scolaire_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recréer la colonne et la clé étrangère en cas de rollback
            if (!Schema::hasColumn('users', 'annee_scolaire_id')) {
                $table->foreignId('annee_scolaire_id')->nullable()->after('ecole_id')->constrained('annee_scolaires')->onDelete('cascade');
            }
        });
    }
};