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
        Schema::create('ecoles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('nom_ecole');
            $table->string('sigle_ecole')->nullable();
            $table->string('logo')->nullable();
            $table->string('adresse');
            $table->string('ville')->nullable();
            $table->string('telephone');
            $table->string('fax')->nullable();
            $table->string('email');
            $table->string('directeur');
            $table->longText('entete_document')->nullable();
            $table->longText('sous_entete_document')->nullable();
            

            $table->text('footer_bulletin')->nullable();
            $table->boolean('sms_notification')->default(false);
            $table->integer('sms_disponible')->default(0);
            $table->enum('arrondi_moyenne', ['coupe', 'arrondi', 'arrondi_superieur'])->default('coupe');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecoles');
    }
};