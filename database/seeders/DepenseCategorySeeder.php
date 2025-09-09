<?php

namespace Database\Seeders;

use App\Models\DepenseCategorie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Scolarité', 'Cantine', 'Transport'];

        foreach ($categories as $cat) {
            DepenseCategorie::firstOrCreate([
                'nom' => $cat,
                'ecole_id' => 1,
                'annee_scolaire_id' => 1, // ajout de l'année scolaire
            ]);
        }
    }
}
