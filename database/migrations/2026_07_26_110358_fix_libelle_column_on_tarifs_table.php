<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer une éventuelle clé étrangère sur libelle
        try {
            Schema::table('tarifs', function (Blueprint $table) {
                $table->dropForeign(['libelle']);
            });
        } catch (\Throwable $e) {
            // Aucune clé étrangère
        }

        // Modifier la colonne en VARCHAR
        DB::statement("
            ALTER TABLE tarifs
            MODIFY COLUMN libelle VARCHAR(255) NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE tarifs
            MODIFY COLUMN libelle BIGINT UNSIGNED NOT NULL
        ");

        Schema::table('tarifs', function (Blueprint $table) {
            $table->foreign('libelle')
                  ->references('id')
                  ->on('libelles');
        });
    }
};