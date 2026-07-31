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
        Schema::create('reinscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->date('date_reinscription');
            $table->enum('statut', ['en_attente', 'validée', 'refusée'])->default('en_attente');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reinscriptions');
    }
};