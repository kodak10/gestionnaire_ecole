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
        Schema::table('reinscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('reinscriptions', 'ecole_id')) {
                $table->unsignedBigInteger('ecole_id')->after('id')->nullable();

                // Si tu veux une contrainte vers `ecoles`
                $table->foreign('ecole_id')->references('id')->on('ecoles')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reinscriptions', function (Blueprint $table) {
            //
        });
    }
};
