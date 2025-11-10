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

        $userId = Auth::id();
        $moisScolaires = MoisScolaire::orderBy('numero')->get();

        $typeFrais = TypeFrais::get();

        return view('dashboard.pages.comptabilites.relances', compact('classes', 'moisScolaires', 'typeFrais'));
    }

    public function getRelanceData(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'nullable|exists:mois_scolaires,id', 
            'type_frais_id' => 'nullable|exists:type_frais,id'   
        ]);

        try {
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $moisReference = $request->date_reference
                ? MoisScolaire::find($request->date_reference)
                : MoisScolaire::orderBy('numero', 'desc')->first();

            if (!$moisReference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun mois scolaire trouvé pour cette année.'
                ]);
            }

            // Récupérer toutes les inscriptions actives TRIÉES par nom et prénom
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


            // Ordre des mois (année scolaire commence en août)
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

                    // VÉRIFICATION SPÉCIALE POUR CANTINE ET TRANSPORT
                    if ($nom === 'Cantine' && !$inscription->cantine_active) {
                        continue; // Ignorer si l'élève n'a pas la cantine active
                    }

                    if ($nom === 'Transport' && !$inscription->transport_active) {
                        continue; // Ignorer si l'élève n'a pas le transport actif
                    }

                    // Tarifs mensuels pour le type de frais
                    $tarifsQuery = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId)
                        ->where('niveau_id', $niveau->id)
                        ->where('type_frais_id', $type->id);

                    $tarifs = $tarifsQuery->get()->keyBy('mois_id');

                    // Total attendu (avant réduction)
                    $totalAttendu = $tarifs->sum('montant');

                    // Vérifier s'il y a une réduction applicable
                    $reduction = Reduction::where('inscription_id', $inscription->id)
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId)
                        ->where(function ($query) use ($type) {
                            $query->whereNull('type_frais_id') // réduction globale
                                ->orWhere('type_frais_id', $type->id); // ou spécifique
                        })
                        ->sum('montant');

                    // Appliquer la réduction uniquement si frais = scolarité (id = 2)
                    if ($type->id == 2) {
                        $totalAttendu = max(0, $totalAttendu - $reduction);
                    }

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

                // Calcul des totaux globaux
                $totalAttenduGlobal = collect($fraisData)->sum('total_attendu');
                $totalPayeGlobal = collect($fraisData)->sum('total_paye');
                $resteAPayerGlobal = collect($fraisData)->sum('reste_a_payer');

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
                'type_frais_id' => $request->type_frais_id
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

        $dernierMois = end($detailsMois);
        return strpos($dernierMois['statut'], '✅') !== false ? 'À jour' : 'En retard';
    }

    private function getMoisRetard($detailsMois)
    {
        foreach ($detailsMois as $detail) {
            if (strpos($detail['statut'], '❌') !== false) {
                return $detail['mois'];
            }
        }
        return null;
    }

    // public function imprimerRelance(Request $request)
    // {
    //     $request->validate([
    //         'classe_id' => 'required|exists:classes,id',
    //         'date_reference' => 'required|exists:mois_scolaires,id',
    //         'type_frais_id' => 'nullable|exists:type_frais,id'
    //     ]);

    //     $ecoleId = session('current_ecole_id'); 
    //     $anneeScolaireId = session('current_annee_scolaire_id');
    //     $userId = Auth::id();

    //     $moisReference   = MoisScolaire::find($request->date_reference);
    //     $typeFraisId     = $request->type_frais_id;

    //     $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
    //     ->where('inscriptions.classe_id', $request->classe_id)
    //     ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
    //     ->where('inscriptions.ecole_id', $ecoleId)
    //     ->where('inscriptions.statut', 'active')
    //     ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
    //     ->orderBy('eleves.nom')
    //     ->orderBy('eleves.prenom')
    //     ->select('inscriptions.*')
    //     ->get();

    //     $recus = [];

    //     foreach ($inscriptions as $inscription) {
    //         $eleve  = $inscription->eleve;
    //         $classe = $inscription->classe->nom;
    //         $niveau = $inscription->classe->niveau->nom;

    //         // Types de frais à inclure
    //         $typesFrais = TypeFrais::whereIn('nom', [
    //             'Frais d\'inscription',
    //             'Scolarité',
    //             'Cantine',
    //             'Transport'
    //         ])->get();

    //         foreach ($typesFrais as $type) {
    //             if ($typeFraisId && $type->id != $typeFraisId) continue;
    //             if ($type->nom == 'Cantine' && !$inscription->cantine_active) continue;
    //             if ($type->nom == 'Transport' && !$inscription->transport_active) continue;

    //             // Tarif attendu pour le mois courant
    //             $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
    //                 ->where('ecole_id', $ecoleId)
    //                 ->where('niveau_id', $inscription->classe->niveau->id)
    //                 ->where('mois_id', $moisReference->id)
    //                 ->where('type_frais_id', $type->id)
    //                 ->first();

    //             $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;

    //             // ✅ Cumul attendu jusqu’au mois de référence
    //             $cumulAttendu = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
    //                 ->where('ecole_id', $ecoleId)
    //                 ->where('niveau_id', $inscription->classe->niveau->id)
    //                 ->where('type_frais_id', $type->id)
    //                 ->where('mois_id', '<=', $moisReference->id)
    //                 ->sum('montant');

    //             // ✅ Réduction applicable (uniquement pour scolarité = id 2)
    //             $reduction = 0;
    //             if ($type->id == 2) {
    //                 $reduction = Reduction::where('inscription_id', $inscription->id)
    //                     ->where('annee_scolaire_id', $anneeScolaireId)
    //                     ->where('ecole_id', $ecoleId)
    //                     ->where(function ($query) use ($type) {
    //                         $query->whereNull('type_frais_id') // réduction globale
    //                             ->orWhere('type_frais_id', $type->id); // réduction spécifique
    //                     })
    //                     ->sum('montant');
    //             }

    //             // Appliquer réduction sur cumul et total annuel
    //             if ($type->id == 2) {
    //                 $cumulAttendu = max(0, $cumulAttendu - $reduction);
    //             }

    //             // ✅ Paiements cumulés
    //             $cumulPaye = PaiementDetail::where('inscription_id', $inscription->id)
    //                 ->where('type_frais_id', $type->id)
    //                 ->sum('montant');

    //             // Montant payé et reste pour le mois
    //             if ($cumulPaye >= $cumulAttendu) {
    //                 $montantPayeMois = $montantAttenduMois;
    //                 $resteMois = 0;
    //             } else {
    //                 $resteCumul      = $cumulAttendu - $cumulPaye;
    //                 $montantPayeMois = max(0, $montantAttenduMois - $resteCumul);
    //                 $resteMois       = $montantAttenduMois - $montantPayeMois;
    //             }

    //             // ✅ Total annuel attendu (avant réduction)
    //             $tarifsAnnee = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
    //                 ->where('ecole_id', $ecoleId)
    //                 ->where('niveau_id', $inscription->classe->niveau->id)
    //                 ->where('type_frais_id', $type->id)
    //                 ->get();

    //             $totalAttenduAnnee = $tarifsAnnee->sum('montant');

    //             // Appliquer réduction sur total annuel (si scolarité)
    //             if ($type->id == 2) {
    //                 $totalAttenduAnnee = max(0, $totalAttenduAnnee - $reduction);
    //             }

    //             $totalPayeAnnee = $cumulPaye;
    //             $resteTotal     = max(0, $totalAttenduAnnee - $totalPayeAnnee);

    //             // Inclure uniquement si un reste à payer pour le mois
    //             if ($resteMois > 0) {
    //                 $recus[] = [
    //                     'parent'          => $eleve->parent_nom ?? '-',
    //                     'eleve'           => $eleve->nom . ' ' . $eleve->prenom,
    //                     'classe'          => $classe,
    //                     'niveau'          => $niveau,
    //                     'mois'            => $moisReference->nom,
    //                     'type'            => $type->nom,
    //                     'montant_attendu' => $montantAttenduMois,
    //                     'montant_paye'    => $montantPayeMois,
    //                     'reste_mois'      => $resteMois,
    //                     'reste_total'     => $resteTotal
    //                 ];
    //             }
    //         }
    //     }

    //     $pdf = Pdf::loadView('dashboard.documents.scolarite.relance-form', [
    //         'recus'      => $recus,
    //         'mois'       => $moisReference ? $moisReference->nom : 'Tous mois',
    //         'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous types'
    //     ])->setPaper('A4', 'portrait');

    //     return $pdf->stream('relance_paiements.pdf');
    // }

