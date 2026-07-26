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
        Schema::table('niveaux', function (Blueprint $table) {
            // Supprimer la clé étrangère si elle existe
            if (Schema::hasColumn('niveaux', 'annee_scolaire_id')) {
                // Supprimer la contrainte de clé étrangère
                $table->dropForeign(['annee_scolaire_id']);
                // Supprimer la colonne
                $table->dropColumn('annee_scolaire_id');
            }
            
            // Ajouter la colonne ordre si elle n'existe pas
            if (!Schema::hasColumn('niveaux', 'ordre')) {
                $table->integer('ordre')->default(0)->after('nom');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('niveaux', function (Blueprint $table) {
            // Recréer la colonne annee_scolaire_id
            if (!Schema::hasColumn('niveaux', 'annee_scolaire_id')) {
                $table->foreignId('annee_scolaire_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            // Supprimer la colonne ordre
            if (Schema::hasColumn('niveaux', 'ordre')) {
                $table->dropColumn('ordre');
            }
        });
    }
};