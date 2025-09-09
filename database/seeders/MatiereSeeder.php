<?php

namespace Database\Seeders;

use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatiereSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ecoleId = 1; // ID de l'école par défaut

        $matieresPrimaire = [
            // Matières communes
            ['nom' => 'Français', 'coefficient' => 4],
            ['nom' => 'Mathématiques', 'coefficient' => 4],
            ['nom' => 'Éducation scientifique', 'coefficient' => 2],
            ['nom' => 'Histoire-Géographie', 'coefficient' => 2],
            ['nom' => 'Éducation civique et morale', 'coefficient' => 1],
            ['nom' => 'Éducation artistique', 'coefficient' => 1],
            ['nom' => 'Éducation physique et sportive', 'coefficient' => 1],
            // Matières spécifiques
            ['nom' => 'Lecture', 'coefficient' => 3],
            ['nom' => 'Écriture', 'coefficient' => 3],
            ['nom' => 'Langues nationales', 'coefficient' => 1],
            ['nom' => 'Informatique', 'coefficient' => 1],
        ];

        // Création des matières
        foreach ($matieresPrimaire as $matiere) {
            Matiere::firstOrCreate(
                ['nom' => $matiere['nom'], 'ecole_id' => $ecoleId], // condition unique
                array_merge($matiere, ['ecole_id' => $ecoleId])     // valeurs à insérer si pas trouvé
            );
        }


        // Attacher les matières aux classes selon les niveaux
        $this->attachMatieresToClasses($ecoleId);
    }

    protected function attachMatieresToClasses(int $ecoleId): void
    {
        // Récupérer tous les niveaux du primaire (CP1 à CM2)
        $niveauxPrimaire = Niveau::whereIn('nom', ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'])->get();

        foreach ($niveauxPrimaire as $niveau) {
            $classes = $niveau->classes;

            foreach ($classes as $classe) {

                // Matières communes
                $matieresCommunes = Matiere::whereIn('nom', [
                    'Français', 
                    'Mathématiques', 
                    'Éducation scientifique',
                    'Histoire-Géographie',
                    'Éducation civique et morale',
                    'Éducation artistique',
                    'Éducation physique et sportive'
                ])->get();

                foreach ($matieresCommunes as $matiere) {
                    $classe->matieres()->syncWithoutDetaching([
                        $matiere->id => [
                            'coefficient' => $matiere->coefficient,
                            'ecole_id' => $ecoleId
                        ]
                    ]);
                }

                // Matières spécifiques selon le niveau
                if (in_array($niveau->nom, ['CP1', 'CP2', 'CE1'])) {
                    $matieresSpecifiques = Matiere::whereIn('nom', [
                        'Lecture',
                        'Écriture',
                        'Langues nationales'
                    ])->get();

                    foreach ($matieresSpecifiques as $matiere) {
                        $classe->matieres()->syncWithoutDetaching([
                            $matiere->id => [
                                'coefficient' => $matiere->coefficient,
                                'ecole_id' => $ecoleId
                            ]
                        ]);
                    }
                }

                // Informatique à partir du CE2
                if (in_array($niveau->nom, ['CE2', 'CM1', 'CM2'])) {
                    $informatique = Matiere::where('nom', 'Informatique')->first();
                    if ($informatique) {
                        $classe->matieres()->syncWithoutDetaching([
                            $informatique->id => [
                                'coefficient' => $informatique->coefficient,
                                'ecole_id' => $ecoleId
                            ]
                        ]);
                    }
                }
            }
        }
    }

}
