<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mention;

class MentionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ⚠️ Adapte ces IDs selon tes données existantes
        $anneeScolaireId = 1;
        $ecoleId = 1;

        $mentions = [
            [
                'nom' => 'Insuffisant',
                'min_note' => 0,
                'max_note' => 9,
            ],
            [
                'nom' => 'Passable',
                'min_note' => 10,
                'max_note' => 11,
            ],
            [
                'nom' => 'Assez Bien',
                'min_note' => 12,
                'max_note' => 13,
            ],
            [
                'nom' => 'Bien',
                'min_note' => 14,
                'max_note' => 15,
            ],
            [
                'nom' => 'Très Bien',
                'min_note' => 16,
                'max_note' => 17,
            ],
            [
                'nom' => 'Excellent',
                'min_note' => 18,
                'max_note' => 20,
            ],
        ];

        foreach ($mentions as $mention) {
            Mention::create([
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'nom' => $mention['nom'],
                'min_note' => $mention['min_note'],
                'max_note' => $mention['max_note'],
            ]);
        }
    }
}
