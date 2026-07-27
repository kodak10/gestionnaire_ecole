<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToReductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reductions', function (Blueprint $table) {
            // Ajouter la colonne user_id après tarif_id
            $table->unsignedBigInteger('user_id')->nullable()->after('tarif_id');
            
            // Ajouter la clé étrangère vers la table users
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reductions', function (Blueprint $table) {
            // Supprimer la clé étrangère d'abord
            $table->dropForeign(['user_id']);
            
            // Puis supprimer la colonne
            $table->dropColumn('user_id');
        });
    }
}