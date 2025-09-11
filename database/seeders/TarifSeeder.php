<?php

namespace Database\Seeders;

use App\Models\Niveau;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Database\Seeder;

class TarifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $niveaux = Niveau::all();
        $typeFrais = TypeFrais::all();

        foreach ($niveaux as $niveau) {
            foreach ($typeFrais as $frais) {
                $montant = $this->getMontantParType($frais->nom, $niveau->nom);
                $obligatoire = in_array($frais->nom, ['Frais d\'inscription', 'Scolarité']);

                Tarif::create([
                    'annee_scolaire_id' => 1,
                    'ecole_id' => 1,
                    'type_frais_id' => $frais->id,
                    'niveau_id' => $niveau->id,
                    'obligatoire' => $obligatoire,
                    'montant' => $montant,
                ]);
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
                'CP1' => 90000, 'CP2' => 90000,
                'CE1' => 95000, 'CE2' => 95000,
                'CM1' => 100000, 'CM2' => 100000
            ],
            'Cantine' => [
                'PS' => 72000, 'MS' => 72000, 'GS' => 72000,
                'CP1' => 72000, 'CP2' => 72000,
                'CE1' => 72000, 'CE2' => 72000,
                'CM1' => 72000, 'CM2' => 72000
            ],
            'Transport' => [
                'PS' => 90000, 'MS' => 90000, 'GS' => 90000,
                'CP1' => 90000, 'CP2' => 90000,
                'CE1' => 90000, 'CE2' => 90000,
                'CM1' => 90000, 'CM2' => 90000
            ]
        ];

        return $tarifs[$type][$niveau] ?? 0;
    }
}
