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
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained()->onDelete('cascade');
            $table->string('nom_prenoms');
            $table->string('matricule')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('telephone')->nullable();
            $table->string('genre')->nullable(); // M ou F
            $table->string('specialite')->nullable(); // ex: Mathématiques, Français, etc.
            $table->date('date_naissance')->nullable();
            $table->string('adresse')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
