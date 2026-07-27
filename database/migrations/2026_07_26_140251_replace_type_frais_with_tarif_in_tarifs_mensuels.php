<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifs_mensuels', function (Blueprint $table) {
            // Supprimer l'ancienne clé étrangère
            $table->dropForeign(['type_frais_id']);
            
            // Supprimer la colonne type_frais_id
            $table->dropColumn('type_frais_id');
            
            // Ajouter la nouvelle colonne tarif_id
            $table->foreignId('tarif_id')
                ->after('id')
                ->constrained('tarifs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tarifs_mensuels', function (Blueprint $table) {
            // Supprimer tarif_id
            $table->dropForeign(['tarif_id']);
            $table->dropColumn('tarif_id');
            
            // Recréer type_frais_id
            $table->foreignId('type_frais_id')
                ->after('id')
                ->constrained('type_frais')
                ->onDelete('cascade');
        });
    }
};