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
        Schema::create('niveau_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained()->onDelete('cascade');
            $table->foreignId('ecole_id')->constrained()->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained()->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained()->onDelete('cascade');
            $table->integer('coefficient')->default(1);
            $table->timestamps();

            $table->unique(['niveau_id', 'matiere_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('niveau_matiere');
    }
};
