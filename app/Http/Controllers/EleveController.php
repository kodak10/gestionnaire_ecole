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

class EleveController extends Controller
{
   
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
            // Tri par défaut : nom puis prénom
            return $q->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                    ->orderBy('eleves.nom', 'asc')
                    ->orderBy('eleves.prenom', 'asc')
                    ->select('inscriptions.*');
        });

        $inscriptions = $query->paginate(12);
        $classes = Classe::all();
        $fraiss = TypeFrais::all();
        $viewMode = $request->get('view_mode', 'grid');

        return view('dashboard.pages.eleves.index', compact('inscriptions', 'classes', 'fraiss', 'viewMode'));
    }
 
    public function refresh()
    {
        return redirect()->route('eleves.index')->with('success', 'Liste actualisée');
    }

    public function export(Request $request)
    {
        $format = $request->format;

        $anneeScolaireId = session('current_annee_scolaire_id');
        $ecoleId = session('current_ecole_id');
        
        // Récupérer les élèves avec les mêmes filtres que l'index
        $query = Inscription::with(['eleve', 'classe'])
            ->where('inscriptions.ecole_id', $ecoleId)
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId);

        // Appliquer les filtres
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

        // 🔹 Tri toujours par nom puis prénom
        $query->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom', 'asc')
            ->orderBy('eleves.prenom', 'asc')
            ->select('inscriptions.*');

        $eleves = $query->get();

        // 🔹 Construire les filtres dynamiquement
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

        // 🔹 Export Excel
        if ($format === 'excel') {
            return Excel::download(new ElevesExport($eleves, $filters), 'liste_eleves_' . date('Y-m-d') . '.xlsx');
        }

        // 🔹 Export PDF
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
        
        // Filtrer et trier les classes
        $classes = Classe::where('ecole_id', $ecoleId)
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->orderBy('nom', 'desc')      // tri par nom
                        ->get();

        // Récupérer les types de frais
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
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'num_extrait' => 'nullable|string|max:255',
            'naissance' => 'required|date',
            'sexe' => 'required|in:Masculin,Féminin',
            'classe_id' => 'required|exists:classes,id',
            'parent_nom' => 'required|string|max:255',
            'parent_telephone' => 'required|string|max:20',
            'mode_paiement' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'code_national' => 'nullable|string|max:10',
        ]);

        $classe = Classe::with('ecole')->findOrFail($request->classe_id);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Génération du matricule
        $matricule = $this->genererMatriculeEleve($ecoleId);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('eleves/photos', 'public');
        }

        $eleve = Eleve::create([
            'matricule' => $matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'num_extrait' => $request->num_extrait,
            'sexe' => $request->sexe,
            'naissance' => $request->naissance,
            'lieu_naissance' => $request->lieu_naissance,
            'photo_path' => $photoPath,
            'infos_medicales' => $request->infos_medicales,
            'parent_nom' => $request->parent_nom,
            'parent_telephone' => $request->parent_telephone,
            'parent_telephone02' => $request->parent_telephone02,
            'code_national' => $request->code_national,
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
        ]);

        // Créer l'inscription
        $inscription = Inscription::create([
            'annee_scolaire_id' => $anneeScolaireId,
            'ecole_id' => $ecoleId,
            'eleve_id' => $eleve->id,
            'classe_id' => $request->classe_id,
            'cantine_active' => $request->has('cantine_active'),
            'transport_active' => $request->has('transport_active'),
        ]);

        return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès!');
    }

    private function genererMatriculeEleve(int $ecoleId): string
    {
        $ecole = Ecole::findOrFail($ecoleId);
        $alias = strtoupper($ecole->sigle_ecole);

        do {
            // Récupérer le dernier numéro
            $dernierEleve = Eleve::where('ecole_id', $ecoleId)
                ->where('matricule', 'like', $alias . '-%')
                ->orderByDesc('id') // id est plus fiable que created_at
                ->first();

            $dernierNumero = 0;
            if ($dernierEleve && preg_match('/-(\d+)$/', $dernierEleve->matricule, $matches)) {
                $dernierNumero = intval($matches[1]);
            }

            $nouveauNumero = $dernierNumero + 1;
            $numeroFormate = str_pad($nouveauNumero, 5, '0', STR_PAD_LEFT);
            $matricule = $alias . '-' . $numeroFormate;

            // On boucle tant que le matricule existe déjà
        } while (Eleve::where('matricule', $matricule)->exists());

        return $matricule;
    }

    public function show($id)
    {
        $inscription = Inscription::with(['eleve', 'classe', 'anneeScolaire', 'paiements.typeFrais', 'paiements.mois'])->findOrFail($id);
        return view('dashboard.pages.eleves.show', compact('inscription'));
    }

    public function edit($id)
    {
        $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
        $scolarite = TypeFrais::where('nom', 'Scolarité')->first();

        $transports = TypeFrais::where('nom', 'Transport')->first();
        $cantines = TypeFrais::where('nom', 'Cantine')->first();
        $tarifs = Tarif::all();

        $eleve = Inscription::findOrFail($id);
        $classes = Classe::all();

        return view('dashboard.pages.eleves.edit', compact('eleve', 'classes', 'transports', 'cantines', 'tarifs', 'fraisInscription', 'scolarite'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'photo_path' => 'nullable|image|mimes:jpeg,jpg,png|max:4096',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'classe_id' => 'required|exists:classes,id',
            'parent_nom' => 'required|string|max:255',
            'parent_telephone' => 'required|string|max:11',
            'parent_telephone02' => 'nullable|string|max:11',
        ]);

        $inscription = Inscription::with('eleve')->findOrFail($id);
        $eleve = $inscription->eleve;

        // Upload photo si nécessaire
        if ($request->hasFile('photo_path')) {
            $validatedData['photo_path'] = $request->file('photo_path')->store('eleves_photos', 'public');
        }

        // Mettre à jour l'élève
        $eleve->update($validatedData);

        // Mettre à jour l'inscription : classe, transport et cantine
        $inscription->update([
            'classe_id' => $request->classe_id,
            'cantine_active' => $request->has('cantine_active'),
            'transport_active' => $request->has('transport_active'),
        ]);

        return redirect()->route('eleves.index')->with('success', 'Élève mis à jour avec succès');
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $eleve = Eleve::findOrFail($id);
            
            // Supprimer d'abord les relations enfants
            foreach ($eleve->inscriptions as $inscription) {
                // Supprimer les paiements_details liés aux inscriptions
                foreach ($inscription->paiements as $paiement) {
                    // Supprimer les détails de paiement
                    $paiement->details()->delete();
                    // Supprimer le paiement
                    $paiement->delete();
                }
                
                // Supprimer les réductions liées
                $inscription->reductions()->delete();
                
                // Supprimer les notes liées
                $inscription->notes()->delete();
                
                // Supprimer l'inscription
                $inscription->delete();
            }
            
            // Supprimer les réinscriptions
            $eleve->reinscriptions()->delete();
            
            // Supprimer les réductions directes
            $eleve->reductions()->delete();
            
            // Finalement supprimer l'élève
            $eleve->delete();

            DB::commit();

            Log::info('Élève supprimé', ['eleve_id' => $eleve->id, 'matricule' => $eleve->matricule]);
            return redirect()->route('eleves.index')->with('success', 'Élève supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression élève: ' . $e->getMessage());
            
            return redirect()->route('eleves.index')
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
   
}