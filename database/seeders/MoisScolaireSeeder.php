<?php

namespace Database\Seeders;

use App\Models\MoisScolaire;
use Illuminate\Database\Seeder;

class MoisScolaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mois scolaires allant de Août à Juin
        $mois = [
            ['numero' => 8, 'nom' => 'Août'],
            ['numero' => 9, 'nom' => 'Septembre'],
            ['numero' => 10, 'nom' => 'Octobre'],
            ['numero' => 11, 'nom' => 'Novembre'],
            ['numero' => 12, 'nom' => 'Décembre'],
            ['numero' => 1, 'nom' => 'Janvier'],
            ['numero' => 2, 'nom' => 'Février'],
            ['numero' => 3, 'nom' => 'Mars'],
            ['numero' => 4, 'nom' => 'Avril'],
            ['numero' => 5, 'nom' => 'Mai'],
            ['numero' => 6, 'nom' => 'Juin'],
        ];

        foreach ($mois as $m) {
            MoisScolaire::firstOrCreate(
                ['numero' => $m['numero']],
                ['nom' => $m['nom']]
            );
        }
    }
}
