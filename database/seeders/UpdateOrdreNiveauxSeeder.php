<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Niveau;

class UpdateOrdreNiveauxSeeder extends Seeder
{
    public function run(): void
    {
        $ordres = [
            'Petite Section'  => 1,
            'Moyenne Section' => 2,
            'Grande Section'  => 3,
            'CP1'             => 4,
            'CP2'             => 5,
            'CE1'             => 6,
            'CE2'             => 7,
            'CM1'             => 8,
            'CM2'             => 9,
        ];

        foreach ($ordres as $nom => $ordre) {
            Niveau::where('nom', $nom)
                ->update(['ordre' => $ordre]);
        }
    }
}
