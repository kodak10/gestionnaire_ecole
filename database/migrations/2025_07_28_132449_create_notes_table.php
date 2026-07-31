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
        Schema::create('notes', function (Blueprint $table) {
    $table->id();

    $table->foreignId('annee_scolaire_id')
        ->constrained('annee_scolaires')
        ->cascadeOnDelete();

    $table->foreignId('ecole_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('inscription_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('classe_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('matiere_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('mois_id')
        ->constrained('mois_scolaires')
        ->cascadeOnDelete();

    // Colonne temporaire pour import ancien dump
    $table->string('annee_scolaire')->nullable();

    $table->decimal('valeur', 5, 2)->nullable();

    $table->decimal('coefficient', 5, 2)
        ->default(1);

    $table->text('appreciation')
        ->nullable();

    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->timestamps();

    $table->unique(
        [
            'inscription_id',
            'matiere_id',
            'mois_id',
            'annee_scolaire_id',
            'ecole_id'
        ],
        'note_unique'
    );
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};