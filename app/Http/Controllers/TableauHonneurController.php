<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\MoisScolaire;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Mention;
use App\Models\Ecole;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class TableauHonneurController extends Controller
{
    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
    ->get();

        $moisScolaire = MoisScolaire::orderBy('numero')
            ->get();

        return view('dashboard.pages.eleves.notes.tableaux-honneur.index', compact('classes', 'moisScolaire'));
    }

    public function genererMensuel(Request $request)
    {
        $request->validate([
            'classe_id' => 'nullable|exists:classes,id',
            'mois_id' => 'required|exists:mois_scolaires,id',
            'nombre_eleves' => 'required|integer|min:1|max:20'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $mois = MoisScolaire::findOrFail($request->mois_id);
        $classe = $request->classe_id ? Classe::with('niveau.matieres')->find($request->classe_id) : null;
        $ecole = Ecole::find($ecoleId);
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        // Récupérer les inscriptions actives
        $query = Inscription::with(['eleve', 'classe'])
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active');

        if ($classe) {
            $query->where('inscriptions.classe_id', $classe->id);
        }

        $inscriptions = $query->get();

        $elevesAvecMoyennes = [];

        foreach ($inscriptions as $inscription) {
            $currentClasse = $classe ?: $inscription->classe;

            // Base uniforme pour le classement (ex: 20)
            $classeBase = 20;

            // Moyenne pour le classement
            $moyenneClassement = $this->calculerMoyenneEleve($inscription->id, [$request->mois_id], $classeBase);

            // Moyenne réelle selon la base de la classe (pour affichage)
            $moyBaseReelle = $currentClasse->moy_base ?? 20;
            $moyenneReelle = $this->calculerMoyenneEleve($inscription->id, [$request->mois_id], $moyBaseReelle);

            if ($moyenneClassement['moyenne'] > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => $moyenneClassement['moyenne'], // pour classement
                    'moyenne_sur_20' => $moyenneClassement['moyenne_sur_20'],
                    'moy_base' => $moyBaseReelle,               // base réelle pour affichage
                    'moyenne_reelle' => $moyenneReelle['moyenne'], // moyenne réelle pour affichage
                    'total_notes' => $moyenneClassement['total_notes'],
                    'total_coeffs' => $moyenneClassement['total_coeffs']
                ];
            }
        }

        // Trier par moyenne décroissante (classement)
        usort($elevesAvecMoyennes, function($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });

        // Prendre les N meilleurs
        $meilleursEleves = array_slice($elevesAvecMoyennes, 0, $request->nombre_eleves);

        // Générer le PDF
        $pdf = PDF::loadView('dashboard.documents.tableau-honneur-mensuel', [
            'meilleursEleves' => $meilleursEleves,
            'mois' => $mois,
            'classe' => $classe,
            'nombreEleves' => $request->nombre_eleves,
            'ecole' => $ecole,
            'anneeScolaire' => $anneeScolaire
        ])->setPaper('a4', 'landscape');

        $filename = $classe ? 
            "tableau-honneur-{$classe->nom}-{$mois->nom}.pdf" : 
            "tableau-honneur-general-{$mois->nom}.pdf";

        return $pdf->stream($filename);
    }

    public function genererAnnuel(Request $request)
    {
        $request->validate([
            'classe_id' => 'nullable|exists:classes,id',
            'mois_ids' => 'required|array|min:1',
            'mois_ids.*' => 'exists:mois_scolaires,id',
            'nombre_eleves' => 'required|integer|min:1|max:20'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classe = $request->classe_id ? Classe::with('niveau.matieres')->find($request->classe_id) : null;
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $ecole = Ecole::find($ecoleId);
        
        // Récupérer les mois sélectionnés
        $moisScolaires = MoisScolaire::whereIn('id', $request->mois_ids)->orderBy('id')->get();

        $query = Inscription::with(['eleve', 'classe'])
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active');

        if ($classe) {
            $query->where('inscriptions.classe_id', $classe->id);
        }

        $inscriptions = $query->get();
        $elevesAvecMoyennes = [];

        foreach ($inscriptions as $inscription) {
            $currentClasse = $classe ?: $inscription->classe;

            $classeBase = 20; // Base uniforme pour classement
            $moyenneClassement = $this->calculerMoyenneEleve($inscription->id, $request->mois_ids, $classeBase);

            $moyBaseReelle = $currentClasse->moy_base ?? 20;
            $moyenneReelle = $this->calculerMoyenneEleve($inscription->id, $request->mois_ids, $moyBaseReelle);

            if ($moyenneClassement['moyenne'] > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => $moyenneClassement['moyenne'],
                    'moyenne_sur_20' => $moyenneClassement['moyenne_sur_20'],
                    'moy_base' => $moyBaseReelle,
                    'moyenne_reelle' => $moyenneReelle['moyenne'],
                    'total_notes' => $moyenneClassement['total_notes'],
                    'total_coeffs' => $moyenneClassement['total_coeffs']
                ];
            }
        }

        // Trier décroissant
        usort($elevesAvecMoyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        // Prendre N meilleurs
        $meilleursEleves = array_slice($elevesAvecMoyennes, 0, $request->nombre_eleves);

        $pdf = PDF::loadView('dashboard.documents.tableau-honneur-annuel', [
            'meilleursEleves' => $meilleursEleves,
            'anneeScolaire' => $anneeScolaire,
            'classe' => $classe,
            'nombreEleves' => $request->nombre_eleves,
            'ecole' => $ecole,
            'moisScolaires' => $moisScolaires
        ])->setPaper('a4', 'landscape');

        $filename = $classe ? 
            "tableau-honneur-annuel-{$classe->nom}.pdf" : 
            "tableau-honneur-annuel-general.pdf";

        return $pdf->stream($filename);
    }

    // public function genererMajor(Request $request)
    // {
    //     $request->validate([
    //         'type' => 'required|in:classe,general',
    //         'classe_id' => 'nullable|exists:classes,id',
    //         'periode' => 'required|in:mois,annee',
    //         'mois_ids' => 'required_if:periode,annee|array|min:1',
    //         'mois_ids.*' => 'exists:mois_scolaires,id',
    //         'mois_id' => 'required_if:periode,mois|exists:mois_scolaires,id'
    //     ]);

    //     $ecoleId = session('current_ecole_id'); 
    //     $anneeScolaireId = session('current_annee_scolaire_id');

    //     $classe = $request->classe_id ? Classe::with('niveau.matieres')->find($request->classe_id) : null;
    //     $mois = $request->mois_id ? MoisScolaire::find($request->mois_id) : null;
    //     $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    //     $ecole = Ecole::find($ecoleId);
        
    //     // Récupérer les mois sélectionnés pour l'annuel
    //     $moisScolaires = ($request->periode == 'annee' && $request->has('mois_ids')) 
    //         ? MoisScolaire::whereIn('id', $request->mois_ids)->orderBy('id')->get() 
    //         : null;

    //     // Récupérer les inscriptions actives
    //     $query = Inscription::with(['eleve', 'classe'])
    //         ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
    //         ->where('inscriptions.ecole_id', $ecoleId)
    //         ->where('inscriptions.statut', 'active');

    //     if ($classe && $request->type === 'classe') {
    //         $query->where('inscriptions.classe_id', $classe->id);
    //     }

    //     $inscriptions = $query->get();
    //     $elevesAvecMoyennes = [];

    //     // Déterminer les mois à utiliser pour le calcul
    //     $moisIds = [];
    //     if ($request->periode === 'mois') {
    //         $moisIds = [$request->mois_id];
    //     } else {
    //         $moisIds = $request->mois_ids;
    //     }

    //     foreach ($inscriptions as $inscription) {
    //         $currentClasse = $classe ?: $inscription->classe;

    //         $classeBase = 20; // Base uniforme pour le classement
    //         $moyenneClassement = $this->calculerMoyenneEleve($inscription->id, $moisIds, $classeBase);

    //         $moyBaseReelle = $currentClasse->moy_base ?? 20;
    //         $moyenneReelle = $this->calculerMoyenneEleve($inscription->id, $moisIds, $moyBaseReelle);

    //         if ($moyenneClassement['moyenne'] > 0) {
    //             $elevesAvecMoyennes[] = [
    //                 'inscription' => $inscription,
    //                 'moyenne' => $moyenneClassement['moyenne'],
    //                 'moyenne_sur_20' => $moyenneClassement['moyenne_sur_20'],
    //                 'moy_base' => $moyBaseReelle,
    //                 'moyenne_reelle' => $moyenneReelle['moyenne'],
    //                 'total_notes' => $moyenneClassement['total_notes'],
    //                 'total_coeffs' => $moyenneClassement['total_coeffs']
    //             ];
    //         }
    //     }

    //     // Trier décroissant et prendre le premier (major)
    //     usort($elevesAvecMoyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);
    //     $major = $elevesAvecMoyennes[0] ?? null;

    //     if (!$major) {
    //         return back()->with('error', 'Aucun major trouvé pour les critères sélectionnés.');
    //     }

    //     $pdf = PDF::loadView('dashboard.documents.tableau-honneur-major', [
    //         'eleve' => $major,
    //         'periode' => $request->periode,
    //         'mois' => $mois,
    //         'moisScolaires' => $moisScolaires,
    //         'anneeScolaire' => $anneeScolaire,
    //         'classe' => $classe,
    //         'type' => $request->type,
    //         'ecole' => $ecole
    //     ])->setPaper('a4', 'landscape');

    //     $filename = $request->type === 'classe' && $classe ? 
    //         "major-{$classe->nom}-{$request->periode}.pdf" : 
    //         "major-general-{$request->periode}.pdf";

    //     return $pdf->stream($filename);
    // }

    public function genererMajor(Request $request)
{
    $request->validate([
        'classe_ids' => 'required|array|min:1',
        'classe_ids.*' => 'exists:classes,id',
        'mois_ids' => 'required|array|min:1',
        'mois_ids.*' => 'exists:mois_scolaires,id',
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    $ecole = Ecole::find($ecoleId);
    
    // Récupérer les mois sélectionnés
    $moisScolaires = MoisScolaire::whereIn('id', $request->mois_ids)
        ->orderBy('id')
        ->get();

    // Récupérer TOUTES les classes sélectionnées
    $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
    ->get();

    $tousLesMajors = [];

    foreach ($classes as $classe) {
        // Récupérer les inscriptions actives pour cette classe
        $inscriptions = Inscription::with(['eleve', 'classe.enseignant'])
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('classe_id', $classe->id)
            ->where('statut', 'active')
            ->get();

        $elevesAvecMoyennes = [];

        foreach ($inscriptions as $inscription) {
            $classeBase = 20;
            $moyenneClassement = $this->calculerMoyenneEleve($inscription->id, $request->mois_ids, $classeBase);

            $moyBaseReelle = $classe->moy_base ?? 20;
            $moyenneReelle = $this->calculerMoyenneEleve($inscription->id, $request->mois_ids, $moyBaseReelle);

            if ($moyenneClassement['moyenne'] > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => $moyenneClassement['moyenne'],
                    'moyenne_sur_20' => $moyenneClassement['moyenne_sur_20'],
                    'moy_base' => $moyBaseReelle,
                    'moyenne_reelle' => $moyenneReelle['moyenne'],
                    'total_notes' => $moyenneClassement['total_notes'],
                    'total_coeffs' => $moyenneClassement['total_coeffs']
                ];
            }
        }

        // Trier et prendre le major de cette classe
        if (!empty($elevesAvecMoyennes)) {
            usort($elevesAvecMoyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);
            $tousLesMajors[] = $elevesAvecMoyennes[0];
        }
    }

    if (empty($tousLesMajors)) {
        return back()->with('error', 'Aucun major trouvé pour les critères sélectionnés.');
    }

    // Pour chaque major, générer un PDF avec une page
    $pdf = PDF::loadView('dashboard.documents.tableau-honneur-major', [
        'majors' => $tousLesMajors,
        'moisScolaires' => $moisScolaires,
        'anneeScolaire' => $anneeScolaire,
        'ecole' => $ecole
    ])->setPaper('a4', 'landscape');

    $filename = "certificats-major-" . date('Y-m-d') . ".pdf";

    return $pdf->stream($filename);
}

    public function genererDiplome(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'type' => 'required|in:mensuel,annuel',
            'mois_id' => 'required_if:type,mensuel|exists:mois_scolaires,id',
            'mois_ids' => 'required_if:type,annuel|array|min:1',
            'mois_ids.*' => 'exists:mois_scolaires,id'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $inscription = Inscription::with(['eleve', 'classe'])->findOrFail($request->inscription_id);
        $mois = $request->mois_id ? MoisScolaire::find($request->mois_id) : null;
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $ecole = Ecole::find($ecoleId);
        
        // Récupérer les mois sélectionnés pour l'annuel
        $moisScolaires = ($request->type == 'annuel' && $request->has('mois_ids')) 
            ? MoisScolaire::whereIn('id', $request->mois_ids)->orderBy('id')->get() 
            : null;

        // Calculer la moyenne selon le type
        $moyBase = $inscription->classe->moy_base ?? 20;
        
        // Déterminer les mois à utiliser pour le calcul
        $moisIds = [];
        if ($request->type == 'mensuel') {
            $moisIds = [$request->mois_id];
        } else {
            $moisIds = $request->mois_ids;
        }

        $moyenneData = $this->calculerMoyenneEleve($inscription->id, $moisIds, $moyBase);

        $eleveData = [
            'inscription' => $inscription,
            'moyenne' => $moyenneData['moyenne'],
            'moyenne_sur_20' => $moyenneData['moyenne_sur_20'],
            'moy_base' => $moyBase,
            'total_notes' => $moyenneData['total_notes'],
            'total_coeffs' => $moyenneData['total_coeffs']
        ];

        $pdf = PDF::loadView('dashboard.documents.diplome-excellence', [
            'eleve' => $eleveData,
            'type' => $request->type,
            'mois' => $mois,
            'moisScolaires' => $moisScolaires,
            'anneeScolaire' => $anneeScolaire,
            'ecole' => $ecole
        ])->setPaper('a4', 'landscape');

        $filename = "diplome-excellence-{$inscription->eleve->nom}-{$inscription->eleve->prenom}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Calcule la moyenne d'un élève sur plusieurs mois
     * @param array|null $moisIds Tableau des IDs des mois à inclure (null pour tous les mois)
     */
    private function calculerMoyenneEleve($inscriptionId, $moisIds = null, $moyBase = 20)
    {
        $query = Note::where('inscription_id', $inscriptionId)->with(['matiere']);

        if ($moisIds && !empty($moisIds)) {
            $query->whereIn('mois_id', $moisIds);
        }

        $notes = $query->get();
        
        // Grouper par matière pour la moyenne sur plusieurs mois
        $matieresData = [];
        
        foreach ($notes as $note) {
            $matiereId = $note->matiere_id;
            
            if (!isset($matieresData[$matiereId])) {
                // Récupérer la classe et ses matières
                $inscription = Inscription::with(['classe.niveau.matieres'])->find($inscriptionId);
                $currentClasse = $inscription->classe;
                $matierePivot = $currentClasse->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
                
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
            // Moyenne de la matière sur tous les mois sélectionnés
            $moyenneMatiere = count($data['notes']) > 0 
                ? array_sum($data['notes']) / count($data['notes']) 
                : null;
            
            if ($moyenneMatiere !== null && $data['coefficient'] > 0) {
                $totalNotes += ($moyenneMatiere / $data['base']) * $moyBase * $data['coefficient'];
                $totalCoeffs += $data['coefficient'];
            }
        }

        $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;
        $moyenneArrondie = round($moyenne, 2);

        // Moyenne sur 20 pour les mentions
        $moyenneSur20 = $moyBase > 0 ? round(($moyenne / $moyBase) * 20, 2) : 0;

        return [
            'moyenne' => $moyenneArrondie,
            'moyenne_sur_20' => $moyenneSur20,
            'total_notes' => $totalNotes,
            'total_coeffs' => $totalCoeffs
        ];
    }

    private function getMention($moyenne, $moyBase)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Conversion de la moyenne de la classe sur 20
        $moyenneSur20 = $moyBase > 0 ? ($moyenne / $moyBase) * 20 : $moyenne;

        // Arrondir à l'entier le plus proche pour correspondre aux plages des mentions
        $moyenneArrondie = round($moyenneSur20);

        // Récupérer toutes les mentions de l'école et année scolaire
        $mentions = Mention::where('ecole_id', $ecoleId)
                           ->where('annee_scolaire_id', $anneeScolaireId)
                           ->get();

        // Chercher la mention dont la moyenne tombe dans la plage
        $mention = $mentions->first(function ($m) use ($moyenneArrondie) {
            return $moyenneArrondie >= $m->min_note && $moyenneArrondie <= $m->max_note;
        });

        Log::info('Mention trouvée', [
            'moyenneOriginale' => $moyenne,
            'moyenneSur20' => $moyenneSur20,
            'moyenneArrondie' => $moyenneArrondie,
            'mention' => $mention ? $mention->nom : 'Non classé'
        ]);

        return $mention ? $mention->nom : 'Non classé';
    }
}