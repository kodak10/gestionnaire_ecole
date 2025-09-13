<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Ecole;

class MigrateEleves extends Command
{
    protected $signature = 'app:migrate-eleves';
    protected $description = 'Migration des élèves depuis tbl_eleves_2025_2026 vers eleves + inscriptions';

    public function handle()
    {
        $this->info("Début de la migration...");

        $elevesAncien = DB::table('tbl_eleves_2025_2026')->get();

        $ecoleId = 1; // Id fixe
        $anneeScolaireId = 1; // Id fixe

        $ecole = Ecole::findOrFail($ecoleId);
        $alias = strtoupper($ecole->sigle_ecole);

        $dernierEleve = Eleve::where('ecole_id', $ecoleId)
            ->where('matricule', 'like', $alias . '-%')
            ->orderByDesc('created_at')
            ->first();

        $dernierNumero = 0;
        if ($dernierEleve && preg_match('/-(\d+)$/', $dernierEleve->matricule, $matches)) {
            $dernierNumero = intval($matches[1]);
        }

        // foreach ($elevesAncien as $ligne) {

        //     $dernierNumero++;
        //     $numeroFormate = str_pad($dernierNumero, 5, '0', STR_PAD_LEFT);
        //     $matricule = $alias . '-' . $numeroFormate;

        //     // Créer l'élève
        //     $eleve = Eleve::create([
        //         'matricule' => $matricule,
        //         'nom' => $ligne->NomEleve ?? 'Inconnu',
        //         'prenom' => $ligne->PrenomEleve ?? '',
        //         'num_extrait' => $ligne->NumeroExtrait ?? null,
        //         'sexe' => ($ligne->Sexe == 'Garçon' || $ligne->Sexe == 'Masculin') ? 'Masculin' : 'Féminin',
        //         'naissance' => $ligne->DateNaissance ?? now(),
        //         'lieu_naissance' => $ligne->LieuNaissance ?? null,
        //         'parent_nom' => $ligne->NomPrenomTiteur ?? 'Parent',
        //         'parent_telephone' => $ligne->TelPere ?? '0000000000',
        //         'parent_telephone02' => $ligne->TelMere ?? '0000000000',
        //         'parent_email' => $ligne->MailParent ?? null,
        //         'ecole_id' => $ecoleId,
        //         'annee_scolaire_id' => $anneeScolaireId,
        //     ]);

        //     // Créer l'inscription avec le même ID de classe
        //     Inscription::create([
        //         'eleve_id' => $eleve->id,
        //         'classe_id' => $ligne->Id_TblClasse, // directement l'ancien id
        //         'ecole_id' => $ecoleId,
        //         'annee_scolaire_id' => $anneeScolaireId,
        //         'cantine_active' => false,
        //         'transport_active' => false,
        //         'statut' => 'active',
        //     ]);

        //     $this->info("Élève migré: {$eleve->nom} {$eleve->prenom} (Matricule: {$eleve->matricule})");
        // }
        foreach ($elevesAncien as $ligne) {
            // Trouver l'élève correspondant (ex: par nom + prénom, ou mieux par matricule si dispo)
            $eleve = Eleve::where('nom', $ligne->NomEleve)
                ->where('prenom', $ligne->PrenomEleve)
                ->where('ecole_id', $ecoleId)
                ->first();

            if ($eleve) {
                $eleve->update([
                    'parent_telephone'   => $ligne->TelPere ?? '0000000000',
                    'parent_telephone02' => $ligne->TelMere ?? '0000000000',
                ]);

                $this->info("Téléphones mis à jour pour {$eleve->nom} {$eleve->prenom}");
            } else {
                $this->warn("Élève introuvable: {$ligne->NomEleve} {$ligne->PrenomEleve}");
            }
        }


        $this->info("Migration terminée !");
    }
}
