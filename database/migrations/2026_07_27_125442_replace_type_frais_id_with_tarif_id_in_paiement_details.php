<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_details', function (Blueprint $table) {
            // Ajouter la colonne tarif_id
            if (!Schema::hasColumn('paiement_details', 'tarif_id')) {
                $table->foreignId('tarif_id')
                    ->nullable()
                    ->after('inscription_id')
                    ->constrained('tarifs')
                    ->nullOnDelete();
            }
        });

        // Migrer les données existantes (si nécessaire)
        // Copier les données de type_frais_id vers tarif_id
        DB::statement('
            UPDATE paiement_details pd
            SET pd.tarif_id = (
                SELECT t.id 
                FROM tarifs t 
                WHERE t.type_frais_id = pd.type_frais_id 
                AND t.annee_scolaire_id = (
                    SELECT i.annee_scolaire_id 
                    FROM inscriptions i 
                    WHERE i.id = pd.inscription_id
                )
                AND t.ecole_id = (
                    SELECT i.ecole_id 
                    FROM inscriptions i 
                    WHERE i.id = pd.inscription_id
                )
                AND t.niveau_id = (
                    SELECT i.classe_id 
                    FROM inscriptions i 
                    JOIN classes c ON c.id = i.classe_id 
                    WHERE i.id = pd.inscription_id
                )
                LIMIT 1
            )
            WHERE pd.tarif_id IS NULL
        ');

        Schema::table('paiement_details', function (Blueprint $table) {
            // Supprimer la colonne type_frais_id
            if (Schema::hasColumn('paiement_details', 'type_frais_id')) {
                $table->dropForeign(['type_frais_id']);
                $table->dropColumn('type_frais_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paiement_details', function (Blueprint $table) {
            // Recréer la colonne type_frais_id
            if (!Schema::hasColumn('paiement_details', 'type_frais_id')) {
                $table->foreignId('type_frais_id')
                    ->nullable()
                    ->after('inscription_id')
                    ->constrained('type_frais')
                    ->nullOnDelete();
            }
        });

        // Migrer les données en sens inverse
        DB::statement('
            UPDATE paiement_details pd
            SET pd.type_frais_id = (
                SELECT t.type_frais_id 
                FROM tarifs t 
                WHERE t.id = pd.tarif_id
                LIMIT 1
            )
            WHERE pd.type_frais_id IS NULL
        ');

        Schema::table('paiement_details', function (Blueprint $table) {
            // Supprimer la colonne tarif_id
            if (Schema::hasColumn('paiement_details', 'tarif_id')) {
                $table->dropForeign(['tarif_id']);
                $table->dropColumn('tarif_id');
            }
        });
    }
};