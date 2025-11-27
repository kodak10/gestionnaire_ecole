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

        $classes = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')
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

    // Récupérer les meilleurs élèves
    $query = Inscription::with(['eleve', 'classe.niveau'])
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
        $moyBase = $currentClasse->moy_base ?? 20;

        $notes = Note::where('inscription_id', $inscription->id)
            ->where('mois_id', $request->mois_id)
            ->with('matiere')
            ->get();

        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($notes as $note) {
            $matierePivot = $currentClasse->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
            $base = $matierePivot->denominateur ?? 20;
            $coeff = $matierePivot->coefficient ?? 1;

            if ($note->valeur !== null && $coeff > 0) {
                $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                $totalCoeffs += $coeff;
            }
        }

        $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;

        if ($moyenne > 0) {
            $elevesAvecMoyennes[] = [
                'inscription' => $inscription,
                'moyenne' => round($moyenne, 2),
                'moyenne_sur_20' => round(($moyenne / $moyBase) * 20, 2),
                'moy_base' => $moyBase
            ];
        }
    }

    // Trier par moyenne décroissante
    usort($elevesAvecMoyennes, function($a, $b) {
        return $b['moyenne'] <=> $a['moyenne'];
    });

    // Prendre les N premiers
    $meilleursEleves = array_slice($elevesAvecMoyennes, 0, $request->nombre_eleves);

    $pdf = PDF::loadView('dashboard.documents.tableau-honneur-mensuel', [
        'meilleursEleves' => $meilleursEleves,
        'mois' => $mois,
        'classe' => $classe,
        'nombreEleves' => $request->nombre_eleves,
        'ecole' => $ecole,
        'anneeScolaire' => $anneeScolaire
    ])->setPaper('a4', 'landscape'); // Forcer l'orientation paysage

    $filename = $classe ? 
        "tableau-honneur-{$classe->nom}-{$mois->nom}.pdf" : 
        "tableau-honneur-general-{$mois->nom}.pdf";

    return $pdf->stream($filename);
}

    // Générer tableau d'honneur annuel
    public function genererAnnuel(Request $request)
{
    $request->validate([
        'classe_id' => 'nullable|exists:classes,id',
        'nombre_eleves' => 'required|integer|min:1|max:20'
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $classe = $request->classe_id ? Classe::with('niveau.matieres')->find($request->classe_id) : null;
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    $ecole = Ecole::find($ecoleId);

    // Récupérer les meilleurs élèves sur l'année
    $query = Inscription::with(['eleve', 'classe.niveau'])
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
        $moyBase = $currentClasse->moy_base ?? 20;

        $notes = Note::where('inscription_id', $inscription->id)
            ->with('matiere')
            ->get();

        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($notes as $note) {
            $matierePivot = $currentClasse->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
            $base = $matierePivot->denominateur ?? 20;
            $coeff = $matierePivot->coefficient ?? 1;

            if ($note->valeur !== null && $coeff > 0) {
                $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                $totalCoeffs += $coeff;
            }
        }

        $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;

        if ($moyenne > 0) {
            $elevesAvecMoyennes[] = [
                'inscription' => $inscription,
                'moyenne' => round($moyenne, 2),
                'moyenne_sur_20' => round(($moyenne / $moyBase) * 20, 2),
                'moy_base' => $moyBase
            ];
        }
    }

    // Trier par moyenne décroissante
    usort($elevesAvecMoyennes, function($a, $b) {
        return $b['moyenne'] <=> $a['moyenne'];
    });

    // Prendre les N premiers
    $meilleursEleves = array_slice($elevesAvecMoyennes, 0, $request->nombre_eleves);

    $pdf = PDF::loadView('dashboard.documents.tableau-honneur-annuel', [
        'meilleursEleves' => $meilleursEleves,
        'anneeScolaire' => $anneeScolaire,
        'classe' => $classe,
        'nombreEleves' => $request->nombre_eleves,
        'ecole' => $ecole
    ])->setPaper('a4', 'landscape'); // Forcer l'orientation paysage

    $filename = $classe ? 
        "tableau-honneur-annuel-{$classe->nom}.pdf" : 
        "tableau-honneur-annuel-general.pdf";

    return $pdf->stream($filename);
}

    // Générer certificat de major
    public function genererMajor(Request $request)
    {
        $request->validate([
            'type' => 'required|in:classe,general',
            'classe_id' => 'required_if:type,classe|exists:classes,id',
            'periode' => 'required|in:mois,annee',
            'mois_id' => 'required_if:periode,mois|exists:mois_scolaires,id'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classe = $request->classe_id ? Classe::with('niveau.matieres')->find($request->classe_id) : null;
        $mois = $request->mois_id ? MoisScolaire::find($request->mois_id) : null;
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $ecole = Ecole::find($ecoleId);

        // Trouver le major
        $query = Inscription::with(['eleve', 'classe.niveau'])
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
            $moyBase = $currentClasse->moy_base ?? 20;

            $notesQuery = Note::where('inscription_id', $inscription->id)
                ->with('matiere');

            if ($request->periode == 'mois' && $mois) {
                $notesQuery->where('mois_id', $mois->id);
            }

            $notes = $notesQuery->get();

            $totalNotes = 0;
            $totalCoeffs = 0;

            foreach ($notes as $note) {
                $matierePivot = $currentClasse->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
                $base = $matierePivot->denominateur ?? 20;
                $coeff = $matierePivot->coefficient ?? 1;

                if ($note->valeur !== null && $coeff > 0) {
                    $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                    $totalCoeffs += $coeff;
                }
            }

            $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;

            if ($moyenne > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => round($moyenne, 2),
                    'moyenne_sur_20' => round(($moyenne / $moyBase) * 20, 2),
                    'moy_base' => $moyBase
                ];
            }
        }

        // Trier par moyenne décroissante et prendre le premier
        usort($elevesAvecMoyennes, function($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });

        $major = count($elevesAvecMoyennes) > 0 ? $elevesAvecMoyennes[0] : null;

        if (!$major) {
            return back()->with('error', 'Aucun major trouvé pour les critères sélectionnés.');
        }

        $pdf = PDF::loadView('dashboard.documents.certificat-major', [
            'eleve' => $major,
            'periode' => $request->periode,
            'mois' => $mois,
            'anneeScolaire' => $anneeScolaire,
            'classe' => $classe,
            'type' => $request->type,
            'ecole' => $ecole
        ]);

        $filename = $request->type == 'classe' ? 
            "major-{$classe->nom}-{$request->periode}.pdf" : 
            "major-general-{$request->periode}.pdf";

        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream($filename);
    }

    // Générer diplôme d'excellence avec photo
    public function genererDiplome(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'type' => 'required|in:mensuel,annuel',
        'mois_id' => 'required_if:type,mensuel|exists:mois_scolaires,id'
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $inscription = Inscription::with(['eleve', 'classe.niveau.matieres'])->findOrFail($request->inscription_id);
    $mois = $request->mois_id ? MoisScolaire::find($request->mois_id) : null;
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    $ecole = Ecole::find($ecoleId);

    // Calculer la moyenne selon le type
    $moyBase = $inscription->classe->moy_base ?? 20;

    $notesQuery = Note::where('inscription_id', $inscription->id)
        ->with('matiere');

    if ($request->type == 'mensuel' && $mois) {
        $notesQuery->where('mois_id', $mois->id);
    }

    $notes = $notesQuery->get();

    $totalNotes = 0;
    $totalCoeffs = 0;

    foreach ($notes as $note) {
        $matierePivot = $inscription->classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
        $base = $matierePivot->denominateur ?? 20;
        $coeff = $matierePivot->coefficient ?? 1;

        if ($note->valeur !== null && $coeff > 0) {
            $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
            $totalCoeffs += $coeff;
        }
    }

    $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;
    $moyenneArrondie = round($moyenne, 2);
    $moyenneSur20 = round(($moyenne / $moyBase) * 20, 2);

    $eleveData = [
        'inscription' => $inscription,
        'moyenne' => $moyenneArrondie,
        'moyenne_sur_20' => $moyenneSur20,
        'moy_base' => $moyBase
    ];

    $pdf = PDF::loadView('dashboard.documents.diplome-excellence', [
        'eleve' => $eleveData,
        'type' => $request->type,
        'mois' => $mois,
        'anneeScolaire' => $anneeScolaire,
        'ecole' => $ecole
    ])->setPaper('a4', 'landscape'); // Forcer l'orientation paysage

    $filename = "diplome-excellence-{$inscription->eleve->nom}-{$inscription->eleve->prenom}.pdf";

    return $pdf->stream($filename);
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