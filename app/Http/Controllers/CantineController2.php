<?php

namespace App\Http\Controllers;

use App\Models\CantineMensuelle;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\PaiementCantine;
use App\Models\PaiementDetail;
use App\Models\Reduction;
use App\Models\ReductionCantine;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Illuminate\Support\Facades\Validator;

class CantineController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur|Caissiere']);
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

        $moisScolaires = MoisScolaire::orderBy('id')->get(); // ajouter la liste des mois

        return view('dashboard.pages.cantines.index', compact('classes', 'moisScolaires'));
    }

    public function elevesByClasseCantine(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id'
        ]);

        try {
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $eleves = Inscription::with('eleve')
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('classe_id', $request->classe_id)
                ->where('cantine_active', true)
            
                ->get()
                ->sortBy(function($inscription) {
                    // Tri par nom puis prénom
                    return $inscription->eleve->nom . ' ' . $inscription->eleve->prenom;
                })
                ->values() // réindexe les clés
                ->map(function($inscription) {
                    return [
                        'id' => $inscription->id,
                        'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                        'matricule' => $inscription->eleve->matricule,
                        'cantine_active' => $inscription->cantine_active
                    ];
                });

            return response()->json($eleves);

        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getEleveCantine(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
        ]);

        try {
            $inscription = Inscription::with(['eleve', 'classe.niveau'])
                ->findOrFail($request->inscription_id);

            
            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $niveauId = $inscription->classe->niveau->id;

            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            $tarifCantine = Tarif::where([
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'type_frais_id' => $typeCantine->id ?? 0
            ])->first();

            $montantCantine = $tarifCantine->montant ?? 0;

            // Récupérer les paiements liés à la Cantine
            $paiements = Paiement::with('details.typeFrais')
                ->whereHas('details', function($q) use ($inscription, $typeCantine) {
                    $q->where('inscription_id', $inscription->id)
                    ->where('type_frais_id', $typeCantine->id ?? 0);
                })
                ->orderByDesc('created_at')
                ->get();

            // Calcul du total payé pour la Cantine
            $totalPayeCantine = $paiements->sum(function($paiement) use ($typeCantine) {
                return $paiement->details->where('type_frais_id', $typeCantine->id ?? 0)->sum('montant');
            });

            $resteCantine = max(0, $montantCantine - $totalPayeCantine);

            return response()->json([
                'success' => true,
                'eleve' => [
                    'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                    'matricule' => $inscription->eleve->matricule,
                    'classe' => $inscription->classe->nom
                ],
                'frais' => [
                    'cantine' => $montantCantine
                ],
                'total_paye' => [
                    'cantine' => $totalPayeCantine
                ],
                'reste_a_payer' => [
                    'cantine' => $resteCantine
                ],
                'paiements' => $paiements
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant_cantine' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'date_paiement' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $ecoleId = session('current_ecole_id'); 
            $anneeScolaireId = session('current_annee_scolaire_id');
            $userId = Auth::id();

            $inscription = Inscription::with('eleve', 'classe.niveau')->findOrFail($request->inscription_id);

            if (!$inscription->cantine_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'a pas la cantine active.'
                ]);
            }

            $niveauId = $inscription->classe->niveau->id;

            $typeCantine = TypeFrais::where('nom', 'Cantine')->first();
            if (!$typeCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de frais "Cantine" non trouvé.'
                ]);
            }

            $tarifCantine = Tarif::where([
                'annee_scolaire_id' => $anneeScolaireId,
                'niveau_id' => $niveauId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeCantine->id
            ])->first();

            if (!$tarifCantine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif de cantine non trouvé pour cette configuration.'
                ]);
            }

            $totalPayeCantine = PaiementDetail::where('inscription_id', $request->inscription_id)
                ->where('type_frais_id', $typeCantine->id)
                ->sum('montant');

            $resteAPayer = max(0, $tarifCantine->montant - $totalPayeCantine);

            if ($request->montant_cantine > $resteAPayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant saisi dépasse le reste à payer (' . $resteAPayer . ' FCFA).'
                ]);
            }

            // Paiement global
            $paiement = Paiement::create([
                'user_id' => auth()->id(),
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'montant' => $request->montant_cantine,
                'mode_paiement' => $request->mode_paiement,
                'reference' => null,
                'description' => 'Paiement Cantine',
                'created_at' => $request->date_paiement,
                'updated_at' => $request->date_paiement
            ]);


            // Paiement détail
            PaiementDetail::create([
                'paiement_id' => $paiement->id,
                'inscription_id' => $request->inscription_id,
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'type_frais_id' => $typeCantine->id,
                'montant' => $request->montant_cantine,
                'created_at' => $request->date_paiement,
                'updated_at' => $request->date_paiement
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement Cantine enregistré avec succès.',
                'paiement_id' => $paiement->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur storePaiementCantine', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage()
            ]);
        }
    }
    
    public function generateReceipt($paiementId)
    {
        $paiement = Paiement::with([
            'details.inscription.eleve',
            'details.inscription.classe',
            'details.typeFrais',
            'user',
            'anneeScolaire', // probleme
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

        // Récupérer l'année scolaire et niveau
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        $userId = Auth::id();

        // On ne prend que le type frais "Cantine"
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();
        if (!$typeCantine) {
            abort(404, "Type de frais 'Cantine' introuvable.");
        }

        // Récupérer le tarif cantine
        $tarifCantine = Tarif::where([
            'ecole_id' => $ecole->id,
            'annee_scolaire_id' => $anneeScolaireId,
            'niveau_id' => $classe->niveau->id,
            'type_frais_id' => $typeCantine->id
        ])->first();

        $montantCantine = $tarifCantine->montant ?? 0;

        // Déjà payé (uniquement cantine)
        $totalPayeCantine = PaiementDetail::where('inscription_id', $inscription->id)
            ->where('type_frais_id', $typeCantine->id)
            ->sum('montant');

        // Reste à payer
        $reste_total = max(0, $montantCantine - $totalPayeCantine);

        // Montant total payé sur ce reçu
        $montant_total = $paiement->details->sum('montant');

        $pdf = Pdf::loadView('dashboard.documents.scolarite.recu_paiement', compact(
            'paiement',
            'eleve',
            'classe',
            'ecole',
            'montant_total',
            'reste_total'
        ));

        return $pdf->stream("recu_paiement_{$paiement->id}.pdf");
    }

    public function printScolarite($eleveId)
    {
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');
        
        // Récupérer les données de scolarité
        $typeScolarite = TypeFrais::where('nom', 'like', '%cantine%')->first();
        $tarifScolarite = Tarif::where('type_frais_id', $typeScolarite->id)
            ->where('niveau_id', $eleve->classe->niveau_id)
            ->first();

        $paiements = Paiement::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId->id)
            ->where('type_frais_id', $typeScolarite->id)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $reduction = ReductionCantine::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeScolaireId->id)
            ->where('type_frais', 'scolarite')
            ->sum('montant');

        $totalPaye = $paiements->sum('montant');
        $montantScolarite = $tarifScolarite ? $tarifScolarite->montant : 0;
        $montantApresReduction = max($montantScolarite - $reduction, 0);
        $resteAPayer = max($montantApresReduction - $totalPaye, 0);

        $data = [
            'eleve' => $eleve,
            'anneeScolaire' => $anneeScolaireId, // probleme
            'paiements' => $paiements,
            'montantScolarite' => $montantScolarite,
            'reduction' => $reduction,
            'montantApresReduction' => $montantApresReduction,
            'totalPaye' => $totalPaye,
            'resteAPayer' => $resteAPayer
        ];

        $pdf = PDF::loadView('scolarite.print', $data);
        return $pdf->stream('scolarite-' . $eleve->matricule . '.pdf');
    }















 // Ajoutez ces méthodes dans votre CantineController existant

