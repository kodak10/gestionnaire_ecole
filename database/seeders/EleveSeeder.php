<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Classe;
use App\Models\Inscription;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $ecole = Ecole::first(); // école par défaut
        $sigle = $ecole ? $ecole->sigle_ecole : 'XXX';
        $anneeActive = AnneeScolaire::where('est_active', true)->first();

        $classes = Classe::all(); // récupérer toutes les classes existantes
        if ($classes->isEmpty()) {
            $this->command->info('Aucune classe existante. Veuillez créer des classes avant de lancer ce seeder.');
            return;
        }

        $noms = ['Diallo', 'Traoré', 'Touré', 'Keita', 'Konaté', 'Cissé', 'Diop', 'Ndiaye', 'Fall', 'Sow', 'Ba', 'Diakhate'];
        $prenomsMasculins = ['Mamadou', 'Abdoulaye', 'Ibrahima', 'Mohamed', 'Alioune', 'Cheikh', 'Ousmane', 'Pape', 'Amadou', 'Seydou', 'Moussa', 'Djibril'];
        $prenomsFeminins = ['Aminata', 'Fatou', 'Aïssatou', 'Ramatoulaye', 'Mariama', 'Awa', 'Khadija', 'Aïcha', 'Djenabou', 'Nafissatou', 'Sokhna', 'Rokhaya'];

        // Créer 10 élèves
        for ($i = 0; $i < 10; $i++) {
            $sexe = $faker->randomElement(['Masculin', 'Féminin']);
            $nom = $faker->randomElement($noms);
            $prenom = $sexe === 'Masculin' ? $faker->randomElement($prenomsMasculins) : $faker->randomElement($prenomsFeminins);

            $eleve = Eleve::create([
                'ecole_id' => $ecole->id,
                'matricule' => $sigle . '-' . $faker->unique()->numberBetween(1000, 9999),
                'nom' => $nom,
                'prenom' => $prenom,
                'num_extrait' => 'EXT-' . $faker->numberBetween(10000, 99999),
                'sexe' => $sexe,
                'naissance' => $faker->dateTimeBetween('-15 years', '-5 years'),
                'lieu_naissance' => $faker->city,
                'photo_path' => null,
                'infos_medicales' => $faker->optional()->sentence,
                'parent_nom' => $nom . ' ' . $faker->firstName,
                'parent_telephone' => $faker->phoneNumber,
                'parent_email' => $faker->optional()->safeEmail,
            ]);

            // Assigner l'élève à une classe existante aléatoirement
            $classe = $classes->random();

            Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $classe->id,
                'annee_scolaire_id' => $anneeActive->id,
                'cantine_active' => $faker->boolean(50),
                'transport_active' => $faker->boolean(30),
                'statut' => 'active',
            ]);
        }
    }
}
