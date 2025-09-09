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
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->foreignId('ecole_id')->constrained();
            $table->string('matricule')->unique();
            $table->string('code_national')->nullable()->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['Masculin', 'Féminin']);
            $table->date('naissance');
            $table->string('lieu_naissance')->nullable();
             $table->string('num_extrait')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('infos_medicales')->nullable();
            $table->string('parent_nom');
            $table->string('parent_telephone');
            $table->string('parent_email')->nullable();
           
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};
