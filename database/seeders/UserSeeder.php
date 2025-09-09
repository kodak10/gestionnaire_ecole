<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Ecole;
use App\Models\AnneeScolaire;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        

        // Créer l'année scolaire si elle n'existe pas
        $anneeScolaire = AnneeScolaire::firstOrCreate(
            ['annee' => '2025-2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin' => '2026-06-30',
                'est_active' => true,
            ]
        );

        User::firstOrCreate(
            ['pseudo' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'), // changer le mot de passe après installation
                'ecole_id' => $ecole->id,
                'annee_scolaire_id' => $anneeScolaire->id,
                'is_active' => true,
            ]
        );
    }
}
