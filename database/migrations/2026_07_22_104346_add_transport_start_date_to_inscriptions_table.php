<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('inscriptions', 'transport_start_date')) {
                $table->date('transport_start_date')->nullable()->after('transport_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'transport_start_date')) {
                $table->dropColumn('transport_start_date');
            }
        });
    }
};