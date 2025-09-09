<?php

namespace Database\Seeders;

use App\Models\Niveau;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

            Tarif::create([
                'type_frais_id' => $frais->id,
                'niveau_id' => $niveau->id,
                'montant' => $montant,
                'ecole_id' => 1, // <-- ici on fixe l'école
            ]);
        }
    }
}

private function getMontantParType($type, $niveauNom)
{
    // Mapping des noms complets aux abréviations utilisées dans les tarifs
    $map = [
        'Petite Section' => 'PS',
        'Moyenne Section' => 'MS',
        'Grande Section' => 'GS',
        'CP1' => 'CP',
        'CP2' => 'CP',
        'CE1' => 'CE1',
        'CE2' => 'CE2',
        'CM1' => 'CM1',
        'CM2' => 'CM2',
    ];

    $niveau = $map[$niveauNom] ?? $niveauNom;

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
