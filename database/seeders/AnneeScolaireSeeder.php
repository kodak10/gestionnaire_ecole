<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnneeScolaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // École 1 - année scolaire
        AnneeScolaire::create([
            'ecole_id'    => 1,
            'annee'       => '2025-2026',
            'date_debut'  => '2025-09-01',
            'date_fin'    => '2026-06-30',
            'est_active'  => true,
        ]);

        // École 2 - année scolaire
        AnneeScolaire::create([
            'ecole_id'    => 2,
            'annee'       => '2025-2026',
            'date_debut'  => '2025-09-01',
            'date_fin'    => '2026-06-30',
            'est_active'  => true,
        ]);
    }


}
