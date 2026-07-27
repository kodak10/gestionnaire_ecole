<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si la colonne type_frais_id existe et la remplacer par tarif_id
        Schema::table('reductions', function (Blueprint $table) {
            // Supprimer l'ancienne clé étrangère si elle existe
            if (Schema::hasColumn('reductions', 'type_frais_id')) {
                $table->dropForeign(['type_frais_id']);
                $table->dropColumn('type_frais_id');
            }

            // Ajouter la colonne tarif_id
            if (!Schema::hasColumn('reductions', 'tarif_id')) {
                $table->foreignId('tarif_id')
                    ->nullable()
                    ->after('inscription_id')
                    ->constrained('tarifs')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reductions', function (Blueprint $table) {
            if (Schema::hasColumn('reductions', 'tarif_id')) {
                $table->dropForeign(['tarif_id']);
                $table->dropColumn('tarif_id');
            }

            if (!Schema::hasColumn('reductions', 'type_frais_id')) {
                $table->foreignId('type_frais_id')
                    ->nullable()
                    ->after('inscription_id')
                    ->constrained('type_frais')
                    ->onDelete('set null');
            }
        });
    }
};