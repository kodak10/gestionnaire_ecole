<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Mention;
use App\Models\MoisScolaire;
use App\Models\Note;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $notes = Note::with(['inscription.eleve', 'matiere', 'classe', 'mois'])
            ->filter($request)
            ->join('mois_scolaires', 'notes.mois_id', '=', 'mois_scolaires.id')
            ->orderBy('mois_scolaires.nom', 'asc')
            ->select('notes.*')
            ->paginate(20);

        $eleves = Eleve::orderBy('nom')->get();
        $matieres = Matiere::orderBy('nom')->get();
        $classes = Classe::orderBy('nom')->get();
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.index', compact('notes', 'eleves', 'matieres', 'classes', 'moisScolaire'));
    }

    // NoteController.php
public function filterByClasse(Request $request)
{
    $request->validate([
        'classe_id' => 'nullable|exists:classes,id',
    ]);

    $notes = Note::with(['inscription.eleve', 'matiere', 'classe', 'mois'])
        ->when($request->classe_id, function($q) use ($request) {
            $q->where('classe_id', $request->classe_id);
        })
        ->get()
        ->map(function($note) {
            return [
                'id' => $note->id,
                'eleve' => $note->inscription->eleve->prenom . ' ' . $note->inscription->eleve->nom,
                'matiere' => $note->matiere->nom,
                'classe' => $note->classe->nom,
                'valeur' => $note->valeur,
                'coefficient' => $note->coefficient,
                'mois' => $note->mois->nom,
            ];
        });

    return response()->json($notes);
}


    public function show(Note $note)
    {
        return view('notes.show', compact('note'));
    }

    public function create()
    {
        $classes = Classe::with('niveau')->orderBy('nom')->get();
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.create', compact('moisScolaire', 'classes'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'mois_id' => 'required|exists:mois_scolaires,id',
            'coefficient' => 'required|integer|min:1',
            'notes' => 'required|array',
            'notes.*.inscription_id' => 'required|exists:inscriptions,id', // Changé de eleve_id à inscription_id
            'notes.*.valeur' => 'required|numeric|min:0|max:20'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');


        foreach ($validated['notes'] as $noteData) {
            // Récupérer l'inscription pour obtenir l'élève_id
            $inscription = Inscription::findOrFail($noteData['inscription_id']);
            
            Note::updateOrCreate(
                [
                    'inscription_id' => $noteData['inscription_id'], // Utilisation de inscription_id
                    'matiere_id' => $validated['matiere_id'],
                    'mois_id' => $validated['mois_id'],
                    'annee_scolaire_id' => $anneeScolaireId,
                    'ecole_id' => $ecoleId,

                ],
                [
                    'eleve_id' => $inscription->eleve_id, // On garde aussi l'eleve_id pour compatibilité
                    'classe_id' => $validated['classe_id'],
                    'valeur' => $noteData['valeur'],
                    'coefficient' => $validated['coefficient'],
                    'user_id' => Auth::id(),
                    // 'annee_scolaire_id' => $anneeScolaireId,
                    // 'ecole_id' => $ecoleId,
                ]
            );
        }

        return redirect()->route('notes.index')
            ->with('success', 'Notes enregistrées avec succès');
    }

    public function edit(Note $note)
{
    $eleves = Eleve::orderBy('nom')->get();
    $matieres = Matiere::orderBy('nom')->get();
    $classes = Classe::orderBy('nom')->get();
    $inscriptions = Inscription::with('eleve')->orderBy('id')->get();
    $moisScolaire = MoisScolaire::all();

    return view('dashboard.pages.eleves.notes.edit', compact(
        'note', 
        'eleves', 
        'matieres', 
        'classes', 
        'inscriptions',
        'moisScolaire'
    ));
}


    // Méthode pour récupérer les inscriptions d'une classe (pour AJAX)
    public function getInscriptionsByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $inscriptions = Inscription::with('eleve')
            ->where('classe_id', $request->classe_id)
            ->where('statut', 'active')
            ->get()
            ->filter(function($inscription) {
                return $inscription->eleve !== null; // Filtre les inscriptions sans élève
            })
            ->sortBy(function($inscription) {
                return $inscription->eleve->prenom . ' ' . $inscription->eleve->nom;
            })
            ->values()
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                ];
            });

        return response()->json($inscriptions);
    }



    public function getMatieresByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);

        $matieres = $classe->niveau->matieres->map(function($matiere) {
            return [
                'id' => $matiere->id,
                'nom' => $matiere->nom,
                'coefficient' => $matiere->pivot->coefficient ?? 1, // si tu utilises une table pivot niveau_matiere
            ];
        });

        return response()->json($matieres);
    }

    public function getNotesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'mois_id' => 'required|exists:mois_scolaires,id',
        ]);

        $notes = Note::with(['inscription.eleve'])
            ->where('classe_id', $request->classe_id)
            ->where('matiere_id', $request->matiere_id)
            ->where('mois_id', $request->mois_id)
            ->get()
            ->map(function($note){
                return [
                    'id' => $note->id,
                    'inscription_id' => $note->inscription_id,
                    'eleve' => $note->inscription->eleve->prenom . ' ' . $note->inscription->eleve->nom,
                    'valeur' => $note->valeur,
                ];
            });

        return response()->json($notes);
    }

