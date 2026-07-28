<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCantineStartDateToInscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // Ajouter la colonne cantine_start_date après cantine_tarif_id
            $table->date('cantine_start_date')->nullable()->after('cantine_tarif_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropColumn('cantine_start_date');
        });
    }
}