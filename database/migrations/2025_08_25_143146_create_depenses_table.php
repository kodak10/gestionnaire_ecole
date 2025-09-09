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
    Schema::create('depenses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('annee_scolaire_id')->constrained();
        $table->foreignId('ecole_id')->constrained();
        $table->string('libelle');
        $table->text('description')->nullable();
        $table->decimal('montant', 10, 2);
        $table->date('date_depense');
        $table->foreignId('depense_category_id')->constrained('depense_categories')->onDelete('cascade');
        $table->string('mode_paiement')->nullable();
        $table->string('beneficiaire')->nullable();
        $table->string('reference')->nullable();
        $table->string('justificatif')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
