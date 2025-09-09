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
        Schema::create('paiement_detail_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_transport_id')->constrained('paiement_transports')->onDelete('cascade');
            $table->foreignId('mois_id')->constrained('mois_scolaires')->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_detail_transports');
    }
};
