<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\MoisScolaire;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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

        return view('dashboard.pages.eleves.notes.index', compact('notes', 'eleves', 'matieres', 'classes'));
    }

    public function show(Note $note)
    {
        return view('notes.show', compact('note'));
    }

    public function create()
    {
        $classes = Classe::with(['inscriptions.eleve' => function($query) {
            $query->orderBy('nom');
        }])->orderBy('nom')->get();
        
        $matieres = Matiere::orderBy('nom')->get();
        $currentYear = Carbon::now()->format('Y');
        $nextYear = Carbon::now()->addYear()->format('Y');
        $anneeScolaire = $currentYear . '-' . $nextYear;
        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.eleves.notes.create', compact('moisScolaire', 'classes', 'matieres', 'anneeScolaire'));
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

        foreach ($validated['notes'] as $noteData) {
            // Récupérer l'inscription pour obtenir l'élève_id
            $inscription = Inscription::findOrFail($noteData['inscription_id']);
            
            Note::updateOrCreate(
                [
                    'inscription_id' => $noteData['inscription_id'], // Utilisation de inscription_id
                    'matiere_id' => $validated['matiere_id'],
                    'mois_id' => $validated['mois_id'],
                    'annee_scolaire' => $validated['annee_scolaire'] ?? date('Y') . '-' . (date('Y') + 1)
                ],
                [
                    'eleve_id' => $inscription->eleve_id, // On garde aussi l'eleve_id pour compatibilité
                    'classe_id' => $validated['classe_id'],
                    'valeur' => $noteData['valeur'],
                    'coefficient' => $validated['coefficient'],
                    'user_id' => Auth::id(),
                ]
            );
        }

        return redirect()->route('notes.index')
            ->with('success', 'Notes enregistrées en masse avec succès');
    }

    public function edit(Note $note)
    {
        $eleves = Eleve::orderBy('nom')->get();
        $matieres = Matiere::orderBy('nom')->get();
        $classes = Classe::orderBy('nom')->get();
        $inscriptions = Inscription::with('eleve')->orderBy('id')->get();

        return view('notes.edit', compact('note', 'eleves', 'matieres', 'classes', 'inscriptions'));
    }

    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id', // Changé de eleve_id à inscription_id
            'matiere_id' => 'required|exists:matieres,id',
            'classe_id' => 'required|exists:classes,id',
            'valeur' => 'required|numeric|min:0|max:20',
            'coefficient' => 'required|integer|min:1',
            'appreciation' => 'nullable|string'
        ]);

        // Récupérer l'inscription pour obtenir l'élève_id
        $inscription = Inscription::findOrFail($validated['inscription_id']);
        $validated['eleve_id'] = $inscription->eleve_id;

        $note->update($validated);

        return redirect()->route('notes.index')
            ->with('success', 'Note mise à jour avec succès');
    }

    public function destroy(Note $note)
    {
        $note->delete();

        return redirect()->route('notes.index')
            ->with('success', 'Note supprimée avec succès');
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
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                    'matricule' => $inscription->eleve->matricule
                ];
            });

        return response()->json($inscriptions);
    }
}