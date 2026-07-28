<?php

namespace App\Http\Controllers;

use App\Exports\ElevesExport;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Paiement;
use App\Models\PaiementDetail;
use App\Models\TypeFrais;
use App\Models\Tarif;

use PDF;
use Illuminate\Support\Facades\Auth;

class EleveController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur|Administrateur')->except(['index', 'export', 'edit', 'update']);
    }

    public function index(Request $request)
    {
        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');

        $query = Inscription::with(['eleve', 'classe'])
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'active');

        // Filtre par classe
        $query->when($request->filled('classe_id'), function($q) use ($request) {
            return $q->where('classe_id', $request->classe_id);
        });

        // Filtre par nom ou prénom
        $query->when($request->filled('nom'), function($q) use ($request) {
            return $q->whereHas('eleve', function($q) use ($request) {
                $q->where('nom', 'like', '%'.$request->nom.'%')
                ->orWhere('prenom', 'like', '%'.$request->nom.'%');
            });
        });

        // Filtre par sexe
        $query->when($request->filled('sexe'), function($q) use ($request) {
            return $q->whereHas('eleve', function($q) use ($request) {
                $q->where('sexe', $request->sexe);
            });
        });

        // Filtre par cantine
        $query->when($request->filled('cantine'), function($q) use ($request) {
            if ($request->cantine == '1') {
                return $q->where('cantine_active', true);
            } else {
                return $q->where('cantine_active', false);
            }
        });

        // Filtre par transport
        $query->when($request->filled('transport'), function($q) use ($request) {
            if ($request->transport == '1') {
                return $q->where('transport_active', true);
            } else {
                return $q->where('transport_active', false);
            }
        });

        // Appliquer le tri
        $sort = $request->get('sort', 'asc');
        $query->when($request->filled('sort_by'), function($q) use ($request, $sort) {
            if (in_array($request->sort_by, ['nom', 'prenom', 'sexe'])) {
                return $q->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                        ->orderBy('eleves.'.$request->sort_by, $sort)
                        ->select('inscriptions.*');
            } elseif (in_array($request->sort_by, ['cantine_active', 'transport_active'])) {
                return $q->orderBy($request->sort_by, $sort);
            } else {
                return $q->orderBy($request->sort_by, $sort);
            }
        }, function($q) {
            return $q->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                    ->orderBy('eleves.nom', 'asc')
                    ->orderBy('eleves.prenom', 'asc')
                    ->select('inscriptions.*');
        });

        $inscriptions = $query->paginate(12);
        $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
    ->get();

        $fraiss = TypeFrais::all();
        $viewMode = $request->get('view_mode', 'grid');

        Log::info($inscriptions->count() . ' élèves chargés pour l\'index', [
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'filters' => $request->only(['classe_id', 'nom', 'sexe', 'cantine', 'transport']),
            'sort_by' => $request->get('sort_by'),
            'sort' => $request->get('sort'),
        ]);

        return view('dashboard.pages.eleves.index', compact('inscriptions', 'classes', 'fraiss', 'viewMode'));
    }
 
    public function refresh()
    {
        return redirect()->route('eleves.index')->with('success', 'Liste actualisée');
    }

    public function export(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
            abort(403, 'Vous n\'avez pas la permission d\'exporter la liste des élèves.');
        }

        $format = $request->format;
        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');
        
        $query = Inscription::with(['eleve', 'classe'])
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId);

        $query->when($request->filled('classe_id'), function($q) use ($request) {
            return $q->where('classe_id', $request->classe_id);
        });

        $query->when($request->filled('nom'), function($q) use ($request) {
            return $q->whereHas('eleve', function($q) use ($request) {
                $q->where('nom', 'like', '%'.$request->nom.'%')
                ->orWhere('prenom', 'like', '%'.$request->nom.'%');
            });
        });

        $query->when($request->filled('sexe'), function($q) use ($request) {
            return $q->whereHas('eleve', function($q) use ($request) {
                $q->where('sexe', $request->sexe);
            });
        });

        $query->when($request->filled('cantine'), function($q) use ($request) {
            return $q->where('cantine_active', $request->cantine == '1');
        });

        $query->when($request->filled('transport'), function($q) use ($request) {
            return $q->where('transport_active', $request->transport == '1');
        });

        $query->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom', 'asc')
            ->orderBy('eleves.prenom', 'asc')
            ->select('inscriptions.*');

        $eleves = $query->get();

        $filters = [
            'classe' => $request->classe_id ? Classe::find($request->classe_id)->nom : 'Toutes',
            'nom'    => $request->nom ?: 'Tous',
            'sexe'   => $request->sexe ?: 'Tous',
        ];

        if ($request->filled('cantine')) {
            $filters['cantine'] = $request->cantine == '1' ? 'Oui' : 'Non';
        }

        if ($request->filled('transport')) {
            $filters['transport'] = $request->transport == '1' ? 'Oui' : 'Non';
        }

        if ($format === 'excel') {
            return Excel::download(new ElevesExport($eleves, $filters), 'liste_eleves_' . date('Y-m-d') . '.xlsx');
        }

        if ($format === 'pdf') {
            $data = [
                'eleves'  => $eleves,
                'title'   => 'Liste des Élèves',
                'date'    => now()->locale('fr')->translatedFormat('d F Y'),
                'filters' => $filters
            ];
            
            $pdf = PDF::loadView('dashboard.documents.liste', $data)
                    ->setPaper('a4', 'landscape');

            return $pdf->stream('liste_eleves_' . date('Y-m-d') . '.pdf');
        }

        return redirect()->back()->with('error', 'Format non supporté');
    }

   public function create(Request $request)
{
    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');
    
    $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
        ->ordered()
        ->get();

    // Récupérer les types de frais
    $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
    $scolarite = TypeFrais::where('nom', 'Scolarité')->first();
    $transports = TypeFrais::where('nom', 'Transport')->first();
    $cantines = TypeFrais::where('nom', 'Cantine')->first();
    
    // Récupérer TOUS les tarifs (avec niveau_id = NULL pour les génériques)
    $tarifs = Tarif::where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->get()
        ->groupBy('type_frais_id');

    return view('dashboard.pages.eleves.create', compact(
        'classes',
        'fraisInscription',
        'scolarite',
        'transports',
        'cantines',
        'tarifs'
    ));
}
  

