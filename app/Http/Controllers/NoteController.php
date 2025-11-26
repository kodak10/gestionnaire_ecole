<?php

namespace App\Http\Controllers;

use \DB;
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
        'notes' => 'array',
        'notes.*.inscription_id' => 'required|exists:inscriptions,id',
        'notes.*.valeur' => 'nullable|numeric',
    ]);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');

    $classe = Classe::with('niveau.matieres')->findOrFail($validated['classe_id']);
    $matierePivot = $classe->niveau->matieres->firstWhere('id', $validated['matiere_id'])->pivot ?? null;
    $base = $matierePivot->denominateur;

    foreach ($validated['notes'] as $noteData) {
        $valeur = $noteData['valeur'];

        if ($valeur === null || $valeur === '') {
            continue;
        }

        $inscription = Inscription::findOrFail($noteData['inscription_id']);

        if ($valeur > $base) {
            // Retourner avec les anciennes valeurs
            return back()
                ->withInput()
                ->with('error', "La note de {$inscription->eleve->nom} dépasse la base autorisée ({$base}).");
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
                'appreciation' => $this->generateAppreciation($valeur, $base),
            ]
        );
    }

    return redirect()->route('notes.create')
        ->with('success', 'Notes enregistrées avec succès')
        ->withInput(); // ← Ajoutez cette ligne pour conserver les valeurs
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

// public function generateBulletin(Request $request)
// {
//     $request->validate([
//         'classe_id' => 'required|exists:classes,id',
//         'mois_id' => 'required|exists:mois_scolaires,id'
//     ]);

//     // Récupérer école et année scolaire en session
//     $ecoleId = session('current_ecole_id');
//     $anneeScolaireId = session('current_annee_scolaire_id');
//     $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

//     // Classe et niveau avec matières (triées par ordre numérique)
//     $classe = Classe::with(['niveau.matieres' => function ($q) {
//         $q->orderByPivot('ordre');
//     }])->findOrFail($request->classe_id);

//     $mois = MoisScolaire::findOrFail($request->mois_id);

//     // Récupérer inscriptions avec élèves et notes pour le mois
//     $inscriptions = Inscription::with(['eleve', 'notes' => function ($q) use ($request) {
//         $q->where('mois_id', $request->mois_id)->with('matiere');
//     }])
//         ->where('classe_id', $request->classe_id)
//         ->where('statut', 'active')
//         ->get();

//     // Mentions de l'école
//     $mentions = Mention::where('ecole_id', $ecoleId)
//         ->orderBy('min_note')
//         ->get();

//     $elevesAvecMoyennes = [];
//     $moyBase = $classe->moy_base;

//     foreach ($inscriptions as $inscription) {
//         $notes = $inscription->notes ?? collect();
//         $totalNotes = 0;
//         $totalCoeffs = 0;

//         foreach ($notes as $note) {

//             // Pivot matière
//             $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
//             $base = $matierePivot->denominateur;
//             $coeff = $matierePivot->coefficient;

//             $note->base = $base;
//             $note->coefficient = $coeff;

//             // ⛔ Notes NULL ignorées — ✔ Notes = 0 comptées
//             if ($note->valeur !== null && $coeff > 0) {
//                 $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
//                 $totalCoeffs += $coeff;
//             }

//             $note->execo = ($note->valeur == $base);
//         }

//         $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;

//         $moyenneArrondie = round($moyenne, 2);

//         // Mention
//         $mentionNom = $this->getMention($moyenneArrondie, $moyBase);

//         // Distinctions / sanctions
//         $distinctions = $this->calculerDistinctions($moyenneArrondie, $moyBase);
//         $sanctions = $this->calculerSanctions($moyenneArrondie, $moyBase);

//         $execoCount = $notes->filter(fn($n) => isset($n->execo) && $n->execo)->count();

//         $elevesAvecMoyennes[] = [
//             'inscription' => $inscription,
//             'notes' => $notes,
//             'moyenne' => $moyenneArrondie,
//             'mention' => $mentionNom,
//             'execo_count' => $execoCount,
//             'total_notes' => $totalNotes,
//             'total_coeffs' => $totalCoeffs,
//             'distinctions' => $distinctions,
//             'sanctions' => $sanctions,
//         ];
//     }

//     // Classement par matière
//     $matieres = $classe->niveau->matieres
//         ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
//         ->values();

