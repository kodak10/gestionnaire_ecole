<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Niveau;
use Illuminate\Http\Request;
use PDF;

class DocumentController extends Controller
{
    // Page des fiches d'inscription avec recherche
    public function inscriptions(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $anneeScolaire = AnneeScolaire::where('id', $anneeScolaireId)->first();

        $query = Inscription::with(['eleve', 'classe.niveau'])
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaire->id)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active');

        // Recherche par nom d'élève
        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        // Filtre par classe
        if ($request->filled('classe_id')) {
            $query->where('inscriptions.classe_id', $request->classe_id);
        }

        $inscriptions = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->paginate(20);

        $classes = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')->get();

        return view('dashboard.pages.documents.inscriptions', compact('inscriptions', 'classes'));
    }

    // Page des certificats de scolarité avec recherche
    public function certificatsScolarite(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $anneeScolaire = AnneeScolaire::where('id', $anneeScolaireId)->first();
        
        $query = Inscription::with(['eleve', 'classe.niveau'])
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaire->id)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active');

        // Recherche par nom d'élève
        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                  ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        // Filtre par classe
        if ($request->filled('classe_id')) {
            $query->where('inscriptions.classe_id', $request->classe_id);
        }

        $inscriptions = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->paginate(20);

        $classes = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')->get();

        return view('dashboard.pages.documents.certificats-scolarite', compact('inscriptions', 'classes'));
    }

    // Page des fiches de fréquentation avec recherche
    public function fichesPresence(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $query = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        // Recherche par nom de classe
        if ($request->filled('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }

        // Filtre par niveau
        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        $classes = $query->orderBy('nom')->get();

        $niveaux = Niveau::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')
            ->get();

        $moisScolaire = MoisScolaire::all();

        return view('dashboard.pages.documents.fiches-presence', compact('classes', 'niveaux', 'moisScolaire'));
    }

    // Page des fiches de fréquentation
    public function fichesFrequentation(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $anneeScolaire = AnneeScolaire::where('id', $anneeScolaireId)->first();

        $query = Inscription::with(['eleve', 'classe.niveau'])
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaire->id)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active');

        // Recherche par nom d'élève
        if ($request->filled('nom')) {
            $query->where(function($q) use ($request) {
                $q->where('eleves.nom', 'like', '%' . $request->nom . '%')
                ->orWhere('eleves.prenom', 'like', '%' . $request->nom . '%');
            });
        }

        // Filtre par classe
        if ($request->filled('classe_id')) {
            $query->where('inscriptions.classe_id', $request->classe_id);
        }

        $inscriptions = $query->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->paginate(20);

        $classes = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')->get();

        return view('dashboard.pages.documents.fiches-frequentation', compact('inscriptions', 'classes'));
    }

    // Générer fiche de fréquentation individuelle
    public function genererFicheFrequentation(Eleve $eleve)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'anneeScolaire'])
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'active')
            ->first();

        if (!$inscription) {
            abort(404, 'Inscription non trouvée pour cette année académique');
        }

        // Récupérer tous les mois scolaires de l'année académique
        $moisScolaires = MoisScolaire::orderBy('numero')
            ->get();

        $pdf = PDF::loadView('dashboard.documents.fiche-frequentation', [
            'inscription' => $inscription,
            'moisScolaires' => $moisScolaires
        ]);

        return $pdf->stream('fiche-frequentation-' . $eleve->nom . '-' . $eleve->prenom . '.pdf');
    }

    // Générer fiche d'inscription
    public function genererFicheInscription(Eleve $eleve)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'active')
            ->first();

        if (!$inscription) {
            abort(404, 'Inscription non trouvée');
        }

        $pdf = PDF::loadView('dashboard.documents.fiche-inscription', [
            'inscription' => $inscription
        ]);

        return $pdf->stream('fiche-inscription-' . $eleve->nom . '-' . $eleve->prenom . '.pdf');
    }

    public function genererCertificatScolarite(Eleve $eleve)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'active')
            ->first();

        if (!$inscription) {
            abort(404, 'Inscription non trouvée');
        }

        $pdf = PDF::loadView('dashboard.documents.certificat-scolarite', [
            'inscription' => $inscription
        ]);

        return $pdf->stream('certificat-scolarite-' . $eleve->nom . '-' . $eleve->prenom . '.pdf');
    }

    // Générer fiche de PRESENCE
    public function genererFichePresence(Classe $classe)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        $eleves = Inscription::with(['eleve'])
        ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
        ->where('inscriptions.classe_id', $classe->id)
        ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
        ->where('inscriptions.ecole_id', $ecoleId)
        ->where('inscriptions.statut', 'active')
        ->orderBy('eleves.nom')
        ->orderBy('eleves.prenom')
        ->select('inscriptions.*')
        ->get();


        $pdf = PDF::loadView('dashboard.documents.fiche-presence', [
            'classe' => $classe,
            'eleves' => $eleves,
            'anneeScolaire' => AnneeScolaire::find($anneeScolaireId)
        ]);

        return $pdf->stream('fiche-presence-' . $classe->nom . '.pdf');
    }
}