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
        Schema::table('ecoles', function (Blueprint $table) {
            $table->boolean('sms_notification')->default(false)->after('footer_bulletin')->comment('Activer l\'envoi des messages de paiement par SMS');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecoles', function (Blueprint $table) {
            //
        });
    }
};
