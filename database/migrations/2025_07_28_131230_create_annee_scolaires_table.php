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
        Schema::create('annee_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('annee');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('est_active')->default(false);
            $table->timestamps();
            $table->unique(['ecole_id', 'annee'], 'unique_annee_ecole');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annee_scolaires');
    }
};