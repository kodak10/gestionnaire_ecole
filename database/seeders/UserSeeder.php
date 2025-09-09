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
        // Récupérer l'école créée ou la créer si elle n'existe pas
        $ecole = Ecole::firstOrCreate(
            ['sigle_ecole' => 'EP'],
            [
                'nom_ecole'   => 'École Primaire EXCELLE',
                'adresse'     => '123 Rue des Écoles, 75000 Paris',
                'telephone'   => '01 23 45 67 89',
                'email'       => 'contact@ecole-les-lilas.fr',
                'directeur'   => 'M. Jean Durand',
                'logo'        => 'logos/ecole-lilas.png',
            ]
        );

        // Créer l'année scolaire si elle n'existe pas
        $anneeScolaire = AnneeScolaire::firstOrCreate(
            ['annee' => '2025-2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin'   => '2026-06-30',
                'est_active' => true,
            ]
        );

        // Créer l'utilisateur admin
        User::firstOrCreate(
            ['pseudo' => 'admin'],
            [
                'name'             => 'Admin',
                'password'         => Hash::make('password'), // à changer après installation
                'ecole_id'         => $ecole->id,
                'annee_scolaire_id'=> $anneeScolaire->id,
                'is_active'        => true,
            ]
        );
    }
}