public function store(Request $request)
{
    $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'num_extrait' => 'nullable|string|max:255',
        'naissance' => 'required|date',
        'lieu_naissance' => 'nullable|string|max:255',
        'sexe' => 'required|in:Masculin,Féminin',
        'nationalite' => 'nullable|string|max:255',
        'code_national' => 'nullable|string|max:255',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        'infos_medicales' => 'nullable|string',
        'pere_nom' => 'required|string|max:255',
        'pere_contact' => 'required|string|max:20',
        'pere_contact02' => 'nullable|string|max:20',
        'mere_nom' => 'nullable|string|max:255',
        'mere_contact' => 'nullable|string|max:20',
        'mere_contact02' => 'nullable|string|max:20',
        'parent_adresse' => 'nullable|string|max:255',
        'classe_id' => 'required|exists:classes,id',
        'transport_active' => 'nullable|boolean',
        'cantine_active' => 'nullable|boolean',
        'parent_nom' => 'nullable|string|max:255',
        'parent_telephone' => 'nullable|string|max:20',
        'parent_telephone02' => 'nullable|string|max:20',
        'mode_paiement' => 'nullable|string',
        'frais_inscription' => 'nullable|numeric|min:0',
        'frais_scolarite' => 'nullable|numeric|min:0',
        'frais_transport' => 'nullable|numeric|min:0',
        'frais_cantine' => 'nullable|numeric|min:0',
        'date_paiement' => 'nullable|date',
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $matricule = $this->genererMatriculeEleve($ecoleId);

    $photoPath = null;
    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
        $photoPath = $request->file('photo')->store('eleves_photos', 'public');
    }

    $transportActive = $request->has('transport_active') && $request->input('transport_active') !== 'off';
    $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') !== 'off';

    DB::beginTransaction();

    try {
        // 1. Création de l'élève
        $eleve = Eleve::create([
            'matricule' => $matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'num_extrait' => $request->num_extrait,
            'sexe' => $request->sexe,
            'naissance' => $request->naissance,
            'lieu_naissance' => $request->lieu_naissance,
            'nationalite' => $request->nationalite ?? 'Ivoirienne',
            'photo_path' => $photoPath,
            'infos_medicales' => $request->infos_medicales,
            'code_national' => $request->code_national,
            'pere_nom' => $request->pere_nom,
            'pere_contact' => $request->pere_contact,
            'pere_contact02' => $request->pere_contact02,
            'mere_nom' => $request->mere_nom,
            'mere_contact' => $request->mere_contact,
            'mere_contact02' => $request->mere_contact02,
            'parent_adresse' => $request->parent_adresse,
            'is_active' => true,
            'parent_nom' => $request->parent_nom ?? $request->pere_nom,
            'parent_telephone' => $request->parent_telephone ?? $request->pere_contact,
            'parent_telephone02' => $request->parent_telephone02 ?? $request->pere_contact02,
            'ecole_id' => $ecoleId,
            'classe_id' => $request->classe_id,
            'annee_scolaire_id' => $anneeScolaireId,
        ]);

        // 2. Récupérer le niveau de la classe
        $classe = Classe::with('niveau')->find($request->classe_id);
        $niveauId = $classe->niveau_id;

        // 3. Création de l'inscription
        $inscription = Inscription::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'eleve_id' => $eleve->id,
            'classe_id' => $request->classe_id,
            'cantine_active' => $cantineActive,
            'transport_active' => $transportActive,
            'cantine_start_date' => $cantineActive ? now() : null,
            'transport_start_date' => $transportActive ? now() : null,
            'cantine_end_date' => null,
            'transport_end_date' => null,
        ]);

        // 4. Gestion du paiement
        $fraisInscription = floatval($request->frais_inscription ?? 0);
        $fraisScolarite = floatval($request->frais_scolarite ?? 0);
        $fraisTransport = floatval($request->frais_transport ?? 0);
        $fraisCantine = floatval($request->frais_cantine ?? 0);
        
        $totalPaiement = $fraisInscription + $fraisScolarite + $fraisTransport + $fraisCantine;

        Log::info('Montants de paiement reçus', [
            'inscription' => $fraisInscription,
            'scolarite' => $fraisScolarite,
            'transport' => $fraisTransport,
            'cantine' => $fraisCantine,
            'total' => $totalPaiement
        ]);

        if ($totalPaiement > 0) {
            $datePaiement = $request->date_paiement ?? now();
            $modePaiement = $request->mode_paiement ?? 'especes';

            // 4.1 Créer le paiement
            $paiement = Paiement::create([
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_id' => $ecoleId,
                'montant' => $totalPaiement,
                'mode_paiement' => $modePaiement,
                'reference' => $request->reference ?? null,
                'user_id' => auth()->id(),
                'created_at' => $datePaiement,
                'updated_at' => $datePaiement
            ]);

            Log::info('Paiement créé', ['paiement_id' => $paiement->id, 'montant' => $totalPaiement]);

            // Récupérer les types de frais
            $typeInscription = TypeFrais::where('nom', "Frais d'inscription")->first();
            $typeScolarite = TypeFrais::where('nom', "Scolarité")->first();
            $typeTransport = TypeFrais::where('nom', "Transport")->first();
            $typeCantine = TypeFrais::where('nom', "Cantine")->first();

            // 4.2 Récupérer les tarifs correspondants
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

            // 4.3 Détail pour l'inscription
            if ($fraisInscription > 0 && $tarifInscription) {
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'inscription_id' => $inscription->id,
                    'tarif_id' => $tarifInscription->id,
                    'montant' => $fraisInscription
                ]);
                Log::info('Détail inscription créé', ['montant' => $fraisInscription]);
            }

            // 4.4 Détail pour la scolarité
            if ($fraisScolarite > 0 && $tarifScolarite) {
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'inscription_id' => $inscription->id,
                    'tarif_id' => $tarifScolarite->id,
                    'montant' => $fraisScolarite
                ]);
                Log::info('Détail scolarité créé', ['montant' => $fraisScolarite]);
            }

            // 4.5 Détail pour le transport
            if ($fraisTransport > 0 && $tarifTransport) {
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'inscription_id' => $inscription->id,
                    'tarif_id' => $tarifTransport->id,
                    'montant' => $fraisTransport
                ]);
                Log::info('Détail transport créé', ['montant' => $fraisTransport]);
            }

            // 4.6 Détail pour la cantine
            if ($fraisCantine > 0 && $tarifCantine) {
                PaiementDetail::create([
                    'paiement_id' => $paiement->id,
                    'inscription_id' => $inscription->id,
                    'tarif_id' => $tarifCantine->id,
                    'montant' => $fraisCantine
                ]);
                Log::info('Détail cantine créé', ['montant' => $fraisCantine]);
            }

            Log::info('Paiement complet enregistré', [
                'eleve_id' => $eleve->id,
                'inscription_id' => $inscription->id,
                'total' => $totalPaiement
            ]);
        } else {
            Log::info('Aucun paiement enregistré (total = 0)');
        }

        DB::commit();

        activity()
            ->performedOn($eleve)
            ->causedBy(auth()->user())
            ->withProperties([
                'matricule' => $eleve->matricule,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'classe_id' => $request->classe_id,
                'paiement' => $totalPaiement > 0 ? $totalPaiement : 'Aucun',
                'ip' => $request->ip()
            ])
            ->log("Nouvel élève inscrit : {$eleve->nom} {$eleve->prenom}");

        $message = 'Élève inscrit avec succès!';
        if ($totalPaiement > 0) {
            $message .= ' Paiement de ' . number_format($totalPaiement, 0, ',', ' ') . ' FCFA enregistré.';
        }

        return redirect()->route('eleves.index')->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());
        Log::error('Trace: ' . $e->getTraceAsString());
        return redirect()->back()->with('error', 'Erreur lors de l\'inscription: ' . $e->getMessage());
    }
}

    private function genererMatriculeEleve(int $ecoleId): string
    {
        $ecole = Ecole::findOrFail($ecoleId);
        $alias = strtoupper($ecole->sigle_ecole);

        do {
            $dernierEleve = Eleve::where('ecole_id', $ecoleId)
                ->where('matricule', 'like', $alias . '-%')
                ->orderByDesc('id')
                ->first();

            $dernierNumero = 0;
            if ($dernierEleve && preg_match('/-(\d+)$/', $dernierEleve->matricule, $matches)) {
                $dernierNumero = intval($matches[1]);
            }

            $nouveauNumero = $dernierNumero + 1;
            $numeroFormate = str_pad($nouveauNumero, 5, '0', STR_PAD_LEFT);
            $matricule = $alias . '-' . $numeroFormate;

        } while (Eleve::where('matricule', $matricule)->exists());

        return $matricule;
    }

    public function show($id)
    {
        $ecoleId = session('current_ecole_id');
        
        $inscription = Inscription::with(['eleve', 'classe', 'anneeScolaire', 'paiements.typeFrais', 'paiements.mois'])
            ->where('ecole_id', $ecoleId)
            ->findOrFail($id);
            
        return view('dashboard.pages.eleves.show', compact('inscription'));
    }

   public function edit($id)
{
    if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
        abort(403, 'Vous n\'avez pas la permission d\'éditer cet élève.');
    }

    $anneeScolaireId = session('current_annee_scolaire_id');
    $ecoleId = session('current_ecole_id');

    // Vérifier que l'inscription appartient bien à l'école de l'utilisateur
    $inscription = Inscription::with('eleve')
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->findOrFail($id);
    
    $eleve = $inscription->eleve;

    $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
    $scolarite = TypeFrais::where('nom', 'Scolarité')->first();
    $transports = TypeFrais::where('nom', 'Transport')->first();
    $cantines = TypeFrais::where('nom', 'Cantine')->first();
    
    // Récupérer TOUS les tarifs (avec niveau_id = NULL pour les génériques)
    $tarifs = Tarif::where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->get()
        ->groupBy('type_frais_id');

    $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
        ->ordered()
        ->get();

    return view('dashboard.pages.eleves.edit', compact('inscription', 'eleve', 'classes', 'transports', 'cantines', 'tarifs', 'fraisInscription', 'scolarite'));
}

  /**
 * Gère les dates de début/fin pour un service (transport ou cantine)
 */
