<?php

namespace App\Http\Controllers;

use App\Exports\ElevesExport;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
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

        $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
        $scolarite        = TypeFrais::where('nom', 'Scolarité')->first();
        $transports       = TypeFrais::where('nom', 'Transport')->first();
        $cantines         = TypeFrais::where('nom', 'Cantine')->first();
        $tarifs           = Tarif::all();

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
    ]);

    $ecoleId = session('current_ecole_id'); 
    $anneeScolaireId = session('current_annee_scolaire_id');

    $matricule = $this->genererMatriculeEleve($ecoleId);

    $photoPath = null;
    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
        // Upload dans le dossier "eleves_photos"
        $photoPath = $request->file('photo')->store('eleves_photos', 'public');
    }

    $transportActive = $request->has('transport_active') && $request->input('transport_active') !== 'off';
    $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') !== 'off';

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

    $inscription = Inscription::create([
        'annee_scolaire_id' => $anneeScolaireId,
        'ecole_id' => $ecoleId,
        'eleve_id' => $eleve->id,
        'classe_id' => $request->classe_id,
        'cantine_active' => $cantineActive,
        'transport_active' => $transportActive,
    ]);

    activity()
        ->performedOn($eleve)
        ->causedBy(auth()->user())
        ->withProperties([
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'classe_id' => $request->classe_id,
            'ip' => $request->ip()
        ])
        ->log("Nouvel élève inscrit : {$eleve->nom} {$eleve->prenom}");

    return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès!');
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
        $tarifs = Tarif::all();

       $classes = Classe::forEcoleAndAnnee($ecoleId, $anneeScolaireId)
    ->ordered()
    ->get();

        return view('dashboard.pages.eleves.edit', compact('inscription', 'eleve', 'classes', 'transports', 'cantines', 'tarifs', 'fraisInscription', 'scolarite'));
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

    $transportActive = $request->has('transport_active') && $request->input('transport_active') == '1';
    $cantineActive = $request->has('cantine_active') && $request->input('cantine_active') == '1';

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

    // Gestion de la photo - avec le bon dossier "eleves_photos"
    if ($request->hasFile('photo_path') && $request->file('photo_path')->isValid()) {
        // Supprimer l'ancienne photo si elle existe
        if ($eleve->photo_path && \Storage::disk('public')->exists($eleve->photo_path)) {
            \Storage::disk('public')->delete($eleve->photo_path);
        }
        
        // Upload nouvelle photo dans le dossier "eleves_photos"
        $path = $request->file('photo_path')->store('eleves_photos', 'public');
        $updateData['photo_path'] = $path;
    }

    $eleve->update($updateData);

    $inscription->update([
        'classe_id' => $request->classe_id,
        'cantine_active' => $cantineActive,
        'transport_active' => $transportActive,
    ]);

    activity()
        ->performedOn($eleve)
        ->causedBy(auth()->user())
        ->withProperties([
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
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