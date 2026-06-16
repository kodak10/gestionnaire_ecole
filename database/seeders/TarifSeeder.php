<?php

namespace Database\Seeders;

use App\Models\Niveau;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Database\Seeder;

class TarifSeeder extends Seeder
{
    public function run()
    {
        $niveaux = Niveau::all();
        $typeFrais = TypeFrais::all();

        foreach ($niveaux as $niveau) {
            foreach ($typeFrais as $frais) {
                $montant = $this->getMontantParType($frais->nom, $niveau->nom);
                $obligatoire = in_array($frais->nom, ['Frais d\'inscription', 'Scolarité']);

                Tarif::updateOrCreate(
                    [
                        'annee_scolaire_id' => 3,
                        'ecole_id' => 1,
                        'type_frais_id' => $frais->id,
                        'niveau_id' => $niveau->id,
                    ],
                    [
                        'obligatoire' => $obligatoire,
                        'montant' => $montant,
                    ]
                );
            }
        }
    }

    private function getMontantParType($type, $niveauNom)
    {
        $map = [
            'Petite Section' => 'PS',
            'Moyenne Section' => 'MS',
            'Grande Section' => 'GS',
            'CP1' => 'CP1',
            'CP2' => 'CP2',
            'CE1' => 'CE1',
            'CE2' => 'CE2',
            'CM1' => 'CM1',
            'CM2' => 'CM2',
        ];

        $niveau = $map[$niveauNom] ?? $niveauNom;

        $tarifs = [
            "Frais d'inscription" => [
                'PS' => 20000, 'MS' => 20000, 'GS' => 20000,
                'CP1' => 20000, 'CP2' => 20000, 'CE1' => 20000,
                'CE2' => 20000, 'CM1' => 20000, 'CM2' => 20000
            ],
            'Scolarité' => [
                'PS' => 80000, 'MS' => 80000, 'GS' => 80000,
                'CP1' => 95000, 'CP2' => 95000,
                'CE1' => 95000, 'CE2' => 95000,
                'CM1' => 100000, 'CM2' => 100000
            ],
            'Cantine' => [
                'PS' => 10000 * 9, // 9 mois (Septembre à Mai) à 10.000 F/mois
                'MS' => 10000 * 9,
                'GS' => 10000 * 9,
                'CP1' => 10000 * 9,
                'CP2' => 10000 * 9,
                'CE1' => 10000 * 9,
                'CE2' => 10000 * 9,
                'CM1' => 10000 * 9,
                'CM2' => 10000 * 9
            ],
            'Transport' => [
                'PS' => 10000 * 9, // 9 mois (Septembre à Mai) à 10.000 F/mois
                'MS' => 10000 * 9,
                'GS' => 10000 * 9,
                'CP1' => 10000 * 9,
                'CP2' => 10000 * 9,
                'CE1' => 10000 * 9,
                'CE2' => 10000 * 9,
                'CM1' => 10000 * 9,
                'CM2' => 10000 * 9
            ]
        ];

        return $tarifs[$type][$niveau] ?? 0;
    }
}