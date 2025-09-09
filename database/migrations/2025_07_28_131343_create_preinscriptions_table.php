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
        Schema::create('preinscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained();
             $table->foreignId('ecole_id')->constrained();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['Masculin', 'Féminin']);
            $table->date('date_naissance');
            $table->string('lieu_naissance');
            $table->string('adresse')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('classe_demandee');
            $table->string('ecole_provenance')->nullable();
            $table->string('nom_parent');
            $table->string('telephone_parent');
            $table->string('email_parent')->nullable();
            $table->enum('statut', ['en_attente', 'validée', 'refusée'])->default('en_attente');
            $table->date('date_preinscription');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_inscriptions');
    }
};
