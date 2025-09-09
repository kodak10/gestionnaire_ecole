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
        // École 1 - années scolaires
        AnneeScolaire::create([
            'ecole_id'    => 1,
            'annee'       => '2024-2025',
            'date_debut'  => '2024-09-01',
            'date_fin'    => '2025-06-30',
            'est_active'  => true,
        ]);

        AnneeScolaire::create([
            'ecole_id'    => 1,
            'annee'       => '2023-2024',
            'date_debut'  => '2023-09-01',
            'date_fin'    => '2024-06-30',
            'est_active'  => false,
        ]);

        // École 2 - années scolaires
        AnneeScolaire::create([
            'ecole_id'    => 2,
            'annee'       => '2024-2025',
            'date_debut'  => '2024-09-01',
            'date_fin'    => '2025-06-30',
            'est_active'  => true,
        ]);

        AnneeScolaire::create([
            'ecole_id'    => 2,
            'annee'       => '2023-2024',
            'date_debut'  => '2023-09-01',
            'date_fin'    => '2024-06-30',
            'est_active'  => false,
        ]);
    }

}