//     foreach ($elevesAvecMoyennes as &$eleve) {
//         foreach ($matieres as $matiere) {
//             $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
//             if ($note) {
//                 $note->base = $matiere->pivot->denominateur;
//                 $note->coefficient = $matiere->pivot->coefficient;
//             }
//         }
//     }
//     unset($eleve);

//     // ----------------------------
//     // 🔥🔥🔥 BLOC CORRIGÉ ICI 🔥🔥🔥
//     // ----------------------------
//     foreach ($matieres as $matiere) {
//         $notesMatiere = [];
//         foreach ($elevesAvecMoyennes as $index => &$eleve) {
//             $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
//             if ($note) {
//                 $notesMatiere[] = [
//                     'note_obj' => $note,
//                     'eleve_index' => $index,
//                 ];
//             }
//         }

//         // Tri sécurisé : NULL → bas, 0 → pris en compte normalement
//         usort($notesMatiere, function ($a, $b) {
//             $va = $a['note_obj']->valeur ?? -1;
//             $vb = $b['note_obj']->valeur ?? -1;
//             return $vb <=> $va;
//         });

//         foreach ($notesMatiere as $idx => $data) {
//             $note = $data['note_obj'];

//             if ($idx === 0) {
//                 $note->rang_matiere = 1;
//             } else {
//                 $prev = $notesMatiere[$idx - 1]['note_obj'];

//                 if ($note->valeur !== null && $note->valeur == $prev->valeur) {
//                     $note->rang_matiere = $prev->rang_matiere;
//                 } else {
//                     $note->rang_matiere = $idx + 1;
//                 }
//             }

//             $note->rang_matiere_text = $this->formatRang($note->rang_matiere);
//         }
//     }
//     // ----------------------------


//     // Classement général
//     usort($elevesAvecMoyennes, function ($a, $b) {
//         if ($a['moyenne'] != $b['moyenne']) {
//             return $b['moyenne'] <=> $a['moyenne'];
//         }
//         return $a['inscription']->eleve->nom <=> $b['inscription']->eleve->nom;
//     });

//     $moyKeys = array_map(fn($e) => sprintf('%.2f', $e['moyenne']), $elevesAvecMoyennes);
//     $moyCounts = array_count_values($moyKeys);

//     foreach ($elevesAvecMoyennes as $index => &$eleve) {
//         $key = sprintf('%.2f', $eleve['moyenne']);
//         $eleve['exaequo'] = ($moyCounts[$key] > 1);

//         if ($index === 0) {
//             $eleve['rang_general'] = 1;
//         } else {
//             $prev = $elevesAvecMoyennes[$index - 1];
//             $eleve['rang_general'] = ($key == sprintf('%.2f', $prev['moyenne']))
//                 ? $prev['rang_general']
//                 : $index + 1;
//         }

//         $eleve['rang_text'] = $this->formatRang($eleve['rang_general'], $eleve['exaequo']);
//     }
//     unset($eleve);

//     // Moyenne de classe
//     $moyennes = array_column($elevesAvecMoyennes, 'moyenne');
//     // $moyennesFiltrees = array_filter($moyennes, fn($m) => $m > 0);
//     $moyennesFiltrees = array_filter($moyennes, fn($m) => $m !== null);

//     $moyClasse = count($moyennesFiltrees) > 0 ? array_sum($moyennesFiltrees) / count($moyennesFiltrees) : 0;

//     $moyPremier = count($moyennes) > 0 ? max($moyennes) : 0;
//     $moyennesNonNull = array_filter($moyennes, fn($m) => $m !== null);
//     $moyDernier = count($moyennesNonNull) > 0 ? min($moyennesNonNull) : 0;


//     // Génération PDF
//     $pdf = Pdf::loadView('dashboard.documents.bulletin', [
//         'classe' => $classe,
//         'mois' => $mois,
//         'elevesAvecMoyennes' => $elevesAvecMoyennes,
//         'matieres' => $matieres,
//         'moyClasse' => round($moyClasse, 2),
//         'moyPremier' => round($moyPremier, 2),
//         'moyDernier' => round($moyDernier, 2),
//         'effectif' => count($elevesAvecMoyennes),
//         'anneeScolaire' => $anneeScolaire,
//     ]);

//     return $pdf->stream('bulletins-' . $classe->nom . '-' . $mois->nom . '.pdf');
// }


