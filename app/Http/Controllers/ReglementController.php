<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReglementController extends Controller
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

        return view('dashboard.pages.comptabilites.reglement', compact('classes'));
    }

public function elevesByClasse(Request $request)
{
    $request->validate([
        'classe_id' => 'required|exists:classes,id'
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $eleves = Inscription::with('eleve')
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->where('classe_id', $request->classe_id)
        ->whereHas('eleve', fn($q) => $q->where('is_active', 1))
        ->get()
        ->map(fn($i) => [
            'inscription_id' => $i->id,  // Changé de 'id' à 'inscription_id'
            'nom_complet' => $i->eleve->nom . ' ' . $i->eleve->prenom,
            'matricule' => $i->eleve->matricule
        ]);

    return response()->json($eleves);
}

public function eleveData(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
            ->findOrFail($request->inscription_id);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveauId = $inscription->classe->niveau->id;

        // Récupérer les types de frais
        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
        $typeTransport = TypeFrais::where('nom', "Transport")->first();
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();

        // Récupérer les tarifs
        $tarifInscription = Tarif::where('type_frais_id', $typeInscription->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifTransport = Tarif::where('type_frais_id', $typeTransport->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifCantine = Tarif::where('type_frais_id', $typeCantine->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $montantInscription = $tarifInscription->montant ?? 0;
        $montantScolarite = $tarifScolarite->montant ?? 0;
        $montantTransport = $tarifTransport->montant ?? 0;
        $montantCantine = $tarifCantine->montant ?? 0;

        // Appliquer les réductions sur la scolarité
        $reduction = $inscription->reductions->sum('montant');
        $montantScolarite = max(0, $montantScolarite - $reduction);

        // Paiements liés avec les détails incluant le tarif et le libellé
        $paiements = Paiement::with(['details.tarif', 'details.tarif.typeFrais'])
            ->whereHas('details', fn($q) => $q->where('inscription_id', $inscription->id))
            ->orderByDesc('created_at')
            ->get();

        // Calculer les totaux payés par type
        $totalPayeInscription = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('tarif_id', $tarifInscription->id ?? 0)
            ->sum('montant');

        $totalPayeScolarite = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('tarif_id', $tarifScolarite->id ?? 0)
            ->sum('montant');

        $totalPayeTransport = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('tarif_id', $tarifTransport->id ?? 0)
            ->sum('montant');

        $totalPayeCantine = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('tarif_id', $tarifCantine->id ?? 0)
            ->sum('montant');

        $resteInscription = max(0, $montantInscription - $totalPayeInscription);
        $resteScolarite = max(0, $montantScolarite - $totalPayeScolarite);
        $resteTransport = max(0, $montantTransport - $totalPayeTransport);
        $resteCantine = max(0, $montantCantine - $totalPayeCantine);

        // Formater les paiements pour le JavaScript
        $paiementsFormatted = $paiements->map(function($paiement) {
            $details = $paiement->details->map(function($detail) {
                // Récupérer le libellé à partir du tarif
                $libelle = 'Inconnu';
                if ($detail->tarif) {
                    $libelle = $detail->tarif->libelle ?? 
                               ($detail->tarif->typeFrais->nom ?? 'Inconnu');
                }
                return [
                    'id' => $detail->id,
                    'montant' => $detail->montant,
                    'libelle' => $libelle,
                    'type_frais_id' => $detail->tarif->type_frais_id ?? null
                ];
            });

            return [
                'id' => $paiement->id,
                'montant' => $paiement->montant,
                'mode_paiement' => $paiement->mode_paiement,
                'created_at' => $paiement->created_at,
                'details' => $details
            ];
        });

        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'frais' => [
                'inscription' => $montantInscription,
                'scolarite' => $montantScolarite,
                'transport' => $montantTransport,
                'cantine' => $montantCantine
            ],
            'total_paye' => [
                'inscription' => $totalPayeInscription,
                'scolarite' => $totalPayeScolarite,
                'transport' => $totalPayeTransport,
                'cantine' => $totalPayeCantine
            ],
            'reste_a_payer' => [
                'inscription' => $resteInscription,
                'scolarite' => $resteScolarite,
                'transport' => $resteTransport,
                'cantine' => $resteCantine
            ],
            'reduction' => [
                'scolarite' => $reduction
            ],
            'paiements' => $paiementsFormatted
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur eleveData: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

    // public function storePaiement(Request $request)
    // {
    //     $request->validate([
    //         'inscription_id' => 'required|exists:inscriptions,id',
    //         'montant_inscription' => 'nullable|numeric|min:0',
    //         'montant_scolarite' => 'nullable|numeric|min:0',
    //         'montant_transport' => 'nullable|numeric|min:0',
    //         'montant_cantine' => 'nullable|numeric|min:0',
    //         'date_paiement' => 'required|date',
    //         'mode_paiement' => 'required|string',
    //         'reference' => 'nullable|string|max:255'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
    //             ->findOrFail($request->inscription_id);
            
    //         $ecoleId = session('current_ecole_id'); 
    //         $anneeScolaireId = session('current_annee_scolaire_id');

    //         $montantInscription = floatval($request->montant_inscription ?? 0);
    //         $montantScolarite = floatval($request->montant_scolarite ?? 0);
    //         $montantTransport = floatval($request->montant_transport ?? 0);
    //         $montantCantine = floatval($request->montant_cantine ?? 0);

    //         $total = $montantInscription + $montantScolarite + $montantTransport + $montantCantine;

    //         if ($total <= 0) {
    //             return response()->json([
    //                 'success' => false, 
    //                 'message' => 'Aucun montant à payer'
    //             ]);
    //         }

    //         // Créer le paiement
    //         $paiement = Paiement::create([
    //             'annee_scolaire_id' => $anneeScolaireId,
    //             'ecole_id' => $ecoleId,
    //             'montant' => $total,
    //             'mode_paiement' => $request->mode_paiement,
    //             'reference' => $request->reference,
    //             'user_id' => auth()->id(),
    //             'created_at' => $request->date_paiement,
    //             'updated_at' => $request->date_paiement
    //         ]);

    //         Log::info('Paiement créé', ['paiement_id' => $paiement->id, 'montant' => $total]);

    //         // Récupérer les types de frais
    //         $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
    //         $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
    //         $typeTransport = TypeFrais::where('nom', "Transport")->first();
    //         $typeCantine = TypeFrais::where('nom', "Cantine")->first();

    //         $niveauId = $inscription->classe->niveau->id;

    //         // Récupérer les tarifs
    //         $tarifInscription = Tarif::where('type_frais_id', $typeInscription->id ?? 0)
    //             ->where('annee_scolaire_id', $anneeScolaireId)
    //             ->where('ecole_id', $ecoleId)
    //             ->where(function($q) use ($niveauId) {
    //                 $q->where('niveau_id', $niveauId)
    //                   ->orWhereNull('niveau_id');
    //             })
    //             ->first();

    //         $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id ?? 0)
    //             ->where('annee_scolaire_id', $anneeScolaireId)
    //             ->where('ecole_id', $ecoleId)
    //             ->where(function($q) use ($niveauId) {
    //                 $q->where('niveau_id', $niveauId)
    //                   ->orWhereNull('niveau_id');
    //             })
    //             ->first();

    //         $tarifTransport = Tarif::where('type_frais_id', $typeTransport->id ?? 0)
    //             ->where('annee_scolaire_id', $anneeScolaireId)
    //             ->where('ecole_id', $ecoleId)
    //             ->where(function($q) use ($niveauId) {
    //                 $q->where('niveau_id', $niveauId)
    //                   ->orWhereNull('niveau_id');
    //             })
    //             ->first();

    //         $tarifCantine = Tarif::where('type_frais_id', $typeCantine->id ?? 0)
    //             ->where('annee_scolaire_id', $anneeScolaireId)
    //             ->where('ecole_id', $ecoleId)
    //             ->where(function($q) use ($niveauId) {
    //                 $q->where('niveau_id', $niveauId)
    //                   ->orWhereNull('niveau_id');
    //             })
    //             ->first();

    //         // Créer les détails avec tarif_id
    //         if ($montantInscription > 0 && $tarifInscription) {
    //             PaiementDetail::create([
    //                 'paiement_id' => $paiement->id,
    //                 'inscription_id' => $inscription->id,
    //                 'tarif_id' => $tarifInscription->id,
    //                 'montant' => $montantInscription
    //             ]);
    //             Log::info('Détail inscription créé', ['montant' => $montantInscription]);
    //         }

    //         if ($montantScolarite > 0 && $tarifScolarite) {
    //             PaiementDetail::create([
    //                 'paiement_id' => $paiement->id,
    //                 'inscription_id' => $inscription->id,
    //                 'tarif_id' => $tarifScolarite->id,
    //                 'montant' => $montantScolarite
    //             ]);
    //             Log::info('Détail scolarité créé', ['montant' => $montantScolarite]);
    //         }

    //         if ($montantTransport > 0 && $tarifTransport) {
    //             PaiementDetail::create([
    //                 'paiement_id' => $paiement->id,
    //                 'inscription_id' => $inscription->id,
    //                 'tarif_id' => $tarifTransport->id,
    //                 'montant' => $montantTransport
    //             ]);
    //             Log::info('Détail transport créé', ['montant' => $montantTransport]);
    //         }

    //         if ($montantCantine > 0 && $tarifCantine) {
    //             PaiementDetail::create([
    //                 'paiement_id' => $paiement->id,
    //                 'inscription_id' => $inscription->id,
    //                 'tarif_id' => $tarifCantine->id,
    //                 'montant' => $montantCantine
    //             ]);
    //             Log::info('Détail cantine créé', ['montant' => $montantCantine]);
    //         }

    //         // ENVOI DU SMS
    //         try {
    //             $ecole = Ecole::find($ecoleId);
                
    //             if ($ecole && $ecole->canSendSms()) {
    //                 $smsService = new \App\Services\SmsService($ecoleId);
                    
    //                 $phoneNumber = $inscription->eleve->parent_telephone ?? 
    //                                $inscription->eleve->telephone ?? null;
                    
    //                 if ($phoneNumber) {
    //                     $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
                        
    //                     // Calculer le reste total à payer
    //                     $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)->sum('montant');
    //                     $totalFrais = $montantInscription + $montantScolarite + $montantTransport + $montantCantine;
    //                     $resteTotal = max(0, $totalFrais - $totalPaye);
                        
    //                     $message = "✅ Paiement reçu de " . number_format($total, 0, ',', ' ') . " FCFA pour " . $inscription->eleve->nom . " " . $inscription->eleve->prenom . ". Reste: " . number_format($resteTotal, 0, ',', ' ') . " FCFA. Merci.";
                        
    //                     $result = $smsService->sendSms($phoneNumber, $message, $ecoleId);
                        
    //                     if ($result['success']) {
    //                         Log::info('✅ SMS de paiement envoyé avec succès', [
    //                             'paiement_id' => $paiement->id,
    //                             'inscription_id' => $inscription->id
    //                         ]);
    //                     }
    //                 }
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('❌ Erreur lors de l\'envoi du SMS', [
    //                 'paiement_id' => $paiement->id,
    //                 'error' => $e->getMessage()
    //             ]);
    //         }

    //         DB::commit();
            
    //         return response()->json([
    //             'success' => true, 
    //             'paiement_id' => $paiement->id,
    //             'message' => 'Paiement enregistré avec succès'
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
            
    //         Log::error('❌ Erreur lors de l\'enregistrement du paiement', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false, 
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }

    public function storePaiement(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'montant_inscription' => 'nullable|numeric|min:0',
        'montant_scolarite' => 'nullable|numeric|min:0',
        'montant_transport' => 'nullable|numeric|min:0',
        'montant_cantine' => 'nullable|numeric|min:0',
        'date_paiement' => 'required|date',
        'mode_paiement' => 'required|string',
        'reference' => 'nullable|string|max:255'
    ]);

    try {
        DB::beginTransaction();

        $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
            ->findOrFail($request->inscription_id);
        
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $montantInscription = floatval($request->montant_inscription ?? 0);
        $montantScolarite = floatval($request->montant_scolarite ?? 0);
        $montantTransport = floatval($request->montant_transport ?? 0);
        $montantCantine = floatval($request->montant_cantine ?? 0);

        $total = $montantInscription + $montantScolarite + $montantTransport + $montantCantine;

        if ($total <= 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Aucun montant à payer'
            ]);
        }

        // Créer le paiement
        $paiement = Paiement::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'montant' => $total,
            'mode_paiement' => $request->mode_paiement,
            'reference' => $request->reference,
            'user_id' => auth()->id(),
            'created_at' => $request->date_paiement,
            'updated_at' => $request->date_paiement
        ]);

        Log::info('Paiement créé', ['paiement_id' => $paiement->id, 'montant' => $total]);

        // Récupérer les types de frais
        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
        $typeTransport = TypeFrais::where('nom', "Transport")->first();
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();

        $niveauId = $inscription->classe->niveau->id;

        // Récupérer les tarifs
        $tarifInscription = Tarif::where('type_frais_id', $typeInscription->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifTransport = Tarif::where('type_frais_id', $typeTransport->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        $tarifCantine = Tarif::where('type_frais_id', $typeCantine->id ?? 0)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->first();

        // Créer les détails avec tarif_id
        if ($montantInscription > 0 && $tarifInscription) {
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $inscription->id,
                'tarif_id' => $tarifInscription->id,
                'montant' => $montantInscription
            ]);
        }

        if ($montantScolarite > 0 && $tarifScolarite) {
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $inscription->id,
                'tarif_id' => $tarifScolarite->id,
                'montant' => $montantScolarite
            ]);
        }

        if ($montantTransport > 0 && $tarifTransport) {
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $inscription->id,
                'tarif_id' => $tarifTransport->id,
                'montant' => $montantTransport
            ]);
        }

        if ($montantCantine > 0 && $tarifCantine) {
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $inscription->id,
                'tarif_id' => $tarifCantine->id,
                'montant' => $montantCantine
            ]);
        }

        // ENVOI DU SMS AVEC ORANGE API
        try {
            $ecole = Ecole::find($ecoleId);
            
            if ($ecole && $ecole->canSendSms()) {
                // Utiliser Orange API comme provider principal
                $smsService = new \App\Services\SmsService($ecoleId, 'orange');
                
                $phoneNumber = $inscription->eleve->parent_telephone ?? 
                               $inscription->eleve->telephone ?? null;
                
                if ($phoneNumber) {
                    // Calculer le reste total à payer
                    $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)->sum('montant');
                    $totalFrais = $montantInscription + $montantScolarite + $montantTransport + $montantCantine;
                    $resteTotal = max(0, $totalFrais - $totalPaye);
                    
                    $message = "✅ Paiement reçu de " . number_format($total, 0, ',', ' ') . " FCFA pour " . $inscription->eleve->nom . " " . $inscription->eleve->prenom . ". Reste: " . number_format($resteTotal, 0, ',', ' ') . " FCFA. Merci.";
                    
                    $result = $smsService->sendSms($phoneNumber, $message, $ecoleId);
                    
                    if ($result['success']) {
                        Log::info('✅ SMS de paiement envoyé avec succès via Orange', [
                            'paiement_id' => $paiement->id,
                            'inscription_id' => $inscription->id,
                            'provider' => 'orange'
                        ]);
                    } else {
                        Log::warning('⚠️ Échec envoi SMS via Orange, tentative avec QuickNotify', [
                            'paiement_id' => $paiement->id,
                            'error' => $result['message'] ?? 'Erreur inconnue'
                        ]);
                        
                        // Fallback vers QuickNotify si Orange échoue
                        $smsServiceFallback = new \App\Services\SmsService($ecoleId, 'quick_notify');
                        $resultFallback = $smsServiceFallback->sendSms($phoneNumber, $message, $ecoleId);
                        
                        if ($resultFallback['success']) {
                            Log::info('✅ SMS de paiement envoyé avec succès via QuickNotify (fallback)', [
                                'paiement_id' => $paiement->id,
                                'inscription_id' => $inscription->id,
                                'provider' => 'quick_notify'
                            ]);
                        } else {
                            Log::error('❌ Échec envoi SMS via QuickNotify (fallback)', [
                                'paiement_id' => $paiement->id,
                                'error' => $resultFallback['message'] ?? 'Erreur inconnue'
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'envoi du SMS', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage()
            ]);
        }

        DB::commit();
        
        return response()->json([
            'success' => true, 
            'paiement_id' => $paiement->id,
            'message' => 'Paiement enregistré avec succès'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur lors de l\'enregistrement du paiement', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
}

    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with([
            'details.inscription.eleve',
            'details.inscription.classe',
            'details.inscription.reductions',
            'details.tarif.typeFrais',
            'user',
            'anneeScolaire',
            'ecole'
        ])->find($paiementId);

        if (!$paiement) {
            abort(404, "Paiement introuvable.");
        }

        $inscription = $paiement->details->first()?->inscription;
        if (!$inscription) {
            abort(404, "Inscription introuvable pour ce paiement.");
        }

        $eleve = $inscription->eleve;
        $classe = $inscription->classe;
        $ecole = $paiement->ecole;

        $montant_total = $paiement->details->sum('montant');

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', compact(
            'paiement',
            'eleve',
            'classe',
            'ecole',
            'montant_total'
        ));

        return $pdf->stream("recu_paiement_{$paiement->id}.pdf");
    }

    public function deletePaiement(Request $request)
    {
        $request->validate(['paiement_id' => 'required|exists:paiements,id']);

        try {
            DB::beginTransaction();

            $paiement = Paiement::findOrFail($request->paiement_id);
            $paiement->details()->delete();
            $paiement->delete();

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function receipt($paiementId)
    {
        $paiement = Paiement::with(['details.inscription.eleve', 'details.inscription.classe.niveau', 'user'])
            ->findOrFail($paiementId);

        $eleve = optional($paiement->details->first()->inscription)->eleve;
        $classe = optional($paiement->details->first()->inscription)->classe;

        $data = [
            'paiement' => $paiement,
            'eleve' => $eleve,
            'classe' => $classe,
            'details' => $paiement->details
        ];

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', $data)
            ->setPaper('A5', 'portrait');

        return $pdf->stream("recu_paiement_$paiementId.pdf");
    }
}