<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\Note;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParcheminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Directeur|Enseignant']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
    ->get();

        return view('dashboard.pages.eleves.notes.parchemin.index', compact('classes'));
    }

    public function generer(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classe = Classe::findOrFail($request->classe_id);
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $ecole = Ecole::find($ecoleId);

        // Récupérer les élèves de la classe avec leurs moyennes annuelles
        $inscriptions = Inscription::with(['eleve'])
            ->where('classe_id', $request->classe_id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'active')
            ->get();

        $elevesAvecMention = [];

        foreach ($inscriptions as $inscription) {
            // Calculer la moyenne annuelle de l'élève sur tous les mois
            $moyenneData = $this->calculerMoyenneAnnuelle($inscription->id, $classe->id, $anneeScolaireId);
            
            // Déterminer la mention
            $mention = $this->getMentionParchemin($moyenneData['moyenne'], $classe->moy_base ?? 20);
            
            // Déterminer la section/classe suivante
            $classeSuivante = $this->getClasseSuivante($classe->nom);

            $elevesAvecMention[] = [
                'inscription' => $inscription,
                'moyenne' => $moyenneData['moyenne'],
                'moyenne_formatee' => number_format($moyenneData['moyenne'], 2, ',', ' '),
                'mention' => $mention,
                'classe_suivante' => $classeSuivante,
                'statut' => $this->getStatut($mention),
                'photo_path' => $inscription->eleve->photo_path
            ];
        }

        if (empty($elevesAvecMention)) {
            return back()->with('error', 'Aucun élève trouvé dans cette classe.');
        }

        // Générer le PDF
        $pdf = PDF::loadView('dashboard.documents.parchemin', [
            'eleves' => $elevesAvecMention,
            'classe' => $classe,
            'anneeScolaire' => $anneeScolaire,
            'ecole' => $ecole,
            'date_generation' => now()
        ])->setPaper('a4', 'portrait');

        $filename = "parchemin-{$classe->nom}-" . date('Y-m-d') . ".pdf";

        return $pdf->stream($filename);
    }

    /**
     * Calcule la moyenne annuelle d'un élève
     */
    private function calculerMoyenneAnnuelle($inscriptionId, $classeId, $anneeScolaireId)
    {
        $classe = Classe::with('niveau.matieres')->find($classeId);
        $moyBase = $classe->moy_base ?? 20;

        // Récupérer toutes les notes de l'élève pour l'année scolaire
        $notes = Note::where('inscription_id', $inscriptionId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->with('matiere')
            ->get();

        // Grouper par matière
        $matieresData = [];

        foreach ($notes as $note) {
            $matiereId = $note->matiere_id;
            
            if (!isset($matieresData[$matiereId])) {
                $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
                
                $matieresData[$matiereId] = [
                    'notes' => [],
                    'coefficient' => $matierePivot->coefficient ?? 1,
                    'base' => $matierePivot->denominateur ?? 20
                ];
            }
            
            if ($note->valeur !== null) {
                $matieresData[$matiereId]['notes'][] = $note->valeur;
            }
        }

        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($matieresData as $data) {
            // Moyenne de la matière sur tous les mois
            $moyenneMatiere = count($data['notes']) > 0 
                ? array_sum($data['notes']) / count($data['notes']) 
                : null;
            
            if ($moyenneMatiere !== null && $data['coefficient'] > 0) {
                $totalNotes += ($moyenneMatiere / $data['base']) * $moyBase * $data['coefficient'];
                $totalCoeffs += $data['coefficient'];
            }
        }

        $moyenne = $totalCoeffs > 0 ? round($totalNotes / $totalCoeffs, 2) : 0;

        return [
            'moyenne' => $moyenne,
            'total_notes' => $totalNotes,
            'total_coeffs' => $totalCoeffs
        ];
    }

    /**
     * Détermine la mention pour le parchemin
     */
    private function getMentionParchemin($moyenne, $moyBase)
    {
        // Convertir la moyenne sur 20
        $moyenneSur20 = $moyBase > 0 ? ($moyenne / $moyBase) * 20 : $moyenne;

        if ($moyenneSur20 < 10) return 'Passable';
        if ($moyenneSur20 < 12) return 'Assez-bien';
        if ($moyenneSur20 < 14) return 'Bien';
        if ($moyenneSur20 < 16) return 'Très-bien';
        return 'Excellent';
    }

    /**
     * Détermine la classe suivante
     */
    private function getClasseSuivante($classeNom)
    {
        $classesOrder = [
            'Petite Section' => 'Moyenne Section',
            'Moyenne Section' => 'Grande Section',
            'Grande Section' => 'CP1',
            'CP1' => 'CP2',
            'CP2' => 'CE1',
            'CE1' => 'CE2',
            'CE2' => 'CM1',
            'CM1' => 'CM2',
            'CM2' => '6ème'
        ];

        return $classesOrder[$classeNom] ?? 'Classe supérieure';
    }

    /**
     * Détermine le statut (admis/non admis)
     */
    private function getStatut($mention)
    {
        $mentionsAdmis = ['Assez-bien', 'Bien', 'Très-bien', 'Excellent'];
        return in_array($mention, $mentionsAdmis) ? 'admis' : 'non_admis';
    }
}