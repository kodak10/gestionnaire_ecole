<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\TypeFrais;
use App\Models\Inscription;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\MoisScolaire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BilanFinancierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Directeur|Caissiere']);
    }

    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        // ✅ Récupérer TOUS les mois scolaires (sans filtre d'année scolaire)
        $moisScolaires = MoisScolaire::orderBy('numero')->get();

        // Période sélectionnée (mois ou année)
        $periode = $request->get('periode', 'annuelle'); // 'annuelle' ou 'mensuelle'
        $moisId = $request->get('mois_id', null);

        // Statistiques financières générales
        $statistiques = $this->getStatistiquesFinancieres($ecoleId, $anneeScolaireId, $periode, $moisId);
        
        // Répartition des paiements par type de frais
        $repartitionParType = $this->getRepartitionParTypeFrais($ecoleId, $anneeScolaireId, $periode, $moisId);
        
        // Évolution des paiements par mois
        $evolutionPaiements = $this->getEvolutionPaiements($ecoleId, $anneeScolaireId);
        
        // Répartition par mode de paiement
        $repartitionMode = $this->getRepartitionModePaiement($ecoleId, $anneeScolaireId, $periode, $moisId);
        
        // Paiements par classe avec montants attendus, payés et restants
        $paiementsParClasse = $this->getPaiementsParClasse($ecoleId, $anneeScolaireId, $periode, $moisId);

        // Taux de recouvrement
        $tauxRecouvrement = $this->getTauxRecouvrement($ecoleId, $anneeScolaireId, $periode, $moisId);

        return view('dashboard.pages.bilans.financier', compact(
            'statistiques',
            'repartitionParType',
            'evolutionPaiements',
            'repartitionMode',
            'paiementsParClasse',
            'tauxRecouvrement',
            'moisScolaires',
            'periode',
            'moisId'
        ));
    }

    private function getStatistiquesFinancieres($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        // Total des paiements
        $totalPaiements = $this->getPaiementsQuery($ecoleId, $anneeScolaireId, $periode, $moisId)->sum('montant');

        // Nombre de paiements
        $nombrePaiements = $this->getPaiementsQuery($ecoleId, $anneeScolaireId, $periode, $moisId)->count();

        // Total attendu pour la période
        $totalAttendu = $this->getMontantAttendu($ecoleId, $anneeScolaireId, $periode, $moisId);

        // Paiement moyen
        $paiementMoyen = $nombrePaiements > 0 ? round($totalPaiements / $nombrePaiements, 0) : 0;

        return [
            'total_paiements' => $totalPaiements,
            'nombre_paiements' => $nombrePaiements,
            'paiement_moyen' => $paiementMoyen,
            'total_attendu' => $totalAttendu
        ];
    }

    private function getPaiementsQuery($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        $query = Paiement::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($periode === 'mensuelle' && $moisId) {
            $mois = MoisScolaire::find($moisId);
            if ($mois) {
                $query->whereMonth('created_at', $mois->numero);
            }
        }

        return $query;
    }

    private function getMontantAttendu($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        // Nombre d'élèves actifs
        $nbEleves = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->count();

        if ($nbEleves === 0) {
            return 0;
        }

        // Récupérer les tarifs annuels par type de frais pour la période
        $tarifs = Tarif::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        $totalAttendu = 0;

        if ($periode === 'annuelle') {
            // Pour chaque élève, calculer le total attendu annuel
            foreach ($tarifs as $tarif) {
                if ($tarif->obligatoire) {
                    $totalAttendu += $tarif->montant * $nbEleves;
                } else {
                    $typeFrais = $tarif->typeFrais;
                    if ($typeFrais) {
                        $count = Inscription::where('ecole_id', $ecoleId)
                            ->where('annee_scolaire_id', $anneeScolaireId)
                            ->where('statut', 'active');
                        
                        if ($typeFrais->nom === 'Transport') {
                            $count->where('transport_active', true);
                        } elseif ($typeFrais->nom === 'Cantine') {
                            $count->where('cantine_active', true);
                        }
                        
                        $totalAttendu += $tarif->montant * $count->count();
                    }
                }
            }
        } else {
            // Mensuelle - utiliser TarifMensuel
            if ($moisId) {
                $tarifsMensuels = TarifMensuel::where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('mois_id', $moisId)
                    ->get();

                foreach ($tarifsMensuels as $tarifMensuel) {
                    $tarif = $tarifMensuel->tarif;
                    if ($tarif) {
                        if ($tarif->obligatoire) {
                            $totalAttendu += $tarifMensuel->montant * $nbEleves;
                        } else {
                            $typeFrais = $tarif->typeFrais;
                            if ($typeFrais) {
                                $count = Inscription::where('ecole_id', $ecoleId)
                                    ->where('annee_scolaire_id', $anneeScolaireId)
                                    ->where('statut', 'active');
                                
                                if ($typeFrais->nom === 'Transport') {
                                    $count->where('transport_active', true);
                                } elseif ($typeFrais->nom === 'Cantine') {
                                    $count->where('cantine_active', true);
                                }
                                
                                $totalAttendu += $tarifMensuel->montant * $count->count();
                            }
                        }
                    }
                }
            }
        }

        return $totalAttendu;
    }

    private function getPaiementsParClasse($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        // Récupérer toutes les classes
        $classes = \App\Models\Classe::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        $resultats = [];

        foreach ($classes as $classe) {
            // Nombre d'élèves dans la classe
            $nbEleves = Inscription::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $classe->id)
                ->where('statut', 'active')
                ->count();

            if ($nbEleves === 0) {
                continue;
            }

            // Montant total payé par les élèves de cette classe
            $totalPaye = PaiementDetail::whereHas('paiement', function($q) use ($ecoleId, $anneeScolaireId, $periode, $moisId) {
                    $q->where('ecole_id', $ecoleId)
                      ->where('annee_scolaire_id', $anneeScolaireId);
                    
                    if ($periode === 'mensuelle' && $moisId) {
                        $mois = MoisScolaire::find($moisId);
                        if ($mois) {
                            $q->whereMonth('created_at', $mois->numero);
                        }
                    }
                })
                ->whereHas('inscription', function($q) use ($classe) {
                    $q->where('classe_id', $classe->id);
                })
                ->sum('montant');

            // Montant attendu pour cette classe
            $totalAttendu = $this->getMontantAttenduParClasse($ecoleId, $anneeScolaireId, $classe->id, $periode, $moisId);

            $reste = $totalAttendu - $totalPaye;

            $resultats[] = (object) [
                'nom' => $classe->nom,
                'eleves' => $nbEleves,
                'total_attendu' => $totalAttendu,
                'total_paye' => $totalPaye,
                'reste' => $reste > 0 ? $reste : 0,
                'taux_recouvrement' => $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 2) : 0
            ];
        }

        // Trier par taux de recouvrement (optionnel)
        usort($resultats, function($a, $b) {
            return $b->taux_recouvrement <=> $a->taux_recouvrement;
        });

        return collect($resultats);
    }

    private function getMontantAttenduParClasse($ecoleId, $anneeScolaireId, $classeId, $periode, $moisId)
    {
        $nbEleves = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('classe_id', $classeId)
            ->where('statut', 'active')
            ->count();

        if ($nbEleves === 0) {
            return 0;
        }

        // Récupérer le niveau de la classe
        $classe = \App\Models\Classe::with('niveau')->find($classeId);
        $niveauId = $classe->niveau_id ?? null;

        $totalAttendu = 0;

        if ($periode === 'annuelle') {
            // Tarifs annuels pour ce niveau
            $tarifs = Tarif::where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where(function($q) use ($niveauId) {
                    $q->where('niveau_id', $niveauId)
                      ->orWhereNull('niveau_id');
                })
                ->get();

            foreach ($tarifs as $tarif) {
                if ($tarif->obligatoire) {
                    $totalAttendu += $tarif->montant * $nbEleves;
                } else {
                    $typeFrais = $tarif->typeFrais;
                    if ($typeFrais) {
                        $count = Inscription::where('ecole_id', $ecoleId)
                            ->where('annee_scolaire_id', $anneeScolaireId)
                            ->where('classe_id', $classeId)
                            ->where('statut', 'active');
                        
                        if ($typeFrais->nom === 'Transport') {
                            $count->where('transport_active', true);
                        } elseif ($typeFrais->nom === 'Cantine') {
                            $count->where('cantine_active', true);
                        }
                        
                        $totalAttendu += $tarif->montant * $count->count();
                    }
                }
            }
        } else {
            // Mensuelle
            if ($moisId) {
                $tarifsMensuels = TarifMensuel::where('ecole_id', $ecoleId)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('mois_id', $moisId)
                    ->where(function($q) use ($niveauId) {
                        $q->where('niveau_id', $niveauId)
                          ->orWhereNull('niveau_id');
                    })
                    ->get();

                foreach ($tarifsMensuels as $tarifMensuel) {
                    $tarif = $tarifMensuel->tarif;
                    if ($tarif) {
                        if ($tarif->obligatoire) {
                            $totalAttendu += $tarifMensuel->montant * $nbEleves;
                        } else {
                            $typeFrais = $tarif->typeFrais;
                            if ($typeFrais) {
                                $count = Inscription::where('ecole_id', $ecoleId)
                                    ->where('annee_scolaire_id', $anneeScolaireId)
                                    ->where('classe_id', $classeId)
                                    ->where('statut', 'active');
                                
                                if ($typeFrais->nom === 'Transport') {
                                    $count->where('transport_active', true);
                                } elseif ($typeFrais->nom === 'Cantine') {
                                    $count->where('cantine_active', true);
                                }
                                
                                $totalAttendu += $tarifMensuel->montant * $count->count();
                            }
                        }
                    }
                }
            }
        }

        return $totalAttendu;
    }

    private function getRepartitionParTypeFrais($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        $query = PaiementDetail::whereHas('paiement', function($q) use ($ecoleId, $anneeScolaireId, $periode, $moisId) {
                $q->where('ecole_id', $ecoleId)
                  ->where('annee_scolaire_id', $anneeScolaireId);
                
                if ($periode === 'mensuelle' && $moisId) {
                    $mois = MoisScolaire::find($moisId);
                    if ($mois) {
                        $q->whereMonth('created_at', $mois->numero);
                    }
                }
            })
            ->join('tarifs', 'paiement_details.tarif_id', '=', 'tarifs.id')
            ->join('type_frais', 'tarifs.type_frais_id', '=', 'type_frais.id')
            ->select('type_frais.nom as type', DB::raw('SUM(paiement_details.montant) as total'))
            ->groupBy('type_frais.id', 'type_frais.nom')
            ->orderBy('total', 'desc')
            ->get();

        return $query;
    }

    private function getEvolutionPaiements($ecoleId, $anneeScolaireId)
    {
        return Paiement::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->select(
                DB::raw('MONTH(created_at) as mois'),
                DB::raw('YEAR(created_at) as annee'),
                DB::raw('COUNT(*) as nombre'),
                DB::raw('SUM(montant) as total')
            )
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();
    }

    private function getRepartitionModePaiement($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        $query = Paiement::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($periode === 'mensuelle' && $moisId) {
            $mois = MoisScolaire::find($moisId);
            if ($mois) {
                $query->whereMonth('created_at', $mois->numero);
            }
        }

        return $query->select('mode_paiement', DB::raw('COUNT(*) as nombre'), DB::raw('SUM(montant) as total'))
            ->groupBy('mode_paiement')
            ->get();
    }

    private function getTauxRecouvrement($ecoleId, $anneeScolaireId, $periode, $moisId)
    {
        $totalAttendu = $this->getMontantAttendu($ecoleId, $anneeScolaireId, $periode, $moisId);
        $totalPaye = $this->getPaiementsQuery($ecoleId, $anneeScolaireId, $periode, $moisId)->sum('montant');

        $taux = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 2) : 0;

        return [
            'total_attendu' => $totalAttendu,
            'total_paye' => $totalPaye,
            'taux' => $taux,
            'reste' => $totalAttendu - $totalPaye
        ];
    }

    public function export(Request $request)
    {
        // TODO: Exporter en PDF/Excel
        return redirect()->back()->with('info', 'Fonctionnalité d\'export en cours de développement');
    }
}