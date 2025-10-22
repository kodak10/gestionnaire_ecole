<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\MoisScolaire;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Note;
use Illuminate\Http\Request;
use PDF;

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

    // Générer tableau d'honneur mensuel
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
        $classe = $request->classe_id ? Classe::find($request->classe_id) : null;

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
            $notes = Note::where('inscription_id', $inscription->id)
                ->where('mois_id', $request->mois_id)
                ->get();

            $totalNotes = 0;
            $totalCoeffs = 0;

            foreach ($notes as $note) {
                $totalNotes += $note->valeur * $note->coefficient;
                $totalCoeffs += $note->coefficient;
            }

            $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

            if ($moyenne > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => round($moyenne, 2)
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
            'nombreEleves' => $request->nombre_eleves
        ]);

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

        $classe = $request->classe_id ? Classe::find($request->classe_id) : null;
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

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
            $notes = Note::where('inscription_id', $inscription->id)
                ->get();

            $totalNotes = 0;
            $totalCoeffs = 0;

            foreach ($notes as $note) {
                $totalNotes += $note->valeur * $note->coefficient;
                $totalCoeffs += $note->coefficient;
            }

            $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

            if ($moyenne > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => round($moyenne, 2)
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
            'nombreEleves' => $request->nombre_eleves
        ]);

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

        $classe = $request->classe_id ? Classe::find($request->classe_id) : null;
        $mois = $request->mois_id ? MoisScolaire::find($request->mois_id) : null;
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

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
            $notesQuery = Note::where('inscription_id', $inscription->id);

            if ($request->periode == 'mois' && $mois) {
                $notesQuery->where('mois_id', $mois->id);
            }

            $notes = $notesQuery->get();

            $totalNotes = 0;
            $totalCoeffs = 0;

            foreach ($notes as $note) {
                $totalNotes += $note->valeur * $note->coefficient;
                $totalCoeffs += $note->coefficient;
            }

            $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

            if ($moyenne > 0) {
                $elevesAvecMoyennes[] = [
                    'inscription' => $inscription,
                    'moyenne' => round($moyenne, 2)
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
            'major' => $major,
            'periode' => $request->periode,
            'mois' => $mois,
            'anneeScolaire' => $anneeScolaire,
            'classe' => $classe,
            'type' => $request->type
        ]);

        $filename = $request->type == 'classe' ? 
            "major-{$classe->nom}-{$request->periode}.pdf" : 
            "major-general-{$request->periode}.pdf";

        return $pdf->stream($filename);
    }
}