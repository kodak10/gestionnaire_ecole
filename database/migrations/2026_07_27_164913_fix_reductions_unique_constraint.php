<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixReductionsUniqueConstraint extends Migration
{
    public function up()
    {
        Schema::table('reductions', function (Blueprint $table) {
            // Supprimer d'abord les clés étrangères qui utilisent cet index
            $table->dropForeign(['inscription_id']);
            $table->dropForeign(['annee_scolaire_id']);
            
            // Supprimer l'ancienne contrainte unique
            $table->dropUnique(['inscription_id', 'annee_scolaire_id']);
        });

        Schema::table('reductions', function (Blueprint $table) {
            // Ajouter la nouvelle contrainte unique (inscription_id + tarif_id + annee_scolaire_id)
            $table->unique(['inscription_id', 'tarif_id', 'annee_scolaire_id'], 'reductions_inscription_tarif_annee_unique');
            
            // Recréer les clés étrangères
            $table->foreign('inscription_id')
                  ->references('id')
                  ->on('inscriptions')
                  ->onDelete('cascade');
                  
            $table->foreign('annee_scolaire_id')
                  ->references('id')
                  ->on('annee_scolaires')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('reductions', function (Blueprint $table) {
            // Supprimer les clés étrangères
            $table->dropForeign(['inscription_id']);
            $table->dropForeign(['annee_scolaire_id']);
            
            // Supprimer la nouvelle contrainte
            $table->dropUnique('reductions_inscription_tarif_annee_unique');
        });

        Schema::table('reductions', function (Blueprint $table) {
            // Recréer l'ancienne contrainte
            $table->unique(['inscription_id', 'annee_scolaire_id']);
            
            // Recréer les clés étrangères
            $table->foreign('inscription_id')
                  ->references('id')
                  ->on('inscriptions')
                  ->onDelete('cascade');
                  
            $table->foreign('annee_scolaire_id')
                  ->references('id')
                  ->on('annee_scolaires')
                  ->onDelete('cascade');
        });
    }
}