private function handleServiceDates($isActive, $wasActive, $startDateField, $endDateField)
{
    $data = [];
    
    if ($isActive) {
        // Si le service est activé
        if (!$wasActive) {
            // Si c'était désactivé avant, on met la date de début à maintenant
            $data[$startDateField] = now();
            $data[$endDateField] = null;
        }
        // Si déjà actif, on ne modifie pas les dates
    } else {
        // Si le service est désactivé
        if ($wasActive) {
            // Si c'était actif avant, on met la date de fin à maintenant
            $data[$endDateField] = now();
            // On garde la date de début pour historique
        }
        // Si déjà inactif, on ne modifie pas les dates
    }
    
    return $data;
}

public function update(Request $request, $id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur', 'Directeur'])) {
            abort(403, 'Vous n\'avez pas la permission d\'éditer cet élève.');
        }

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'num_extrait' => 'nullable|string|max:255',
            'naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'nationalite' => 'nullable|string|max:255',
            'code_national' => 'nullable|string|max:255',
            'photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'infos_medicales' => 'nullable|string',
            'pere_nom' => 'nullable|string|max:255',
            'pere_contact' => 'nullable|string|max:20',
            'pere_contact02' => 'nullable|string|max:20',
            'mere_nom' => 'nullable|string|max:255',
            'mere_contact' => 'nullable|string|max:20',
            'mere_contact02' => 'nullable|string|max:20',
            'parent_adresse' => 'nullable|string|max:255',
            'classe_id' => 'required|exists:classes,id',
            'parent_nom' => 'nullable|string|max:255',
            'parent_telephone' => 'nullable|string|max:20',
            'parent_telephone02' => 'nullable|string|max:20',
        ]);

        // Vérifier que l'inscription appartient bien à l'école de l'utilisateur
        $inscription = Inscription::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->findOrFail($id);
        
        $eleve = $inscription->eleve;

        // Récupérer les valeurs des checkboxes
        $transportActive = $request->has('transport_active') && $request->input('transport_active') == '1';
        $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') == '1';

        // Préparer les données de mise à jour de l'inscription
        $inscriptionData = [
            'classe_id' => $request->classe_id,
            'cantine_active' => $cantineActive,
            'transport_active' => $transportActive,
        ];

        // Gestion des dates pour le Transport
        $transportDates = $this->handleServiceDates(
            $transportActive,
            $inscription->transport_active,
            'transport_start_date',
            'transport_end_date'
        );
        $inscriptionData = array_merge($inscriptionData, $transportDates);

        // Gestion des dates pour la Cantine
        $cantineDates = $this->handleServiceDates(
            $cantineActive,
            $inscription->cantine_active,
            'cantine_start_date',
            'cantine_end_date'
        );
        $inscriptionData = array_merge($inscriptionData, $cantineDates);

        // Log des changements de dates
        Log::info('Mise à jour des dates de service', [
            'inscription_id' => $inscription->id,
            'transport' => [
                'active' => $transportActive,
                'was_active' => $inscription->transport_active,
                'start_date' => $transportDates['transport_start_date'] ?? $inscription->transport_start_date,
                'end_date' => $transportDates['transport_end_date'] ?? $inscription->transport_end_date,
            ],
            'cantine' => [
                'active' => $cantineActive,
                'was_active' => $inscription->cantine_active,
                'start_date' => $cantineDates['cantine_start_date'] ?? $inscription->cantine_start_date,
                'end_date' => $cantineDates['cantine_end_date'] ?? $inscription->cantine_end_date,
            ]
        ]);

        // Préparer les données de l'élève
        $updateData = [
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'num_extrait' => $request->num_extrait,
            'sexe' => $request->sexe,
            'naissance' => $request->naissance,
            'lieu_naissance' => $request->lieu_naissance,
            'nationalite' => $request->nationalite ?? 'Ivoirienne',
            'infos_medicales' => $request->infos_medicales,
            'code_national' => $request->code_national,
            'pere_nom' => $request->pere_nom,
            'pere_contact' => $request->pere_contact,
            'pere_contact02' => $request->pere_contact02,
            'mere_nom' => $request->mere_nom,
            'mere_contact' => $request->mere_contact,
            'mere_contact02' => $request->mere_contact02,
            'parent_adresse' => $request->parent_adresse,
            'classe_id' => $request->classe_id,
            'parent_nom' => $request->parent_nom ?? $request->pere_nom,
            'parent_telephone' => $request->parent_telephone ?? $request->pere_contact,
            'parent_telephone02' => $request->parent_telephone02 ?? $request->pere_contact02,
        ];

        // Gestion de la photo
        if ($request->hasFile('photo_path') && $request->file('photo_path')->isValid()) {
            // Supprimer l'ancienne photo si elle existe
            if ($eleve->photo_path && \Storage::disk('public')->exists($eleve->photo_path)) {
                \Storage::disk('public')->delete($eleve->photo_path);
            }
            
            // Upload nouvelle photo dans le dossier "eleves_photos"
            $path = $request->file('photo_path')->store('eleves_photos', 'public');
            $updateData['photo_path'] = $path;
        }

        // Mettre à jour l'élève
        $eleve->update($updateData);

        // Mettre à jour l'inscription AVEC les dates
        $inscription->update($inscriptionData);

        // Log de l'activité
        activity()
            ->performedOn($eleve)
            ->causedBy(auth()->user())
            ->withProperties([
                'matricule' => $eleve->matricule,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'transport_active' => $transportActive,
                'cantine_active' => $cantineActive,
                'transport_start_date' => $inscription->transport_start_date,
                'transport_end_date' => $inscription->transport_end_date,
                'cantine_start_date' => $inscription->cantine_start_date,
                'cantine_end_date' => $inscription->cantine_end_date,
                'ip' => $request->ip()
            ])
            ->log("Élève modifié : {$eleve->nom} {$eleve->prenom}");

        return redirect()->route('eleves.index')->with('success', 'Élève modifié avec succès!');
    }

    public function destroy($id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdministrateur', 'Administrateur'])) {
            abort(403, 'Vous n\'avez pas la permission de supprimer un élève.');
        }

        $ecoleId = session('current_ecole_id');

        try {
            DB::beginTransaction();

            // Vérifier que l'élève appartient bien à l'école de l'utilisateur
            $eleve = Eleve::where('ecole_id', $ecoleId)->findOrFail($id);
            
            foreach ($eleve->inscriptions as $inscription) {
                foreach ($inscription->paiements as $paiement) {
                    $paiement->details()->delete();
                    $paiement->delete();
                }
                
                $inscription->reductions()->delete();
                $inscription->notes()->delete();
                $inscription->delete();
            }
            
            $eleve->reinscriptions()->delete();
            $eleve->reductions()->delete();
            $eleve->delete();

            DB::commit();

            Log::info('Élève supprimé', ['eleve_id' => $eleve->id, 'matricule' => $eleve->matricule, 'ecole_id' => $ecoleId]);
            return redirect()->route('eleves.index')->with('success', 'Élève supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression élève: ' . $e->getMessage());
            
            return redirect()->route('eleves.index')
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }


  
}