<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('paiement_detail_transports');
        Schema::dropIfExists('paiement_transports');

        Schema::dropIfExists('paiement_detail_cantines');
        Schema::dropIfExists('paiement_cantines');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Les tables seront recréées par les migrations d'origine
    }
};