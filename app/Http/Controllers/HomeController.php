<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();

        // Total élèves de l'école
        $totalEleves = Eleve::where('ecole_id', session('current_ecole_id'))->count();

        // Récupérer l'année scolaire depuis la session
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Total inscriptions de l'année en cours
        $totalInscriptions = $anneeScolaireId
            ? Inscription::where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', session('current_ecole_id'))
                        ->count()
            : 0;

        // Calcul des frais attendus et perçus
        $fraisStats = $this->getFraisStatistics(session('current_ecole_id'), $anneeScolaireId);

        return view('dashboard.pages.home', compact(
            'user', 
            'totalEleves', 
            'totalInscriptions',
            'fraisStats'
        ));
    }


    /**
     * Calculer les statistiques des frais
     */
    private function getFraisStatistics($ecoleId, $anneeScolaireId)
    {
        if (!$anneeScolaireId) {
            return [
                'frais_attendus' => 0,
                'frais_percus' => 0,
                'pourcentage_perception' => 0,
                'evolution_frais' => 0
            ];
        }

        // Calcul des frais attendus (somme des tarifs pour toutes les inscriptions)
        $fraisAttendus = Inscription::where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->join('classes', 'inscriptions.classe_id', '=', 'classes.id')
            ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
            ->join('tarifs', function($join) use ($anneeScolaireId) {
                $join->on('niveaux.id', '=', 'tarifs.niveau_id')
                     ->where('tarifs.annee_scolaire_id', $anneeScolaireId)
                     ->where('tarifs.ecole_id', 'inscriptions.ecole_id');
            })
            ->select(DB::raw('COALESCE(SUM(tarifs.montant), 0) as total_attendu'))
            ->value('total_attendu');

        // Calcul des frais perçus (somme des paiements)
        $fraisPercus = Paiement::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->sum('montant');

        // Pourcentage de perception
        $pourcentagePerception = $fraisAttendus > 0 
            ? round(($fraisPercus / $fraisAttendus) * 100, 2) 
            : 0;

        // Calcul de l'évolution (comparaison avec le mois précédent)
        $evolution = $this->calculateEvolution($ecoleId, $anneeScolaireId, $fraisPercus);

        return [
            'frais_attendus' => $fraisAttendus,
            'frais_percus' => $fraisPercus,
            'pourcentage_perception' => $pourcentagePerception,
            'evolution_frais' => $evolution
        ];
    }

    /**
     * Calculer l'évolution des frais perçus
     */
    private function calculateEvolution($ecoleId, $anneeScolaireId, $fraisPercusActuel)
    {
        // Mois précédent
        $debutMoisPrecedent = now()->subMonth()->startOfMonth();
        $finMoisPrecedent = now()->subMonth()->endOfMonth();

        $fraisMoisPrecedent = Paiement::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->whereBetween('created_at', [$debutMoisPrecedent, $finMoisPrecedent])
            ->sum('montant');

        if ($fraisMoisPrecedent > 0) {
            $evolution = (($fraisPercusActuel - $fraisMoisPrecedent) / $fraisMoisPrecedent) * 100;
            return round($evolution, 2);
        }

        return $fraisPercusActuel > 0 ? 100 : 0;
    }

    /**
     * API pour les données du tableau de bord (utilisé pour AJAX)
     */
    
}