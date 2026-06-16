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
                                'annee_scolaire_id' => 3,
                                'ecole_id' => 1,
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
                // Inscription uniquement en Septembre (mois 9)
                return ($moisNumero === 9) ? 20000 : 0;

            case "Cantine":
                // Cantine de Septembre à Mai (mois 9 à 5) à 10.000 F/mois
                if (in_array($moisNumero, [9, 10, 11, 12, 1, 2, 3, 4, 5])) {
                    return 10000;
                }
                return 0;

            case "Transport":
                // Transport de Septembre à Mai (mois 9 à 5) à 10.000 F/mois
                if (in_array($moisNumero, [9, 10, 11, 12, 1, 2, 3, 4, 5])) {
                    return 10000;
                }
                return 0;

            case "Scolarité":
                switch ($niveau) {
                    case 'PS':
                    case 'MS':
                    case 'GS': // Maternelle - Total 80.000 FCFA
                        switch ($moisNumero) {
                            case 9:  return 0;   // Septembre (inscription déjà payée)
                            case 10: return 0;   // Octobre (pas de paiement)
                            case 11: return 25000; // Novembre
                            case 12: return 20000; // Décembre
                            case 1:  return 15000; // Janvier
                            case 2:  return 10000; // Février
                            case 3:  return 10000; // Mars
                            case 4:  return 0;   // Avril
                            case 5:  return 0;   // Mai
                            default: return 0;
                        }

                    case 'CP1':
                    case 'CP2':
                    case 'CE1':
                    case 'CE2': // CP & CE - Total 95.000 FCFA
                        switch ($moisNumero) {
                            case 9:  return 0;   // Septembre
                            case 10: return 0;   // Octobre
                            case 11: return 25000; // Novembre
                            case 12: return 25000; // Décembre
                            case 1:  return 25000; // Janvier
                            case 2:  return 10000; // Février
                            case 3:  return 10000; // Mars
                            case 4:  return 0;   // Avril
                            case 5:  return 0;   // Mai
                            default: return 0;
                        }

                    case 'CM1':
                    case 'CM2': // CM - Total 100.000 FCFA
                        switch ($moisNumero) {
                            case 9:  return 0;   // Septembre
                            case 10: return 0;   // Octobre
                            case 11: return 25000; // Novembre
                            case 12: return 25000; // Décembre
                            case 1:  return 25000; // Janvier
                            case 2:  return 15000; // Février
                            case 3:  return 10000; // Mars
                            case 4:  return 0;   // Avril
                            case 5:  return 0;   // Mai
                            default: return 0;
                        }
                }
                return 0;

            default:
                return 0;
        }
    }
}