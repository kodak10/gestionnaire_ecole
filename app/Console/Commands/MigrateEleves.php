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

        foreach ($elevesAncien as $ligne) {

            $dernierNumero++;
            $numeroFormate = str_pad($dernierNumero, 5, '0', STR_PAD_LEFT);
            $matricule = $alias . '-' . $numeroFormate;

            // Créer l'élève
            $eleve = Eleve::create([
                'matricule' => $matricule,
                'nom' => $ligne->NomEleve ?? 'Inconnu',
                'prenom' => $ligne->PrenomEleve ?? '',
                'num_extrait' => $ligne->NumeroExtrait ?? null,
                'sexe' => ($ligne->Sexe == 'Garçon' || $ligne->Sexe == 'Masculin') ? 'Masculin' : 'Féminin',
                'naissance' => $ligne->DateNaissance ?? now(),
                'lieu_naissance' => $ligne->LieuNaissance ?? null,
                'parent_nom' => $ligne->NomPrenomTiteur ?? 'Parent',
                'parent_telephone' => $ligne->CelulTuteur ?? '0000000000',
                'parent_email' => $ligne->MailParent ?? null,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
            ]);

            // Créer l'inscription avec le même ID de classe
            Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $ligne->Id_TblClasse, // directement l'ancien id
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'cantine_active' => false,
                'transport_active' => false,
                'statut' => 'active',
            ]);

            $this->info("Élève migré: {$eleve->nom} {$eleve->prenom} (Matricule: {$eleve->matricule})");
        }

        $this->info("Migration terminée !");
    }
}