public function generateBulletin(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'mois_id' => 'required|exists:mois_scolaires,id'
    ]);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

    $classe = Classe::with(['niveau.matieres' => function ($q) {
        $q->orderByPivot('ordre');
    }])->findOrFail($request->classe_id);

    $mois = MoisScolaire::findOrFail($request->mois_id);

    $inscriptions = Inscription::with(['eleve', 'notes' => function ($q) use ($request) {
        $q->where('mois_id', $request->mois_id)->with('matiere');
    }])
        ->where('classe_id', $request->classe_id)
        ->where('statut', 'active')
        ->get();

    $mentions = Mention::where('ecole_id', $ecoleId)
        ->orderBy('min_note')
        ->get();

    $elevesAvecMoyennes = [];
    $moyBase = $classe->moy_base;

    foreach ($inscriptions as $inscription) {
        $notes = $inscription->notes ?? collect();
        $totalNotes = 0;
        $totalCoeffs = 0;

        foreach ($notes as $note) {
            $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
            $base = $matierePivot->denominateur;
            $coeff = $matierePivot->coefficient;

            $note->base = $base;
            $note->coefficient = $coeff;

            if ($note->valeur !== null && $coeff > 0) {
                $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                $totalCoeffs += $coeff;
            }

            $note->execo = ($note->valeur == $base);
        }

        $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;
        $moyenneArrondie = $moyenne !== null ? round($moyenne, 2) : null;

        $mentionNom = $moyenneArrondie !== null
            ? $this->getMention($moyenneArrondie, $moyBase)
            : 'N/A';

        $distinctions = $moyenneArrondie !== null ? $this->calculerDistinctions($moyenneArrondie, $moyBase) : [];
        $sanctions = $moyenneArrondie !== null ? $this->calculerSanctions($moyenneArrondie, $moyBase) : [];

        $execoCount = $notes->filter(fn($n) => isset($n->execo) && $n->execo)->count();

        $elevesAvecMoyennes[] = [
            'inscription' => $inscription,
            'notes' => $notes,
            'moyenne' => $moyenneArrondie ?? 0, // Pour affichage PDF
            'mention' => $mentionNom,
            'execo_count' => $execoCount,
            'total_notes' => $totalNotes,
            'total_coeffs' => $totalCoeffs,
            'distinctions' => $distinctions,
            'sanctions' => $sanctions,
        ];

        Log::info('Mention trouvée', [
            'moyenneOriginale' => $moyenne,
            'moyenneArrondie' => $moyenneArrondie,
            'mention' => $mentionNom
        ]);
    }

    // Classement par matière
    $matieres = $classe->niveau->matieres
        ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
        ->values();

    foreach ($elevesAvecMoyennes as &$eleve) {
        foreach ($matieres as $matiere) {
            $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
            if ($note) {
                $note->base = $matiere->pivot->denominateur;
                $note->coefficient = $matiere->pivot->coefficient;
            }
        }
    }
    unset($eleve);

    // Classement par matière avec rang
    foreach ($matieres as $matiere) {
        $notesMatiere = [];
        foreach ($elevesAvecMoyennes as $index => &$eleve) {
            $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
            if ($note) {
                $notesMatiere[] = ['note_obj' => $note, 'eleve_index' => $index];
            }
        }

        usort($notesMatiere, function ($a, $b) {
            $va = $a['note_obj']->valeur ?? -1;
            $vb = $b['note_obj']->valeur ?? -1;
            return $vb <=> $va;
        });

        foreach ($notesMatiere as $idx => $data) {
            $note = $data['note_obj'];
            if ($idx === 0) {
                $note->rang_matiere = 1;
            } else {
                $prev = $notesMatiere[$idx - 1]['note_obj'];
                $note->rang_matiere = ($note->valeur !== null && $note->valeur == $prev->valeur)
                    ? $prev->rang_matiere
                    : $idx + 1;
            }
            $note->rang_matiere_text = $this->formatRang($note->rang_matiere);
        }
    }
    unset($eleve);

    // Classement général
    usort($elevesAvecMoyennes, function ($a, $b) {
        return $b['moyenne'] <=> $a['moyenne'] ?: strcmp($a['inscription']->eleve->nom, $b['inscription']->eleve->nom);
    });

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

        $eleve['rang_text'] = $this->formatRang($eleve['rang_general'], $eleve['exaequo']);
    }
    unset($eleve);

    // Moyennes de classe en ignorant élèves sans note
    $elevesAvecNotes = array_filter($elevesAvecMoyennes, fn($e) => $e['total_coeffs'] > 0);
    $moyClasse = count($elevesAvecNotes) > 0
        ? array_sum(array_column($elevesAvecNotes, 'moyenne')) / count($elevesAvecNotes)
        : 0;
    $moyPremier = count($elevesAvecNotes) > 0
        ? max(array_column($elevesAvecNotes, 'moyenne'))
        : 0;
    $moyDernier = count($elevesAvecNotes) > 0
        ? min(array_column($elevesAvecNotes, 'moyenne'))
        : 0;

    Log::info('Stats de classe', [
        'effectifAvecNotes' => count($elevesAvecNotes),
        'moyClasse' => $moyClasse,
        'moyPremier' => $moyPremier,
        'moyDernier' => $moyDernier
    ]);

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


