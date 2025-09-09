<?php

namespace Database\Seeders;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TarifMensuelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveaux = Niveau::all();
        $typeFrais = TypeFrais::all();
        $moisScolaires = MoisScolaire::all();

        foreach ($niveaux as $niveau) {
            foreach ($typeFrais as $frais) {
                foreach ($moisScolaires as $mois) {
                    $montant = $this->getMontantParType($frais->nom, $niveau->nom);

                    // Si le montant est > 0, on crée le tarif mensuel
                    if ($montant > 0) {
                        TarifMensuel::updateOrCreate(
                            [
                                'type_frais_id' => $frais->id,
                                'niveau_id' => $niveau->id,
                                'mois_id' => $mois->id,
                            ],
                            [
                                'montant' => $montant
                            ]
                        );
                    }
                }
            }
        }
    }

    private function getMontantParType($type, $niveau)
    {
        $tarifs = [
            "Frais d'inscription" => [
                'PS' => 10000, 'MS' => 10000, 'GS' => 10000,
                'CP' => 10000, 'CE1' => 10000, 'CE2' => 10000,
                'CM1' => 10000, 'CM2' => 10000
            ],
            'Scolarité' => [
                'PS' => 150000, 'MS' => 160000, 'GS' => 170000,
                'CP' => 180000, 'CE1' => 190000, 'CE2' => 200000,
                'CM1' => 210000, 'CM2' => 220000
            ],
            'Cantine' => [
                'PS' => 50000, 'MS' => 50000, 'GS' => 50000,
                'CP' => 60000, 'CE1' => 60000, 'CE2' => 60000,
                'CM1' => 70000, 'CM2' => 70000
            ],
            'Transport' => [
                'PS' => 80000, 'MS' => 80000, 'GS' => 80000,
                'CP' => 90000, 'CE1' => 90000, 'CE2' => 90000,
                'CM1' => 100000, 'CM2' => 100000
            ]
        ];

        return $tarifs[$type][$niveau] ?? 0;
    }
}