public function generateBulletin(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'mois_id' => 'required|exists:mois_scolaires,id'
    ]);

    $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);
    $mois = MoisScolaire::findOrFail($request->mois_id);

    $inscriptions = Inscription::with(['eleve', 'notes' => function($q) use ($request) {
        $q->where('mois_id', $request->mois_id)->with('matiere');
    }])
    ->where('classe_id', $request->classe_id)
    ->where('statut', 'active')
    ->get();

    $mentions = Mention::where('ecole_id', auth()->user()->ecole_id)
        ->orderBy('min_note')
        ->get();

    $elevesAvecMoyennes = [];

    foreach ($inscriptions as $inscription) {
        $notes = $inscription->notes ?? collect();
        $totalNotes = 0;
        $totalCoeffs = 0;
        $execo = false;

        foreach ($notes as $note) {
            $totalNotes += $note->valeur * $note->coefficient;
            $totalCoeffs += $note->coefficient;
            if($note->valeur == 20) $execo = true; // Execo général
        }

        $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

        $mention = $mentions->first(function($m) use ($moyenne) {
            return $moyenne >= $m->min_note && $moyenne <= $m->max_note;
        });

        $elevesAvecMoyennes[] = [
            'inscription' => $inscription,
            'notes' => $notes,
            'moyenne' => round($moyenne, 2),
            'mention' => $mention ? $mention->nom : 'Non classé',
            'execo' => $execo,
        ];
    }

    // Classement général
    usort($elevesAvecMoyennes, function($a, $b) {
        if($b['moyenne'] == $a['moyenne']){
            if($a['execo'] && !$b['execo']) return 1;
            if(!$a['execo'] && $b['execo']) return -1;
            return strcmp(
                $a['inscription']->eleve->prenom . $a['inscription']->eleve->nom,
                $b['inscription']->eleve->prenom . $b['inscription']->eleve->nom
            );
        }
        return $b['moyenne'] <=> $a['moyenne'];
    });

    $rang = 1;
    $prevMoyenne = null;
    foreach($elevesAvecMoyennes as $i => &$eleve){
        if($prevMoyenne !== null && $eleve['moyenne'] == $prevMoyenne){
            $eleve['rang'] = $rang;
        } else {
            $rang = $i + 1;
            $eleve['rang'] = $rang;
        }
        $prevMoyenne = $eleve['moyenne'];
    }

    $pdf = Pdf::loadView('dashboard.documents.bulletin', [
        'classe' => $classe,
        'mois' => $mois,
        'elevesAvecMoyennes' => $elevesAvecMoyennes
    ]);

    return $pdf->stream('bulletins-' . $classe->nom . '.pdf');
}




}