public function imprimerRelance(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'type_frais_id' => 'nullable|exists:type_frais,id'
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');
    $userId = Auth::id();

    $moisReference = MoisScolaire::find($request->date_reference);
    $typeFraisId   = $request->type_frais_id;

    Log::info("=== Début impression relance ===", [
        'classe_id' => $request->classe_id,
        'mois_reference' => $moisReference->nom ?? 'inconnu',
        'type_frais_id' => $typeFraisId,
        'annee_scolaire_id' => $anneeScolaireId,
        'ecole_id' => $ecoleId
    ]);

    $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
    ->where('inscriptions.classe_id', $request->classe_id)
    ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)  // <-- ici
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

        // ✅ Déterminer le mois d’inscription via created_at
        $moisInscriptionNumero = $inscription->created_at->format('n'); // ex: 9 pour septembre
        $moisInscription = MoisScolaire::where('numero', $moisInscriptionNumero)->first();
        $moisInscriptionId = $moisInscription ? $moisInscription->id : 1;

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

            // --- MONTANT ATTENDU DU MOIS ---
            $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $inscription->classe->niveau->id)
                ->where('mois_id', $moisReference->id)
                ->where('type_frais_id', $type->id)
                ->first();

            $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;

            // --- CUMUL ATTENDU (à partir du mois d'inscription) ---
            $cumulAttendu = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $inscription->classe->niveau->id)
                ->where('type_frais_id', $type->id)
                ->whereBetween('mois_id', [$moisInscriptionId, $moisReference->id])
                ->sum('montant');

            // --- RÉDUCTION ---
            $reduction = 0;
            if ($type->id == 2) {
                $reduction = Reduction::where('inscription_id', $inscription->id)
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where(function ($query) use ($type) {
                        $query->whereNull('type_frais_id')
                            ->orWhere('type_frais_id', $type->id);
                    })
                    ->sum('montant');
            }

            if ($type->id == 2) {
                $cumulAttendu = max(0, $cumulAttendu - $reduction);
            }

            // --- CUMUL PAYÉ ---
            $cumulPaye = PaiementDetail::where('inscription_id', $inscription->id)
                ->where('type_frais_id', $type->id)
                ->sum('montant');

            // --- LOG INTERMÉDIAIRE ---
            Log::info("Calcul pour {$eleve->nom} {$eleve->prenom} ({$type->nom})", [
                'mois_inscription_numero' => $moisInscriptionNumero,
                'cumul_attendu' => $cumulAttendu,
                'cumul_paye' => $cumulPaye,
                'reduction' => $reduction,
                'montant_attendu_mois' => $montantAttenduMois,
            ]);

            // --- MONTANT PAYÉ ET RESTE DU MOIS ---
            if ($cumulPaye >= $cumulAttendu) {
                $montantPayeMois = $montantAttenduMois;
                $resteMois = 0;
            } else {
                $resteCumul = $cumulAttendu - $cumulPaye;
                $montantPayeMois = max(0, $montantAttenduMois - $resteCumul);
                $resteMois = $montantAttenduMois - $montantPayeMois;
            }

            // --- TOTAL ANNUEL (depuis mois d'inscription) ---
            $tarifsAnnee = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $inscription->classe->niveau->id)
                ->where('type_frais_id', $type->id)
                ->where('mois_id', '>=', $moisInscriptionId)
                ->get();

            $totalAttenduAnnee = $tarifsAnnee->sum('montant');
            if ($type->id == 2) {
                $totalAttenduAnnee = max(0, $totalAttenduAnnee - $reduction);
            }

            $totalPayeAnnee = $cumulPaye;
            $resteTotal = max(0, $totalAttenduAnnee - $totalPayeAnnee);

            if ($resteMois > 0) {
                $recus[] = [
                    'parent'          => $eleve->parent_nom ?? '-',
                    'eleve'           => $eleve->nom . ' ' . $eleve->prenom,
                    'classe'          => $classe,
                    'niveau'          => $niveau,
                    'mois'            => $moisReference->nom,
                    'type'            => $type->nom,
                    'montant_attendu' => $montantAttenduMois,
                    'montant_paye'    => $montantPayeMois,
                    'reste_mois'      => $resteMois,
                    'reste_total'     => $resteTotal
                ];
            }
        }
    }

    $pdf = Pdf::loadView('dashboard.documents.scolarite.relance-form', [
        'recus'      => $recus,
        'mois'       => $moisReference ? $moisReference->nom : 'Tous mois',
        'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous types'
    ])->setPaper('A4', 'portrait');

    return $pdf->stream('relance_paiements.pdf');
}







   


