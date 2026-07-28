<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inscription;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Niveau;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BilanScolaireController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Directeur']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Statistiques générales
        $statistiques = $this->getStatistiquesGenerales($ecoleId, $anneeScolaireId);
        
        // Répartition par classe
        $repartitionClasse = $this->getRepartitionParClasse($ecoleId, $anneeScolaireId);
        
        // Répartition par niveau
        $repartitionNiveau = $this->getRepartitionParNiveau($ecoleId, $anneeScolaireId);
        
        // Répartition par sexe
        $repartitionSexe = $this->getRepartitionParSexe($ecoleId, $anneeScolaireId);
        
        // Évolution des inscriptions
        $evolutionInscriptions = $this->getEvolutionInscriptions($ecoleId, $anneeScolaireId);
        
        // Services (Transport/Cantine)
        $services = $this->getServicesStats($ecoleId, $anneeScolaireId);

        return view('dashboard.pages.bilans.scolaire', compact(
            'statistiques',
            'repartitionClasse',
            'repartitionNiveau',
            'repartitionSexe',
            'evolutionInscriptions',
            'services'
        ));
    }

    private function getStatistiquesGenerales($ecoleId, $anneeScolaireId)
    {
        $totalEleves = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->count();

        $totalClasses = Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->count();

        $totalNiveaux = Niveau::where('ecole_id', $ecoleId)
            ->count();

        $moyenneParClasse = $totalClasses > 0 ? round($totalEleves / $totalClasses, 1) : 0;

        return [
            'total_eleves' => $totalEleves,
            'total_classes' => $totalClasses,
            'total_niveaux' => $totalNiveaux,
            'moyenne_par_classe' => $moyenneParClasse
        ];
    }

    private function getRepartitionParClasse($ecoleId, $anneeScolaireId)
    {
        return Inscription::where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->join('classes', 'inscriptions.classe_id', '=', 'classes.id')
            ->select('classes.nom', DB::raw('count(*) as total'))
            ->groupBy('classes.id', 'classes.nom')
            ->orderBy('classes.nom')
            ->get();
    }

    private function getRepartitionParNiveau($ecoleId, $anneeScolaireId)
    {
        return Inscription::where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->join('classes', 'inscriptions.classe_id', '=', 'classes.id')
            ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
            ->select('niveaux.nom', DB::raw('count(*) as total'))
            ->groupBy('niveaux.id', 'niveaux.nom')
            ->orderBy('niveaux.nom')
            ->get();
    }

    private function getRepartitionParSexe($ecoleId, $anneeScolaireId)
    {
        return Inscription::where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->select('eleves.sexe', DB::raw('count(*) as total'))
            ->groupBy('eleves.sexe')
            ->get();
    }

    private function getEvolutionInscriptions($ecoleId, $anneeScolaireId)
    {
        // Récupérer les inscriptions par mois
        return Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->select(
                DB::raw('MONTH(created_at) as mois'),
                DB::raw('YEAR(created_at) as annee'),
                DB::raw('count(*) as total')
            )
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();
    }

    private function getServicesStats($ecoleId, $anneeScolaireId)
    {
        $totalEleves = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->count();

        $transport = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->where('transport_active', true)
            ->count();

        $cantine = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->where('cantine_active', true)
            ->count();

        return [
            'transport' => [
                'total' => $transport,
                'pourcentage' => $totalEleves > 0 ? round(($transport / $totalEleves) * 100, 1) : 0
            ],
            'cantine' => [
                'total' => $cantine,
                'pourcentage' => $totalEleves > 0 ? round(($cantine / $totalEleves) * 100, 1) : 0
            ],
            'total_eleves' => $totalEleves
        ];
    }

    public function export(Request $request)
    {
        // TODO: Exporter en PDF/Excel
        return redirect()->back()->with('info', 'Fonctionnalité d\'export en cours de développement');
    }
}