public function generateRecapMoyennes(Request $request)
{
    $request->validate([
        'mois_id' => 'required|exists:mois_scolaires,id',
    ]);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $mois = MoisScolaire::findOrFail($request->mois_id);

    $classes = Classe::with([
        'enseignant',
        'niveau.matieres',
        'inscriptions.eleve'
    ])
    ->where('ecole_id', $ecoleId)
    ->where('annee_scolaire_id', $anneeScolaireId)
    ->get();

    $data = [];

    foreach ($classes as $classe) {
        $matieres = $classe->niveau->matieres;
        $inscriptions = $classe->inscriptions;
        $eleves = [];
        $matieresAvecNotes = collect();

        foreach ($inscriptions as $inscription) {
            $notes = Note::with('matiere')
                ->where('inscription_id', $inscription->id)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('mois_id', $mois->id)
                ->get();

            $totalNotes = 0;
            $totalCoeffs = 0;
            $notesParMatiere = [];

            foreach ($notes as $note) {
                $matierePivot = $matieres->firstWhere('id', $note->matiere_id)?->pivot;
                if (!$matierePivot) continue;

                $base = $matierePivot->denominateur ?? 20;
                $coeff = $matierePivot->coefficient ?? 1;

                $notesParMatiere[$note->matiere->nom] = [
                    'valeur' => $note->valeur,
                    'base' => $base,
                    'coefficient' => $coeff,
                ];

                if ($note->valeur > 0) {
                    $matieresAvecNotes->put($note->matiere->id, $note->matiere);
                    $totalNotes += ($note->valeur / $base) * $classe->moy_base * $coeff;
                    $totalCoeffs += $coeff;
                }
            }

            $moyenne = $totalCoeffs > 0 ? $totalNotes / $totalCoeffs : 0;

            $eleves[] = [
                'nom' => $inscription->eleve->nom,
                'prenom' => $inscription->eleve->prenom,
                'notes' => $notesParMatiere,
                'moyenne' => $moyenne ? number_format($moyenne, 2, ',', '') : '',
            ];
        }

        // Ignorer classe si aucune note
        if ($matieresAvecNotes->isEmpty()) continue;

        // Trier les élèves par Nom puis Prénom
        usort($eleves, function ($a, $b) {
            $cmpNom = strcmp(strtoupper($a['nom']), strtoupper($b['nom']));
            return $cmpNom === 0 ? strcmp(strtoupper($a['prenom']), strtoupper($b['prenom'])) : $cmpNom;
        });

        // Calcul des rangs généraux
        $moyennes = array_column($eleves, 'moyenne');
        $moyKeys = array_map(fn($m) => floatval(str_replace(',', '.', $m)), $moyennes);
        $sortedMoy = $moyKeys;
        rsort($sortedMoy); // décroissant

        foreach ($eleves as &$eleve) {
            $rang = array_search(floatval(str_replace(',', '.', $eleve['moyenne'])), $sortedMoy) + 1;
            $eleve['rang'] = $this->formatRang($rang);
        }
        unset($eleve);

        $matieresFiltrees = $matieres
            ->whereIn('id', $matieresAvecNotes->keys())
            ->sortBy(fn($matiere) => (int)($matiere->pivot->ordre ?? 0))
            ->values();

        $data[] = [
            'classe' => $classe,
            'enseignant' => $classe->enseignant?->name ?? '—',
            'eleves' => $eleves,
            'matieres' => $matieresFiltrees,
            'mois_nom' => $mois->nom,
        ];
    }

    if (empty($data)) {
        return back()->with('error', "Aucune note trouvée pour le mois de {$mois->nom}.");
    }

    $pdf = Pdf::loadView('dashboard.documents.recap_moyennes', compact('data'))
        ->setPaper('a4', 'landscape');

    return $pdf->stream('recap_moyennes_' . $mois->nom . '.pdf');
}




}