<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
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
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Directeur|Enseignant']);
    }

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $query = Note::with(['inscription.eleve', 'matiere', 'classe', 'mois'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        
        // Filtre par classe
        if ($request->has('classe_id') && $request->classe_id != '') {
            $query->where('classe_id', $request->classe_id);
        }
        
        // Filtre par matière
        if ($request->has('matiere_id') && $request->matiere_id != '') {
            $query->where('matiere_id', $request->matiere_id);
        }
        
        // Filtre par mois
        if ($request->has('mois_id') && $request->mois_id != '') {
            $query->where('mois_id', $request->mois_id);
        }
        
        // Recherche par nom d'élève
        if ($request->has('nom') && $request->nom != '') {
            $query->whereHas('inscription.eleve', function($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->nom . '%')
                ->orWhere('prenom', 'like', '%' . $request->nom . '%');
            });
        }
        
        // Tri des résultats
        $sortBy = $request->get('sort_by', 'created_at');
        $sort = $request->get('sort', 'desc');
        $query->orderBy($sortBy, $sort);
        
        $notes = $query->paginate(20);
        
        $eleves = Eleve::orderBy('nom')->get();
        $matieres = Matiere::orderBy('nom')->get();
        $classes = Classe::orderBy('nom')->get();
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.index', compact('notes', 'eleves', 'matieres', 'classes', 'moisScolaire'));
    }

    public function filterByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'nullable|exists:classes,id',
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');


        $notes = Note::with(['inscription.eleve', 'matiere', 'classe', 'mois'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->when($request->classe_id, function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            })
            ->get()
            ->map(function($note) {
                return [
                    'id' => $note->id,
                    'eleve' => $note->inscription->eleve->nom . ' ' . $note->inscription->eleve->prenom,
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
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::with('niveau')
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->orderBy('id')->get();

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
            'notes.*.inscription_id' => 'required|exists:inscriptions,id',
            'notes.*.valeur' => 'required|numeric|min:0|max:20'
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        foreach ($validated['notes'] as $noteData) {
            // Récupérer l'inscription pour obtenir l'élève_id
            $inscription = Inscription::findOrFail($noteData['inscription_id']);
            
            Note::updateOrCreate(
                [
                    'inscription_id' => $noteData['inscription_id'],
                    'matiere_id' => $validated['matiere_id'],
                    'mois_id' => $validated['mois_id'],
                    'annee_scolaire_id' => $anneeScolaireId,
                    'ecole_id' => $ecoleId,

                ],
                [
                    'eleve_id' => $inscription->eleve_id,
                    'classe_id' => $validated['classe_id'],
                    'valeur' => $noteData['valeur'],
                    'coefficient' => $validated['coefficient'],
                    'user_id' => Auth::id(),
                    'appreciation' => $this->generateAppreciation($noteData['valeur']),

                    // 'annee_scolaire_id' => $anneeScolaireId,
                    // 'ecole_id' => $ecoleId,
                ]
            );
        }

        return redirect()->route('notes.create')->with('success', 'Notes enregistrées avec succès');
    }

    private function generateAppreciation($valeur)
{
    if ($valeur < 8) return 'Très insuffisant';
    if ($valeur < 10) return 'Insuffisant';
    if ($valeur < 12) return 'Passable';
    if ($valeur < 14) return 'Assez Bien';
    if ($valeur < 16) return 'Bien';
    if ($valeur < 18) return 'Très Bien';
    return 'Excellent';
}

    // je en gère pas ça
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

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');


        $inscriptions = Inscription::with('eleve')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('classe_id', $request->classe_id)
            ->where('statut', 'active')
            ->get()
            ->filter(function($inscription) {
                return $inscription->eleve !== null;
            })
            ->sortBy(function($inscription) {
                return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
            })
            ->values()
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
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
                'coefficient' => $matiere->pivot->coefficient ?? 1,
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
                    'eleve' => $note->inscription->eleve->nom . ' ' . $note->inscription->eleve->prenom,
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

    // Récupérer école et année scolaire en session
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

    // Classe et niveau avec matières (triées par ordre numérique)
    $classe = Classe::with(['niveau.matieres' => function ($q) {
        $q->orderByPivot('ordre');
    }])->findOrFail($request->classe_id);

    $mois = MoisScolaire::findOrFail($request->mois_id);

    // Récupérer inscriptions avec élèves et notes pour le mois
    $inscriptions = Inscription::with(['eleve', 'notes' => function ($q) use ($request) {
        $q->where('mois_id', $request->mois_id)->with('matiere');
    }])
        ->where('classe_id', $request->classe_id)
        ->where('statut', 'active')
        ->get();

    // Mentions de l'école
    $mentions = Mention::where('ecole_id', $ecoleId)
        ->orderBy('min_note')
        ->get();

    $elevesAvecMoyennes = [];
    foreach ($inscriptions as $inscription) {
        $notes = $inscription->notes ?? collect();
        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($notes as $note) {
            $totalNotes += ($note->valeur * ($note->coefficient ?? 1));
            $totalCoeffs += ($note->coefficient ?? 1);
            $note->execo = ($note->valeur == 20);
        }

        $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;
        $moyenneArrondie = round($moyenne, 2);

        $mention = $mentions->first(function ($m) use ($moyenneArrondie) {
            // Cas particulier : dernière mention (max = 20)
            if ($m->max_note >= 20) {
                return $moyenneArrondie >= $m->min_note && $moyenneArrondie <= 20;
            }

            // Tolérance plus large pour éviter les décimales entre 17.00 et 17.99
            return $moyenneArrondie >= $m->min_note && $moyenneArrondie < ($m->max_note + 1);
        });



        // Distinctions et sanctions par élève
        $distinctions = $this->calculerDistinctions($moyenneArrondie);
        $sanctions = $this->calculerSanctions($moyenneArrondie);

        $elevesAvecMoyennes[] = [
            'inscription' => $inscription,
            'notes' => $notes,
            'moyenne' => $moyenneArrondie,
            'mention' => $mention ? $mention->nom : 'Non classé',
            'execo_count' => $notes->where('valeur', 20)->count(),
            'total_notes' => $totalNotes,
            'total_coeffs' => $totalCoeffs,
            'distinctions' => $distinctions,
            'sanctions' => $sanctions,
        ];
    }

    // Classement par matière (respect de l'ordre pivot)
    $matieres = $classe->niveau->matieres
        ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
        ->values();

    foreach ($matieres as $matiere) {
        $notesMatiere = [];
        foreach ($elevesAvecMoyennes as $index => &$eleve) {
            $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
            if ($note) {
                $notesMatiere[] = [
                    'note_obj' => $note,
                    'eleve_index' => $index,
                ];
            }
        }

        // Trier par valeur décroissante
        usort($notesMatiere, fn($a, $b) => $b['note_obj']->valeur <=> $a['note_obj']->valeur);

        // Attribuer les rangs par matière avec ex-aequo
        foreach ($notesMatiere as $idx => $data) {
            if ($idx === 0) {
                $data['note_obj']->rang_matiere = 1;
            } else {
                $prev = $notesMatiere[$idx - 1];
                $data['note_obj']->rang_matiere = ($data['note_obj']->valeur == $prev['note_obj']->valeur)
                    ? $prev['note_obj']->rang_matiere
                    : $idx + 1;
            }
        }
    }

    // Classement général (tri par moyenne décroissante, puis par nom)
    usort($elevesAvecMoyennes, function ($a, $b) {
        if ($a['moyenne'] != $b['moyenne']) {
            return $b['moyenne'] <=> $a['moyenne'];
        }
        return $a['inscription']->eleve->nom <=> $b['inscription']->eleve->nom;
    });

    // Attribution rang général avec ex-aequo (corrigé)
    $moyKeys = array_map(fn($e) => sprintf('%.2f', $e['moyenne']), $elevesAvecMoyennes);
    $moyCounts = array_count_values($moyKeys);

    foreach ($elevesAvecMoyennes as $index => &$eleve) {
        $key = sprintf('%.2f', $eleve['moyenne']);
        $eleve['exaequo'] = ($moyCounts[$key] > 1);

        if ($index === 0) {
            $eleve['rang_general'] = 1;
        } else {
            $prev = $elevesAvecMoyennes[$index - 1];
            $eleve['rang_general'] = ($key == sprintf('%.2f', $prev['moyenne']))
                ? $prev['rang_general']
                : $index + 1;
        }

        // rang_text pour TOUS les élèves
        $eleve['rang_text'] = $this->formatRang($eleve['rang_general'], $eleve['exaequo']);
    }
    unset($eleve);

    // Statistiques de classe
    $moyennes = array_column($elevesAvecMoyennes, 'moyenne');
    $moyClasse = count($moyennes) > 0 ? array_sum($moyennes) / count($moyennes) : 0;
    $moyPremier = count($moyennes) > 0 ? max($moyennes) : 0;
    $moyDernier = count($moyennes) > 0 ? min($moyennes) : 0;

    // Génération PDF
    $pdf = Pdf::loadView('dashboard.documents.bulletin', [
        'classe' => $classe,
        'mois' => $mois,
        'elevesAvecMoyennes' => $elevesAvecMoyennes,
        'matieres' => $matieres,
        'moyClasse' => round($moyClasse, 2),
        'moyPremier' => round($moyPremier, 2),
        'moyDernier' => round($moyDernier, 2),
        'effectif' => count($elevesAvecMoyennes),
        'anneeScolaire' => $anneeScolaire,
    ]);

    $pdf->setOption('defaultFont', 'DejaVu Sans');

    return $pdf->stream('bulletins-' . $classe->nom . '-' . $mois->nom . '.pdf');
}



private function calculerDistinctions($moyenne)
{
    $distinctions = [
        'tableau_honneur' => false,
        'encouragement'   => false,
        'felicitation'    => false,
    ];

    if ($moyenne >= 16) {
        $distinctions['felicitation'] = true;
    } elseif ($moyenne >= 14) {
        $distinctions['encouragement'] = true;
    } elseif ($moyenne >= 12) {
        $distinctions['tableau_honneur'] = true;
    }

    return $distinctions;
}

private function calculerSanctions($moyenne)
{
    $sanctions = [
        'avertissement_travail' => false,
        'blame_travail'          => false,
        'avertissement_conduite' => false,
        'blame_conduite'         => false,
    ];

    if ($moyenne < 8) {
        $sanctions['blame_travail'] = true;
    } elseif ($moyenne < 10) {
        $sanctions['avertissement_travail'] = true;
    }

    // tu pourras ajouter ici d’autres règles pour la conduite plus tard
    return $sanctions;
}

private function formatRang($rang, $exaequo = false)
{
    if (!$rang) {
        return '';
    }

    $suffix = match($rang) {
        1 => 'er',
        default => 'e',
    };

    $texte = $rang . $suffix;

    if ($exaequo) {
        $texte .= ' ex æquo';
    }

    return $texte;
}

public function generateFichesMoyennes(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'mois_id' => 'required|exists:mois_scolaires,id'
    ]);

    $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);
    $mois = MoisScolaire::findOrFail($request->mois_id);

    // Récupérer tous les élèves de la classe avec la jointure correcte
    $eleves = Inscription::with(['eleve'])
        ->where('classe_id', $request->classe_id)
        ->where('statut', 'active')
        ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
        ->orderBy('eleves.nom')
        ->orderBy('eleves.prenom')
        ->select('inscriptions.*')
        ->get();

    $pdf = Pdf::loadView('dashboard.documents.fiches-moyennes', [
        'classe' => $classe,
        'mois' => $mois,
        'eleves' => $eleves
    ])->setPaper('a4', 'landscape');

    return $pdf->stream('fiche-notes-' . $classe->nom . '-' . $mois->nom . '.pdf');
}

}