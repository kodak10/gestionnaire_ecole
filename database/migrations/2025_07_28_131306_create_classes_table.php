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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->foreignId('niveau_id')->constrained();
            $table->string('nom');
            $table->unsignedInteger('capacite')->default(50);
            $table->decimal('moy_base', 10, 2)->default(20.00);
            $table->foreignId('enseignant_id')->nullable()->constrained('enseignants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};