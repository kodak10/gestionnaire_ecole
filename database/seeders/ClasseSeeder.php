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
        $anneeActive = AnneeScolaire::where('est_active', true)->first();
        $niveaux = Niveau::all();

        foreach ($niveaux as $niveau) {
            for ($i = 1; $i <= 1; $i++) { 
                Classe::create([
                    'niveau_id' => $niveau->id,
                    'annee_scolaire_id' => $anneeActive->id,
                    'ecole_id' => 1, // <-- fixer l'école ici
                    'nom' => $niveau->nom . '_' . chr(64 + $i), // CM2_A, CM2_B, etc.
                ]);
            }
        }
    }

}
