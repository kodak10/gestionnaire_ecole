<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifs', function (Blueprint $table) {
            // Vérifier si la colonne niveau_id existe
            if (Schema::hasColumn('tarifs', 'niveau_id')) {
                // Vérifier si la clé étrangère existe avant de la supprimer
                $foreignKeyName = $this->getForeignKeyName('tarifs', 'niveau_id');
                
                if ($foreignKeyName) {
                    try {
                        $table->dropForeign($foreignKeyName);
                    } catch (\Exception $e) {
                        // Ignorer l'erreur si la clé n'existe pas
                    }
                }
                
                // Modifier la colonne pour la rendre nullable
                $table->foreignId('niveau_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tarifs', function (Blueprint $table) {
            if (Schema::hasColumn('tarifs', 'niveau_id')) {
                // Vérifier si la clé étrangère existe avant de la supprimer
                $foreignKeyName = $this->getForeignKeyName('tarifs', 'niveau_id');
                
                if ($foreignKeyName) {
                    try {
                        $table->dropForeign($foreignKeyName);
                    } catch (\Exception $e) {
                        // Ignorer l'erreur si la clé n'existe pas
                    }
                }
                
                $table->foreignId('niveau_id')->nullable(false)->change();
                $table->foreign('niveau_id')->references('id')->on('niveaux')->onDelete('cascade');
            }
        });
    }

    /**
     * Récupérer le nom de la clé étrangère
     */
    private function getForeignKeyName($tableName, $columnName)
    {
        try {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            $result = $connection->select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$databaseName, $tableName, $columnName]);
            
            return $result ? $result[0]->CONSTRAINT_NAME : null;
        } catch (\Exception $e) {
            return null;
        }
    }
};