<?php

namespace Database\Seeders;

use App\Models\TypeFrais;
use Illuminate\Database\Seeder;

class TypeFraisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['nom' => "Frais d'inscription", 'obligatoire' => true, 'ecole_id' => 1, 'annee_scolaire_id' => 3],
            ['nom' => 'Scolarité', 'obligatoire' => true, 'ecole_id' => 1, 'annee_scolaire_id' => 3],
            ['nom' => 'Cantine', 'obligatoire' => false, 'ecole_id' => 1, 'annee_scolaire_id' => 3],
            ['nom' => 'Transport', 'obligatoire' => false, 'ecole_id' => 1, 'annee_scolaire_id' => 3],
        ];

        foreach ($types as $type) {
            TypeFrais::create($type);
        }
    }
}
