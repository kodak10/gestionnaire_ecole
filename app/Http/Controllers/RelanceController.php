<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use App\Models\UserAnneeScolaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RelanceController extends Controller
{
    public function index()
    {
        $classes = Classe::with('niveau')->orderBy('nom')->get();
        $userId = Auth::id();
        $ecoleId = Auth::user()->ecole_id;

        $userAnnee = UserAnneeScolaire::where('user_id', $userId)
            ->where('ecole_id', $ecoleId)
            ->latest('id')
            ->first();

        $moisScolaires = [];
        if ($userAnnee) {
            $moisScolaires = MoisScolaire::orderBy('numero')
                ->get();
        }

        $typeFrais = TypeFrais::get();

        return view('dashboard.pages.comptabilites.relances', compact('classes', 'moisScolaires', 'typeFrais'));
    }

    public function getRelanceData(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'nullable|exists:mois_scolaires,id', // maintenant c'est un mois
            'type_frais_id' => 'nullable|exists:type_frais,id'       // ajout du type de frais
        ]);
        Log::info('getRelanceData called with: ', $request->all());

        try {
            $userId = Auth::id();
            $ecoleId = Auth::user()->ecole_id;

            $userAnnee = UserAnneeScolaire::where('user_id', $userId)
                ->where('ecole_id', $ecoleId)
                ->latest('id')
                ->first();

            if (!$userAnnee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie pour cet utilisateur.'
                ]);
            }

            $anneeScolaireId = $userAnnee->annee_scolaire_id;

            // 🔹 Récupérer le mois sélectionné ou le dernier mois
            $moisReference = $request->date_reference
                ? MoisScolaire::find($request->date_reference)
                : MoisScolaire::orderBy('numero', 'desc')->first();

            if (!$moisReference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun mois scolaire trouvé pour cette année.'
                ]);
            }

            // 🔹 Récupérer les inscriptions
            $inscriptions = Inscription::with(['eleve', 'classe.niveau', 'paiements'])
            ->where('classe_id', $request->classe_id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->when(isset($request->type_frais_id) && in_array($request->type_frais_id, [3, 4]), function($query) use ($request) {
                if ($request->type_frais_id == 3) { // Cantine
                    $query->where('cantine_active', true);
                } elseif ($request->type_frais_id == 4) { // Transport
                    $query->where('transport_active', true);
                }
            })
            ->get();



            $result = [];

            foreach ($inscriptions as $inscription) {
                $niveau = $inscription->classe->niveau;

                // Tous les mois scolaires
                $moisScolaires = MoisScolaire::orderBy('numero')->get();

                // 🔹 Tarifs mensuels filtrés par type de frais si sélectionné
                $tarifsQuery = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('niveau_id', $niveau->id);

                if ($request->type_frais_id) {
                    $tarifsQuery->where('type_frais_id', $request->type_frais_id);
                }

                $tarifs = $tarifsQuery->get()->keyBy('mois_id');

                $totalAttendu = $tarifs->sum('montant');
                $totalPaye = $inscription->paiements->sum('montant');

                $detailsMois = [];
                $cumulAttendu = 0;

                foreach ($moisScolaires as $mois) {
                    $montantMois = $tarifs->has($mois->id) ? $tarifs[$mois->id]->montant : 0;
                    $cumulAttendu += $montantMois;

                    // 🔹 Ne considérer que les mois jusqu'au mois sélectionné
                    $estPasse = $mois->numero <= $moisReference->numero;

                    if ($estPasse) {
                        $statut = ($totalPaye >= $cumulAttendu) ? '✅ À jour' : '❌ En retard';

                        $detailsMois[] = [
                            'mois' => $mois->nom,
                            'attendu_cumul' => $cumulAttendu,
                            'statut' => $statut
                        ];
                    }
                }

                $statut = $this->determinerStatut($detailsMois);

                $result[] = [
                    'eleve' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                    'classe' => $inscription->classe->nom,
                    'niveau' => $niveau->nom,
                    'total_attendu' => $totalAttendu,
                    'total_paye' => $totalPaye,
                    'reste_a_payer' => max(0, $totalAttendu - $totalPaye),
                    'statut' => $statut,
                    'details_mois' => $detailsMois,
                    'en_retard_depuis' => $this->getMoisRetard($detailsMois)
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

    // private function getMoisRetard($detailsMois)
    // {
    //     foreach ($detailsMois as $detail) {
    //         if (strpos($detail['statut'], '❌') !== false) {
    //             return $detail['mois'];
    //         }
    //     }
    //     return null;
    // }

    private function getMoisRetard($detailsMois)
{
    foreach ($detailsMois as $detail) {
        if (strpos($detail['statut'], '❌') !== false) { // utiliser ['statut'] au lieu de ->statut
            return $detail['mois']; // utiliser ['mois'] au lieu de ->mois
        }
    }
    return null;
}



public function imprimerRelance(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id',
        'date_reference' => 'required|exists:mois_scolaires,id',
        'type_frais_id' => 'nullable|exists:type_frais,id'
    ]);

    $userId = Auth::id();
    $ecoleId = Auth::user()->ecole_id;

    $userAnnee = UserAnneeScolaire::where('user_id', $userId)
        ->where('ecole_id', $ecoleId)
        ->latest('id')
        ->first();

    if (!$userAnnee) {
        return redirect()->back()->with('error', 'Aucune année scolaire définie pour cet utilisateur.');
    }

    $anneeScolaireId = $userAnnee->annee_scolaire_id;
    $moisReference = MoisScolaire::find($request->date_reference);
    $typeFraisId = $request->type_frais_id;

    Log::info('imprimerRelance called with: ', $request->all());
    $typeFrais = $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous types';

    // 🔹 Récupération des inscriptions avec filtrage Cantine/Transport
    $inscriptions = Inscription::with(['eleve', 'classe.niveau', 'paiements'])
        ->where('classe_id', $request->classe_id)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('statut', 'active')
        ->when(in_array($typeFraisId, [3, 4]), function($query) use ($typeFraisId) {
            if ($typeFraisId == 3) { // Cantine
                $query->where('cantine_active', true);
            } elseif ($typeFraisId == 4) { // Transport
                $query->where('transport_active', true);
            }
        })
        ->get();

    $recus = [];

    foreach ($inscriptions as $inscription) {
        $eleve = $inscription->eleve;
        $classe = $inscription->classe->nom;
        $niveau = $inscription->classe->niveau->nom;

        // Tarif attendu pour le mois
        $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('niveau_id', $inscription->classe->niveau->id)
            ->where('mois_id', $moisReference->id)
            ->when($typeFraisId, fn($q) => $q->where('type_frais_id', $typeFraisId))
            ->first();

        $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;

        // Paiement cumulé pour le mois
        $paiementPayeMois = $inscription->paiements
            ->filter(fn($paiement) => !$typeFraisId || $paiement->type_frais_id == $typeFraisId)
            ->sum('montant');

        // Paiement total de l'année pour le type de frais
        $tarifsAnnee = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where('niveau_id', $inscription->classe->niveau->id)
            ->when($typeFraisId, fn($q) => $q->where('type_frais_id', $typeFraisId))
            ->get();

        $totalAttenduAnnee = $tarifsAnnee->sum('montant');
        $totalPayeAnnee = $paiementPayeMois;

        $resteMois = max(0, $montantAttenduMois - $paiementPayeMois);
        $resteTotal = max(0, $totalAttenduAnnee - $totalPayeAnnee);

        // ⚠️ On inclut uniquement si le reste du mois > 0
        if ($resteMois > 0) {
            $recus[] = [
                'parent' => $eleve->parent_nom ?? '-',
                'eleve' => $eleve->prenom . ' ' . $eleve->nom,
                'classe' => $classe,
                'mois' => $moisReference->nom,
                'type' => $typeFrais,
                'montant_attendu' => $montantAttenduMois,
                'montant_paye' => $paiementPayeMois,
                'reste_mois' => $resteMois,
                'reste_total' => $resteTotal
            ];
        }
    }

    $pdf = Pdf::loadView('dashboard.documents.scolarite.relance-form', [
    'recus' => $recus,
        'mois' => $moisReference ? $moisReference->nom : 'Tous mois',
        'type_frais' => $typeFrais
    ])->setPaper('A4', 'portrait');


    return $pdf->stream('relance_paiements.pdf');
}





}
