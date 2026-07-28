<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            // Ajouter la colonne pour le mode d'arrondi des moyennes
            if (!Schema::hasColumn('ecoles', 'arrondi_moyenne')) {
                $table->enum('arrondi_moyenne', ['coupe', 'arrondi', 'arrondi_superieur'])->default('coupe')->after('sms_disponible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            if (Schema::hasColumn('ecoles', 'arrondi_moyenne')) {
                $table->dropColumn('arrondi_moyenne');
            }
        });
    }
};