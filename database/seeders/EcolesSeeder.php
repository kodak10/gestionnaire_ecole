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
            'nom_ecole'   => 'École Primaire EXCELLE',
            'sigle_ecole' => 'EP',
            'adresse'     => '123 Rue des Écoles, 75000 Paris',
            'telephone'   => '01 23 45 67 89',
            'email'       => 'contact@ecole-les-lilas.fr',
            'directeur'   => 'M. Jean Durand',
            'logo'        => 'logos/ecole-lilas.png',
        ]);

       
    }

}
