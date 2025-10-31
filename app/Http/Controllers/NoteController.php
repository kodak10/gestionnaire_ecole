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
use Illuminate\Support\Facades\Log;

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
        'coefficient' => 'required|numeric',
        'notes' => 'required|array',
        'notes.*.inscription_id' => 'required|exists:inscriptions,id',
        'notes.*.valeur' => 'required|numeric',
    ]);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');

    // Récupération du niveau_matiere pour connaître la base (denominateur)
    $classe = Classe::with('niveau.matieres')->findOrFail($validated['classe_id']);
    $matierePivot = $classe->niveau->matieres->firstWhere('id', $validated['matiere_id'])->pivot ?? null;
    $base = $matierePivot->denominateur; // base par défaut 20

    foreach ($validated['notes'] as $noteData) {
        $inscription = Inscription::findOrFail($noteData['inscription_id']);
        $valeur = $noteData['valeur'];

        // Ne pas dépasser la base
        if ($valeur > $base) {
            return back()->with('error', "La note de {$inscription->eleve->nom} dépasse la base autorisée ({$base}).");
        }

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
                'valeur' => $valeur,
                'coefficient' => $validated['coefficient'],
                'user_id' => Auth::id(),
                // Appréciation adaptée à la base réelle
                'appreciation' => $this->generateAppreciation($valeur, $base),
            ]
        );
    }

    return redirect()->route('notes.create')->with('success', 'Notes enregistrées avec succès');
}

/**
 * Génère une appréciation en fonction de la note et de la base de la matière.
 */
