<?php

namespace App\Http\Controllers;

use App\Exports\RelanceExport;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\PaiementDetail;
use App\Models\Reduction;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class RelanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::with('niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('id')
            ->get();

        $moisScolaires = MoisScolaire::orderBy('numero')->get();
        $typeFrais = TypeFrais::get();

        return view('dashboard.pages.comptabilites.relances', compact('classes', 'moisScolaires', 'typeFrais'));
    }

    public function getRelanceData(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'nullable|exists:mois_scolaires,id', 
            'type_frais_id' => 'nullable|exists:type_frais,id',
            'montant_min' => 'nullable|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0'
        ]);

        if ($request->montant_min && $request->montant_max && 
            $request->montant_min > $request->montant_max) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant minimum ne peut pas être supérieur au montant maximum'
            ]);
        }

        try {
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $moisReference = $request->date_reference
                ? MoisScolaire::find($request->date_reference)
                : MoisScolaire::orderBy('numero', 'desc')->first();

            if (!$moisReference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun mois scolaire trouvé pour cette année.'
                ]);
            }

            $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
                ->where('inscriptions.classe_id', $request->classe_id)
                ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
                ->where('inscriptions.ecole_id', $ecoleId)
                ->where('inscriptions.statut', 'active')
                ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                ->orderBy('eleves.nom')
                ->orderBy('eleves.prenom')
                ->select('inscriptions.*')
                ->get();

            $moisScolaires = MoisScolaire::orderByRaw("
                CASE
                    WHEN numero >= 8 THEN numero + 0
                    ELSE numero + 12
                END
            ")->get();

            $result = [];

            foreach ($inscriptions as $inscription) {
                $niveau = $inscription->classe->niveau;

                $typesFrais = TypeFrais::whereIn('nom', [
                    'Frais d\'inscription',
                    'Scolarité',
                    'Cantine',
                    'Transport'
                ])->get()->keyBy('nom');

                $fraisData = [];
                $totalAttenduGlobal = 0;
                $totalPayeGlobal = 0;

                foreach ($typesFrais as $nom => $type) {
                    if ($request->type_frais_id && $request->type_frais_id != $type->id) {
                        continue;
                    }

                    // Vérification pour Cantine et Transport
                    if ($nom === 'Cantine' && !$inscription->cantine_active) {
                        continue;
                    }
                    if ($nom === 'Transport' && !$inscription->transport_active) {
                        continue;
                    }

                    // Mois d'inscription pour Cantine et Transport
                    $moisInscription = (int) $inscription->created_at->format('n');
                    $jourInscription = (int) $inscription->created_at->format('j');

                    // Récupérer les tarifs mensuels
                    $tarifsQuery = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId)
                        ->where('niveau_id', $niveau->id)
                        ->where('type_frais_id', $type->id);

                    $tarifs = $tarifsQuery->get()->keyBy('mois_id');

                    // Calculer le total attendu (en tenant compte du demi-tarif pour Cantine/Transport)
                    $totalAttendu = 0;
                    foreach ($tarifs as $moisId => $tarif) {
                        $mois = MoisScolaire::find($moisId);
                        if (!$mois) continue;
                        
                        $montant = $tarif->montant;
                        
                        // Demi-tarif pour Cantine et Transport si inscription après le 15
                        if (in_array($nom, ['Cantine', 'Transport'])) {
                            if ($mois->numero == $moisInscription && $jourInscription > 15) {
                                $montant = $tarif->montant / 2;
                            }
                            // Ignorer les mois avant l'inscription
                            if ($mois->numero < $moisInscription) {
                                continue;
                            }
                        }
                        $totalAttendu += $montant;
                    }

                    // Vérifier la réduction pour Scolarité
                    $reduction = 0;
                    if ($nom === 'Scolarité') {
                        $reduction = Reduction::where('inscription_id', $inscription->id)
                            ->where('annee_scolaire_id', $anneeScolaireId)
                            ->where('ecole_id', $ecoleId)
                            ->where(function($query) use ($type) {
                                $query->whereNull('type_frais_id')
                                    ->orWhere('type_frais_id', $type->id);
                            })
                            ->sum('montant');
                        $totalAttendu = max(0, $totalAttendu - $reduction);
                    }

                    // Total payé
                    $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)
                        ->where('type_frais_id', $type->id)
                        ->sum('montant');

                    $resteAPayer = max(0, $totalAttendu - $totalPaye);

                    // Détail par mois
                    $detailsMois = [];
                    $cumulAttendu = 0;

                    foreach ($moisScolaires as $mois) {
                        $moisId = $mois->id;
                        $montantMois = 0;
                        
                        if ($tarifs->has($moisId)) {
                            $montantMois = $tarifs[$moisId]->montant;
                            
                            // Demi-tarif pour Cantine/Transport
                            if (in_array($nom, ['Cantine', 'Transport'])) {
                                if ($mois->numero == $moisInscription && $jourInscription > 15) {
                                    $montantMois = $tarifs[$moisId]->montant / 2;
                                }
                                if ($mois->numero < $moisInscription) {
                                    $montantMois = 0;
                                }
                            }
                        }
                        
                        $cumulAttendu += $montantMois;

                        if ($mois->numero <= $moisReference->numero && $mois->numero >= ($moisInscription ?? 8)) {
                            // Statut du mois
                            $estPaye = ($totalPaye >= $cumulAttendu);
                            $statut = $estPaye ? '✅ À jour' : '❌ En retard';
                            
                            $detailsMois[] = [
                                'mois' => $mois->nom,
                                'montant_mois' => $montantMois,
                                'attendu_cumul' => $cumulAttendu,
                                'paye_cumul' => min($totalPaye, $cumulAttendu),
                                'statut' => $statut,
                                'est_paye' => $estPaye
                            ];
                        }
                    }

                    $fraisData[$nom] = [
                        'total_attendu' => $totalAttendu,
                        'total_paye' => $totalPaye,
                        'reste_a_payer' => $resteAPayer,
                        'statut' => $this->determinerStatut($detailsMois),
                        'details_mois' => $detailsMois,
                        'en_retard_depuis' => $this->getMoisRetard($detailsMois)
                    ];

                    $totalAttenduGlobal += $totalAttendu;
                    $totalPayeGlobal += $totalPaye;
                }

                $resteAPayerGlobal = max(0, $totalAttenduGlobal - $totalPayeGlobal);

                // Filtre par montant
                if ($request->montant_min || $request->montant_max) {
                    $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                    $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                    
                    if ($resteAPayerGlobal < $montantMin || $resteAPayerGlobal > $montantMax) {
                        continue;
                    }
                }

                $result[] = [
                    'eleve' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                    'classe' => $inscription->classe->nom,
                    'niveau' => $niveau->nom,
                    'cantine_active' => $inscription->cantine_active,
                    'transport_active' => $inscription->transport_active,
                    'total_attendu' => $totalAttenduGlobal,
                    'total_paye' => $totalPayeGlobal,
                    'reste_a_payer' => $resteAPayerGlobal,
                    'statut' => collect($fraisData)->pluck('statut')->contains('En retard') ? 'En retard' : 'À jour',
                    'details_mois' => collect($fraisData)->pluck('details_mois')->flatten(1),
                    'en_retard_depuis' => collect($fraisData)->pluck('en_retard_depuis')->filter()->first() ?? null,
                    'frais_details' => $fraisData
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'classe' => Classe::with('niveau')->find($request->classe_id)->nom,
                'mois_reference' => $moisReference->nom,
                'type_frais_id' => $request->type_frais_id,
                'montant_min' => $request->montant_min,
                'montant_max' => $request->montant_max
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur getRelanceData: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
            ]);
        }
    }

    private function determinerStatut($detailsMois)
    {
        if (empty($detailsMois)) {
            return 'Non débuté';
        }

        foreach ($detailsMois as $detail) {
            if (!$detail['est_paye']) {
                return 'En retard';
            }
        }
        return 'À jour';
    }

    private function getMoisRetard($detailsMois)
    {
        foreach ($detailsMois as $detail) {
            if (!$detail['est_paye']) {
                return $detail['mois'];
            }
        }
        return null;
    }

   
    public function imprimerRelance(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'required|exists:mois_scolaires,id',
            'type_frais_id' => 'nullable|exists:type_frais,id',
            'montant_min' => 'nullable|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0'
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();

        // Mois sélectionné
        $moisReference = MoisScolaire::find($request->date_reference);
        if (!$moisReference) {
            return back()->with('error', 'Mois de référence invalide.');
        }

        // ✅ Relance = mois précédent
        $moisPrecedent = MoisScolaire::where('numero', '<', $moisReference->numero)
            ->orderByDesc('numero')
            ->first();

        if (!$moisPrecedent) {
            return back()->with('error', 'Aucun mois précédent trouvé pour la relance.');
        }

        $typeFraisId = $request->type_frais_id;

        $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
            ->where('inscriptions.classe_id', $request->classe_id)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.statut', 'active')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        $recus = [];

        foreach ($inscriptions as $inscription) {
            $eleve  = $inscription->eleve;
            $classe = $inscription->classe->nom;
            $niveau = $inscription->classe->niveau->nom;

            // Mois d'inscription (pour cantine et transport uniquement)
            $moisInscriptionNumero = $inscription->created_at->format('n');
            $moisInscription = MoisScolaire::where('numero', $moisInscriptionNumero)->first();
            $moisInscriptionId = $moisInscription ? $moisInscription->id : 1;

            $typesFrais = TypeFrais::whereIn('nom', [
                'Frais d\'inscription', 'Scolarité', 'Cantine', 'Transport'
            ])->get();

            foreach ($typesFrais as $type) {
                if ($typeFraisId && $type->id != $typeFraisId) continue;

                // ✅ Vérification des options actives
                if ($type->nom == 'Cantine' && !$inscription->cantine_active) continue;
                if ($type->nom == 'Transport' && !$inscription->transport_active) continue;

                // ✅ Détermination du mois de départ du calcul
                // Scolarité & Inscription → depuis le début
                // Cantine & Transport → à partir du mois d’inscription
                $debutPeriode = in_array($type->nom, ['Cantine', 'Transport'])
                    ? $moisInscriptionId
                    : 1;

                // Si le mois précédent est avant le mois d’inscription → on saute
                if ($moisPrecedent->id < $debutPeriode) continue;

                // --- Montant attendu pour le mois précédent ---
                $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $inscription->classe->niveau->id)
                    ->where('mois_id', $moisPrecedent->id)
                    ->where('type_frais_id', $type->id)
                    ->first();

                $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;
                if ($montantAttenduMois <= 0) continue;

                // --- Cumul attendu jusqu'au mois précédent ---
                $cumulAttendu = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $inscription->classe->niveau->id)
                    ->where('type_frais_id', $type->id)
                    ->whereBetween('mois_id', [$debutPeriode, $moisPrecedent->id])
                    ->sum('montant');

                // --- Réduction scolarité ---
                $reduction = 0;
                if ($type->nom == 'Scolarité') {
                    $reduction = Reduction::where('inscription_id', $inscription->id)
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId)
                        ->where(function ($q) use ($type) {
                            $q->whereNull('type_frais_id')->orWhere('type_frais_id', $type->id);
                        })
                        ->sum('montant');
                    $cumulAttendu = max(0, $cumulAttendu - $reduction);
                }

                // --- Cumul payé ---
                $cumulPaye = PaiementDetail::where('inscription_id', $inscription->id)
                    ->where('type_frais_id', $type->id)
                    ->sum('montant');

                // --- Calcul du reste pour le mois précédent ---
                $cumulAttenduAvant = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $inscription->classe->niveau->id)
                    ->where('type_frais_id', $type->id)
                    ->whereBetween('mois_id', [$debutPeriode, $moisPrecedent->id - 1])
                    ->sum('montant');

                $montantPayeMois = max(0, $cumulPaye - $cumulAttenduAvant);
                $resteMois = max(0, $montantAttenduMois - $montantPayeMois);

                // --- Total annuel attendu & payé ---
                $totalAttenduAnnee = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $inscription->classe->niveau->id)
                    ->where('type_frais_id', $type->id)
                    ->sum('montant');

                if ($type->nom == 'Scolarité') {
                    $totalAttenduAnnee = max(0, $totalAttenduAnnee - $reduction);
                }

                $totalPayeAnnee = $cumulPaye;
                $resteTotal = max(0, $totalAttenduAnnee - $totalPayeAnnee);

                // Appliquer le filtre par intervalle de montant
                if ($request->montant_min || $request->montant_max) {
                    $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                    $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                    
                    if ($resteTotal < $montantMin || $resteTotal > $montantMax) {
                        continue; // Ne pas inclure cet élève
                    }
                }


                if ($resteMois > 0) {
                    $recus[] = [
                        'parent'          => $eleve->parent_nom ?? '-',
                        'eleve'           => $eleve->nom . ' ' . $eleve->prenom,
                        'classe'          => $classe,
                        'niveau'          => $niveau,
                        'mois'            => $moisPrecedent->nom,
                        'type'            => $type->nom,
                        'montant_attendu' => $montantAttenduMois,
                        'montant_paye'    => $montantPayeMois,
                        'reste_mois'      => $resteMois,
                        'reste_total'     => $resteTotal
                    ];
                }
            }
        }

        if (empty($recus)) {
            return back()->with('info', 'Aucune relance à générer pour le mois précédent.');
        }

        $pdf = Pdf::loadView('dashboard.documents.scolarite.relance-form', [
            'recus'      => $recus,
            'mois'       => $moisPrecedent->nom, // relance sur le mois précédent
            'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous types'
        ])->setPaper('A4', 'portrait');

        return $pdf->stream('relance_paiements_'.$moisPrecedent->nom.'.pdf');
    }



    public function export(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'nullable|exists:mois_scolaires,id',
            'type_frais_id' => 'nullable|exists:type_frais,id',
            'format' => 'required|in:pdf,excel',
            'montant_min' => 'nullable|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0',
        ]);

        try {
            $ecoleId = session('current_ecole_id');
            $anneeScolaireId = session('current_annee_scolaire_id');

            $moisScolaires = MoisScolaire::orderBy('numero')->get();
            $moisReference = $request->date_reference ? MoisScolaire::find($request->date_reference) : null;
            $typeFraisId = $request->type_frais_id;

            $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
                ->where('inscriptions.classe_id', $request->classe_id)
                ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
                ->where('inscriptions.ecole_id', $ecoleId)
                ->where('inscriptions.statut', 'active')
                ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                ->orderBy('eleves.nom')
                ->orderBy('eleves.prenom')
                ->select('inscriptions.*')
                ->get();

            $result = [];

            foreach ($inscriptions as $inscription) {
                $eleve = $inscription->eleve;
                $classe = $inscription->classe->nom;
                $niveau = $inscription->classe->niveau->nom;

                $typesFrais = TypeFrais::whereIn('nom', [
                    'Frais d\'inscription',
                    'Scolarité',
                    'Cantine',
                    'Transport'
                ])->get();

                foreach ($typesFrais as $type) {
                    if ($typeFraisId && $type->id != $typeFraisId) continue;
                    if ($type->nom == 'Cantine' && !$inscription->cantine_active) continue;
                    if ($type->nom == 'Transport' && !$inscription->transport_active) continue;

                    $detailsMois = [];
                    $totalAttendu = 0;
                    $totalPaye = 0;

                    foreach ($moisScolaires as $mois) {
                        if ($moisReference && $mois->id != $moisReference->id) continue;

                        $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                            ->where('ecole_id', $ecoleId)
                            ->where('niveau_id', $inscription->classe->niveau->id)
                            ->where('type_frais_id', $type->id)
                            ->where('mois_id', $mois->id)
                            ->first();

                        $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;

                        // Cumul attendu jusqu’au mois
                        $cumulAttendu = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                            ->where('ecole_id', $ecoleId)
                            ->where('niveau_id', $inscription->classe->niveau->id)
                            ->where('type_frais_id', $type->id)
                            ->where('mois_id', '<=', $mois->id)
                            ->sum('montant');

                        // Réduction scolarité
                        $reduction = 0;
                        if ($type->nom === 'Scolarité') {
                            $reduction = Reduction::where('inscription_id', $inscription->id)
                                ->where('annee_scolaire_id', $anneeScolaireId)
                                ->where('ecole_id', $ecoleId)
                                ->where(function($query) use ($type) {
                                    $query->whereNull('type_frais_id')
                                        ->orWhere('type_frais_id', $type->id);
                                })
                                ->sum('montant');

                            $cumulAttendu = max(0, $cumulAttendu - $reduction);
                        }

                        $cumulPaye = PaiementDetail::where('inscription_id', $inscription->id)
                            ->where('type_frais_id', $type->id)
                            ->sum('montant');

                        if ($cumulPaye >= $cumulAttendu) {
                            $montantPayeMois = $montantAttenduMois;
                            $resteMois = 0;
                        } else {
                            $resteCumul = $cumulAttendu - $cumulPaye;
                            $montantPayeMois = max(0, $montantAttenduMois - $resteCumul);
                            $resteMois = $montantAttenduMois - $montantPayeMois;
                        }

                        $detailsMois[] = [
                            'mois' => $mois->nom,
                            'montant_attendu' => $montantAttenduMois,
                            'montant_paye' => $montantPayeMois,
                            'reste_mois' => $resteMois
                        ];

                        $totalAttendu += $montantAttenduMois;
                        $totalPaye += $montantPayeMois;
                    }

                    $resteTotal = max(0, $totalAttendu - $totalPaye);

                    $resteTotal = max(0, $totalAttendu - $totalPaye);
                
                    // Appliquer le filtre par intervalle de montant
                    if ($request->montant_min || $request->montant_max) {
                        $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                        $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                        
                        if ($resteTotal < $montantMin || $resteTotal > $montantMax) {
                            continue; // Ne pas inclure cet élève
                        }
                    }

                    if ($totalAttendu > 0) {
                        $result[] = [
                            'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                            'classe' => $classe,
                            'niveau' => $niveau,
                            'type' => $type->nom,
                            'details_mois' => $detailsMois,
                            'total_attendu' => $totalAttendu,
                            'total_paye' => $totalPaye,
                            'reste_total' => $resteTotal
                        ];
                    }
                }
            }

            $filters = [
                'classe' => Classe::find($request->classe_id)->nom,
                'mois' => $moisReference ? $moisReference->nom : 'Tous',
                'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous',
                'montant_min' => $request->montant_min,
                'montant_max' => $request->montant_max
            ];

            if ($request->format === 'excel') {
                return Excel::download(new RelanceExport($result, $filters),
                    'relance_paiements_' . date('Y-m-d') . '.xlsx');
            }

            if ($request->format === 'pdf') {
                $pdf = PDF::loadView('dashboard.documents.liste-relance', [
                    'data' => $result,
                    'filters' => $filters,
                    'title' => 'Relance des Paiements',
                    'date' => date('d/m/Y')
                ])->setPaper('A4', 'landscape');

                return $pdf->download('relance_paiements_' . date('Y-m-d') . '.pdf');
            }

            return redirect()->back()->with('error', 'Format non supporté');

        } catch (\Exception $e) {
            Log::error("Erreur export relance: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'exportation: ' . $e->getMessage());
        }
    }





    private function getRelanceDataInternal($request)
    {

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();

        $moisReference = $request->date_reference
            ? MoisScolaire::find($request->date_reference)
            : MoisScolaire::orderBy('numero', 'desc')->first();

        if (!$moisReference) {
            return [
                'success' => false,
                'message' => 'Aucun mois scolaire trouvé pour cette année.'
            ];
        }

        $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
        ->where('inscriptions.classe_id', $request->classe_id)
        ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
        ->where('inscriptions.ecole_id', $ecoleId)
        ->where('inscriptions.statut', 'active')
        ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
        ->orderBy('eleves.nom')
        ->orderBy('eleves.prenom')
        ->select('inscriptions.*')
        ->get();

        $moisScolaires = MoisScolaire::orderByRaw("
            CASE
                WHEN numero >= 8 THEN numero + 0
                ELSE numero + 12
            END
        ")->get();

        $result = [];

        foreach ($inscriptions as $inscription) {
            $niveau = $inscription->classe->niveau;

            // Types de frais à gérer
            $typesFrais = TypeFrais::whereIn('nom', [
                'Frais d\'inscription',
                'Scolarité',
                'Cantine',
                'Transport'
            ])->get()->keyBy('nom');

            $fraisData = [];

            foreach ($typesFrais as $nom => $type) {
                // Si on filtre par type de frais
                if ($request->type_frais_id && $request->type_frais_id != $type->id) {
                    continue;
                }

                // Tarifs mensuels pour le type de frais
                $tarifsQuery = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $niveau->id)
                    ->where('type_frais_id', $type->id);

                $tarifs = $tarifsQuery->get()->keyBy('mois_id');

                $totalAttendu = $tarifs->sum('montant');

                // Total payé via paiement_details
                $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)
                    ->where('type_frais_id', $type->id)
                    ->sum('montant');

                // Détail par mois
                $detailsMois = [];
                $cumulAttendu = 0;

                foreach ($moisScolaires as $mois) {
                    $montantMois = $tarifs->has($mois->id) ? $tarifs[$mois->id]->montant : 0;
                    $cumulAttendu += $montantMois;

                    if ($mois->numero <= $moisReference->numero) {
                        $statut = ($totalPaye >= $cumulAttendu) ? '✅ À jour' : '❌ En retard';
                        $detailsMois[] = [
                            'mois' => $mois->nom,
                            'attendu_cumul' => $cumulAttendu,
                            'statut' => $statut
                        ];
                    }
                }

                $fraisData[$nom] = [
                    'total_attendu' => $totalAttendu,
                    'total_paye' => $totalPaye,
                    'reste_a_payer' => max(0, $totalAttendu - $totalPaye),
                    'statut' => $this->determinerStatut($detailsMois),
                    'details_mois' => $detailsMois,
                    'en_retard_depuis' => $this->getMoisRetard($detailsMois)
                ];
            }

            $result[] = [
                'eleve' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'classe' => $inscription->classe->nom,
                'niveau' => $niveau->nom,
                'total_attendu' => collect($fraisData)->sum('total_attendu'),
                'total_paye' => collect($fraisData)->sum('total_paye'),
                'reste_total' => max(0, collect($fraisData)->sum('total_attendu') - collect($fraisData)->sum('total_paye')),
                ];

        }

        return [
            'success' => true,
            'data' => $result,
            'mois_reference' => $moisReference->nom
        ];
    }

}
