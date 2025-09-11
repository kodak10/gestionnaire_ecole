<?php

namespace Database\Seeders;

use App\Models\Ecole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EcolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Ecole::create([
            'nom_ecole'   => 'Groupe Scolaire Excelle',
            'sigle_ecole' => 'GS Excelle',
            'adresse'     => '123 Rue des Écoles, 75000 Paris',
            'telephone'   => '0708395524 / 0708395524',
            'email'       => 'Groupeexcelle01@gmail.com',
            'directeur'   => 'KONE ISMAEL ABOUBAKAR',
            'logo'        => 'logos/gs-excelle.png',
        ]);

        Ecole::create([
            'nom_ecole'   => 'Collège Moderne Excellence',
            'sigle_ecole' => 'CME',
            'adresse'     => '456 Avenue de la Réussite, 75010 Paris',
            'telephone'   => '01 98 76 54 32',
            'email'       => 'info@college-excellence.fr',
            'directeur'   => 'Mme Claire Martin',
            'logo'        => 'logos/college-excellence.png',
        ]);
    }

}
