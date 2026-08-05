<?php

namespace App\Http\Controllers;

use DB;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\Mention;
use App\Models\MoisScolaire;
use App\Models\Note;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMois;
use App\Services\TableService;
use App\Rules\ExistsInDynamicTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NoteController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Directeur|Enseignant']);
        $this->tableService = $tableService;
    }

    // ==================== MÉTHODES DE BASE ====================

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $query = Note::with(['eleve', 'matiere', 'classe', 'mois'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        
        if ($request->has('classe_id') && $request->classe_id != '') {
            $query->where('classe_id', $request->classe_id);
        }
        
        if ($request->has('matiere_id') && $request->matiere_id != '') {
            $query->where('matiere_id', $request->matiere_id);
        }
        
        if ($request->has('mois_id') && $request->mois_id != '') {
            $query->where('mois_id', $request->mois_id);
        }
        
        if ($request->has('nom') && $request->nom != '') {
            $query->whereHas('eleve', function($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('prenom', 'like', '%' . $request->nom . '%');
            });
        }
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sort = $request->get('sort', 'desc');
        $query->orderBy($sortBy, $sort);
        
        $notes = $query->paginate(20);
        
        $eleves = Eleve::orderBy('nom')->get();
        $matieres = Matiere::orderBy('nom')->get();
        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)->ordered()->get();
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.index', compact('notes', 'eleves', 'matieres', 'classes', 'moisScolaire'));
    }

    public function filterByClasse(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'nullable',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
        ]);

        $notes = Note::with(['eleve', 'matiere', 'classe', 'mois'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->when($request->classe_id, function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            })
            ->get()
            ->map(function($note) {
                return [
                    'id' => $note->id,
                    'eleve' => $note->eleve ? $note->eleve->nom . ' ' . $note->eleve->prenom : 'Inconnu',
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
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $matieresTable = $this->tableService->getMatieresTableName($ecoleId, $annee);
        $elevesTable = $this->tableService->getElevesTableName($ecoleId, $annee);
        
        $validated = $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'matiere_id' => [
                'required',
                new ExistsInDynamicTable($matieresTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'mois_id' => 'required|exists:mois_scolaires,id',
            'coefficient' => 'required|numeric',
            'notes' => 'array',
            'notes.*.eleve_id' => [
                'required',
                new ExistsInDynamicTable($elevesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'notes.*.valeur' => 'nullable|numeric',
        ]);

        $classe = Classe::with('niveau.matieres')->findOrFail($validated['classe_id']);
        $matierePivot = $classe->niveau->matieres->firstWhere('id', $validated['matiere_id'])->pivot ?? null;
        $base = $matierePivot->denominateur ?? 20;

        $savedCount = 0;
        $updatedCount = 0;

        foreach ($validated['notes'] as $noteData) {
            $valeur = $noteData['valeur'];

            if ($valeur === null || $valeur === '') {
                continue;
            }

            $eleve = Eleve::findOrFail($noteData['eleve_id']);

            if ($valeur > $base) {
                return back()
                    ->withInput()
                    ->with('error', "La note de {$eleve->nom} dépasse la base autorisée ({$base}).");
            }

            $existingNote = Note::where('eleve_id', $noteData['eleve_id'])
                ->where('matiere_id', $validated['matiere_id'])
                ->where('mois_id', $validated['mois_id'])
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->first();

            if ($existingNote) {
                $existingNote->update([
                    'valeur' => $valeur,
                    'coefficient' => $validated['coefficient'],
                    'user_id' => Auth::id(),
                    'appreciation' => $this->generateAppreciation($valeur, $base),
                ]);
                $updatedCount++;
            } else {
                Note::create([
                    'eleve_id' => $noteData['eleve_id'],
                    'matiere_id' => $validated['matiere_id'],
                    'mois_id' => $validated['mois_id'],
                    'annee_scolaire_id' => $anneeScolaireId,
                    'ecole_id' => $ecoleId,
                    'classe_id' => $validated['classe_id'],
                    'valeur' => $valeur,
                    'coefficient' => $validated['coefficient'],
                    'user_id' => Auth::id(),
                    'appreciation' => $this->generateAppreciation($valeur, $base),
                ]);
                $savedCount++;
            }
        }

        $message = [];
        if ($savedCount > 0) $message[] = $savedCount . ' nouvelle(s) note(s) enregistrée(s)';
        if ($updatedCount > 0) $message[] = $updatedCount . ' note(s) mise(s) à jour';

        return redirect()->route('notes.create')
            ->with('success', implode(' et ', $message) . ' avec succès');
    }

    public function edit(Note $note)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $eleves = Eleve::orderBy('nom')->get();
        $matieres = Matiere::orderBy('nom')->get();
        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)->ordered()->get();
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.edit', compact(
            'note', 'eleves', 'matieres', 'classes', 'moisScolaire'
        ));
    }

    public function update(Request $request, Note $note)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $matieresTable = $this->tableService->getMatieresTableName($ecoleId, $annee);
        
        $request->validate([
            'valeur' => 'required|numeric',
            'coefficient' => 'required|numeric',
            'matiere_id' => [
                'required',
                new ExistsInDynamicTable($matieresTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'mois_id' => 'required|exists:mois_scolaires,id',
        ]);

        $note->update([
            'valeur' => $request->valeur,
            'coefficient' => $request->coefficient,
            'matiere_id' => $request->matiere_id,
            'mois_id' => $request->mois_id,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('notes.index')
            ->with('success', 'Note mise à jour avec succès');
    }

    public function destroy(Note $note)
    {
        $note->delete();
        return redirect()->route('notes.index')
            ->with('success', 'Note supprimée avec succès');
    }

    // ==================== MÉTHODES AJAX ====================

    public function getElevesByClasse(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ]
        ]);

        try {
            $eleves = Eleve::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $request->classe_id)
                ->where('is_active', 1)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get()
                ->map(function($eleve) {
                    return [
                        'id' => $eleve->id,
                        'nom_complet' => $eleve->nom . ' ' . $eleve->prenom,
                    ];
                });

            return response()->json($eleves);

        } catch (\Exception $e) {
            Log::error('Erreur getElevesByClasse: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors du chargement des élèves: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMatieresByClasse(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ]
        ]);

        try {
            $classe = Classe::with('niveau')->findOrFail($request->classe_id);
            
            if (!$classe->niveau) {
                return response()->json([
                    'error' => 'Cette classe n\'a pas de niveau associé'
                ], 404);
            }

            $matieres = $classe->niveau->matieres()
                ->orderBy('nom')
                ->get()
                ->map(function($matiere) use ($classe) {
                    $pivot = $matiere->pivot;
                    return [
                        'id' => $matiere->id,
                        'nom' => $matiere->nom,
                        'coefficient' => $pivot->coefficient ?? 1,
                        'base' => $pivot->denominateur ?? 20,
                        'ordre' => $pivot->ordre ?? 0,
                    ];
                });

            return response()->json($matieres);

        } catch (\Exception $e) {
            Log::error('Erreur getMatieresByClasse: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Erreur lors du chargement des matières: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getNotesByClasse(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        $matieresTable = $this->tableService->getMatieresTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'matiere_id' => [
                'required',
                new ExistsInDynamicTable($matieresTable, 'id', $ecoleId, $anneeScolaireId) // Maintenant avec annee_scolaire_id
            ],
            'mois_id' => 'required|exists:mois_scolaires,id',
        ]);

        try {
            $notes = Note::with(['eleve'])
                ->where('classe_id', $request->classe_id)
                ->where('matiere_id', $request->matiere_id)
                ->where('mois_id', $request->mois_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->get()
                ->map(function($note){
                    return [
                        'id' => $note->id,
                        'eleve_id' => $note->eleve_id,
                        'eleve' => $note->eleve ? $note->eleve->nom . ' ' . $note->eleve->prenom : 'Élève inconnu',
                        'valeur' => $note->valeur,
                    ];
                });

            return response()->json($notes);

        } catch (\Exception $e) {
            Log::error('Erreur getNotesByClasse: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors du chargement des notes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkExistingMoisMoyenne(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'mois_id' => 'required|exists:mois_scolaires,id'
        ]);
        
        $exists = MoyenneMois::where('classe_id', $request->classe_id)
            ->where('mois_id', $request->mois_id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->exists();
        
        return response()->json([
            'exists' => $exists,
            'can_modify' => !$exists
        ]);
    }

    // ==================== MÉTHODES DE CALCUL ====================

    private function appliquerArrondi($valeur)
    {
        if ($valeur === null) {
            return null;
        }

        $ecoleId = session('current_ecole_id');
        $ecole = Ecole::find($ecoleId);
        
        if ($ecole && method_exists($ecole, 'appliquerArrondi')) {
            return $ecole->appliquerArrondi($valeur);
        }
        
        return round($valeur, 2);
    }

    private function getMention($moyenne, $moyBase)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        if ($moyenne === null || $moyenne === 0) {
            return 'Non classé';
        }

        $moyenneSur20 = $moyBase > 0 ? ($moyenne / $moyBase) * 20 : $moyenne;
        $moyenneArrondie = round($moyenneSur20);

        $mentions = Mention::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('min_note')
            ->get();

        if ($mentions->isEmpty()) {
            return 'Non classé';
        }

        foreach ($mentions as $mention) {
            if ($moyenneArrondie >= $mention->min_note && $moyenneArrondie <= $mention->max_note) {
                return $mention->nom;
            }
        }

        return 'Non classé';
    }

    private function generateAppreciation($valeur, $base)
    {
        if ($valeur === null || $base === null || $base == 0) {
            return 'Non évalué';
        }
        
        $noteSur20 = ($base > 0) ? ($valeur / $base) * 20 : $valeur;

        if ($noteSur20 < 8) return 'Très insuffisant';
        if ($noteSur20 < 10) return 'Insuffisant';
        if ($noteSur20 < 12) return 'Passable';
        if ($noteSur20 < 14) return 'Assez Bien';
        if ($noteSur20 < 16) return 'Bien';
        if ($noteSur20 < 18) return 'Très Bien';
        return 'Excellent';
    }

    private function calculerDistinctions($moyenne, $moyBase)
    {
        $distinctions = [
            'tableau_honneur' => false,
            'encouragement'   => false,
            'felicitation'    => false,
        ];

        if ($moyenne === null) {
            return $distinctions;
        }

        $pourcentage = ($moyBase > 0) ? ($moyenne / $moyBase) * 100 : 0;

        if ($pourcentage >= 80) {
            $distinctions['felicitation'] = true;
        } elseif ($pourcentage >= 70) {
            $distinctions['encouragement'] = true;
        } elseif ($pourcentage >= 60) {
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

        if ($moyenne === null) {
            return $sanctions;
        }

        $pourcentage = ($moyBase > 0) ? ($moyenne / $moyBase) * 100 : 0;

        if ($pourcentage < 40) {
            $sanctions['blame_travail'] = true;
        } elseif ($pourcentage < 50) {
            $sanctions['avertissement_travail'] = true;
        }

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

    private function determinerDecision($moyenne, $moyBase, $sexe)
    {
        $pourcentage = ($moyBase > 0) ? ($moyenne / $moyBase) * 100 : 0;
        
        if ($pourcentage >= 50) {
            return ($sexe == 'Féminin' || $sexe == 'F' || $sexe == 'femme' || $sexe == 'female') 
                ? 'ADMISE' : 'ADMIS';
        } else {
            return ($sexe == 'Féminin' || $sexe == 'F' || $sexe == 'femme' || $sexe == 'female') 
                ? 'NON ADMISE' : 'NON ADMIS';
        }
    }

    // ==================== GÉNÉRATION DU BULLETIN MENSUEL ====================

    public function generateBulletin(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'mois_id' => 'required|exists:mois_scolaires,id'
        ]);

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        
        $saveMois = $request->has('save_mois') && $request->save_mois == '1';

        $classe = Classe::with(['niveau.matieres' => function ($q) {
            $q->orderByPivot('ordre');
        }])->findOrFail($request->classe_id);

        $mois = MoisScolaire::findOrFail($request->mois_id);

        if ($saveMois) {
            $existingCount = MoyenneMois::where('classe_id', $classe->id)
                ->where('mois_id', $mois->id)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->count();
            
            if ($existingCount > 0) {
                return redirect()->back()->with('error', 'Un bulletin mensuel a déjà été généré pour cette classe et ce mois.');
            }
        }

        $eleves = Eleve::where('classe_id', $request->classe_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('is_active', 1)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $matieres = $classe->niveau->matieres
            ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
            ->values();

        $mentions = Mention::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('min_note')
            ->get();

        if ($mentions->isEmpty()) {
            $mentions = Mention::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', 1)
                ->orderBy('min_note')
                ->get();
        }

        $elevesAvecMoyennes = [];
        $moyBase = $classe->moy_base;
        $isMai = ($mois->id == 10);

        foreach ($eleves as $eleve) {
            $notes = Note::where('eleve_id', $eleve->id)
                ->where('mois_id', $mois->id)
                ->where('classe_id', $classe->id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->get();

            $totalNotes = 0;
            $totalCoeffs = 0;
            $notesData = [];

            foreach ($notes as $note) {
                $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
                $base = $matierePivot->denominateur ?? 20;
                $coeff = $matierePivot->coefficient ?? 1;

                $note->base = $base;
                $note->coefficient = $coeff;

                if ($note->valeur !== null && $coeff > 0) {
                    $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                    $totalCoeffs += $coeff;
                }

                $note->execo = ($note->valeur == $base);
                $notesData[] = $note;
            }

            $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;
            
            $moyenneArrondie = $this->appliquerArrondi($moyenne);

            $mentionNom = $moyenneArrondie !== null
                ? $this->getMention($moyenneArrondie, $moyBase)
                : 'Non classé';

            $distinctions = $moyenneArrondie !== null ? $this->calculerDistinctions($moyenneArrondie, $moyBase) : [];
            $sanctions = $moyenneArrondie !== null ? $this->calculerSanctions($moyenneArrondie, $moyBase) : [];

            $execoCount = collect($notesData)->filter(fn($n) => isset($n->execo) && $n->execo)->count();

            $elevesAvecMoyennes[] = [
                'eleve' => $eleve,
                'notes' => collect($notesData),
                'moyenne' => $moyenneArrondie ?? 0,
                'mention' => $mentionNom,
                'execo_count' => $execoCount,
                'total_notes' => $totalNotes,
                'total_coeffs' => $totalCoeffs,
                'distinctions' => $distinctions,
                'sanctions' => $sanctions,
            ];
        }

        // Classement par matière
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
            return $b['moyenne'] <=> $a['moyenne'] ?: strcmp($a['eleve']->nom, $b['eleve']->nom);
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

        // Moyennes de classe avec arrondi
        $elevesAvecNotes = array_filter($elevesAvecMoyennes, fn($e) => $e['total_coeffs'] > 0);
        
        $moyClasse = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(array_sum(array_column($elevesAvecNotes, 'moyenne')) / count($elevesAvecNotes))
            : 0;
        $moyPremier = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(max(array_column($elevesAvecNotes, 'moyenne')))
            : 0;
        $moyDernier = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(min(array_column($elevesAvecNotes, 'moyenne')))
            : 0;

        // Sauvegarde dans moyenne_mois
        if ($saveMois) {
            $statsClasse = [
                'moyenne_classe' => $moyClasse,
                'moyenne_min' => $moyDernier,
                'moyenne_max' => $moyPremier,
                'effectif' => count($elevesAvecMoyennes)
            ];
            
            foreach ($elevesAvecMoyennes as $eleveData) {
                $this->saveMoyenneMois($eleveData, $classe, $mois, $statsClasse);
            }
            
            session()->flash('success', 'Les moyennes du mois ' . $mois->nom . ' ont été enregistrées avec succès.');
        }

        // Génération du PDF
        $pdf = Pdf::loadView('dashboard.documents.bulletin', [
            'classe' => $classe,
            'mois' => $mois,
            'elevesAvecMoyennes' => $elevesAvecMoyennes,
            'matieres' => $matieres,
            'moyClasse' => $moyClasse,
            'moyPremier' => $moyPremier,
            'moyDernier' => $moyDernier,
            'effectif' => count($elevesAvecMoyennes),
            'anneeScolaire' => $anneeScolaire,
        ]);

        return $pdf->stream('bulletins-' . $classe->nom . '-' . $mois->nom . '.pdf');
    }

    private function saveMoyenneMois($eleveData, $classe, $mois, $statsClasse)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $moyBase = $classe->moy_base;
        $mention = $this->getMention($eleveData['moyenne'], $moyBase);
        
        $detailsNotes = [];
        foreach ($eleveData['notes'] as $note) {
            $detailsNotes[] = [
                'matiere_id' => $note->matiere_id,
                'matiere_nom' => $note->matiere->nom,
                'valeur' => $note->valeur,
                'base' => $note->base,
                'coefficient' => $note->coefficient,
                'appreciation' => $note->appreciation ?? $this->generateAppreciation($note->valeur, $note->base),
                'rang' => $note->rang_matiere ?? null
            ];
        }
        
        MoyenneMois::create([
            'eleve_id' => $eleveData['eleve']->id,
            'classe_id' => $classe->id,
            'mois_id' => $mois->id,
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'moyenne' => $this->appliquerArrondi($eleveData['moyenne']),
            'rang' => $eleveData['rang_general'],
            'exaequo' => $eleveData['exaequo'] ?? false,
            'appreciation' => $mention,
            'details_notes' => $detailsNotes,
            'moyenne_classe' => $statsClasse['moyenne_classe'],
            'moyenne_min' => $statsClasse['moyenne_min'],
            'moyenne_max' => $statsClasse['moyenne_max'],
            'effectif_classe' => $statsClasse['effectif'],
            'user_id' => Auth::id(),
            'date_generation' => now(),
        ]);
        
        return true;
    }

    // ==================== GÉNÉRATION DU BULLETIN ANNUEL ====================

    public function generateBulletinAnnuel(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');
        
        $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
        
        $request->validate([
            'classe_id' => [
                'required',
                new ExistsInDynamicTable($classesTable, 'id', $ecoleId, $anneeScolaireId)
            ],
            'mois_data' => 'required|json',
        ]);

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $classe = Classe::with(['niveau.matieres' => function ($q) {
            $q->orderByPivot('ordre');
        }])->findOrFail($request->classe_id);
        
        $moisData = json_decode($request->mois_data, true);
        
        if (empty($moisData)) {
            return redirect()->back()->with('error', 'Veuillez sélectionner au moins un mois.');
        }
        
        $selectedMoisIds = array_column($moisData, 'id');
        $moisCoefficients = [];
        foreach ($moisData as $data) {
            $moisCoefficients[$data['id']] = $data['coefficient'] ?? 1;
        }
        
        $saveAndClose = $request->has('save_and_close') && $request->save_and_close == '1';
        $appreciationsIndividuelles = $saveAndClose ? ($request->appreciations ?? []) : [];

        $matieres = $classe->niveau->matieres
            ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
            ->values();

        $moisScolaires = MoisScolaire::whereIn('id', $selectedMoisIds)->orderBy('id')->get();

        $moyBase = $classe->moy_base;
        $effectifTotal = Eleve::where('classe_id', $request->classe_id)
            ->where('is_active', 1)
            ->count();

        $eleves = Eleve::where('classe_id', $request->classe_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('is_active', 1)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        // Initialisation des structures de données
        $moyennesParMoisGlobale = [];
        $moyennesParMoisDetails = [];
        $rangsParMois = [];
        $rangsParMoisDetails = [];
        $moyennesParMatiereDetails = [];
        $rangsParMatiereDetails = [];

        // Calcul des moyennes par mois
        foreach ($moisScolaires as $mois) {
            $coeffMois = $moisCoefficients[$mois->id] ?? 1;
            
            foreach ($eleves as $eleve) {
                $notes = Note::where('eleve_id', $eleve->id)
                    ->where('mois_id', $mois->id)
                    ->where('classe_id', $classe->id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->get();

                $totalNotes = 0;
                $totalCoeffs = 0;

                foreach ($notes as $note) {
                    $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
                    $base = $matierePivot->denominateur ?? 20;
                    $coeff = $matierePivot->coefficient ?? 1;

                    if ($note->valeur !== null && $coeff > 0) {
                        $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                        $totalCoeffs += $coeff;
                    }
                }

                $moyenneMois = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;

                if ($moyenneMois !== null) {
                    $moyennesParMoisGlobale[$mois->id][$eleve->id] = [
                        'moyenne' => $moyenneMois,
                        'coefficient' => $coeffMois
                    ];
                    
                    $moyennesParMoisDetails[$eleve->id][$mois->id] = [
                        'mois_id' => $mois->id,
                        'mois_nom' => $mois->nom,
                        'mois_ordre' => $mois->ordre ?? $mois->id,
                        'moyenne' => $moyenneMois,
                        'coefficient' => $coeffMois,
                        'a_des_notes' => $notes->count() > 0
                    ];
                } else {
                    $moyennesParMoisDetails[$eleve->id][$mois->id] = [
                        'mois_id' => $mois->id,
                        'mois_nom' => $mois->nom,
                        'mois_ordre' => $mois->ordre ?? $mois->id,
                        'moyenne' => null,
                        'coefficient' => $coeffMois,
                        'a_des_notes' => false
                    ];
                }
            }
        }

        // Calcul des rangs par mois
        foreach ($moisScolaires as $mois) {
            if (isset($moyennesParMoisGlobale[$mois->id])) {
                $moyennes = [];
                foreach ($moyennesParMoisGlobale[$mois->id] as $eleveId => $data) {
                    $moyennes[$eleveId] = $data['moyenne'];
                }
                arsort($moyennes);
                $rang = 1;
                $prevMoyenne = null;
                foreach ($moyennes as $eleveId => $moyenne) {
                    if ($prevMoyenne !== null && $moyenne < $prevMoyenne) {
                        $rang++;
                    }
                    $rangsParMois[$mois->id][$eleveId] = $rang;
                    $rangsParMoisDetails[$eleveId][$mois->id] = [
                        'rang' => $rang,
                        'effectif_total' => count($moyennes),
                        'moyenne' => $moyenne
                    ];
                    $prevMoyenne = $moyenne;
                }
            }
        }

        // Calcul des moyennes par matière
        foreach ($matieres as $matiere) {
            foreach ($eleves as $eleve) {
                $notesMatiere = Note::where('eleve_id', $eleve->id)
                    ->where('matiere_id', $matiere->id)
                    ->where('classe_id', $classe->id)
                    ->where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->whereIn('mois_id', $selectedMoisIds)
                    ->get();

                if ($notesMatiere->count() > 0) {
                    $moyenneMatiere = $notesMatiere->avg('valeur');
                    $base = $matiere->pivot->denominateur ?? 20;
                    $coeff = $matiere->pivot->coefficient ?? 1;
                    $moyenneConvertie = ($moyenneMatiere / $base) * $moyBase;
                    
                    $moyennesParMatiereDetails[$eleve->id][$matiere->id] = [
                        'matiere_id' => $matiere->id,
                        'matiere_nom' => $matiere->nom,
                        'matiere_ordre' => $matiere->pivot->ordre ?? 0,
                        'moyenne_brute' => $moyenneMatiere,
                        'moyenne' => $moyenneConvertie,
                        'base' => $base,
                        'coefficient' => $coeff,
                        'appreciation' => $this->generateAppreciation($moyenneMatiere, $base),
                        'nb_notes' => $notesMatiere->count()
                    ];
                } else {
                    $moyennesParMatiereDetails[$eleve->id][$matiere->id] = [
                        'matiere_id' => $matiere->id,
                        'matiere_nom' => $matiere->nom,
                        'matiere_ordre' => $matiere->pivot->ordre ?? 0,
                        'moyenne_brute' => null,
                        'moyenne' => null,
                        'base' => $matiere->pivot->denominateur ?? 20,
                        'coefficient' => $matiere->pivot->coefficient ?? 1,
                        'appreciation' => null,
                        'nb_notes' => 0
                    ];
                }
            }

            // Calcul des rangs par matière
            $moyennesMatiereTrie = [];
            foreach ($eleves as $eleve) {
                $data = $moyennesParMatiereDetails[$eleve->id][$matiere->id] ?? null;
                if ($data && $data['moyenne'] !== null) {
                    $moyennesMatiereTrie[] = [
                        'eleve_id' => $eleve->id,
                        'moyenne' => $data['moyenne']
                    ];
                }
            }
            
            usort($moyennesMatiereTrie, function($a, $b) {
                return $b['moyenne'] <=> $a['moyenne'];
            });
            
            $rang = 1;
            $prevMoyenne = null;
            foreach ($moyennesMatiereTrie as $idx => $item) {
                if ($prevMoyenne !== null && $item['moyenne'] < $prevMoyenne) {
                    $rang = $idx + 1;
                }
                $rangsParMatiereDetails[$item['eleve_id']][$matiere->id] = [
                    'rang' => $rang,
                    'exaequo' => ($prevMoyenne !== null && $item['moyenne'] == $prevMoyenne),
                    'effectif_total' => count($moyennesMatiereTrie)
                ];
                $prevMoyenne = $item['moyenne'];
            }
        }

        // Calcul des moyennes générales annuelles
        $elevesAvecMoyennes = [];

        foreach ($eleves as $eleve) {
            $totalNotesMois = 0;
            $totalCoeffsTotal = 0;
            
            foreach ($moisScolaires as $mois) {
                $coeffMois = $moisCoefficients[$mois->id] ?? 1;
                $totalCoeffsTotal += $coeffMois;
                
                if (isset($moyennesParMoisGlobale[$mois->id][$eleve->id])) {
                    $dataMois = $moyennesParMoisGlobale[$mois->id][$eleve->id];
                    $totalNotesMois += $dataMois['moyenne'] * $coeffMois;
                }
            }
            
            $moyenneGenerale = $totalCoeffsTotal > 0 ? ($totalNotesMois / $totalCoeffsTotal) : null;
            $moyenneGeneraleArrondie = $this->appliquerArrondi($moyenneGenerale);

            // Assiduité
            $moisAvecNotes = 0;
            foreach ($moisScolaires as $mois) {
                if (isset($moyennesParMoisGlobale[$mois->id][$eleve->id])) {
                    $moisAvecNotes++;
                }
            }
            $assiduite = count($selectedMoisIds) > 0 ? ($moisAvecNotes / count($selectedMoisIds)) * 100 : 0;

            $matieresAvecMoyenne = [];
            foreach ($matieres as $matiere) {
                $dataMatiere = $moyennesParMatiereDetails[$eleve->id][$matiere->id] ?? null;
                $rangMatiere = $rangsParMatiereDetails[$eleve->id][$matiere->id] ?? null;
                
                if ($dataMatiere) {
                    $matieresAvecMoyenne[] = (object) [
                        'matiere_id' => $matiere->id,
                        'matiere' => $matiere,
                        'valeur' => $dataMatiere['moyenne_brute'],
                        'valeur_convertie' => $dataMatiere['moyenne'],
                        'coefficient' => $dataMatiere['coefficient'],
                        'base' => $dataMatiere['base'],
                        'appreciation' => $dataMatiere['appreciation'],
                        'rang_matiere' => $rangMatiere['rang'] ?? null,
                        'rang_matiere_text' => $this->formatRang($rangMatiere['rang'] ?? null),
                        'nb_notes' => $dataMatiere['nb_notes']
                    ];
                }
            }

            $mentionNom = $moyenneGeneraleArrondie !== null 
                ? $this->getMention($moyenneGeneraleArrondie, $moyBase) 
                : 'Non classé';

            $moyennesParMoisAffichage = [];
            foreach ($moisScolaires as $mois) {
                if (isset($moyennesParMoisGlobale[$mois->id][$eleve->id])) {
                    $dataMois = $moyennesParMoisGlobale[$mois->id][$eleve->id];
                    $moyenneMois = $dataMois['moyenne'];
                    $rangMois = $rangsParMois[$mois->id][$eleve->id] ?? null;
                    $coeffMois = $moisCoefficients[$mois->id] ?? 1;
                    
                    $moyennesParMoisAffichage[] = [
                        'mois' => $mois->nom,
                        'moyenne' => $this->appliquerArrondi($moyenneMois),
                        'coefficient' => $coeffMois,
                        'rang' => $rangMois,
                        'effectif' => $effectifTotal
                    ];
                }
            }

            $elevesAvecMoyennes[] = [
                'eleve' => $eleve,
                'notes' => collect($matieresAvecMoyenne),
                'moyenne' => $moyenneGeneraleArrondie ?? 0,
                'mention' => $mentionNom,
                'assiduite' => round($assiduite, 2),
                'mois_avec_notes' => $moisAvecNotes,
                'total_mois' => count($selectedMoisIds),
                'distinctions' => $moyenneGeneraleArrondie !== null ? $this->calculerDistinctions($moyenneGeneraleArrondie, $moyBase) : [],
                'sanctions' => $moyenneGeneraleArrondie !== null ? $this->calculerSanctions($moyenneGeneraleArrondie, $moyBase) : [],
                'moyennes_par_mois' => $moyennesParMoisAffichage,
                'moyennes_par_mois_raw' => $moyennesParMoisDetails[$eleve->id] ?? [],
                'rangs_par_mois_raw' => $rangsParMoisDetails[$eleve->id] ?? [],
                'moyennes_par_matiere_raw' => $moyennesParMatiereDetails[$eleve->id] ?? [],
                'rangs_par_matiere_raw' => $rangsParMatiereDetails[$eleve->id] ?? [],
                'appreciation_individuelle' => $appreciationsIndividuelles[$eleve->id] ?? null,
                'mois_coefficients' => $moisCoefficients
            ];
        }

        // Classement général
        usort($elevesAvecMoyennes, function ($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });
        
        foreach ($elevesAvecMoyennes as $index => &$eleve) {
            if ($index === 0) {
                $eleve['rang_general'] = 1;
                $eleve['exaequo'] = false;
            } else {
                $prev = $elevesAvecMoyennes[$index - 1];
                if ($eleve['moyenne'] == $prev['moyenne']) {
                    $eleve['rang_general'] = $prev['rang_general'];
                    $eleve['exaequo'] = true;
                    $prev['exaequo'] = true;
                } else {
                    $eleve['rang_general'] = $index + 1;
                    $eleve['exaequo'] = false;
                }
            }
            $eleve['rang_text'] = $this->formatRang($eleve['rang_general'], $eleve['exaequo']);
        }
        unset($eleve);

        // Statistiques de classe avec arrondi
        $elevesAvecNotes = array_filter($elevesAvecMoyennes, fn($e) => $e['moyenne'] > 0);
        $moyClasse = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(array_sum(array_column($elevesAvecNotes, 'moyenne')) / count($elevesAvecNotes))
            : 0;
        $moyPremier = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(max(array_column($elevesAvecNotes, 'moyenne')))
            : 0;
        $moyDernier = count($elevesAvecNotes) > 0
            ? $this->appliquerArrondi(min(array_column($elevesAvecNotes, 'moyenne')))
            : 0;

        // Sauvegarde dans moyenne_generale
        if ($saveAndClose) {
            $existingRecordsCount = MoyenneGenerale::where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->count();
            
            if ($existingRecordsCount > 0) {
                return redirect()->back()->with('error', 'Un bulletin annuel a déjà été généré pour cette classe.');
            }
            
            foreach ($elevesAvecMoyennes as $eleveData) {
                $this->saveMoyenneGenerale($eleveData, $classe, $anneeScolaireId, $ecoleId, $selectedMoisIds, $moyBase, $moisCoefficients);
            }
            
            session()->flash('success', 'Bulletin annuel généré et enregistré avec succès.');
        }

        // Génération du PDF
        $pdf = Pdf::loadView('dashboard.documents.bulletin-annuel', [
            'classe' => $classe,
            'elevesAvecMoyennes' => $elevesAvecMoyennes,
            'matieres' => $matieres,
            'moyClasse' => $moyClasse,
            'moyPremier' => $moyPremier,
            'moyDernier' => $moyDernier,
            'effectif' => count($elevesAvecMoyennes),
            'anneeScolaire' => $anneeScolaire,
            'moisScolaires' => $moisScolaires,
            'moisCoefficients' => $moisCoefficients,
            'saveAndClose' => $saveAndClose,
            'appreciationsIndividuelles' => $appreciationsIndividuelles
        ]);

        return $pdf->stream("bulletin-annuel-{$classe->nom}.pdf");
    }

    private function saveMoyenneGenerale($eleveData, $classe, $anneeScolaireId, $ecoleId, $selectedMoisIds, $moyBase, $moisCoefficients = [])
    {
        $moyennesParMoisFormatted = [];
        foreach ($eleveData['moyennes_par_mois_raw'] as $moisId => $data) {
            $moyennesParMoisFormatted[$moisId] = [
                'mois_nom' => $data['mois_nom'],
                'mois_ordre' => $data['mois_ordre'],
                'moyenne' => $this->appliquerArrondi($data['moyenne']),
                'coefficient' => $data['coefficient'],
                'a_des_notes' => $data['a_des_notes']
            ];
        }
        
        $rangsParMoisFormatted = [];
        if (isset($eleveData['rangs_par_mois_raw'])) {
            foreach ($eleveData['rangs_par_mois_raw'] as $moisId => $data) {
                $rangsParMoisFormatted[$moisId] = [
                    'rang' => $data['rang'],
                    'effectif_total' => $data['effectif_total'],
                    'moyenne' => $this->appliquerArrondi($data['moyenne']),
                ];
            }
        }
        
        $moyennesParMatiereFormatted = [];
        if (isset($eleveData['moyennes_par_matiere_raw'])) {
            foreach ($eleveData['moyennes_par_matiere_raw'] as $matiereId => $data) {
                if ($data && isset($data['moyenne']) && $data['moyenne'] !== null) {
                    $moyennesParMatiereFormatted[] = [
                        'matiere_id' => $matiereId,
                        'matiere_nom' => $data['matiere_nom'],
                        'matiere_ordre' => $data['matiere_ordre'],
                        'moyenne_brute' => $this->appliquerArrondi($data['moyenne_brute']),
                        'moyenne_convertie' => $this->appliquerArrondi($data['moyenne']),
                        'base' => $data['base'],
                        'coefficient' => $data['coefficient'],
                        'appreciation' => $data['appreciation'],
                        'nb_notes' => $data['nb_notes']
                    ];
                }
            }
        }
        
        $rangsParMatiereFormatted = [];
        if (isset($eleveData['rangs_par_matiere_raw'])) {
            foreach ($eleveData['rangs_par_matiere_raw'] as $matiereId => $data) {
                $rangsParMatiereFormatted[$matiereId] = [
                    'rang' => $data['rang'],
                    'exaequo' => $data['exaequo'],
                    'effectif_total' => $data['effectif_total']
                ];
            }
        }
        
        $moyenneAnnuelleCoupee = $this->appliquerArrondi($eleveData['moyenne']);
        
        $sexe = $eleveData['eleve']->sexe ?? '';
        $decision = $this->determinerDecision($moyenneAnnuelleCoupee, $moyBase, $sexe);
        
        MoyenneGenerale::create([
            'eleve_id' => $eleveData['eleve']->id,
            'classe_id' => $classe->id,
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'moyennes_par_mois' => $moyennesParMoisFormatted,
            'rangs_par_mois' => $rangsParMoisFormatted,
            'moyennes_par_matiere' => $moyennesParMatiereFormatted,
            'rangs_par_matiere' => $rangsParMatiereFormatted,
            'moyenne_annuelle' => $moyenneAnnuelleCoupee,
            'rang_general' => $eleveData['rang_general'],
            'exaequo' => $eleveData['exaequo'] ?? false,
            'appreciation_generale' => $eleveData['appreciation_individuelle'] ?? null,
            'decision' => $decision,
            'distinctions' => $eleveData['distinctions'],
            'sanctions' => $eleveData['sanctions'],
            'mois_selectionnes' => $selectedMoisIds,
            'mois_coefficients' => $moisCoefficients,
            'user_id' => Auth::id(),
            'date_cloture' => now(),
        ]);
    }

    // ==================== IMPRESSION DES RÉCAPITULATIFS ====================

    public function generateFichesMoyennes(Request $request)
{
    $ecoleId = session('current_ecole_id');
    $annee = session('current_annee_scolaire');
    
    // Validation manuelle sans exists
    $request->validate([
        'classe_id' => 'required|numeric',
        'mois_id' => 'required|exists:mois_scolaires,id'
    ]);

    // Vérifier que la classe existe dans la table dynamique
    $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
    $classeExists = DB::table($classesTable)->where('id', $request->classe_id)->exists();
    
    if (!$classeExists) {
        return redirect()->back()->with('error', 'La classe sélectionnée n\'existe pas.');
    }

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');

    // Récupérer la classe avec ses matières
    $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);
    $mois = MoisScolaire::findOrFail($request->mois_id);

    // Récupérer les élèves de la classe (table dynamique)
    $eleves = Eleve::where('classe_id', $request->classe_id)
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('is_active', 1)
        ->orderBy('nom')
        ->orderBy('prenom')
        ->get();

    // Récupérer les matières
    $matieres = $classe->niveau->matieres;

    // Récupérer les notes pour chaque élève et chaque matière (table dynamique)
    $elevesAvecNotes = [];

    foreach ($eleves as $eleve) {
        $notesData = [];
        foreach ($matieres as $matiere) {
            $note = Note::where('eleve_id', $eleve->id)
                ->where('matiere_id', $matiere->id)
                ->where('mois_id', $mois->id)
                ->where('classe_id', $classe->id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            $notesData[$matiere->id] = $note ? $note->valeur : null;
        }

        $elevesAvecNotes[] = [
            'eleve' => $eleve,
            'notes' => $notesData
        ];
    }

    // Récupérer l'école
    $ecole = Ecole::find($ecoleId);

    $pdf = Pdf::loadView('dashboard.documents.fiches-moyennes', [
        'classe' => $classe,
        'mois' => $mois,
        'matieres' => $matieres,
        'elevesAvecNotes' => $elevesAvecNotes,
        'ecole' => $ecole
    ])->setPaper('a4', 'landscape');

    return $pdf->stream('fiche-notes-' . $classe->nom . '-' . $mois->nom . '.pdf');
}

public function generateRecapMoyennes(Request $request)
{
    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $annee = session('current_annee_scolaire');
    
    $classesTable = $this->tableService->getClassesTableName($ecoleId, $annee);
    
    $request->validate([
        'type' => 'required|in:mensuel,annuel',
        'classe_id' => 'required_if:type,mensuel|numeric',
        'mois_id' => 'required_if:type,mensuel|exists:mois_scolaires,id'
    ]);

    $type = $request->type;
    
    if ($type == 'mensuel') {
        // Vérifier que la classe existe dans la table dynamique
        $classeExists = DB::table($classesTable)->where('id', $request->classe_id)->exists();
        
        if (!$classeExists) {
            return redirect()->back()->with('error', 'La classe sélectionnée n\'existe pas.');
        }
        
        return $this->generateRecapMensuel($request, $ecoleId, $anneeScolaireId);
    } else {
        return $this->generateRecapAnnuel($request, $ecoleId, $anneeScolaireId);
    }
}

private function generateRecapMensuel($request, $ecoleId, $anneeScolaireId)
{
    $mois = MoisScolaire::findOrFail($request->mois_id);
    $classe = Classe::findOrFail($request->classe_id);
    
    $moyennesMois = MoyenneMois::with(['eleve', 'classe', 'mois'])
        ->where('classe_id', $classe->id)
        ->where('mois_id', $mois->id)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('ecole_id', $ecoleId)
        ->orderBy('moyenne', 'desc')
        ->get();
    
    if ($moyennesMois->isEmpty()) {
        return back()->with('error', "Aucune moyenne enregistrée pour la classe {$classe->nom} au mois de {$mois->nom}.");
    }
    
    $matieres = $classe->niveau->matieres->sortBy(fn($m) => $m->pivot->ordre ?? 0);
    
    $eleves = [];
    $rang = 1;
    foreach ($moyennesMois as $moyenne) {
        $notesDetails = [];
        if ($moyenne->details_notes) {
            $details = is_string($moyenne->details_notes) ? json_decode($moyenne->details_notes, true) : $moyenne->details_notes;
            foreach ($details as $note) {
                $notesDetails[$note['matiere_id']] = $note;
            }
        }
        
        $eleves[] = [
            'rang' => $rang++,
            'nom' => $moyenne->eleve->nom,
            'prenom' => $moyenne->eleve->prenom,
            'moyenne' => number_format($moyenne->moyenne, 2, ',', ''),
            'moyenne_brute' => $moyenne->moyenne,
            'appreciation' => $moyenne->appreciation ?? '-',
            'details' => $notesDetails,
            'rang_general' => $moyenne->rang,
            'exaequo' => $moyenne->exaequo
        ];
    }
    
    $data = [
        'classe' => $classe,
        'enseignant' => $classe->enseignant?->name ?? '—',
        'eleves' => $eleves,
        'matieres' => $matieres,
        'mois' => $mois,
        'type' => 'mensuel',
        'moyenne_classe' => $moyennesMois->first()->moyenne_classe ?? 0,
        'moyenne_min' => $moyennesMois->first()->moyenne_min ?? 0,
        'moyenne_max' => $moyennesMois->first()->moyenne_max ?? 0,
        'effectif' => $moyennesMois->first()->effectif_classe ?? count($eleves),
        'moy_base' => $classe->moy_base
    ];
    
    $pdf = Pdf::loadView('dashboard.documents.recap_moyenne_mensuelle', compact('data'))
        ->setPaper('a4', 'landscape');
    
    return $pdf->stream('recap_moyennes_' . $classe->nom . '_' . $mois->nom . '.pdf');
}

private function generateRecapAnnuel($request, $ecoleId, $anneeScolaireId)
{
    $moyennesGenerales = MoyenneGenerale::with(['eleve', 'classe.niveau.matieres'])
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('ecole_id', $ecoleId)
        ->get();
    
    if ($moyennesGenerales->isEmpty()) {
        return back()->with('error', "Aucune moyenne annuelle enregistrée.");
    }
    
    $classesTraitees = [];
    
    foreach ($moyennesGenerales as $moyenne) {
        $classeId = $moyenne->classe_id;
        
        if (!isset($classesTraitees[$classeId])) {
            $moisListe = [];
            if ($moyenne->moyennes_par_mois) {
                $moisData = is_string($moyenne->moyennes_par_mois) ? json_decode($moyenne->moyennes_par_mois, true) : $moyenne->moyennes_par_mois;
                foreach ($moisData as $moisId => $dataMois) {
                    $moisListe[] = [
                        'id' => $moisId,
                        'nom' => $dataMois['mois_nom'],
                        'ordre' => $dataMois['mois_ordre'] ?? $moisId
                    ];
                }
                usort($moisListe, function($a, $b) {
                    return $a['ordre'] <=> $b['ordre'];
                });
            }
            
            $classesTraitees[$classeId] = [
                'classe' => $moyenne->classe,
                'enseignant' => $moyenne->classe->enseignant?->name ?? '—',
                'eleves' => [],
                'mois_notes' => $moisListe,
                'type' => 'annuel',
                'moyenne_classe' => 0,
                'moyenne_min' => 0,
                'moyenne_max' => 0,
                'effectif' => 0,
                'moy_base' => $moyenne->classe->moy_base
            ];
        }
        
        $moisNotesEleve = [];
        $rangsMoisEleve = [];
        
        if ($moyenne->moyennes_par_mois) {
            $moisData = is_string($moyenne->moyennes_par_mois) ? json_decode($moyenne->moyennes_par_mois, true) : $moyenne->moyennes_par_mois;
            foreach ($moisData as $moisId => $dataMois) {
                $moisNotesEleve[$moisId] = $dataMois['moyenne'];
            }
        }
        
        if ($moyenne->rangs_par_mois) {
            $rangsData = is_string($moyenne->rangs_par_mois) ? json_decode($moyenne->rangs_par_mois, true) : $moyenne->rangs_par_mois;
            foreach ($rangsData as $moisId => $dataRang) {
                $rangsMoisEleve[$moisId] = $dataRang;
            }
        }
        
        $classesTraitees[$classeId]['eleves'][] = [
            'eleve_id' => $moyenne->eleve->id,
            'nom' => $moyenne->eleve->nom,
            'prenom' => $moyenne->eleve->prenom,
            'moyenne' => number_format($moyenne->moyenne_annuelle, 2, ',', ''),
            'moyenne_brute' => $moyenne->moyenne_annuelle,
            'decision' => $moyenne->decision ?? '-',
            'rang_general' => $moyenne->rang_general,
            'exaequo' => $moyenne->exaequo,
            'mois_notes' => $moisNotesEleve,
            'rangs_mois' => $rangsMoisEleve
        ];
        
        $classesTraitees[$classeId]['effectif']++;
        $classesTraitees[$classeId]['moyenne_classe'] += $moyenne->moyenne_annuelle;
        if ($moyenne->moyenne_annuelle > $classesTraitees[$classeId]['moyenne_max']) {
            $classesTraitees[$classeId]['moyenne_max'] = $moyenne->moyenne_annuelle;
        }
        if ($classesTraitees[$classeId]['moyenne_min'] == 0 || $moyenne->moyenne_annuelle < $classesTraitees[$classeId]['moyenne_min']) {
            $classesTraitees[$classeId]['moyenne_min'] = $moyenne->moyenne_annuelle;
        }
    }
    
    foreach ($classesTraitees as &$classeData) {
        if ($classeData['effectif'] > 0) {
            $classeData['moyenne_classe'] = $classeData['moyenne_classe'] / $classeData['effectif'];
            $classeData['moyenne_classe'] = floor($classeData['moyenne_classe'] * 100) / 100;
            $classeData['moyenne_max'] = floor($classeData['moyenne_max'] * 100) / 100;
            $classeData['moyenne_min'] = floor($classeData['moyenne_min'] * 100) / 100;
        }
    }
    
    $data = array_values($classesTraitees);
    
    $pdf = Pdf::loadView('dashboard.documents.recap_moyenne_annuelle', compact('data'))
        ->setPaper('a4', 'landscape');
    
    return $pdf->stream('recap_moyennes_annuelles.pdf');
}



}