<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Niveau;

class NiveauxSeeder extends Seeder
{
    public function run(): void
{
    $niveaux = [
        ['nom' => 'Petite Section', 'ordre' => 1],
        ['nom' => 'Moyenne Section', 'ordre' => 2],
        ['nom' => 'Grande Section', 'ordre' => 3],
        ['nom' => 'CP1', 'ordre' => 4],
        ['nom' => 'CP2', 'ordre' => 5],
        ['nom' => 'CE1', 'ordre' => 6],
        ['nom' => 'CE2', 'ordre' => 7],
        ['nom' => 'CM1', 'ordre' => 8],
        ['nom' => 'CM2', 'ordre' => 9],
    ];

    foreach ($niveaux as $niveau) {
        Niveau::create([
            'nom' => $niveau['nom'],
            'ordre' => $niveau['ordre'],
            'ecole_id' => 1,
            'annee_scolaire_id' => 3,
        ]);
    }
}
}