public function gestion()
{
    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $classes = Classe::with('niveau')
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->orderBy('id')
        ->get();

    return view('dashboard.pages.cantines.gestion', compact('classes'));
}

public function elevesByClasseGestion(Request $request)
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
            ->where('cantine_active', true)
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
                    'date_cantine' => $inscription->eleve->date_cantine
                ];
            });

        return response()->json($eleves);

    } catch (\Exception $e) {
        return response()->json([], 500);
    }
}

public function getEleveCantineMois(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->findOrFail($request->inscription_id);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $niveauId = $inscription->classe->niveau->id;

        $typeCantine = TypeFrais::where('nom', "Cantine")->first();
        if (!$typeCantine) {
            return response()->json(['success' => false, 'message' => 'Type de frais Cantine non trouvé']);
        }

        // Récupérer les tarifs mensuels pour la cantine
        $tarifsMensuels = TarifMensuel::
            where('type_frais_id', $typeCantine->id)
            ->where('niveau_id', $niveauId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('ecole_id', $ecoleId)
            ->get()
            ->keyBy('mois_id');

        // Récupérer tous les mois scolaires
        $moisScolaires = MoisScolaire::orderBy('numero')->get();

        // Récupérer les mois déjà configurés pour cet élève
        $moisConfigures = CantineMensuelle::where('inscription_id', $inscription->id)
            ->get()
            ->keyBy('mois_scolaire_id');

        // Préparer les données des mois
        $moisData = [];
        $totalMontant = 0;

        foreach ($moisScolaires as $mois) {
            $tarifMensuel = $tarifsMensuels->get($mois->id);
            $moisConfigure = $moisConfigures->get($mois->id);

            $montantBase = $tarifMensuel ? $tarifMensuel->montant : 0;
            $montantPersonnalise = $moisConfigure ? $moisConfigure->montant : $montantBase;
            $estCoche = $moisConfigure ? $moisConfigure->est_coche : ($montantBase > 0);
            $estPaye = $moisConfigure ? $moisConfigure->est_paye : false;

            $moisData[] = [
                'mois_id' => $mois->id,
                'mois_nom' => $mois->nom,
                'mois_numero' => $mois->numero,
                'montant_base' => $montantBase,
                'montant_personnalise' => $montantPersonnalise,
                'est_coche' => $estCoche,
                'est_paye' => $estPaye,
                'peut_modifier' => !$estPaye // On ne peut pas modifier les mois déjà payés
            ];

            if ($estCoche && !$estPaye) {
                $totalMontant += $montantPersonnalise;
            }
        }

        // Récupérer les paiements déjà effectués
        $paiements = Paiement::with('details')
            ->whereHas('details', function($q) use ($inscription, $typeCantine) {
                $q->where('inscription_id', $inscription->id)
                  ->where('type_frais_id', $typeCantine->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $totalPaye = $paiements->sum('montant');

        return response()->json([
            'success' => true,
            'eleve' => [
                'id' => $inscription->id,
                'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom,
                'date_cantine' => $inscription->eleve->date_cantine
            ],
            'mois' => $moisData,
            'total_montant' => $totalMontant,
            'total_paye' => $totalPaye,
            'reste_a_payer' => $totalMontant - $totalPaye
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function saveConfigurationCantine(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
        'configurations' => 'required|array',
        'configurations.*.mois_id' => 'required|exists:mois_scolaires,id',
        'configurations.*.montant' => 'required|numeric|min:0',
        'configurations.*.est_coche' => 'required' // On accepte tout type et on convertit
    ]);

    try {
        DB::beginTransaction();

        $inscriptionId = $request->inscription_id;

        foreach ($request->configurations as $config) {
            // Conversion robuste en booléen
            $estCoche = filter_var($config['est_coche'], FILTER_VALIDATE_BOOLEAN);
            
            CantineMensuelle::updateOrCreate(
                [
                    'inscription_id' => $inscriptionId,
                    'mois_scolaire_id' => $config['mois_id']
                ],
                [
                    'montant' => $config['montant'],
                    'est_coche' => $estCoche,
                    'est_paye' => false
                ]
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Configuration de la cantine sauvegardée avec succès'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
        ]);
    }
}
















public function getMoisAPayer(Request $request)
{
    $request->validate([
        'inscription_id' => 'required|exists:inscriptions,id',
    ]);

    try {
        $inscription = Inscription::with(['eleve', 'classe.niveau'])
            ->findOrFail($request->inscription_id);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Récupérer les mois configurés mais non payés
        // Dans votre contrôleur, dans getMoisAPayer()
$moisAPayer = CantineMensuelle::with('moisScolaire')
    ->where('inscription_id', $inscription->id)
    ->where('est_coche', true)
    ->where('est_paye', false)
    ->get()
    ->map(function($cantine) {
        return [
            'mois_id' => $cantine->mois_scolaire_id,
            'mois_nom' => $cantine->moisScolaire->nom,
            'montant' => (float) $cantine->montant, // Forcer le type float
            'est_selectionne' => false
        ];
    });

        // Récupérer le total déjà payé
        $typeCantine = TypeFrais::where('nom', "Cantine")->first();
        $totalPaye = 0;
        
        if ($typeCantine) {
            $totalPaye = PaiementDetail::where('inscription_id', $inscription->id)
                ->where('type_frais_id', $typeCantine->id)
                ->sum('montant');
        }

        // Calculer le total configuré
        $totalConfigure = CantineMensuelle::where('inscription_id', $inscription->id)
            ->where('est_coche', true)
            ->sum('montant');

        $resteAPayer = max(0, $totalConfigure - $totalPaye);

        return response()->json([
            'success' => true,
            'eleve' => [
                'id' => $inscription->id,
                'nom_complet' => $inscription->eleve->nom . ' ' . $inscription->eleve->prenom,
                'matricule' => $inscription->eleve->matricule,
                'classe' => $inscription->classe->nom
            ],
            'mois_a_payer' => $moisAPayer,
            'total_configure' => $totalConfigure,
            'total_paye' => $totalPaye,
            'reste_a_payer' => $resteAPayer
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function storePaiementMensuel(Request $request)
{
    // Si les données viennent en JSON, on les décode
    if ($request->isJson()) {
        $data = $request->json()->all();
    } else {
        $data = $request->all();
    }

    $validator = Validator::make($data, [
        'inscription_id' => 'required|exists:inscriptions,id',
        'montant_encaisse' => 'required|numeric|min:1',
        'mois_selectionnes' => 'required|array',
        'mois_selectionnes.*.mois_id' => 'required|exists:mois_scolaires,id',
        'mois_selectionnes.*.montant' => 'required|numeric|min:0',
        'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
        'date_paiement' => 'required|date'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        DB::beginTransaction();

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        $inscription = Inscription::with('eleve', 'classe.niveau')->findOrFail($data['inscription_id']);

        if (!$inscription->cantine_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'a pas la cantine active.'
            ]);
        }

        $typeCantine = TypeFrais::where('nom', 'Cantine')->first();
        if (!$typeCantine) {
            return response()->json([
                'success' => false,
                'message' => 'Type de frais "Cantine" non trouvé.'
            ]);
        }

        // Calculer le montant total des mois sélectionnés
        $montantTotalMois = collect($data['mois_selectionnes'])->sum('montant');

        if ($data['montant_encaisse'] > $montantTotalMois) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant encaissé ne peut pas être supérieur au total des mois sélectionnés.'
            ]);
        }

        // Préparer la description avec les mois
        $moisNoms = [];
        foreach ($data['mois_selectionnes'] as $mois) {
            $moisScolaire = MoisScolaire::find($mois['mois_id']);
            $moisNoms[] = $moisScolaire->nom . ' (' . number_format($mois['montant'], 0, ',', ' ') . ' FCFA)';
        }

        $description = 'Paiement Cantine - Mois: ' . implode(', ', $moisNoms);

        // Créer le paiement
        $paiement = Paiement::create([
            'user_id' => auth()->id(),
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'montant' => $data['montant_encaisse'],
            'mode_paiement' => $data['mode_paiement'],
            'reference' => null,
            'description' => $description,
            'created_at' => $data['date_paiement'],
            'updated_at' => $data['date_paiement']
        ]);

        // Créer le détail de paiement
        PaiementDetail::create([
            'paiement_id' => $paiement->id,
            'inscription_id' => $data['inscription_id'],
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'type_frais_id' => $typeCantine->id,
            'montant' => $data['montant_encaisse'],
            'created_at' => $data['date_paiement'],
            'updated_at' => $data['date_paiement']
        ]);

        // Marquer les mois comme payés dans CantineMensuelle
        foreach ($data['mois_selectionnes'] as $mois) {
            CantineMensuelle::where('inscription_id', $data['inscription_id'])
                ->where('mois_scolaire_id', $mois['mois_id'])
                ->update([
                    'est_paye' => true,
                    'paiement_id' => $paiement->id
                ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Paiement Cantine enregistré avec succès.',
            'paiement_id' => $paiement->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur storePaiementMensuel', ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage()
        ]);
    }
}
}