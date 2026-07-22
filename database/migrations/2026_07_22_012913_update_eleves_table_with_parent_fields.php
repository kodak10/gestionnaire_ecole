<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            // Ajouter les nouvelles colonnes si elles n'existent pas
            
            // Champs pour le père
            if (!Schema::hasColumn('eleves', 'pere_nom')) {
                $table->string('pere_nom')->nullable()->after('infos_medicales');
            }
            
            if (!Schema::hasColumn('eleves', 'pere_contact')) {
                $table->string('pere_contact')->nullable()->after('pere_nom');
            }
            
            if (!Schema::hasColumn('eleves', 'pere_contact02')) {
                $table->string('pere_contact02')->nullable()->after('pere_contact');
            }
            
            // Champs pour la mère
            if (!Schema::hasColumn('eleves', 'mere_nom')) {
                $table->string('mere_nom')->nullable()->after('pere_contact02');
            }
            
            if (!Schema::hasColumn('eleves', 'mere_contact')) {
                $table->string('mere_contact')->nullable()->after('mere_nom');
            }
            
            if (!Schema::hasColumn('eleves', 'mere_contact02')) {
                $table->string('mere_contact02')->nullable()->after('mere_contact');
            }
            
            // Adresse
            if (!Schema::hasColumn('eleves', 'parent_adresse')) {
                $table->string('parent_adresse')->nullable()->after('mere_contact02');
            }
            
            // Nationalité
            if (!Schema::hasColumn('eleves', 'nationalite')) {
                $table->string('nationalite')->nullable()->default('Ivoirienne')->after('lieu_naissance');
            }
            
            // Classe et options
            if (!Schema::hasColumn('eleves', 'classe_id')) {
                $table->foreignId('classe_id')->nullable()->after('ecole_id')->constrained()->nullOnDelete();
            }
            
            if (!Schema::hasColumn('eleves', 'transport_active')) {
                $table->boolean('transport_active')->default(false)->after('parent_adresse');
            }
            
            if (!Schema::hasColumn('eleves', 'cantine_active')) {
                $table->boolean('cantine_active')->default(false)->after('transport_active');
            }
            
            if (!Schema::hasColumn('eleves', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('cantine_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            // Supprimer les colonnes ajoutées
            $columns = [
                'pere_nom', 'pere_contact', 'pere_contact02',
                'mere_nom', 'mere_contact', 'mere_contact02',
                'parent_adresse', 'nationalite', 'transport_active',
                'cantine_active', 'is_active'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('eleves', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Supprimer la clé étrangère et la colonne classe_id
            if (Schema::hasColumn('eleves', 'classe_id')) {
                $table->dropForeign(['classe_id']);
                $table->dropColumn('classe_id');
            }
        });
    }
};