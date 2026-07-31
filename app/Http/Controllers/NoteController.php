<?php

namespace App\Http\Controllers;

use \DB;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Mention;
use App\Models\MoisScolaire;
use App\Models\Note;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMois;
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

    // ==================== MÉTHODES DE BASE ====================

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $query = Note::with(['inscription.eleve', 'matiere', 'classe', 'mois'])
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
            $query->whereHas('inscription.eleve', function($q) use ($request) {
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
    $base = $matierePivot->denominateur ?? 20;

    $savedCount = 0;
    $updatedCount = 0;

    foreach ($validated['notes'] as $noteData) {
        $valeur = $noteData['valeur'];

        if ($valeur === null || $valeur === '') {
            continue;
        }

        $inscription = Inscription::findOrFail($noteData['inscription_id']);

        if ($valeur > $base) {
            return back()
                ->withInput()
                ->with('error', "La note de {$inscription->eleve->nom} dépasse la base autorisée ({$base}).");
        }

        // Vérifier si la note existe déjà
        $existingNote = Note::where('inscription_id', $noteData['inscription_id'])
            ->where('matiere_id', $validated['matiere_id'])
            ->where('mois_id', $validated['mois_id'])
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->first();

        if ($existingNote) {
            // Mettre à jour
            $existingNote->update([
                'valeur' => $valeur,
                'coefficient' => $validated['coefficient'],
                'user_id' => Auth::id(),
                'appreciation' => $this->generateAppreciation($valeur, $base),
            ]);
            $updatedCount++;
            
            Log::info('Note mise à jour', [
                'note_id' => $existingNote->id,
                'inscription_id' => $noteData['inscription_id'],
                'matiere_id' => $validated['matiere_id'],
                'mois_id' => $validated['mois_id'],
                'valeur' => $valeur
            ]);
        } else {
            // Créer
            Note::create([
                'inscription_id' => $noteData['inscription_id'],
                'matiere_id' => $validated['matiere_id'],
                'mois_id' => $validated['mois_id'],
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'eleve_id' => $inscription->eleve_id,
                'classe_id' => $validated['classe_id'],
                'valeur' => $valeur,
                'coefficient' => $validated['coefficient'],
                'user_id' => Auth::id(),
                'appreciation' => $this->generateAppreciation($valeur, $base),
            ]);
            $savedCount++;
            
            Log::info('Note créée', [
                'inscription_id' => $noteData['inscription_id'],
                'matiere_id' => $validated['matiere_id'],
                'mois_id' => $validated['mois_id'],
                'valeur' => $valeur
            ]);
        }
    }

    $message = [];
    if ($savedCount > 0) {
        $message[] = $savedCount . ' nouvelle(s) note(s) enregistrée(s)';
    }
    if ($updatedCount > 0) {
        $message[] = $updatedCount . ' note(s) mise(s) à jour';
    }

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

    // ==================== MÉTHODES AJAX ====================

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

    // ==================== MÉTHODES DE CALCUL ====================

    /**
     * Applique l'arrondi selon la configuration de l'école
     */
    private function appliquerArrondi($valeur)
    {
        if ($valeur === null) {
            return null;
        }

        $ecoleId = session('current_ecole_id');
        $ecole = Ecole::find($ecoleId);
        
        if ($ecole) {
            return $ecole->appliquerArrondi($valeur);
        }
        
        // Par défaut : coupe à 2 chiffres
        return floor($valeur * 100) / 100;
    }

    /**
     * Récupère la mention en fonction de la moyenne avec fallback
     */
    private function getMention($moyenne, $moyBase)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        if ($moyenne === null || $moyenne === 0) {
            return 'Non classé';
        }

        // Conversion de la moyenne sur 20
        $moyenneSur20 = $moyBase > 0 ? ($moyenne / $moyBase) * 20 : $moyenne;
        $moyenneArrondie = round($moyenneSur20);

        // Essayer avec l'année scolaire actuelle
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

    /**
     * Génère une appréciation en fonction de la note et de la base de la matière.
     */
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

    /**
     * Calcule les distinctions (Tableau d'honneur, Encouragement, Félicitations)
     */
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

    /**
     * Calcule les sanctions
     */
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

    /**
     * Formate le rang avec suffixe
     */
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

    /**
     * Détermine la décision (ADMIS / NON ADMIS) en fonction du sexe et de la moyenne
     */
    private function determinerDecision($moyenne, $moyBase, $sexe)
    {
        $pourcentage = ($moyBase > 0) ? ($moyenne / $moyBase) * 100 : 0;
        
        if ($pourcentage >= 50) {
            return ($sexe == 'Féminin' || $sexe == 'F' || $sexe == 'femme' || $sexe == 'female') 
                ? 'ADMISE' 
                : 'ADMIS';
        } else {
            return ($sexe == 'Féminin' || $sexe == 'F' || $sexe == 'femme' || $sexe == 'female') 
                ? 'NON ADMISE' 
                : 'NON ADMIS';
        }
    }

    // ==================== MÉTHODES DE GÉNÉRATION DE BULLETINS ====================

    public function generateFichesMoyennes(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'mois_id' => 'required|exists:mois_scolaires,id'
        ]);

        $classe = Classe::with('niveau.matieres')->findOrFail($request->classe_id);
        $mois = MoisScolaire::findOrFail($request->mois_id);

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
            'type' => 'required|in:mensuel,annuel',
            'classe_id' => 'required_if:type,mensuel',
            'mois_id' => 'required_if:type,mensuel'
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $type = $request->type;
        
        if ($type == 'mensuel') {
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

    // ==================== GÉNÉRATION DU BULLETIN MENSUEL ====================

    public function generateBulletin(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'mois_id' => 'required|exists:mois_scolaires,id'
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        
        $saveMois = $request->has('save_mois') && $request->save_mois == '1';

        $classe = Classe::with(['niveau.matieres' => function ($q) {
            $q->orderByPivot('ordre');
        }])->findOrFail($request->classe_id);

        $mois = MoisScolaire::findOrFail($request->mois_id);

        // Vérification des enregistrements existants
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

        $inscriptions = Inscription::with(['eleve', 'notes' => function ($q) use ($request) {
            $q->where('mois_id', $request->mois_id)->with('matiere');
        }])
            ->where('classe_id', $request->classe_id)
            ->where('statut', 'active')
            ->get();

        // Éliminer les doublons de notes
        foreach ($inscriptions as $inscription) {
            $notesUniques = [];
            foreach ($inscription->notes as $note) {
                $key = $note->matiere_id;
                if (!isset($notesUniques[$key])) {
                    $notesUniques[$key] = $note;
                }
            }
            $inscription->notes = collect($notesUniques);
        }

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

        foreach ($inscriptions as $inscription) {
            $notes = $inscription->notes ?? collect();
            $totalNotes = 0;
            $totalCoeffs = 0;

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
            }

            $moyenne = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;
            
            // Appliquer l'arrondi selon la configuration
            if ($isMai && $moyenne !== null) {
                $moyenneArrondie = $this->appliquerArrondi($moyenne);
            } else {
                $moyenneArrondie = $moyenne !== null ? round($moyenne, 2) : null;
            }

            $mentionNom = $moyenneArrondie !== null
                ? $this->getMention($moyenneArrondie, $moyBase)
                : 'Non classé';

            $distinctions = $moyenneArrondie !== null ? $this->calculerDistinctions($moyenneArrondie, $moyBase) : [];
            $sanctions = $moyenneArrondie !== null ? $this->calculerSanctions($moyenneArrondie, $moyBase) : [];

            $execoCount = $notes->filter(fn($n) => isset($n->execo) && $n->execo)->count();

            $elevesAvecMoyennes[] = [
                'inscription' => $inscription,
                'notes' => $notes,
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
        $matieres = $classe->niveau->matieres
            ->sortBy(fn($matiere) => (int)$matiere->pivot->ordre)
            ->values();

        foreach ($elevesAvecMoyennes as &$eleve) {
            foreach ($matieres as $matiere) {
                $note = $eleve['notes']->firstWhere('matiere_id', $matiere->id);
                if ($note) {
                    $note->base = $matiere->pivot->denominateur ?? 20;
                    $note->coefficient = $matiere->pivot->coefficient ?? 1;
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

        // Moyennes de classe
        $elevesAvecNotes = array_filter($elevesAvecMoyennes, fn($e) => $e['total_coeffs'] > 0);
        
        if ($isMai) {
            $moyClasse = count($elevesAvecNotes) > 0
                ? floor((array_sum(array_column($elevesAvecNotes, 'moyenne')) / count($elevesAvecNotes)) * 100) / 100
                : 0;
            $moyPremier = count($elevesAvecNotes) > 0
                ? floor(max(array_column($elevesAvecNotes, 'moyenne')) * 100) / 100
                : 0;
            $moyDernier = count($elevesAvecNotes) > 0
                ? floor(min(array_column($elevesAvecNotes, 'moyenne')) * 100) / 100
                : 0;
        } else {
            $moyClasse = count($elevesAvecNotes) > 0
                ? round(array_sum(array_column($elevesAvecNotes, 'moyenne')) / count($elevesAvecNotes), 2)
                : 0;
            $moyPremier = count($elevesAvecNotes) > 0
                ? round(max(array_column($elevesAvecNotes, 'moyenne')), 2)
                : 0;
            $moyDernier = count($elevesAvecNotes) > 0
                ? round(min(array_column($elevesAvecNotes, 'moyenne')), 2)
                : 0;
        }

        // Sauvegarde dans moyenne_mois
        if ($saveMois) {
            $statsClasse = [
                'moyenne_classe' => $moyClasse,
                'moyenne_min' => $moyDernier,
                'moyenne_max' => $moyPremier,
                'effectif' => count($elevesAvecMoyennes)
            ];
            
            foreach ($elevesAvecMoyennes as $eleveData) {
                $this->saveMoyenneMois(
                    $eleveData['inscription'],
                    $classe,
                    $mois,
                    $eleveData,
                    $statsClasse
                );
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

    /**
     * Enregistre la moyenne mensuelle dans la table moyenne_mois
     */
    private function saveMoyenneMois($inscription, $classe, $mois, $eleveData, $statsClasse)
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
            'eleve_id' => $inscription->eleve_id,
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
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'mois_data' => 'required|json',
    ]);

    $ecoleId = session('current_ecole_id');
    $anneeScolaireId = session('current_annee_scolaire_id');
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    $classe = Classe::with(['niveau.matieres' => function ($q) {
        $q->orderByPivot('ordre');
    }])->findOrFail($request->classe_id);
    
    // Décoder les données des mois avec coefficients
    $moisData = json_decode($request->mois_data, true);
    
    if (empty($moisData)) {
        return redirect()->back()->with('error', 'Veuillez sélectionner au moins un mois.');
    }
    
    // Extraire les IDs des mois
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

    // Récupérer les mois sélectionnés avec leurs détails
    $moisScolaires = MoisScolaire::whereIn('id', $selectedMoisIds)->orderBy('id')->get();

    $moyBase = $classe->moy_base;
    $effectifTotal = Inscription::where('classe_id', $request->classe_id)
        ->where('statut', 'active')
        ->count();

    $inscriptions = Inscription::with(['eleve', 'notes' => function ($q) use ($selectedMoisIds) {
        $q->whereIn('mois_id', $selectedMoisIds)->with(['matiere', 'mois']);
    }])
        ->where('classe_id', $request->classe_id)
        ->where('statut', 'active')
        ->get();

    // Éliminer les doublons de notes
    foreach ($inscriptions as $inscription) {
        $notesUniques = [];
        foreach ($inscription->notes as $note) {
            $key = $note->matiere_id . '_' . $note->mois_id;
            if (!isset($notesUniques[$key])) {
                $notesUniques[$key] = $note;
            }
        }
        $inscription->notes = collect($notesUniques);
    }

    // Ajouter les infos de base et coefficient aux notes
    foreach ($inscriptions as $inscription) {
        foreach ($inscription->notes as $note) {
            $matierePivot = $classe->niveau->matieres->firstWhere('id', $note->matiere_id)->pivot ?? null;
            $note->base = $matierePivot->denominateur ?? 20;
            $note->coefficient = $matierePivot->coefficient ?? 1;
            $note->appreciation = $this->generateAppreciation($note->valeur, $note->base);
        }
    }

    // ==================== CALCUL DES RANGS PAR MATIÈRE POUR CHAQUE MOIS ====================
    foreach ($moisScolaires as $mois) {
        foreach ($matieres as $matiere) {
            $notesMatiereMois = [];
            foreach ($inscriptions as $inscription) {
                $note = $inscription->notes->firstWhere(function($n) use ($matiere, $mois) {
                    return $n->matiere_id == $matiere->id && $n->mois_id == $mois->id;
                });
                if ($note && $note->valeur !== null) {
                    $notesMatiereMois[] = [
                        'note' => $note,
                        'inscription_id' => $inscription->id,
                        'valeur' => $note->valeur
                    ];
                }
            }
            
            usort($notesMatiereMois, function ($a, $b) {
                return $b['valeur'] <=> $a['valeur'];
            });
            
            $rang = 1;
            $prevValeur = null;
            foreach ($notesMatiereMois as $idx => $item) {
                if ($prevValeur !== null && $item['valeur'] < $prevValeur) {
                    $rang = $idx + 1;
                }
                $item['note']->rang_matiere = $rang;
                $item['note']->rang_matiere_text = $this->formatRang($rang);
                $prevValeur = $item['valeur'];
            }
        }
    }

    // ==================== CALCUL DES MOYENNES PAR MOIS AVEC COEFFICIENTS ====================
    $moyennesParMoisGlobale = [];
    $moyennesParMoisDetails = [];
    
    foreach ($moisScolaires as $mois) {
        // Utiliser le coefficient défini par l'utilisateur
        $coeffMois = $moisCoefficients[$mois->id] ?? 1;
        
        foreach ($inscriptions as $inscription) {
            $notes = $inscription->notes->where('mois_id', $mois->id);
            $totalNotes = 0;
            $totalCoeffs = 0;
            
            foreach ($notes as $note) {
                $base = $note->base ?? 20;
                $coeff = $note->coefficient ?? 1;
                
                if ($note->valeur !== null && $coeff > 0) {
                    $totalNotes += ($note->valeur / $base) * $moyBase * $coeff;
                    $totalCoeffs += $coeff;
                }
            }
            
            $moyenneMois = $totalCoeffs > 0 ? ($totalNotes / $totalCoeffs) : null;
            
            if ($moyenneMois !== null) {
                $moyennesParMoisGlobale[$mois->id][$inscription->id] = [
                    'moyenne' => $moyenneMois,
                    'coefficient' => $coeffMois
                ];
                
                $moyennesParMoisDetails[$inscription->id][$mois->id] = [
                    'mois_id' => $mois->id,
                    'mois_nom' => $mois->nom,
                    'mois_ordre' => $mois->ordre ?? $mois->id,
                    'moyenne' => $moyenneMois,
                    'coefficient' => $coeffMois,
                    'a_des_notes' => $notes->count() > 0
                ];
            } else {
                $moyennesParMoisDetails[$inscription->id][$mois->id] = [
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
    
    // ==================== CALCUL DES RANGS PAR MOIS ====================
    $rangsParMois = [];
    $rangsParMoisDetails = [];
    
    foreach ($moisScolaires as $mois) {
        if (isset($moyennesParMoisGlobale[$mois->id])) {
            $moyennes = [];
            foreach ($moyennesParMoisGlobale[$mois->id] as $inscriptionId => $data) {
                $moyennes[$inscriptionId] = $data['moyenne'];
            }
            arsort($moyennes);
            $rang = 1;
            $prevMoyenne = null;
            foreach ($moyennes as $inscriptionId => $moyenne) {
                if ($prevMoyenne !== null && $moyenne < $prevMoyenne) {
                    $rang++;
                }
                $rangsParMois[$mois->id][$inscriptionId] = $rang;
                
                $rangsParMoisDetails[$inscriptionId][$mois->id] = [
                    'rang' => $rang,
                    'effectif_total' => count($moyennes),
                    'moyenne' => $moyenne
                ];
                $prevMoyenne = $moyenne;
            }
        }
    }

    // ==================== CALCUL DES MOYENNES PAR MATIÈRE (POUR L'ANNÉE) ====================
    $moyennesParMatiereDetails = [];
    $rangsParMatiereDetails = [];
    
    foreach ($matieres as $matiere) {
        foreach ($inscriptions as $inscription) {
            $notesMatiere = $inscription->notes->filter(function($note) use ($matiere) {
                return $note->matiere_id == $matiere->id && $note->valeur !== null;
            });
            
            if ($notesMatiere->count() > 0) {
                $moyenneMatiere = $notesMatiere->avg('valeur');
                $base = $matiere->pivot->denominateur ?? 20;
                $coeff = $matiere->pivot->coefficient ?? 1;
                $moyenneConvertie = ($moyenneMatiere / $base) * $moyBase;
                
                $moyennesParMatiereDetails[$inscription->id][$matiere->id] = [
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
                $moyennesParMatiereDetails[$inscription->id][$matiere->id] = [
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
        
        // Calculer les rangs par matière
        $moyennesMatiereTrie = [];
        foreach ($inscriptions as $inscription) {
            $data = $moyennesParMatiereDetails[$inscription->id][$matiere->id] ?? null;
            if ($data && $data['moyenne'] !== null) {
                $moyennesMatiereTrie[] = [
                    'inscription_id' => $inscription->id,
                    'moyenne' => $data['moyenne'],
                    'moyenne_brute' => $data['moyenne_brute']
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
            $rangsParMatiereDetails[$item['inscription_id']][$matiere->id] = [
                'rang' => $rang,
                'exaequo' => ($prevMoyenne !== null && $item['moyenne'] == $prevMoyenne),
                'effectif_total' => count($moyennesMatiereTrie)
            ];
            $prevMoyenne = $item['moyenne'];
        }
    }

    // ==================== CALCUL DES MOYENNES GÉNÉRALES ANNUELLES AVEC COEFFICIENTS ====================
    $elevesAvecMoyennes = [];

    foreach ($inscriptions as $inscription) {
        $notes = $inscription->notes ?? collect();
        
        // Calcul de la moyenne générale annuelle avec pondération des mois (coefficients personnalisés)
        $totalNotesMois = 0;
        $totalCoeffsTotal = 0;
        
        foreach ($moisScolaires as $mois) {
            $coeffMois = $moisCoefficients[$mois->id] ?? 1;
            $totalCoeffsTotal += $coeffMois;
            
            if (isset($moyennesParMoisGlobale[$mois->id][$inscription->id])) {
                $dataMois = $moyennesParMoisGlobale[$mois->id][$inscription->id];
                $totalNotesMois += $dataMois['moyenne'] * $coeffMois;
            }
            // Si pas de notes pour ce mois, contribution = 0
        }
        
        // Moyenne = somme des moyennes pondérées / somme de TOUS les coefficients
        $moyenneGenerale = $totalCoeffsTotal > 0 ? ($totalNotesMois / $totalCoeffsTotal) : null;
        $moyenneGeneraleArrondie = $moyenneGenerale !== null ? $this->appliquerArrondi($moyenneGenerale) : null;
        
        // Assiduité
        $moisAvecNotes = 0;
        foreach ($moisScolaires as $mois) {
            if (isset($moyennesParMoisGlobale[$mois->id][$inscription->id])) {
                $moisAvecNotes++;
            }
        }
        $assiduite = count($selectedMoisIds) > 0 ? ($moisAvecNotes / count($selectedMoisIds)) * 100 : 0;
        
        // Construire le récapitulatif des moyennes par mois pour l'affichage
        $moyennesParMoisAffichage = [];
        foreach ($moisScolaires as $mois) {
            if (isset($moyennesParMoisGlobale[$mois->id][$inscription->id])) {
                $dataMois = $moyennesParMoisGlobale[$mois->id][$inscription->id];
                $moyenneMois = $dataMois['moyenne'];
                $rangMois = $rangsParMois[$mois->id][$inscription->id] ?? null;
                $coeffMois = $moisCoefficients[$mois->id] ?? 1;
                
                $moyenneMoisFormatee = $this->appliquerArrondi($moyenneMois);
                
                $moyennesParMoisAffichage[] = [
                    'mois' => $mois->nom,
                    'moyenne' => $moyenneMoisFormatee,
                    'coefficient' => $coeffMois,
                    'rang' => $rangMois,
                    'effectif' => $effectifTotal
                ];
            }
        }
        
        // Récupérer les moyennes par matière pour cet élève
        $matieresAvecMoyenne = [];
        foreach ($matieres as $matiere) {
            $dataMatiere = $moyennesParMatiereDetails[$inscription->id][$matiere->id] ?? null;
            $rangMatiere = $rangsParMatiereDetails[$inscription->id][$matiere->id] ?? null;
            
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
        
        $elevesAvecMoyennes[] = [
            'inscription' => $inscription,
            'notes_originales' => $notes,
            'notes' => collect($matieresAvecMoyenne),
            'moyenne' => $moyenneGeneraleArrondie ?? 0,
            'mention' => $mentionNom,
            'assiduite' => round($assiduite, 2),
            'mois_avec_notes' => $moisAvecNotes,
            'total_mois' => count($selectedMoisIds),
            'distinctions' => $moyenneGeneraleArrondie !== null ? $this->calculerDistinctions($moyenneGeneraleArrondie, $moyBase) : [],
            'sanctions' => $moyenneGeneraleArrondie !== null ? $this->calculerSanctions($moyenneGeneraleArrondie, $moyBase) : [],
            'moyennes_par_mois' => $moyennesParMoisAffichage,
            'moyennes_par_mois_raw' => $moyennesParMoisDetails[$inscription->id] ?? [],
            'rangs_par_mois_raw' => $rangsParMoisDetails[$inscription->id] ?? [],
            'moyennes_par_matiere_raw' => $moyennesParMatiereDetails[$inscription->id] ?? [],
            'rangs_par_matiere_raw' => $rangsParMatiereDetails[$inscription->id] ?? [],
            'appreciation_individuelle' => $appreciationsIndividuelles[$inscription->eleve_id] ?? null,
            // Ajouter les coefficients des mois pour le PDF
            'mois_coefficients' => $moisCoefficients
        ];
    }

    // ==================== CLASSEMENT GÉNÉRAL ====================
    usort($elevesAvecMoyennes, function ($a, $b) {
        return $b['moyenne'] <=> $a['moyenne'];
    });
    
    // Attribution des rangs généraux
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

    // ==================== STATISTIQUES DE CLASSE ====================
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

    // ==================== SAUVEGARDE DANS MOYENNE_GENERALE ====================
    if ($saveAndClose) {
        $existingRecordsCount = MoyenneGenerale::where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->count();
        
        if ($existingRecordsCount > 0) {
            return redirect()->back()->with('error', 'Un bulletin annuel a déjà été généré pour cette classe.');
        }
        
        foreach ($elevesAvecMoyennes as $eleve) {
            $this->saveMoyenneGenerale($eleve, $classe, $anneeScolaireId, $ecoleId, $selectedMoisIds, $moyBase, $moisCoefficients);
        }
        
        session()->flash('success', 'Bulletin annuel généré et enregistré avec succès.');
    }

    // ==================== GÉNÉRATION DU PDF ====================
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

    /**
     * Enregistre la moyenne annuelle dans la table moyenne_generale
     */
private function saveMoyenneGenerale($eleve, $classe, $anneeScolaireId, $ecoleId, $selectedMoisIds, $moyBase, $moisCoefficients = [])
    {
        // Préparer les moyennes par mois formatées
        $moyennesParMoisFormatted = [];
        foreach ($eleve['moyennes_par_mois_raw'] as $moisId => $data) {
            $moyennesParMoisFormatted[$moisId] = [
                'mois_nom' => $data['mois_nom'],
                'mois_ordre' => $data['mois_ordre'],
                'moyenne' => $this->appliquerArrondi($data['moyenne']),
                'coefficient' => $data['coefficient'],
                'a_des_notes' => $data['a_des_notes']
            ];
        }
        
        // Préparer les rangs par mois formatés
        $rangsParMoisFormatted = [];
        if (isset($eleve['rangs_par_mois_raw'])) {
            foreach ($eleve['rangs_par_mois_raw'] as $moisId => $data) {
                $rangsParMoisFormatted[$moisId] = [
                    'rang' => $data['rang'],
                    'effectif_total' => $data['effectif_total'],
                    'moyenne' => $this->appliquerArrondi($data['moyenne']),
                ];
            }
        }
        
        // Préparer les moyennes par matière formatées
        $moyennesParMatiereFormatted = [];
        if (isset($eleve['moyennes_par_matiere_raw'])) {
            foreach ($eleve['moyennes_par_matiere_raw'] as $matiereId => $data) {
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
        
        // Préparer les rangs par matière formatés
        $rangsParMatiereFormatted = [];
        if (isset($eleve['rangs_par_matiere_raw'])) {
            foreach ($eleve['rangs_par_matiere_raw'] as $matiereId => $data) {
                $rangsParMatiereFormatted[$matiereId] = [
                    'rang' => $data['rang'],
                    'exaequo' => $data['exaequo'],
                    'effectif_total' => $data['effectif_total']
                ];
            }
        }
        
        // Préparer les détails complets des notes
        $detailsNotes = [];
        if (isset($eleve['notes_originales'])) {
            foreach ($eleve['notes_originales'] as $note) {
                $detailsNotes[] = [
                    'matiere_id' => $note->matiere_id,
                    'matiere_nom' => $note->matiere->nom,
                    'mois_id' => $note->mois_id,
                    'mois_nom' => $note->mois->nom,
                    'valeur' => $note->valeur,
                    'base' => $note->base,
                    'coefficient' => $note->coefficient,
                    'appreciation' => $note->appreciation,
                    'rang_matiere' => $note->rang_matiere ?? null
                ];
            }
        }
        
        // Moyenne annuelle avec arrondi configuré
        $moyenneAnnuelleCoupee = $this->appliquerArrondi($eleve['moyenne']);
        
        // Déterminer la décision
        $sexe = $eleve['inscription']->eleve->sexe ?? '';
        $decision = $this->determinerDecision($moyenneAnnuelleCoupee, $moyBase, $sexe);
        
        MoyenneGenerale::create([
            'eleve_id' => $eleve['inscription']->eleve_id,
            'classe_id' => $classe->id,
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'moyennes_par_mois' => $moyennesParMoisFormatted,
            'rangs_par_mois' => $rangsParMoisFormatted,
            'moyennes_par_matiere' => $moyennesParMatiereFormatted,
            'rangs_par_matiere' => $rangsParMatiereFormatted,
            'details_notes' => $detailsNotes,
            'moyenne_annuelle' => $moyenneAnnuelleCoupee,
            'rang_general' => $eleve['rang_general'],
            'exaequo' => $eleve['exaequo'] ?? false,
            'appreciation_generale' => $eleve['appreciation_individuelle'] ?? null,
            'decision' => $decision,
            'distinctions' => $eleve['distinctions'],
            'sanctions' => $eleve['sanctions'],
            'mois_selectionnes' => $selectedMoisIds,
            'mois_coefficients' => $moisCoefficients,
            'user_id' => Auth::id(),
            'date_cloture' => now(),
        ]);
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    public function checkExistingMoisMoyenne(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'mois_id' => 'required|exists:mois_scolaires,id'
        ]);
        
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        
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
}