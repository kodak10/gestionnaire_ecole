<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Carbon\Carbon;
use App\Models\TarifMensuel;
use App\Models\MoisScolaire;

class TransportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.transports.index', compact('classes'));
    }

    public function GestionTransport()
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
            ->ordered()
            ->get();

        return view('dashboard.pages.transports.gestion', compact('classes'));
    }   

    public function elevesByClasseTransport(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        try {
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');

            $eleves = Inscription::with('eleve')
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $request->classe_id)
                ->where('transport_active', true)
                ->get()
                ->sortBy(function($inscription) {
                    return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
                })
                ->values()
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                        'matricule' => $inscription->eleve->matricule,
                        'transport_active' => $inscription->transport_active
                    ];
                });

            return response()->json($eleves);

        } catch (\Exception $e) {
            Log::error('Erreur elevesByClasseTransport', ['message' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

public function getEleveTransport(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'transportType'])
            ->findOrFail($request->inscription_id);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveauId = $inscription->classe->niveau->id;

        // Récupérer tous les mois scolaires
        $moisScolaires = MoisScolaire::orderBy('id')->get();

        // Récupérer les types de transport
        $typesTransport = TypeFrais::where('nom', 'like', '%Transport%')
            ->whereHas('tarifsMensuels', function($q) use ($ecoleId, $anneeScolaireId, $niveauId) {
                $q->where('ecole_id', $ecoleId)
                  ->where('annee_scolaire_id', $anneeScolaireId)
                  ->where('niveau_id', $niveauId);
            })
            ->with(['tarifsMensuels' => function($q) use ($ecoleId, $anneeScolaireId, $niveauId) {
                $q->where('ecole_id', $ecoleId)
                  ->where('annee_scolaire_id', $anneeScolaireId)
                  ->where('niveau_id', $niveauId)
                  ->orderBy('mois_id');
            }])
            ->get();

        $transportData = [];
        $selectedTypeId = $inscription->transport_type_id;

        // Récupérer la date de début du transport
        $transportStartDate = $inscription->transport_start_date;

        // Si la date de début est null, utiliser le début de l'année scolaire
        if (!$transportStartDate) {
            $anneeScolaire = \App\Models\AnneeScolaire::find($anneeScolaireId);
            if ($anneeScolaire) {
                $transportStartDate = $anneeScolaire->date_debut;
            } else {
                $transportStartDate = now()->toDateString();
            }
        }

        foreach ($typesTransport as $type) {
            $tarifsMensuels = $type->tarifsMensuels;
            $moisCount = $tarifsMensuels->count();
            $montantMensuel = $tarifsMensuels->first()->montant ?? 0;
            $estActif = $moisCount > 0;

            $montantTotal = 0;
            $moisPayes = 0;
            $moisRestants = 0;
            $montantPaye = 0;
            $montantReste = 0;
            $moisEcoules = 0;

            if ($inscription->transport_type_id == $type->id && $transportStartDate && $montantMensuel > 0) {
                $startDate = Carbon::parse($transportStartDate);
                $today = Carbon::now();
                
                // Calculer le nombre de mois entre la date de début et aujourd'hui
                $moisEcoules = $startDate->diffInMonths($today);
                
                // Ajouter 1 pour inclure le mois en cours si la date de début est passée
                if ($startDate->lte($today)) {
                    $moisEcoules++;
                }
                
                // S'assurer que le nombre de mois n'est pas négatif
                $moisEcoules = max(0, $moisEcoules);
                
                // Limiter au nombre de mois avec tarif
                $moisEcoules = min($moisEcoules, $moisCount);
                
                // Récupérer les paiements déjà effectués
                $moisPayes = PaiementDetail::where('inscription_id', $inscription->id)
                    ->where('type_frais_id', $type->id)
                    ->count();
                
                $moisRestants = max(0, $moisEcoules - $moisPayes);
                $montantTotal = $moisEcoules * $montantMensuel;
                $montantPaye = $moisPayes * $montantMensuel;
                $montantReste = max(0, $montantTotal - $montantPaye);
            }

            $transportData[] = [
                'type_id' => $type->id,
                'type_nom' => $type->nom,
                'montant_mensuel' => (float) $montantMensuel,
                'est_actif' => $estActif,
                'est_selectionne' => ($selectedTypeId == $type->id),
                'mois_ecoules' => (int) $moisEcoules,
                'mois_payes' => (int) $moisPayes,
                'mois_restants' => (int) $moisRestants,
                'montant_total' => (float) $montantTotal,
                'montant_paye' => (float) $montantPaye,
                'montant_reste' => (float) $montantReste,
            ];
        }

        // Si aucun type n'est sélectionné mais qu'il y en a des disponibles, sélectionner le premier
        $hasSelected = false;
        foreach ($transportData as &$item) {
            if ($item['est_selectionne']) {
                $hasSelected = true;
                break;
            }
        }
        
        if (!$hasSelected && count($transportData) > 0) {
            $transportData[0]['est_selectionne'] = true;
            $selectedTypeId = $transportData[0]['type_id'];
        }

        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'transports' => $transportData,
            'selected_transport_id' => $selectedTypeId,
            'transport_start_date' => $transportStartDate
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur getEleveTransport', [
            'message' => $e->getMessage(), 
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false, 
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}

    public function updateTransportType(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'transport_type_id' => 'required|exists:type_frais,id'
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $inscription = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($request->inscription_id);

        // Si la date de début n'est pas définie, la définir à aujourd'hui
        $transportStartDate = $inscription->transport_start_date ?? now()->toDateString();

        $inscription->update([
            'transport_type_id' => $request->transport_type_id,
            'transport_active' => true,
            'transport_start_date' => $transportStartDate
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Type de transport mis à jour avec succès',
            'transport_start_date' => $transportStartDate
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur updateTransportType', ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Enregistrer un paiement transport
     */
public function store(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
        'date_paiement' => 'required|date',
    ]);

    try {
        DB::beginTransaction();

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $inscription = Inscription::with('eleve', 'classe.niveau')
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($request->inscription_id);

        if (!$inscription->transport_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'a pas le transport actif.'
            ]);
        }

        // Récupérer le tarif mensuel
        $tarifMensuel = TarifMensuel::where([
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'niveau_id' => $inscription->classe->niveau->id,
            'type_frais_id' => $inscription->transport_type_id
        ])->first();

        if (!$tarifMensuel) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif mensuel non trouvé pour cette configuration.'
            ]);
        }

        // Vérifier combien de mois sont déjà payés
        $moisPayes = PaiementDetail::where('inscription_id', $inscription->id)
            ->whereHas('paiement', function($q) use ($inscription) {
                $q->where('type_frais_id', $inscription->transport_type_id);
            })
            ->count();

        // Calculer le nombre total de mois depuis le début
        if ($inscription->transport_start_date) {
            $startDate = Carbon::parse($inscription->transport_start_date);
            $today = Carbon::now();
            $moisTotal = $startDate->diffInMonths($today) + 1;
            $moisRestants = max(0, $moisTotal - $moisPayes);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Date de début du transport non définie.'
            ]);
        }

        if ($moisRestants <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tous les mois sont déjà payés.'
            ]);
        }

        // Montant à payer = tarif mensuel * nombre de mois restants
        $montantAPayer = $tarifMensuel->montant * $moisRestants;

        if ($request->montant_transport && $request->montant_transport < $montantAPayer) {
            // Si l'utilisateur saisit un montant partiel
            $montantAPayer = $request->montant_transport;
        }

        // 1. Créer le paiement
        $paiement = Paiement::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'montant' => $montantAPayer,
            'mode_paiement' => $request->mode_paiement,
            'reference' => $request->reference,
            'user_id' => auth()->id(),
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
        ]);

        // 2. Créer le détail du paiement
        PaiementDetail::create([
            'inscription_id' => $inscription->id,
            'type_frais_id' => $inscription->transport_type_id,
            'paiement_id' => $paiement->id,
            'montant' => $montantAPayer
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Paiement Transport enregistré avec succès.',
            'paiement_id' => $paiement->id,
            'montant_paye' => $montantAPayer
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur storePaiementTransport', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage()
        ]);
    }
}

    public function deletePaiement(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id'
        ]);

        try {
            DB::beginTransaction();

            PaiementDetail::where('paiement_id', $request->paiement_id)->delete();
            
            $paiement = Paiement::findOrFail($request->paiement_id);
            $paiement->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur deletePaiement Transport', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ]);
        }
    }

    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with([
            'details.typeFrais',
            'inscription.eleve',
            'inscription.classe.niveau',
            'typeFrais',
            'user',
            'ecole',
            'anneeScolaire'
        ])->find($paiementId);

        if (!$paiement) {
            abort(404, "Paiement introuvable.");
        }

        $inscription = $paiement->inscription;
        if (!$inscription) {
            abort(404, "Inscription introuvable pour ce paiement.");
        }

        $eleve = $inscription->eleve;
        $classe = $inscription->classe;
        $ecole = $paiement->ecole;

        $montant_total = $paiement->montant;
        $typeFrais = $paiement->typeFrais;

        $pdf = PDF::loadView('dashboard.documents.transport.recu_paiement', compact(
            'paiement',
            'eleve',
            'classe',
            'ecole',
            'montant_total',
            'typeFrais'
        ));

        return $pdf->stream("recu_transport_{$paiement->id}.pdf");
    }
}