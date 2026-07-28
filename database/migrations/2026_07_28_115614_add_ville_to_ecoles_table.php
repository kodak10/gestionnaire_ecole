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
            // Ajouter la colonne ville après adresse
            if (!Schema::hasColumn('ecoles', 'ville')) {
                $table->string('ville')->nullable()->after('adresse');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            if (Schema::hasColumn('ecoles', 'ville')) {
                $table->dropColumn('ville');
            }
        });
    }
};