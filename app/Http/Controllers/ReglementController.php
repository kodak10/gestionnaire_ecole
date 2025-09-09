<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReglementController extends Controller
{
    public function index()
    {
        $anneesScolaires = AnneeScolaire::all();
        $classes = Classe::all();
        
        return view('dashboard.pages.comptabilites.reglement', compact('anneesScolaires', 'classes'));
    }
    
    public function elevesByClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);
        
        try {
            $eleves = Inscription::with('eleve')
                ->where('classe_id', $request->classe_id)
                ->whereHas('anneeScolaire', function($query) {
                    $query->where('est_active', true);
                })
                ->get()
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                        'matricule' => $inscription->eleve->matricule
                    ];
                });
                
            return response()->json($eleves);
            
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }
    
  
public function eleveData(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'annee_scolaire_id' => 'required|exists:annee_scolaires,id'
    ]);

    try {
        // Récupération de l'inscription avec relations
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'reductions'])
            ->findOrFail($request->inscription_id);

        $anneeId = session('annee_scolaire_id'); 

        $ecoleId = $inscription->eleve->ecole_id;
        $niveauId = $inscription->classe->niveau->id;

        \Log::info('Inscription trouvée', ['inscription_id' => $inscription->id]);
        \Log::info('IDs récupérés', compact('anneeId', 'ecoleId', 'niveauId'));

        // Récupérer les types de frais
        $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
        $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();

        \Log::info('Type frais trouvés', [
            'inscription' => $typeInscription->id ?? null,
            'scolarite' => $typeScolarite->id ?? null
        ]);

        // Récupérer les tarifs exacts
        $tarifInscription = Tarif::where('annee_scolaire_id', $anneeId)
            ->where('niveau_id', $niveauId)
            ->where('ecole_id', $ecoleId)
            ->where('type_frais_id', $typeInscription->id ?? 0)
            ->first();

        $tarifScolarite = Tarif::where('annee_scolaire_id', $anneeId)
            ->where('niveau_id', $niveauId)
            ->where('ecole_id', $ecoleId)
            ->where('type_frais_id', $typeScolarite->id ?? 0)
            ->first();

        \Log::info('Tarifs trouvés', [
            'tarif_inscription' => $tarifInscription?->montant,
            'tarif_scolarite' => $tarifScolarite?->montant
        ]);

        $montantInscription = $tarifInscription ? $tarifInscription->montant : 0;
        $montantScolarite = $tarifScolarite ? $tarifScolarite->montant : 0;

        // Appliquer les réductions éventuelles
        $reduction = $inscription->reductions->sum('montant'); // ajuster selon ta table reductions
        $montantScolarite = max(0, $montantScolarite - $reduction);

        // Récupérer tous les paiements
        $paiements = Paiement::where('inscription_id', $inscription->id)->get();
        \Log::info('Paiements trouvés', ['count' => $paiements->count()]);

        // Totaux payés
        $totalPayeInscription = $paiements->where('type_frais_id', $typeInscription->id ?? 0)->sum('montant');
        $totalPayeScolarite = $paiements->where('type_frais_id', $typeScolarite->id ?? 0)->sum('montant');

        $resteAPayerInscription = max(0, $montantInscription - $totalPayeInscription);
        $resteAPayerScolarite = max(0, $montantScolarite - $totalPayeScolarite);

        \Log::info('Totaux payés', [
            'inscription' => $totalPayeInscription,
            'scolarite' => $totalPayeScolarite
        ]);

        // $tousPaiements = $paiements->sortByDesc('created_at');
        $tousPaiements = $paiements->sortByDesc('created_at')->values();


        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $inscription->eleve->prenom . ' ' . $inscription->eleve->nom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'frais' => [
                'inscription' => $montantInscription,
                'scolarite' => $montantScolarite
            ],
            'paiements' => $tousPaiements,
            'total_paye' => [
                'inscription' => $totalPayeInscription,
                'scolarite' => $totalPayeScolarite
            ],
            'reste_a_payer' => [
                'inscription' => $resteAPayerInscription,
                'scolarite' => $resteAPayerScolarite
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur eleveData', ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
        ]);
    }
}



public function storePaiement(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
        'montant_inscription' => 'nullable|numeric|min:0',
        'montant_scolarite' => 'nullable|numeric|min:0',
        'date_paiement' => 'required|date',
        'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money'
    ]);
    
    try {
        DB::beginTransaction();
        
        $inscription = Inscription::findOrFail($request->inscription_id);
        $ecoleId = $inscription->ecole_id;

        if ($request->montant_inscription > 0) {
            $paiementInscription = new Paiement();
            $paiementInscription->inscription_id = $request->inscription_id;
            $paiementInscription->annee_scolaire_id = $request->annee_scolaire_id;
            $paiementInscription->ecole_id = $ecoleId;
            $paiementInscription->type_frais_id = 1; // Inscription
            $paiementInscription->montant = $request->montant_inscription;
            $paiementInscription->mode_paiement = $request->mode_paiement;
            $paiementInscription->user_id = auth()->id();
            $paiementInscription->created_at = $request->date_paiement;

            // 🔎 Log avant enregistrement
            Log::info("Paiement inscription à enregistrer", $paiementInscription->toArray());

            $paiementInscription->save();
        }

        if ($request->montant_scolarite > 0) {
            $paiementScolarite = new Paiement();
            $paiementScolarite->inscription_id = $request->inscription_id;
            $paiementScolarite->annee_scolaire_id = $request->annee_scolaire_id;
            $paiementScolarite->ecole_id = $ecoleId;
            $paiementScolarite->type_frais_id = 2; // Scolarité
            $paiementScolarite->montant = $request->montant_scolarite;
            $paiementScolarite->mode_paiement = $request->mode_paiement;
            $paiementScolarite->user_id = auth()->id();
            $paiementScolarite->created_at = $request->date_paiement;

            // 🔎 Log avant enregistrement
            Log::info("Paiement scolarité à enregistrer", $paiementScolarite->toArray());

            $paiementScolarite->save();
        }

        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Paiement(s) enregistré(s) avec succès'
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Erreur lors de l'enregistrement du paiement", ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
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
            
            $paiement = Paiement::findOrFail($request->paiement_id);
            $paiement->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ]);
        }
    }
    
    public function receipt($paiementId)
    {
        $paiement = Paiement::with(['inscription.eleve', 'inscription.classe', 'anneeScolaire'])
            ->findOrFail($paiementId);
            
        return view('reglements.receipt', compact('paiement'));
    }
}