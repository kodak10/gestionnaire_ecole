<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        // Vérifier si des écoles existent, sinon en créer
        $ecoles = Ecole::all();
        if ($ecoles->isEmpty()) {
            $ecole1 = Ecole::create([
                'nom' => 'École Primaire Les Lauriers',
                'adresse' => '123 Avenue des Écoles, Dakar',
                'telephone' => '+221 33 123 45 67',
                'email' => 'contact@lauriers.sn',
                'directeur' => 'M. Abdoulaye Diop',
            ]);

            $ecole2 = Ecole::create([
                'nom' => 'Collège Moderne Excellence',
                'adresse' => '456 Rue des Savants, Thiès',
                'telephone' => '+221 33 987 65 43',
                'email' => 'info@excellence.sn',
                'directeur' => 'Mme Aminata Sow',
            ]);

            $ecoles = collect([$ecole1, $ecole2]);
        }

        // Vérifier si des années scolaires existent, sinon en créer
        $anneesScolaires = AnneeScolaire::all();
        if ($anneesScolaires->isEmpty()) {
            $annee1 = AnneeScolaire::create([
                'annee_debut' => 2024,
                'annee_fin' => 2025,
                'is_active' => true,
            ]);

            $annee2 = AnneeScolaire::create([
                'annee_debut' => 2023,
                'annee_fin' => 2024,
                'is_active' => false,
            ]);

            $anneesScolaires = collect([$annee1, $annee2]);
        }

        // Liste des utilisateurs à créer
        $users = [
            // Utilisateurs pour la première école
            [
                'name' => 'Directeur École Lauriers',
                'pseudo' => 'admin',
                'password' => 'password',
                'ecole_id' => $ecoles[0]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],
            [
                'name' => 'Secrétaire École Lauriers',
                'pseudo' => 'secretaire1',
                'password' => 'password',
                'ecole_id' => $ecoles[0]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],
            [
                'name' => 'Comptable École Lauriers',
                'pseudo' => 'comptable1',
                'password' => 'password',
                'ecole_id' => $ecoles[0]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],

            // Utilisateurs pour la deuxième école
            [
                'name' => 'Directeur Collège Excellence',
                'pseudo' => 'directeur2',
                'password' => 'password',
                'ecole_id' => $ecoles[1]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],
            [
                'name' => 'Enseignant Mathématiques',
                'pseudo' => 'profmath',
                'password' => 'password',
                'ecole_id' => $ecoles[1]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],
            [
                'name' => 'Surveillant Général',
                'pseudo' => 'surveillant',
                'password' => 'password',
                'ecole_id' => $ecoles[1]->id,
                'annee_scolaire_id' => $anneesScolaires[0]->id,
            ],
        ];

        $createdCount = 0;
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
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