<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Reduction;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReductionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Log::info('=== ReductionController index ===', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId
        ]);

        $reductions = Reduction::with(['inscription.eleve', 'inscription.classe', 'tarif.typeFrais'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $typeFrais = TypeFrais::orderBy('nom')->get();

        return view('dashboard.pages.comptabilites.reductions.index', compact('reductions', 'typeFrais'));
    }


// public function getEleveData(Request $request)
// {
//     $request->validate([
//         'inscription_id' => 'required|exists:inscriptions,id'
//     ]);

//     try {
//         $ecoleId = session('current_ecole_id');
//         $anneeScolaireId = session('current_annee_scolaire_id');

//         Log::info('=== ReductionController getEleveData ===', [
//             'inscription_id' => $request->inscription_id,
//             'ecole_id' => $ecoleId,
//             'annee_scolaire_id' => $anneeScolaireId
//         ]);

//         $inscription = Inscription::with(['eleve', 'classe.niveau'])
//             ->where('ecole_id', $ecoleId)
//             ->where('annee_scolaire_id', $anneeScolaireId)
//             ->findOrFail($request->inscription_id);

//         $niveauId = $inscription->classe->niveau_id;

//         Log::info('Inscription trouvée', [
//             'inscription_id' => $inscription->id,
//             'eleve' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
//             'cantine_active' => $inscription->cantine_active,
//             'transport_active' => $inscription->transport_active,
//             'transport_tarif_id' => $inscription->transport_tarif_id,
//             'cantine_tarif_id' => $inscription->cantine_tarif_id,
//             'niveau_id' => $niveauId
//         ]);

//         // Récupérer les réductions existantes
//         $reductions = Reduction::where('inscription_id', $inscription->id)
//             ->where('ecole_id', $ecoleId)
//             ->where('annee_scolaire_id', $anneeScolaireId)
//             ->get()
//             ->keyBy('tarif_id');

//         Log::info('Réductions trouvées', ['count' => $reductions->count()]);

//         // Récupérer les types de frais pour identifier transport et cantine
//         $transportTypeIds = TypeFrais::where('nom', 'LIKE', '%Transport%')
//             ->orWhere('nom', 'LIKE', '%transport%')
//             ->pluck('id')
//             ->toArray();
            
//         $cantineTypeIds = TypeFrais::where('nom', 'LIKE', '%Cantine%')
//             ->orWhere('nom', 'LIKE', '%cantine%')
//             ->pluck('id')
//             ->toArray();

//         Log::info('Types de frais identifiés', [
//             'transport_type_ids' => $transportTypeIds,
//             'cantine_type_ids' => $cantineTypeIds
//         ]);

//         // Récupérer les tarifs pour le niveau de l'élève OU NULL (tous les niveaux)
//         $tarifs = Tarif::where('ecole_id', $ecoleId)
//             ->where('annee_scolaire_id', $anneeScolaireId)
//             ->where(function($q) use ($niveauId) {
//                 $q->where('niveau_id', $niveauId)
//                   ->orWhereNull('niveau_id');
//             })
//             ->with('typeFrais')
//             ->get();

//         Log::info('Tarifs trouvés pour le niveau', [
//             'count' => $tarifs->count(),
//             'niveau_id' => $niveauId
//         ]);

//         // Séparer les tarifs par type
//         $fraisData = [];
//         $transportTarifs = [];
//         $cantineTarifs = [];

//         foreach ($tarifs as $tarif) {
//             $typeNom = $tarif->typeFrais->nom ?? 'Inconnu';
//             $reduction = $reductions->get($tarif->id);

//             $item = [
//                 'tarif_id' => $tarif->id,
//                 'type_frais_id' => $tarif->type_frais_id,
//                 'type_frais_nom' => $typeNom,
//                 'montant_total' => $tarif->montant,
//                 'reduction_actuelle' => $reduction->montant ?? 0,
//                 'reduction_id' => $reduction->id ?? null,
//                 'libelle' => $tarif->libelle ?? '',
//                 'obligatoire' => (bool) $tarif->obligatoire,
//                 'niveau_id' => $tarif->niveau_id
//             ];

//             // Vérifier si c'est un tarif de Transport ou Cantine
//             $isTransport = in_array($tarif->type_frais_id, $transportTypeIds);
//             $isCantine = in_array($tarif->type_frais_id, $cantineTypeIds);

//             // GESTION TRANSPORT
//             if ($isTransport) {
//                 // Afficher UNIQUEMENT si transport_active = 1
//                 if ($inscription->transport_active == 1) {
//                     // Afficher si le tarif est obligatoire OU si c'est le tarif sélectionné
//                     if ($tarif->obligatoire || $tarif->id == $inscription->transport_tarif_id) {
//                         $transportTarifs[] = $item;
//                         Log::info('Ajout transport tarif (service actif)', [
//                             'libelle' => $item['libelle'], 
//                             'montant' => $item['montant_total'],
//                             'obligatoire' => $item['obligatoire'],
//                             'est_selectionne' => ($tarif->id == $inscription->transport_tarif_id)
//                         ]);
//                     }
//                 }
//             } 
//             // GESTION CANTINE
//             elseif ($isCantine) {
//                 // Afficher UNIQUEMENT si cantine_active = 1
//                 if ($inscription->cantine_active == 1) {
//                     // Afficher si le tarif est obligatoire OU si c'est le tarif sélectionné
//                     if ($tarif->obligatoire || $tarif->id == $inscription->cantine_tarif_id) {
//                         $cantineTarifs[] = $item;
//                         Log::info('Ajout cantine tarif (service actif)', [
//                             'libelle' => $item['libelle'], 
//                             'montant' => $item['montant_total'],
//                             'obligatoire' => $item['obligatoire'],
//                             'est_selectionne' => ($tarif->id == $inscription->cantine_tarif_id)
//                         ]);
//                     }
//                 }
//             } 
//             // AUTRES FRAIS - toujours affichés
//             else {
//                 $fraisData[] = $item;
//                 Log::info('Ajout frais normal', [
//                     'type' => $typeNom, 
//                     'libelle' => $item['libelle']
//                 ]);
//             }
//         }

//         Log::info('Résumé des données', [
//             'frais' => count($fraisData),
//             'transport' => count($transportTarifs),
//             'cantine' => count($cantineTarifs),
//             'selected_transport' => $inscription->transport_tarif_id,
//             'selected_cantine' => $inscription->cantine_tarif_id
//         ]);

//         return response()->json([
//             'success' => true,
//             'eleve' => [
//                 'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
//                 'matricule' => $inscription->eleve->matricule,
//                 'classe' => $inscription->classe->nom
//             ],
//             'frais' => $fraisData,
//             'transport_tarifs' => $transportTarifs,
//             'cantine_tarifs' => $cantineTarifs,
//             'selected_transport_tarif' => $inscription->transport_tarif_id,
//             'selected_cantine_tarif' => $inscription->cantine_tarif_id
//         ]);

//     } catch (\Exception $e) {
//         Log::error('ERREUR getEleveData: ' . $e->getMessage());
//         Log::error('Stack trace: ' . $e->getTraceAsString());
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

public function getEleveData(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id'
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Log::info('=== ReductionController getEleveData ===', [
            'inscription_id' => $request->inscription_id,
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId
        ]);

        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($request->inscription_id);

        $niveauId = $inscription->classe->niveau_id;

        Log::info('Inscription trouvée', [
            'inscription_id' => $inscription->id,
            'eleve' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
            'cantine_active' => $inscription->cantine_active,
            'transport_active' => $inscription->transport_active,
            'transport_tarif_id' => $inscription->transport_tarif_id,
            'cantine_tarif_id' => $inscription->cantine_tarif_id,
            'niveau_id' => $niveauId
        ]);

        // Récupérer les réductions existantes
        $reductions = Reduction::where('inscription_id', $inscription->id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get()
            ->keyBy('tarif_id');

        Log::info('Réductions trouvées', ['count' => $reductions->count()]);

        // Récupérer les types de frais pour identifier transport et cantine
        $transportTypeIds = TypeFrais::where('nom', 'LIKE', '%Transport%')
            ->orWhere('nom', 'LIKE', '%transport%')
            ->pluck('id')
            ->toArray();
            
        $cantineTypeIds = TypeFrais::where('nom', 'LIKE', '%Cantine%')
            ->orWhere('nom', 'LIKE', '%cantine%')
            ->pluck('id')
            ->toArray();

        Log::info('Types de frais identifiés', [
            'transport_type_ids' => $transportTypeIds,
            'cantine_type_ids' => $cantineTypeIds
        ]);

        // Récupérer TOUS les tarifs pour le niveau de l'élève OU NULL (tous les niveaux)
        $tarifs = Tarif::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where(function($q) use ($niveauId) {
                $q->where('niveau_id', $niveauId)
                  ->orWhereNull('niveau_id');
            })
            ->with('typeFrais')
            ->get();

        Log::info('Tarifs trouvés pour le niveau', [
            'count' => $tarifs->count(),
            'niveau_id' => $niveauId
        ]);

        // Séparer les tarifs par type
        $fraisData = [];           // Pour le tableau des réductions
        $transportTarifsForSelect = []; // Pour la carte Transport
        $cantineTarifsForSelect = [];   // Pour la carte Cantine

        foreach ($tarifs as $tarif) {
            $typeNom = $tarif->typeFrais->nom ?? 'Inconnu';
            $reduction = $reductions->get($tarif->id);

            $item = [
                'tarif_id' => $tarif->id,
                'type_frais_id' => $tarif->type_frais_id,
                'type_frais_nom' => $typeNom,
                'montant_total' => $tarif->montant,
                'reduction_actuelle' => $reduction->montant ?? 0,
                'reduction_id' => $reduction->id ?? null,
                'libelle' => $tarif->libelle ?? '',
                'obligatoire' => (bool) $tarif->obligatoire,
                'niveau_id' => $tarif->niveau_id
            ];

            // Vérifier si c'est un tarif de Transport ou Cantine
            $isTransport = in_array($tarif->type_frais_id, $transportTypeIds);
            $isCantine = in_array($tarif->type_frais_id, $cantineTypeIds);

            // GESTION TRANSPORT
            if ($isTransport) {
                // ✅ Vérifier d'abord si l'élève fait le service transport
                if ($inscription->transport_active == 1) {
                    // Pour le tableau : UNIQUEMENT si le tarif est sélectionné OU obligatoire
                    if ($tarif->id == $inscription->transport_tarif_id || $tarif->obligatoire) {
                        $fraisData[] = $item;
                        Log::info('Ajout transport tarif au tableau (service actif)', [
                            'libelle' => $item['libelle'],
                            'selected' => ($tarif->id == $inscription->transport_tarif_id),
                            'obligatoire' => $tarif->obligatoire
                        ]);
                    }
                    
                    // Pour la carte : TOUS les tarifs (pour permettre à l'élève de choisir)
                    $transportTarifsForSelect[] = $item;
                }
                // Si transport_active = 0, on n'ajoute RIEN
            } 
            // GESTION CANTINE
            elseif ($isCantine) {
                // ✅ Vérifier d'abord si l'élève fait le service cantine
                if ($inscription->cantine_active == 1) {
                    // Pour le tableau : UNIQUEMENT si le tarif est sélectionné OU obligatoire
                    if ($tarif->id == $inscription->cantine_tarif_id || $tarif->obligatoire) {
                        $fraisData[] = $item;
                        Log::info('Ajout cantine tarif au tableau (service actif)', [
                            'libelle' => $item['libelle'],
                            'selected' => ($tarif->id == $inscription->cantine_tarif_id),
                            'obligatoire' => $tarif->obligatoire
                        ]);
                    }
                    
                    // Pour la carte : TOUS les tarifs (pour permettre à l'élève de choisir)
                    $cantineTarifsForSelect[] = $item;
                }
                // Si cantine_active = 0, on n'ajoute RIEN
            } 
            // AUTRES FRAIS - toujours affichés (Scolarité, Inscription, etc.)
            else {
                $fraisData[] = $item;
                Log::info('Ajout frais normal', [
                    'type' => $typeNom, 
                    'libelle' => $item['libelle']
                ]);
            }
        }

        Log::info('Résumé des données', [
            'frais (tableau)' => count($fraisData),
            'transport (select)' => count($transportTarifsForSelect),
            'cantine (select)' => count($cantineTarifsForSelect),
            'selected_transport' => $inscription->transport_tarif_id,
            'selected_cantine' => $inscription->cantine_tarif_id,
            'transport_active' => $inscription->transport_active,
            'cantine_active' => $inscription->cantine_active
        ]);

        return response()->json([
            'success' => true,
            'eleve' => [
                'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'frais' => $fraisData,
            'transport_tarifs' => $transportTarifsForSelect,
            'cantine_tarifs' => $cantineTarifsForSelect,
            'selected_transport_tarif' => $inscription->transport_tarif_id,
            'selected_cantine_tarif' => $inscription->cantine_tarif_id
        ]);

    } catch (\Exception $e) {
        Log::error('ERREUR getEleveData: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



public function updateTransportTarif(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'tarif_id' => 'required' // Accepte "0" pour désactivation
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Log::info('=== updateTransportTarif ===', [
            'inscription_id' => $request->inscription_id,
            'tarif_id' => $request->tarif_id
        ]);

        $inscription = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($request->inscription_id);

        // Vérifier si c'est la désactivation (tarif_id = 0)
        if ($request->tarif_id == 0) {
            // Désactiver le transport - start_date à null
            $inscription->update([
                'transport_tarif_id' => null,
                'transport_active' => false,
                'transport_start_date' => null // 👈 Mettre à null
            ]);
            
            $message = 'Transport désactivé avec succès';
            Log::info('Transport désactivé', ['inscription_id' => $inscription->id]);
        } else {
            // Activer avec un nouveau tarif - start_date à maintenant
            $inscription->update([
                'transport_tarif_id' => $request->tarif_id,
                'transport_active' => true,
                'transport_start_date' => now() // 👈 Date d'activation
            ]);
            
            $message = 'Tarif de transport sélectionné avec succès';
            Log::info('Tarif transport mis à jour', [
                'inscription_id' => $inscription->id,
                'transport_tarif_id' => $request->tarif_id,
                'transport_start_date' => $inscription->transport_start_date
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        Log::error('ERREUR updateTransportTarif: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function updateCantineTarif(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'tarif_id' => 'required' // Accepte "0" pour désactivation
    ]);

    try {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Log::info('=== updateCantineTarif ===', [
            'inscription_id' => $request->inscription_id,
            'tarif_id' => $request->tarif_id
        ]);

        $inscription = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($request->inscription_id);

        // Vérifier si c'est la désactivation (tarif_id = 0)
        if ($request->tarif_id == 0) {
            // Désactiver la cantine - start_date à null
            $inscription->update([
                'cantine_tarif_id' => null,
                'cantine_active' => false,
                'cantine_start_date' => null // 👈 Mettre à null
            ]);
            
            $message = 'Cantine désactivée avec succès';
            Log::info('Cantine désactivée', ['inscription_id' => $inscription->id]);
        } else {
            // Activer avec un nouveau tarif - start_date à maintenant
            $inscription->update([
                'cantine_tarif_id' => $request->tarif_id,
                'cantine_active' => true,
                'cantine_start_date' => now() // 👈 Date d'activation
            ]);
            
            $message = 'Tarif de cantine sélectionné avec succès';
            Log::info('Tarif cantine mis à jour', [
                'inscription_id' => $inscription->id,
                'cantine_tarif_id' => $request->tarif_id,
                'cantine_start_date' => $inscription->cantine_start_date
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        Log::error('ERREUR updateCantineTarif: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



public function store(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'tarif_id' => 'required|exists:tarifs,id',
        'montant' => 'required|numeric|min:0',
        'raison' => 'nullable|string|max:255'
    ]);

    try {
        DB::beginTransaction();

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        Log::info('=== store Reduction ===', [
            'inscription_id' => $request->inscription_id,
            'tarif_id' => $request->tarif_id,
            'montant' => $request->montant,
            'user_id' => auth()->id()
        ]);

        $reduction = Reduction::where('inscription_id', $request->inscription_id)
            ->where('tarif_id', $request->tarif_id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if ($reduction) {
            $reduction->update([
                'montant' => $request->montant,
                'raison' => $request->raison,
                'user_id' => auth()->id() // Ajout de l'utilisateur qui modifie
            ]);
            $message = 'Réduction mise à jour avec succès';
            Log::info('Réduction mise à jour', ['reduction_id' => $reduction->id]);
        } else {
            $reduction = Reduction::create([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'inscription_id' => $request->inscription_id,
                'tarif_id' => $request->tarif_id,
                'user_id' => auth()->id(), // Ajout de l'utilisateur qui crée
                'montant' => $request->montant,
                'raison' => $request->raison
            ]);
            $message = 'Réduction ajoutée avec succès';
            Log::info('Réduction créée', ['reduction_id' => $reduction->id]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ERREUR store reduction: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function destroy($id)
    {
        try {
            $ecoleId = session('current_ecole_id');

            Log::info('=== destroy Reduction ===', ['reduction_id' => $id]);

            $reduction = Reduction::where('ecole_id', $ecoleId)
                ->findOrFail($id);

            $reduction->delete();

            Log::info('Réduction supprimée', ['reduction_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Réduction supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR delete reduction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}