// public function export(Request $request)
// {
//     $request->validate([
//         'classe_id' => 'required|exists:classes,id',
//         'date_reference' => 'nullable|exists:mois_scolaires,id',
//         'type_frais_id' => 'nullable|exists:type_frais,id',
//         'format' => 'required|in:pdf,excel'
//     ]);

//     try {
//         $ecoleId = session('current_ecole_id'); 
//         $anneeScolaireId = session('current_annee_scolaire_id');

//         $moisReference = $request->date_reference 
//             ? MoisScolaire::find($request->date_reference) 
//             : null;

//         $typeFraisId = $request->type_frais_id;

//         $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
//             ->where('inscriptions.classe_id', $request->classe_id)
//             ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
//             ->where('inscriptions.ecole_id', $ecoleId)
//             ->where('inscriptions.statut', 'active')
//             ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
//             ->orderBy('eleves.nom')
//             ->orderBy('eleves.prenom')
//             ->select('inscriptions.*')
//             ->get();

//         $moisScolaires = MoisScolaire::orderBy('numero')->get();

//         $result = [];

//         foreach ($inscriptions as $inscription) {
//             $eleve = $inscription->eleve;
//             $classe = $inscription->classe->nom;
//             $niveau = $inscription->classe->niveau->nom;

//             $typesFrais = TypeFrais::whereIn('nom', [
//                 'Frais d\'inscription',
//                 'Scolarité',
//                 'Cantine',
//                 'Transport'
//             ])->get();

