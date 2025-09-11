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
        // Récupérer les écoles existantes
        $ecoles = Ecole::all();

        // Récupérer l'année scolaire active
        $anneeActive = AnneeScolaire::where('est_active', true)->first();

        if ($ecoles->isEmpty() || !$anneeActive) {
            $this->command->info('Veuillez d’abord exécuter EcolesSeeder et AnneeScolaireSeeder.');
            return;
        }

        // Liste des utilisateurs à créer
        $users = [
            [
                'name' => 'MME KONE',
                'pseudo' => 'admin',
                'password' => 'password',
                'ecole_id' => $ecoles[0]->id,
                'annee_scolaire_id' => $anneeActive->id,
            ],
            [
                'name' => 'Directeur Collège Excellence',
                'pseudo' => 'admin2',
                'password' => 'password',
                'ecole_id' => $ecoles[1]->id,
                'annee_scolaire_id' => $anneeActive->id,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['pseudo' => $userData['pseudo']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'ecole_id' => $userData['ecole_id'],
                    'annee_scolaire_id' => $userData['annee_scolaire_id'],
                    'is_active' => true,
                ]
            );
        }
    }
}
