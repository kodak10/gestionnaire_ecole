<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            $table->string('iepp')
                ->nullable()
                ->after('nom_ecole');

            $table->string('secteur_pedagogique')
                ->nullable()
                ->after('iepp');

            $table->string('sous_prefecture')
                ->nullable()
                ->after('secteur_pedagogique');

            $table->string('circonscription_primaire')
                ->nullable()
                ->after('sous_prefecture');

            $table->string('num_registre')
                ->nullable()
                ->after('circonscription_primaire');

            $table->string('directeur_etudes')
                ->nullable()
                ->after('directeur');

            $table->string('logo_republique')
                ->nullable()
                ->after('entete_document');

        });
    }

    public function down(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            $table->dropColumn([
                'iepp',
                'secteur_pedagogique',
                'sous_prefecture',
                'circonscription_primaire',
                'num_registre',
                'directeur_etudes',
                'logo_republique',
                'logo_ecole',
            ]);
        });
    }
};