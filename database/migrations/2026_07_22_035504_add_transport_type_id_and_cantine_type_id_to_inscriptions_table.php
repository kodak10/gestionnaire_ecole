<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // Ajouter la colonne transport_type_id
            if (!Schema::hasColumn('inscriptions', 'transport_type_id')) {
                $table->foreignId('transport_type_id')
                    ->nullable()
                    ->after('transport_active')
                    ->constrained('type_frais')
                    ->nullOnDelete();
            }

            // Ajouter la colonne cantine_type_id
            if (!Schema::hasColumn('inscriptions', 'cantine_type_id')) {
                $table->foreignId('cantine_type_id')
                    ->nullable()
                    ->after('cantine_active')
                    ->constrained('type_frais')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'transport_type_id')) {
                $table->dropForeign(['transport_type_id']);
                $table->dropColumn('transport_type_id');
            }

            if (Schema::hasColumn('inscriptions', 'cantine_type_id')) {
                $table->dropForeign(['cantine_type_id']);
                $table->dropColumn('cantine_type_id');
            }
        });
    }
};