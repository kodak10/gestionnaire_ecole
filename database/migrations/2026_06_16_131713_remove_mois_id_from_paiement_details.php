<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Pour paiement_detail_cantines
        Schema::table('paiement_detail_cantines', function (Blueprint $table) {
            // 1. Supprimer la clé étrangère d'abord
            $table->dropForeign(['mois_id']);
            // 2. Puis supprimer la colonne
            $table->dropColumn('mois_id');
        });

        // Pour paiement_detail_transports
        Schema::table('paiement_detail_transports', function (Blueprint $table) {
            // 1. Supprimer la clé étrangère d'abord
            $table->dropForeign(['mois_id']);
            // 2. Puis supprimer la colonne
            $table->dropColumn('mois_id');
        });
    }

    public function down()
    {
        // Recréer les colonnes en cas de rollback
        Schema::table('paiement_detail_cantines', function (Blueprint $table) {
            $table->unsignedBigInteger('mois_id')->nullable();
            // Recréer la clé étrangère
            $table->foreign('mois_id')->references('id')->on('mois_scolaires')->onDelete('set null');
        });

        Schema::table('paiement_detail_transports', function (Blueprint $table) {
            $table->unsignedBigInteger('mois_id')->nullable();
            $table->foreign('mois_id')->references('id')->on('mois_scolaires')->onDelete('set null');
        });
    }
};