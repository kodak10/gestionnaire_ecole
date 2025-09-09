<?php

namespace Database\Seeders;

use App\Models\Mention;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MentionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $ecoleId = 1; // ID de l'école par défaut

        $mentions = [
            [
                'nom' => 'Passable',
                'description' => 'Performance minimale acceptable',
                'min_note' => 10,
                'max_note' => 11,
            ],
            [
                'nom' => 'Assez Bien',
                'description' => 'Travail correct avec quelques insuffisances',
                'min_note' => 12,
                'max_note' => 13,
            ],
            [
                'nom' => 'Bien',
                'description' => 'Bon niveau de maîtrise',
                'min_note' => 14,
                'max_note' => 15,
            ],
            [
                'nom' => 'Très Bien',
                'description' => 'Très bon niveau, peu d’erreurs',
                'min_note' => 16,
                'max_note' => 17,
            ],
            [
                'nom' => 'Excellent',
                'description' => 'Maîtrise parfaite et rigueur exemplaire',
                'min_note' => 18,
                'max_note' => 20,
            ],
        ];

        foreach ($mentions as $mention) {
            Mention::firstOrCreate(
                ['nom' => $mention['nom'], 'ecole_id' => $ecoleId], // condition unique
                array_merge($mention, ['ecole_id' => $ecoleId])     // valeurs à insérer si pas trouvé
            );
        }

    }
}
