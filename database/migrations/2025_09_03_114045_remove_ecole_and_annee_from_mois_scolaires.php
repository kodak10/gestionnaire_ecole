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
        Schema::table('mois_scolaires', function (Blueprint $table) {
             // Supprimer les clés étrangères avant de supprimer les colonnes
            if (Schema::hasColumn('mois_scolaires', 'ecole_id')) {
                $table->dropForeign(['ecole_id']);
                $table->dropColumn('ecole_id');
            }

            if (Schema::hasColumn('mois_scolaires', 'annee_scolaire_id')) {
                $table->dropForeign(['annee_scolaire_id']);
                $table->dropColumn('annee_scolaire_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mois_scolaires', function (Blueprint $table) {
            //
        });
    }
};
