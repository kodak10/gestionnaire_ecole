<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // 1. Supprimer les colonnes type (clés étrangères)
            if (Schema::hasColumn('inscriptions', 'cantine_type_id')) {
                // Supprimer la clé étrangère si elle existe
                try {
                    $table->dropForeign(['cantine_type_id']);
                } catch (\Exception $e) {
                    // La clé étrangère n'existe pas, continuer
                }
                $table->dropColumn('cantine_type_id');
            }

            if (Schema::hasColumn('inscriptions', 'transport_type_id')) {
                try {
                    $table->dropForeign(['transport_type_id']);
                } catch (\Exception $e) {
                    // La clé étrangère n'existe pas, continuer
                }
                $table->dropColumn('transport_type_id');
            }

            // 2. Ajouter les nouvelles colonnes tarif_id
            if (!Schema::hasColumn('inscriptions', 'transport_tarif_id')) {
                $table->foreignId('transport_tarif_id')
                    ->nullable()
                    ->after('transport_active')
                    ->constrained('tarifs')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('inscriptions', 'cantine_tarif_id')) {
                $table->foreignId('cantine_tarif_id')
                    ->nullable()
                    ->after('cantine_active')
                    ->constrained('tarifs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // 1. Supprimer les nouvelles colonnes
            if (Schema::hasColumn('inscriptions', 'transport_tarif_id')) {
                try {
                    $table->dropForeign(['transport_tarif_id']);
                } catch (\Exception $e) {
                    // La clé étrangère n'existe pas, continuer
                }
                $table->dropColumn('transport_tarif_id');
            }

            if (Schema::hasColumn('inscriptions', 'cantine_tarif_id')) {
                try {
                    $table->dropForeign(['cantine_tarif_id']);
                } catch (\Exception $e) {
                    // La clé étrangère n'existe pas, continuer
                }
                $table->dropColumn('cantine_tarif_id');
            }

            // 2. Recréer les anciennes colonnes
            if (!Schema::hasColumn('inscriptions', 'cantine_type_id')) {
                $table->foreignId('cantine_type_id')
                    ->nullable()
                    ->after('cantine_active')
                    ->constrained('type_frais')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('inscriptions', 'transport_type_id')) {
                $table->foreignId('transport_type_id')
                    ->nullable()
                    ->after('transport_active')
                    ->constrained('type_frais')
                    ->nullOnDelete();
            }
        });
    }
};