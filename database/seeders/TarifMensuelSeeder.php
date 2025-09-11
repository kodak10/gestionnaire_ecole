<?php

namespace Database\Seeders;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Database\Seeder;

class TarifMensuelSeeder extends Seeder
{
    public function run(): void
    {
        $niveaux = Niveau::all();
        $typeFrais = TypeFrais::all();
        $moisScolaires = MoisScolaire::all();

        foreach ($niveaux as $niveau) {
            foreach ($typeFrais as $frais) {
                foreach ($moisScolaires as $mois) {
                    $montant = $this->getMontantParType($frais->nom, $niveau->nom, $mois->numero);

                    if ($montant > 0) {
                        TarifMensuel::updateOrCreate(
                            [
                                'type_frais_id' => $frais->id,
                                'niveau_id' => $niveau->id,
                                'mois_id' => $mois->id,
                            ],
                            [
                                'montant' => $montant,
                                'annee_scolaire_id' => 1, // valeur par défaut
                                'ecole_id' => 1,          // valeur par défaut
                            ]
                        );
                    }
                }
            }
        }
    }

    private function getMontantParType($type, $niveau, $moisNumero)
    {
        $niveauMap = [
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

        $niveau = $niveauMap[$niveau] ?? $niveau;

        switch ($type) {
            case "Frais d'inscription":
                return ($moisNumero === 9) ? 20000 : 0; // septembre

            case "Cantine":
                if (in_array($niveau, ['PS', 'MS', 'GS'])) {
                    return in_array($moisNumero, [10,11,12,1,2,3,4,5,6]) ? 8000 : 0; // oct → juin
                }
                return in_array($moisNumero, [9,10,11,12,1,2,3,4,5,6]) ? 8000 : 0; // sept → juin

            case "Transport":
                if (in_array($niveau, ['PS', 'MS', 'GS'])) {
                    return in_array($moisNumero, [10,11,12,1,2,3,4,5,6]) ? 10000 : 0; // oct → juin
                }
                return in_array($moisNumero, [9,10,11,12,1,2,3,4,5,6]) ? 10000 : 0; // sept → juin

            case "Scolarité":
                switch ($niveau) {
                    case 'PS':
                    case 'MS':
                    case 'GS':
                        switch ($moisNumero) {
                            case 10: return 25000;
                            case 11: return 20000;
                            case 12: return 15000;
                            case 1:
                            case 2: return 10000;
                            default: return 0;
                        }
                    case 'CP1':
                    case 'CP2':
                    case 'CE1':
                    case 'CE2':
                        switch ($moisNumero) {
                            case 9:
                            case 10:
                            case 11: return 25000;
                            case 12:
                            case 1: return 10000;
                            default: return 0;
                        }
                    case 'CM1':
                    case 'CM2':
                        switch ($moisNumero) {
                            case 9:
                            case 10:
                            case 11: return 25000;
                            case 12: return 15000;
                            case 1: return 10000;
                            default: return 0;
                        }
                }
                return 0;

            default:
                return 0;
        }
    }
}
