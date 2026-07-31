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
            $table->foreignId('annee_scolaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->unique();
            $table->string('code_national')->nullable()->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['Masculin', 'Féminin']);
            $table->date('naissance');
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable()->default('Ivoirienne');
            $table->string('num_extrait')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('infos_medicales')->nullable();
            // Informations parent
            $table->string('parent_nom');
            $table->string('parent_telephone');
            $table->string('parent_telephone02')->nullable();
            $table->string('parent_email')->nullable();
            // Père
            $table->string('pere_nom')->nullable();
            $table->string('pere_contact')->nullable();
            $table->string('pere_contact02')->nullable();
            // Mère
            $table->string('mere_nom')->nullable();
            $table->string('mere_contact')->nullable();
            $table->string('mere_contact02')->nullable();
            // Adresse parent
            $table->string('parent_adresse')->nullable();
            // Options élève
            $table->boolean('transport_active')->default(false);
            $table->boolean('cantine_active')->default(false);
            $table->boolean('is_active')->default(true);
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