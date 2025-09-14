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
        Schema::table('notes', function (Blueprint $table) {
            // Supprimer la colonne 'annee_scolaire'
            if (Schema::hasColumn('notes', 'annee_scolaire')) {
                $table->dropColumn('annee_scolaire');
            }

            // Ajouter 'ecole_id'
            $table->foreignId('ecole_id')->after('classe_id')->constrained('ecoles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            //
        });
    }
};
