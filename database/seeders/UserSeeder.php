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
        // Créer l'école si elle n'existe pas
        $ecole = Ecole::firstOrCreate(
            ['nom' => 'EPP Excelle'],
            [
                'adresse' => '123 Rue de l\'École, Abidjan',
                'telephone' => '+225 01 23 45 67',
                'email' => 'contact@eppexcelle.ci',
                'directeur' => 'M. Admin',
                'logo' => null // ou mettre un chemin vers le logo par défaut
            ]
        );

        // Créer l'année scolaire si elle n'existe pas
        $anneeScolaire = AnneeScolaire::firstOrCreate(
            ['annee' => '2025-2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin' => '2026-06-30',
                'est_active' => true,
            ]
        );

        // Créer l'utilisateur admin
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
