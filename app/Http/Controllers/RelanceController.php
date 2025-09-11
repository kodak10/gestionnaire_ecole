<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\PaiementDetail;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use App\Models\UserAnneeScolaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        'date_reference' => 'nullable|exists:mois_scolaires,id', // mois de référence
        'type_frais_id' => 'nullable|exists:type_frais,id'       // filtrer par type de frais si nécessaire
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

        $moisReference = $request->date_reference
            ? MoisScolaire::find($request->date_reference)
            : MoisScolaire::orderBy('numero', 'desc')->first();

        if (!$moisReference) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun mois scolaire trouvé pour cette année.'
            ]);
        }

        // Récupérer toutes les inscriptions actives
        $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
            ->where('classe_id', $request->classe_id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active')
            ->get();

        $moisScolaires = MoisScolaire::orderBy('numero')->get();
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
                'eleve' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                'classe' => $inscription->classe->nom,
                'niveau' => $niveau->nom,
                'frais' => $fraisData
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

    $inscriptions = Inscription::with(['eleve', 'classe.niveau'])
        ->where('classe_id', $request->classe_id)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('statut', 'active')
        ->get();

    $recus = [];

    foreach ($inscriptions as $inscription) {
        $eleve = $inscription->eleve;
        $classe = $inscription->classe->nom;
        $niveau = $inscription->classe->niveau->nom;

        // Types de frais à inclure
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

            // Tarif attendu pour le mois
            $tarifMois = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $inscription->classe->niveau->id)
                ->where('mois_id', $moisReference->id)
                ->where('type_frais_id', $type->id)
                ->first();

            $montantAttenduMois = $tarifMois ? $tarifMois->montant : 0;

            // Calculer le montant déjà payé pour le mois
            $moisDebut = \Carbon\Carbon::createFromDate(null, $moisReference->numero, 1)->startOfMonth();
            $moisFin = (clone $moisDebut)->endOfMonth();

            $paiementPayeMois = PaiementDetail::where('inscription_id', $inscription->id)
                ->where('type_frais_id', $type->id)
                ->whereHas('paiement', function($q) use ($moisDebut, $moisFin) {
                    $q->whereBetween('created_at', [$moisDebut, $moisFin]);
                })
                ->sum('montant');

            // Total annuel attendu
            $tarifsAnnee = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                ->where('ecole_id', $ecoleId)
                ->where('niveau_id', $inscription->classe->niveau->id)
                ->where('type_frais_id', $type->id)
                ->get();

            $totalAttenduAnnee = $tarifsAnnee->sum('montant');

            // Total payé sur l'année pour ce type de frais
            $totalPayeAnnee = PaiementDetail::where('inscription_id', $inscription->id)
                ->where('type_frais_id', $type->id)
                ->sum('montant');

            $resteMois = max(0, $montantAttenduMois - $paiementPayeMois);
            $resteTotal = max(0, $totalAttenduAnnee - $totalPayeAnnee);

            // Inclure uniquement si un reste à payer pour le mois
            if ($resteMois > 0) {
                $recus[] = [
                    'parent' => $eleve->parent_nom ?? '-',
                    'eleve' => $eleve->prenom . ' ' . $eleve->nom,
                    'classe' => $classe,
                    'niveau' => $niveau,
                    'mois' => $moisReference->nom,
                    'type' => $type->nom,
                    'montant_attendu' => $montantAttenduMois,
                    'montant_paye' => $paiementPayeMois,
                    'reste_mois' => $resteMois,
                    'reste_total' => $resteTotal
                ];
            }
        }
    }

    $pdf = Pdf::loadView('dashboard.documents.scolarite.relance-form', [
        'recus' => $recus,
        'mois' => $moisReference ? $moisReference->nom : 'Tous mois',
        'type_frais' => $typeFraisId ? TypeFrais::find($typeFraisId)->nom : 'Tous types'
    ])->setPaper('A4', 'portrait');

    return $pdf->stream('relance_paiements.pdf');
}





}
