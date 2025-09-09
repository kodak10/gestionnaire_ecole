<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Niveau;

class NiveauxSeeder extends Seeder
{
    public function run(): void
    {
        $niveaux = [
            ['nom' => 'Petite Section'],
            ['nom' => 'Moyenne Section'],
            ['nom' => 'Grande Section'],
            ['nom' => 'CP1'],
            ['nom' => 'CP2'],
            ['nom' => 'CE1'],
            ['nom' => 'CE2'],
            ['nom' => 'CM1'],
            ['nom' => 'CM2'],
        ];

        foreach ($niveaux as $niveau) {
            Niveau::create([
                'nom' => $niveau['nom'],
                'ecole_id' => 1,            // fixe ou dynamique
                'annee_scolaire_id' => 1,   // fixe ou récupéré
            ]);
        }
    }
}
