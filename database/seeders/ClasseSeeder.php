<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    $niveaux = Niveau::where('ecole_id', 1)
        ->where('annee_scolaire_id', 3)
        ->orderBy('ordre')
        ->get();

    foreach ($niveaux as $niveau) {
        Classe::firstOrCreate(
            [
                'niveau_id' => $niveau->id,
                'annee_scolaire_id' => 3,
                'ecole_id' => 1,
            ],
            [
                'nom' => $niveau->nom . '_A',
            ]
        );
    }
}

}