private function generateAppreciation($valeur, $base)
{
    // Conversion proportionnelle sur 20
    $noteSur20 = ($base > 0) ? ($valeur / $base) * 20 : $valeur;

    if ($noteSur20 < 8) return 'Très insuffisant';
    if ($noteSur20 < 10) return 'Insuffisant';
    if ($noteSur20 < 12) return 'Passable';
    if ($noteSur20 < 14) return 'Assez Bien';
    if ($noteSur20 < 16) return 'Bien';
    if ($noteSur20 < 18) return 'Très Bien';
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
                'base' => $matiere->pivot->denominateur ?? 20,
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
    $moyBase = $classe->moy_base;
    Log::info('Base de moyenne de la classe', ['moy_base' => $moyBase]);

    foreach ($inscriptions as $inscription) {
        $notes = $inscription->notes ?? collect();
        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($notes as $note) {
    // Récupérer base et coefficient depuis la matière du niveau
    $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
    $base = $matierePivot->denominateur;
    $coeff = $matierePivot->coefficient;

    $note->base = $base;
    $note->coefficient = $coeff;

    // ✅ Ignorer les notes nulles ou égales à zéro dans le calcul
    if ($note->valeur > 0 && $coeff > 0) {
        $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
        $totalCoeffs += $coeff;
    }


    $note->execo = ($note->valeur == $base);
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
// Distinctions et sanctions par élève (passer la base de la moyenne de la classe)
$distinctions = $this->calculerDistinctions($moyenneArrondie, $moyBase);
$sanctions = $this->calculerSanctions($moyenneArrondie, $moyBase);

// Obtenir la mention adaptée à la base de la classe (via conversion sur 20)
$mentionNom = $this->getMention($moyenneArrondie, $moyBase);

// Compter les "execo" (notes au barème de la matière) — on a déjà positionné $note->execo lors du parcours
$execoCount = $notes->filter(fn($n) => isset($n->execo) && $n->execo)->count();

$elevesAvecMoyennes[] = [
    'inscription' => $inscription,
    'notes' => $notes,
    'moyenne' => $moyenneArrondie,
    'mention' => $mentionNom,
    'execo_count' => $execoCount,
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

    foreach ($elevesAvecMoyennes as &$eleve) {
        foreach ($matieres as $matiere) {
            $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
            if ($note) {
                // Base et coefficient provenant du pivot niveau_matiere
                $note->base = $matiere->pivot->denominateur;
                $note->coefficient = $matiere->pivot->coefficient;

                Log::info('Note affichage pivot', [
                    'matiere' => $matiere->nom,
                    'valeur' => $note->valeur,
                    'base' => $note->base,
                    'coefficient' => $note->coefficient
                ]);
            }
        }
    }
    unset($eleve);


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

        foreach ($notesMatiere as $idx => $data) {
            $note = $data['note_obj'];
            if ($idx === 0) {
                $note->rang_matiere = 1;
            } else {
                $prev = $notesMatiere[$idx - 1]['note_obj'];
                $note->rang_matiere = ($note->valeur == $prev->valeur)
                    ? $prev->rang_matiere
                    : $idx + 1;
            }

            // Ajouter le texte formaté
            $note->rang_matiere_text = $this->formatRang($note->rang_matiere, ($idx > 0 && $note->valeur == $notesMatiere[$idx - 1]['note_obj']->valeur));
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
    // ✅ On ignore les moyennes nulles (issues de notes à 0)
$moyennesFiltrees = array_filter($moyennes, fn($m) => $m > 0);
$moyClasse = count($moyennesFiltrees) > 0 ? array_sum($moyennesFiltrees) / count($moyennesFiltrees) : 0;

    $moyPremier = count($moyennes) > 0 ? max($moyennes) : 0;
    $moyDernier = count($moyennes) > 0 ? min($moyennes) : 0;

    foreach ($notes as $note) {
    Log::info('Note trouvée', [
        'matiere' => $note->matiere->nom ?? 'N/A',
        'valeur' => $note->valeur,
        'base' => $note->base,
        'coefficient' => $note->coefficient ?? 1,
    ]);
}


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

    return $pdf->stream('bulletins-' . $classe->nom . '-' . $mois->nom . '.pdf');
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








private function calculerDistinctions($moyenne, $moyBase)
{
    $distinctions = [
        'tableau_honneur' => false,
        'encouragement'   => false,
        'felicitation'    => false,
    ];

    if ($moyenne >= (0.8 * $moyBase)) { // 80% ou plus → félicitations
    $distinctions['felicitation'] = true;
} elseif ($moyenne >= (0.7 * $moyBase)) {
    $distinctions['encouragement'] = true;
} elseif ($moyenne >= (0.6 * $moyBase)) {
    $distinctions['tableau_honneur'] = true;
}


    return $distinctions;
}

private function calculerSanctions($moyenne, $moyBase)
{
    $sanctions = [
        'avertissement_travail' => false,
        'blame_travail'          => false,
        'avertissement_conduite' => false,
        'blame_conduite'         => false,
    ];

   if ($moyenne < (0.4 * $moyBase)) {
    $sanctions['blame_travail'] = true;
} elseif ($moyenne < (0.5 * $moyBase)) {
    $sanctions['avertissement_travail'] = true;
}


    // tu pourras ajouter ici d’autres règles pour la conduite plus tard
    return $sanctions;
}

private function formatRangMatiere($rang, $exaequo = false)
{
    if (!$rang) {
        return '-';
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


public function generateRecapMoyennes()
{
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');

    // Charger toutes les classes avec enseignants, inscriptions, élèves et matières
    $classes = Classe::with([
        'enseignant',
        'inscriptions.eleve',
        'niveau.matieres'
    ])
    ->where('ecole_id', $ecoleId)
    ->where('annee_scolaire_id', $anneeScolaireId)
    ->get();

    $data = [];

    foreach ($classes as $classe) {
        $matieres = $classe->niveau->matieres;
        $inscriptions = $classe->inscriptions;

        $eleves = [];

        foreach ($inscriptions as $inscription) {
            $notes = Note::where('inscription_id', $inscription->id)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->get();

            $totalNotes = 0;
            $totalCoeffs = 0;
            $notesParMatiere = [];

            foreach ($matieres as $matiere) {
                $note = $notes->firstWhere('matiere_id', $matiere->id);
                $valeur = ($note && $note->valeur > 0) ? $note->valeur : '';
                $notesParMatiere[$matiere->nom] = [
                    'valeur' => $valeur,
                    'base' => $matiere->pivot->denominateur,
                    'coefficient' => $matiere->pivot->coefficient
                ];

                if ($note && $note->valeur > 0) {
                    $totalNotes += ($note->valeur / $matiere->pivot->denominateur) * $classe->moy_base * $matiere->pivot->coefficient;
                    $totalCoeffs += $matiere->pivot->coefficient;
                }
            }

            $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

            $eleves[] = [
                'nom' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenoms,
                'notes' => $notesParMatiere,
                'moyenne' => $moyenne ? number_format($moyenne, 2, ',', '') : ''
            ];
        }

        $data[] = [
            'classe' => $classe,
            'matieres' => $matieres,
            'eleves' => $eleves,
            'enseignant' => $classe->enseignant?->name ?? '—'
        ];
    }

    $pdf = Pdf::loadView('dashboard.documents.recap_moyennes', compact('data'))
        ->setPaper('a4', 'landscape');

    return $pdf->stream('recap_moyennes_classes.pdf');
}

}