//             $fraisData = [];

//             foreach ($typesFrais as $type) {
//                 if ($typeFraisId && $type->id != $typeFraisId) continue;
//                 if ($type->nom == 'Cantine' && !$inscription->cantine_active) continue;
//                 if ($type->nom == 'Transport' && !$inscription->transport_active) continue;

//                 $detailsMois = [];
//                 $cumulAttendu = 0;

//                 foreach ($moisScolaires as $mois) {
//                     if ($moisReference && $mois->id != $moisReference->id) continue;

//                     $tarif = TarifMensuel::where([
//                         'annee_scolaire_id' => $anneeScolaireId,
//                         'ecole_id' => $ecoleId,
//                         'niveau_id' => $inscription->classe->niveau->id,
//                         'type_frais_id' => $type->id,
//                         'mois_id' => $mois->id
//                     ])->first();

//                     $montantAttendu = $tarif ? $tarif->montant : 0;
//                     $cumulAttendu += $montantAttendu;

//                     // Appliquer réduction si Scolarité (id = 2 ou par type_frais_id)
//                     if ($type->nom == 'Scolarité') {
//                         $reduction = Reduction::where('inscription_id', $inscription->id)
//                             ->where('annee_scolaire_id', $anneeScolaireId)
//                             ->where('ecole_id', $ecoleId)
//                             ->where(function($q) use ($type) {
//                                 $q->whereNull('type_frais_id')
//                                   ->orWhere('type_frais_id', $type->id);
//                             })
//                             ->sum('montant');

