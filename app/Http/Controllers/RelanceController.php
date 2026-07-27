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

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();
    
        $moisScolaires = MoisScolaire::orderBy('numero')->get();
        
        // Récupérer les tarifs pour le filtre
        $tarifs = Tarif::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->with('typeFrais')
            ->get();

        return view('dashboard.pages.comptabilites.relances', compact('classes', 'moisScolaires', 'tarifs'));
    }

    public function getRelanceData(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'date_reference' => 'required|exists:mois_scolaires,id',
            'tarif_id' => 'nullable|exists:tarifs,id',
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

            $moisReference = MoisScolaire::find($request->date_reference);
            if (!$moisReference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mois de référence invalide.'
                ]);
            }

            $tarifId = $request->tarif_id;
            $tarif = $tarifId ? Tarif::with('typeFrais')->find($tarifId) : null;

            if (!$tarif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif invalide.'
                ]);
            }

            // Récupérer les inscriptions de la classe
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

            // Récupérer le type de frais du tarif
            $typeFrais = $tarif->typeFrais;
            $typeFraisNom = $typeFrais ? $typeFrais->nom : '';

            // Récupérer tous les mois jusqu'au mois de référence
            $moisScolaires = MoisScolaire::where('numero', '<=', $moisReference->numero)
                ->orderBy('numero')
                ->get();

            $result = [];

            foreach ($inscriptions as $inscription) {
                $eleve = $inscription->eleve;
                $classe = $inscription->classe;
                $niveau = $classe->niveau;

                // Vérifier si le tarif est pour le niveau de l'élève (NULL = tous les niveaux)
                if ($tarif->niveau_id && $tarif->niveau_id != $niveau->id) {
                    continue;
                }

                // Vérifier si le service est actif pour Cantine et Transport
                if ($typeFraisNom == 'Cantine' && !$inscription->cantine_active) {
                    continue;
                }
                if ($typeFraisNom == 'Transport' && !$inscription->transport_active) {
                    continue;
                }

                // Mois d'inscription (pour Cantine et Transport)
                $moisInscriptionNumero = (int) $inscription->created_at->format('n');
                $jourInscription = (int) $inscription->created_at->format('j');

                // Récupérer le mois d'inscription
                $moisInscription = MoisScolaire::where('numero', $moisInscriptionNumero)->first();
                $moisInscriptionId = $moisInscription ? $moisInscription->id : null;

                // Récupérer les tarifs mensuels pour ce tarif et ce niveau
                $tarifsMensuels = TarifMensuel::where('annee_scolaire_id', $anneeScolaireId)
                    ->where('ecole_id', $ecoleId)
                    ->where('tarif_id', $tarif->id)
                    ->where(function($q) use ($niveau) {
                        $q->where('niveau_id', $niveau->id)
                          ->orWhereNull('niveau_id');
                    })
                    ->get()
                    ->keyBy('mois_id');

                if ($tarifsMensuels->isEmpty()) {
                    continue;
                }

                // Calcul du montant pour le mois de référence
                $montantMensuel = 0;
                $tarifMoisRef = $tarifsMensuels->get($moisReference->id);
                if ($tarifMoisRef) {
                    $montantMensuel = $tarifMoisRef->montant;

                    // Demi-tarif pour Cantine et Transport si inscription après le 15
                    if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                        if ($moisReference->numero == $moisInscriptionNumero && $jourInscription > 15) {
                            $montantMensuel = $montantMensuel / 2;
                        }
                        // Ignorer les mois avant l'inscription
                        if ($moisReference->numero < $moisInscriptionNumero) {
                            $montantMensuel = 0;
                        }
                    }
                }

                if ($montantMensuel <= 0) {
                    continue;
                }

                // Calcul du cumul attendu total (tous les mois jusqu'au mois de référence)
                $cumulAttendu = 0;
                $cumulAttenduAvant = 0;

                foreach ($moisScolaires as $mois) {
                    // Vérifier si le mois est avant l'inscription pour Cantine/Transport
                    if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                        if ($mois->numero < $moisInscriptionNumero) {
                            continue;
                        }
                    }

                    $tarifMensuel = $tarifsMensuels->get($mois->id);
                    if (!$tarifMensuel) {
                        continue;
                    }

                    $montant = $tarifMensuel->montant;

                    // Demi-tarif pour Cantine et Transport
                    if (in_array($typeFraisNom, ['Cantine', 'Transport'])) {
                        if ($mois->numero == $moisInscriptionNumero && $jourInscription > 15) {
                            $montant = $montant / 2;
                        }
                    }

                    // Cumul avant le mois de référence
                    if ($mois->id < $moisReference->id) {
                        $cumulAttenduAvant += $montant;
                    }

                    // Cumul total jusqu'au mois de référence
                    if ($mois->id <= $moisReference->id) {
                        $cumulAttendu += $montant;
                    }
                }

                // Appliquer la réduction si c'est la Scolarité
                $reduction = 0;
                if ($typeFraisNom == 'Scolarité') {
                    $reduction = Reduction::where('inscription_id', $inscription->id)
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId)
                        ->where(function($q) use ($tarif) {
                            $q->whereNull('tarif_id')
                                ->orWhere('tarif_id', $tarif->id);
                        })
                        ->sum('montant');
                    
                    // Répartir la réduction proportionnellement sur le cumul
                    if ($reduction > 0 && $cumulAttendu > 0) {
                        $cumulAttendu = max(0, $cumulAttendu - $reduction);
                    }
                }

                // Total payé (tous les paiements pour ce tarif)
                $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)
                    ->where('tarif_id', $tarif->id)
                    ->sum('montant');

                // Payé avant le mois de référence (en utilisant la date)
                $payeAvant = PaiementDetail::where('inscription_id', $inscription->id)
                    ->where('tarif_id', $tarif->id)
                    ->where('created_at', '<', $moisReference->created_at ?? Carbon::now())
                    ->sum('montant');

                // Reste à payer pour le mois
                $resteMois = max(0, $montantMensuel - ($totalPaye - $payeAvant));

                // Reste à payer cumulé
                $resteCumul = max(0, $cumulAttendu - $totalPaye);

                // Statut
                $statut = $resteMois <= 0 ? 'À jour' : 'En retard';

                // Filtre par montant du reste cumulé
                if ($request->montant_min || $request->montant_max) {
                    $montantMin = $request->montant_min ? (float) $request->montant_min : 0;
                    $montantMax = $request->montant_max ? (float) $request->montant_max : PHP_FLOAT_MAX;
                    
                    if ($resteCumul < $montantMin || $resteCumul > $montantMax) {
                        continue;
                    }
                }

                $result[] = [
                    'eleve' => $eleve->nom . ' ' . $eleve->prenom,
                    'classe' => $classe->nom,
                    'niveau' => $niveau->nom,
                    'cantine_active' => $inscription->cantine_active,
                    'transport_active' => $inscription->transport_active,
                    'tarif_libelle' => $tarif->libelle,
                    'type_frais' => $typeFraisNom,
                    'montant_mois' => $montantMensuel,
                    'cumul_attendu' => $cumulAttendu,
                    'total_paye' => $totalPaye,
                    'reste_mois' => $resteMois,
                    'reste_cumul' => $resteCumul,
                    'statut' => $statut,
                    'mois_reference' => $moisReference->nom,
                    'reduction' => $reduction,
                    'telephone' => $eleve->telephone ?? '',
                    'parent_telephone' => $eleve->parent_telephone ?? '',
                    'parent_nom' => $eleve->parent_nom ?? '',
                    'parent_prenom' => $eleve->parent_prenom ?? '',
                    'id' => $eleve->id
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'classe' => Classe::with('niveau')->find($request->classe_id)->nom,
                'mois_reference' => $moisReference->nom,
                'tarif_libelle' => $tarif->libelle,
                'montant_min' => $request->montant_min,
                'montant_max' => $request->montant_max
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur getRelanceData: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
            ]);
        }
    }

    // Les autres méthodes...
    public function imprimerRelance(Request $request) { /* ... */ }
    public function export(Request $request) { /* ... */ }
    public function sendSms(Request $request) { /* ... */ }
}