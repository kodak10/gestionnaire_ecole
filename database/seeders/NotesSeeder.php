<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inscription;
use App\Models\Note;
use Carbon\Carbon;

class NotesSeeder extends Seeder
{
    public function run()
    {
        Note::truncate();
        // Récupérer toutes les inscriptions avec la classe et le niveau chargés
        $inscriptions = Inscription::with('classe.niveau.matieres')->get();

        foreach ($inscriptions as $inscription) {
            $classe = $inscription->classe;
            $niveau = $classe->niveau;
            $matieres = $niveau->matieres;

            foreach ($matieres as $matiere) {
                // Générer une note aléatoire entre 5 et 20
                $valeur = mt_rand(50, 200) / 10;

                Note::create([
                    'inscription_id' => $inscription->id,
                    'classe_id' => $classe->id,
                    'annee_scolaire_id' => $inscription->annee_scolaire_id,
                    'ecole_id' => $classe->ecole_id,
                    'matiere_id' => $matiere->id,
                    'valeur' => $valeur,
                    'coefficient' => $matiere->pivot->coefficient ?? 1,
                    'appreciation' => $this->generateAppreciation($valeur),
                    'user_id' => 1, // facultatif : l'utilisateur qui a saisi la note
                    'mois_id' => 3, // facultatif : pour le mois
                ]);
            }
        }
    }

    /**
     * Générer une appréciation simple selon la valeur de la note
     */
    private function generateAppreciation($valeur)
    {
        if ($valeur < 10) return 'Insuffisant';
        if ($valeur < 12) return 'Passable';
        if ($valeur < 14) return 'Assez Bien';
        if ($valeur < 16) return 'Bien';
        return 'Très Bien';
    }
}