//                         $montantAttendu = max(0, $montantAttendu - $reduction);
//                     }

//                     $cumulPaye = PaiementDetail::where('inscription_id', $inscription->id)
//                         ->where('type_frais_id', $type->id)
//                         ->sum('montant');

//                     $resteMois = max(0, $montantAttendu - max(0, $cumulAttendu - $cumulPaye));
//                     $montantPayeMois = $montantAttendu - $resteMois;

//                     if ($resteMois > 0 || $montantPayeMois > 0) {
//                         $detailsMois[] = [
//                             'mois' => $mois->nom,
//                             'montant_attendu' => $montantAttendu,
//                             'montant_paye' => $montantPayeMois,
//                             'reste_mois' => $resteMois
//                         ];
//                     }
//                 }

//                 $totalAttendu = collect($detailsMois)->sum('montant_attendu');
//                 $totalPaye = collect($detailsMois)->sum('montant_paye');
//                 $resteTotal = max(0, $totalAttendu - $totalPaye);

//                 if ($totalAttendu > 0) {
//                     $fraisData[$type->nom] = [
//                         'details_mois' => $detailsMois,
//                         'total_attendu' => $totalAttendu,
//                         'total_paye' => $totalPaye,
//                         'reste_total' => $resteTotal,
//                         'statut' => $resteTotal > 0 ? 'En retard' : 'À jour'
//                     ];
//                 }
//             }

//             if (!empty($fraisData)) {
//                 $result[] = [
//                     'eleve' => $eleve->nom . ' ' . $eleve->prenom,
//                     'classe' => $classe,
//                     'niveau' => $niveau,
//                     'type' => implode(', ', array_keys($fraisData)),
//                     'details_mois' => collect($fraisData)->pluck('details_mois')->flatten(1),
//                     'total_attendu' => collect($fraisData)->sum('total_attendu'),
//                     'total_paye' => collect($fraisData)->sum('total_paye'),
//                     'reste_total' => collect($fraisData)->sum('reste_total'),
//                     'statut' => collect($fraisData)->pluck('statut')->contains('En retard') ? 'En retard' : 'À jour'
//                 ];
//             }
//         }

//         $filters = [
//             'classe' => Classe::find($request->classe_id)->nom,
//             'mois' => $moisReference ? $moisReference->nom : 'Tous',
//             'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous'
//         ];

//         if ($request->format === 'excel') {
//             return Excel::download(new RelanceExport($result, $filters), 
//                 'relance_paiements_' . date('Y-m-d') . '.xlsx');
//         }

//         if ($request->format === 'pdf') {
//             $pdf = PDF::loadView('dashboard.documents.liste-relance', [
//                 'data' => $result,
//                 'filters' => $filters,
//                 'title' => 'Relance des Paiements',
//                 'date' => date('d/m/Y')
//             ])->setPaper('A4', 'landscape');

//             return $pdf->download('relance_paiements_' . date('Y-m-d') . '.pdf');
//         }

//         return redirect()->back()->with('error', 'Format non supporté');

//     } catch (\Exception $e) {
//         Log::error("Erreur export relance: " . $e->getMessage());
//         return redirect()->back()->with('error', 'Erreur lors de l\'exportation: ' . $e->getMessage());
//     }
// }

public function export(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'nullable|exists:mois_scolaires,id',
        'type_frais_id' => 'nullable|exists:type_frais,id',
        'format' => 'required|in:pdf,excel'
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
            'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous'
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

        // Récupérer toutes les inscriptions actives
        // $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
        //     ->where('classe_id', $request->classe_id)
        //     ->where('annee_scolaire_id', $anneeScolaireId)
        //     ->where('statut', 'active')
        //     ->get();

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
