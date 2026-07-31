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
        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ecole_id')->constrained()->cascadeOnDelete();
            $table->foreignId('type_frais_id')->constrained()->cascadeOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained()->nullOnDelete();
            $table->string('libelle')->nullable();
            $table->boolean('obligatoire')->default(false);
            $table->decimal('montant', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifs');
    }
};