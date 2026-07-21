<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->enum('type', [
                'recu_paiement',
                'relance',
                'information',
                'bulletin',
                'autre'
            ])->default('autre');
            $table->longText('content');
            $table->json('variables')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_templates');
    }
};