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
                    // 'annee_scolaire_id' => $anneeScolaireId,
                    // 'ecole_id' => $ecoleId,
                ]
            );
        }

        return redirect()->route('notes.index')->with('success', 'Notes enregistrées avec succès');
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
                    'eleve' => $note->inscription->eleve->prenom . ' ' . $note->inscription->eleve->nom,
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

    //     $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);
    //     $mois = MoisScolaire::findOrFail($request->mois_id);

    //     $inscriptions = Inscription::with(['eleve', 'notes' => function($q) use ($request) {
    //         $q->where('mois_id', $request->mois_id)->with('matiere');
    //     }])
    //     ->where('classe_id', $request->classe_id)
    //     ->where('statut', 'active')
    //     ->get();

    //     $mentions = Mention::where('ecole_id', auth()->user()->ecole_id)
    //         ->orderBy('min_note')
    //         ->get();

    //     // Calcul des moyennes et préparation des données
    //     $elevesAvecMoyennes = [];
    //     foreach ($inscriptions as $inscription) {
    //         $notes = $inscription->notes ?? collect();
    //         $totalNotes = 0;
    //         $totalCoeffs = 0;

    //         foreach ($notes as $note) {
    //             $totalNotes += ($note->valeur * ($note->coefficient ?? 1));
    //             $totalCoeffs += ($note->coefficient ?? 1);
    //             $note->execo = ($note->valeur == 20);
    //         }

    //         $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : 0;
    //         $moyenneArrondie = round($moyenne, 2);

    //         $mention = $mentions->first(function($m) use ($moyenneArrondie) {
    //             return $moyenneArrondie >= $m->min_note && $moyenneArrondie <= $m->max_note;
    //         });

    //         $elevesAvecMoyennes[] = [
    //             'inscription' => $inscription,
    //             'notes' => $notes,
    //             'moyenne' => $moyenneArrondie,
    //             'mention' => $mention ? $mention->nom : 'Non classé',
    //             'execo_count' => $notes->where('valeur', 20)->count(),
    //         ];
    //     }

    //     // ✅ CLASSEMENT PAR MATIÈRE
    //     $matieres = $classe->niveau->matieres;
    //     foreach ($matieres as $matiere) {
    //         // récupérer toutes les notes de la matière
    //         $notesMatiere = [];
    //         foreach ($elevesAvecMoyennes as &$eleve) {
    //             $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
    //             if ($note) {
    //                 $notesMatiere[] = $note;
    //             }
    //         }

    //         // trier par valeur décroissante
    //         usort($notesMatiere, function($a, $b) {
    //             return $b->valeur <=> $a->valeur;
    //         });

    //         // attribuer les rangs avec gestion des ex-aequo
    //         foreach ($notesMatiere as $index => $note) {
    //             if ($index === 0) {
    //                 $note->rang_matiere = 1;
    //             } else {
    //                 $prev = $notesMatiere[$index - 1];
    //                 if ($note->valeur == $prev->valeur) {
    //                     $note->rang_matiere = $prev->rang_matiere;
    //                 } else {
    //                     $note->rang_matiere = $index + 1;
    //                 }
    //             }
    //         }
    //     }

    //     // 🔽 Ici reste ton code inchangé : tri des élèves + rang général
    //     usort($elevesAvecMoyennes, function($a, $b) {
    //         if ($a['moyenne'] != $b['moyenne']) {
    //             return $b['moyenne'] <=> $a['moyenne'];
    //         }
    //         $notesA = collect($a['notes'])->pluck('valeur')->sortDesc()->values()->toArray();
    //         $notesB = collect($b['notes'])->pluck('valeur')->sortDesc()->values()->toArray();
    //         $len = min(count($notesA), count($notesB));
    //         for ($i = 0; $i < $len; $i++) {
    //             if ($notesA[$i] != $notesB[$i]) {
    //                 return $notesB[$i] <=> $notesA[$i];
    //             }
    //         }
    //         $sumA = array_sum($notesA);
    //         $sumB = array_sum($notesB);
    //         if ($sumA != $sumB) {
    //             return $sumB <=> $sumA;
    //         }
    //         $nameA = $a['inscription']->eleve->prenom . ' ' . $a['inscription']->eleve->nom;
    //         $nameB = $b['inscription']->eleve->prenom . ' ' . $b['inscription']->eleve->nom;
    //         return strcmp($nameA, $nameB);
    //     });

    //     $moyKeys = array_map(function($e) {
    //         return sprintf('%.2f', $e['moyenne']);
    //     }, $elevesAvecMoyennes);
    //     $moyCounts = array_count_values($moyKeys);

    //     foreach ($elevesAvecMoyennes as $index => &$eleve) {
    //         $key = sprintf('%.2f', $eleve['moyenne']);
    //         $eleve['exaequo'] = ($moyCounts[$key] > 1);

    //         if ($index === 0) {
    //             $eleve['rang_general'] = 1;
    //         } else {
    //             $prev = $elevesAvecMoyennes[$index - 1];
    //             if (sprintf('%.2f', $eleve['moyenne']) == sprintf('%.2f', $prev['moyenne'])) {
    //                 $eleve['rang_general'] = $prev['rang_general'];
    //             } else {
    //                 $eleve['rang_general'] = $index + 1;
    //             }
    //         }
    //     }
    //     unset($eleve);

    //     // PDF
    //     $pdf = Pdf::loadView('dashboard.documents.bulletin', [
    //         'classe' => $classe,
    //         'mois' => $mois,
    //         'elevesAvecMoyennes' => $elevesAvecMoyennes
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

        // Calcul des moyennes et préparation des données
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

            $mention = $mentions->first(function($m) use ($moyenneArrondie) {
                return $moyenneArrondie >= $m->min_note && $moyenneArrondie <= $m->max_note;
            });

            $elevesAvecMoyennes[] = [
                'inscription' => $inscription,
                'notes' => $notes,
                'moyenne' => $moyenneArrondie,
                'mention' => $mention ? $mention->nom : 'Non classé',
                'execo_count' => $notes->where('valeur', 20)->count(),
                'total_notes' => $totalNotes,
                'total_coeffs' => $totalCoeffs,
            ];
        }

        // Classement par matière
        $matieres = $classe->niveau->matieres;
        foreach ($matieres as $matiere) {
            $notesMatiere = [];
            foreach ($elevesAvecMoyennes as &$eleve) {
                $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
                if ($note) {
                    $notesMatiere[] = [
                        'note_obj' => $note,
                        'eleve_index' => array_search($eleve, $elevesAvecMoyennes)
                    ];
                }
            }

            // Trier par valeur décroissante
            usort($notesMatiere, function($a, $b) {
                return $b['note_obj']->valeur <=> $a['note_obj']->valeur;
            });

            // Attribuer les rangs
            foreach ($notesMatiere as $index => $data) {
                if ($index === 0) {
                    $data['note_obj']->rang_matiere = 1;
                } else {
                    $prev = $notesMatiere[$index - 1];
                    if ($data['note_obj']->valeur == $prev['note_obj']->valeur) {
                        $data['note_obj']->rang_matiere = $prev['note_obj']->rang_matiere;
                    } else {
                        $data['note_obj']->rang_matiere = $index + 1;
                    }
                }
            }
        }

        // Classement général
        usort($elevesAvecMoyennes, function($a, $b) {
            if ($a['moyenne'] != $b['moyenne']) {
                return $b['moyenne'] <=> $a['moyenne'];
            }
            return $a['inscription']->eleve->nom <=> $b['inscription']->eleve->nom;
        });

        $moyKeys = array_map(function($e) {
            return sprintf('%.2f', $e['moyenne']);
        }, $elevesAvecMoyennes);
        $moyCounts = array_count_values($moyKeys);

        foreach ($elevesAvecMoyennes as $index => &$eleve) {
            $key = sprintf('%.2f', $eleve['moyenne']);
            $eleve['exaequo'] = ($moyCounts[$key] > 1);

            if ($index === 0) {
                $eleve['rang_general'] = 1;
            } else {
                $prev = $elevesAvecMoyennes[$index - 1];
                if (sprintf('%.2f', $eleve['moyenne']) == sprintf('%.2f', $prev['moyenne'])) {
                    $eleve['rang_general'] = $prev['rang_general'];
                } else {
                    $eleve['rang_general'] = $index + 1;
                }
            }
        }
        unset($eleve);

        // Calcul des statistiques de classe
        $moyennes = array_column($elevesAvecMoyennes, 'moyenne');
        $moyClasse = count($moyennes) > 0 ? array_sum($moyennes) / count($moyennes) : 0;
        $moyPremier = count($moyennes) > 0 ? max($moyennes) : 0;
        $moyDernier = count($moyennes) > 0 ? min($moyennes) : 0;

        $pdf = Pdf::loadView('dashboard.documents.bulletin', [
            'classe' => $classe,
            'mois' => $mois,
            'elevesAvecMoyennes' => $elevesAvecMoyennes,
            'moyClasse' => round($moyClasse, 2),
            'moyPremier' => round($moyPremier, 2),
            'moyDernier' => round($moyDernier, 2),
            'effectif' => count($elevesAvecMoyennes),
            'anneeScolaire' => $anneeScolaire,
        ]);

        return $pdf->stream('bulletins-' . $classe->nom . '-' . $mois->nom . '.pdf');
    }

}