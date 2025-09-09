<?php

namespace App\Http\Controllers;

use App\Exports\ElevesExport;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\Tarif;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class EleveController extends Controller
{
   
        public function index(Request $request)
    {
        $query = Inscription::with(['eleve', 'classe', 'anneeScolaire']);

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
            return $q->whereHas('eleve', function($q) use ($request) {
                if ($request->cantine == '1') {
                    $q->where('cantine_active', true);
                } else {
                    $q->where('cantine_active', false);
                }
            });
        });

        // Filtre par transport
        $query->when($request->filled('transport'), function($q) use ($request) {
            return $q->whereHas('eleve', function($q) use ($request) {
                if ($request->transport == '1') {
                    $q->where('transport_active', true);
                } else {
                    $q->where('transport_active', false);
                }
            });
        });

        // Tri
        $sort = $request->get('sort', 'asc');
        $query->when($request->filled('sort_by'), function($q) use ($request, $sort) {
            if (in_array($request->sort_by, ['nom', 'prenom', 'sexe', 'cantine_active', 'transport_active'])) {
                return $q->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
                        ->orderBy('eleves.'.$request->sort_by, $sort)
                        ->select('inscriptions.*');
            } else {
                return $q->orderBy($request->sort_by, $sort);
            }
        }, function($q) {
            return $q->orderBy('created_at', 'desc');
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

    public function export($type)
    {
        if ($type == 'pdf') {
            $eleves = Eleve::with('classe')->get();
            $pdf = PDF::loadView('exports.eleves-pdf', compact('eleves'));
            return $pdf->download('eleves-list.pdf');
        }

        return Excel::download(new ElevesExport, 'eleves-list.xlsx');
    }

    public function create()
    {
        $anneeActive = AnneeScolaire::where('est_active', true)->first();
        $classes = Classe::where('annee_scolaire_id', $anneeActive->id)->get();
        
        $fraisInscription = TypeFrais::where('nom', 'Frais d\'inscription')->first();
        $scolarite = TypeFrais::where('nom', 'Scolarité')->first();
        $transports = TypeFrais::where('nom', 'Transport')->first();
        $cantines = TypeFrais::where('nom', 'Cantine')->first();
        $tarifs = Tarif::all();

        return view('dashboard.pages.eleves.create', compact(
            'classes',
            'fraisInscription',
            'scolarite',
            'transports',
            'cantines',
            'tarifs'
        ));
    }

    private function creerPaiementsPourEleve(Eleve $eleve, ?string $modePaiement = null, ?int $userId = null): void
{
    // Récupérer le niveau de l'élève depuis la classe
    $niveauId = $eleve->classe->niveau_id;

    // Récupérer tous les tarifs mensuels pour ce niveau
    $tarifs = TarifMensuel::where('niveau_id', $niveauId)->get();

    foreach ($tarifs as $tarif) {
        // Vérifier s'il n'existe pas déjà un paiement identique (éviter doublons)
        $paiementExiste = $eleve->paiements()
            ->where('type_frais_id', $tarif->type_frais_id)
            ->where('mois_id', $tarif->mois_id)
            ->exists();

        if ($paiementExiste) continue; // passer au suivant

        // Créer un paiement avec le montant du tarif et infos basiques
        $eleve->paiements()->create([
            'type_frais_id' => $tarif->type_frais_id,
            'mois_id' => $tarif->mois_id,
            'montant' => $tarif->montant,
            'mode_paiement' => $modePaiement ?? 'Non spécifié',
            'reference' => null,
            'user_id' => 1, // à récupérer  selon contexte (auth()->id() par exemple)
        ]);
    }
}

private function genererMatriculeEleve(int $ecoleId): string
{
    // Récupérer l'alias de l'école
    $ecole = Ecole::findOrFail($ecoleId);
    $alias = strtoupper($ecole->sigle_ecole);

   // Trouver le dernier matricule pour cette école en cherchant par pattern '%ALIAS-%'
    $dernierEleve = Eleve::where('ecole_id', $ecoleId)
    ->where('matricule', 'like', $alias . '-%')
    ->orderByDesc('created_at')
    ->first();


    $dernierNumero = 0;

    if ($dernierEleve && preg_match('/-(\d+)$/', $dernierEleve->matricule, $matches)) {
        $dernierNumero = intval($matches[1]);
    }

    $nouveauNumero = $dernierNumero + 1;

    // Formatage sur 5 chiffres, ex: 00001
    $numeroFormate = str_pad($nouveauNumero, 5, '0', STR_PAD_LEFT);

    return $alias . '-' . $numeroFormate;
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

        'montant_scolarite' => 'nullable|numeric|min:0',
        'montant_transport' => 'nullable|numeric|min:0',
        'montant_cantine' => 'nullable|numeric|min:0',
        'code_national' => 'nullable|string|max:10',
        // 'documents_fournis.*' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120'
    ]);

    $classe = Classe::with('ecole')->findOrFail($request->classe_id);
    $ecoleId = $classe->ecole->id;

    // Génération du matricule
    $matricule = $this->genererMatriculeEleve($ecoleId);

    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('eleves/photos', 'public');
    }

    $documentsPaths = [];
    if ($request->hasFile('documents_fournis')) {
        foreach ($request->file('documents_fournis') as $file) {
            $documentsPaths[] = $file->store('eleves/documents', 'public');
        }
    }

    $ecoleId = auth()->user()->ecole_id ?? 1;
    $anneeScolaireId = session('annee_scolaire_id') ?? auth()->user()->annee_scolaire_id ;

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
        'parent_email' => $request->parent_email,
        'code_national' => $request->code_national,
        'ecole_id' => $ecoleId,
        'annee_scolaire_id' => $anneeScolaireId,
        // 'documents_fournis' => !empty($documentsPaths) ? json_encode($documentsPaths) : null,
    ]);

    // CRÉATION DE L'INSCRIPTION
    // Récupérer l'année scolaire active
    $anneeActive = AnneeScolaire::where('est_active', true)->first();
    
    if (!$anneeActive) {
        return redirect()->back()->with('error', 'Aucune année scolaire active trouvée');
    }

    // Créer l'inscription
    $inscription = Inscription::create([
        'annee_scolaire_id' => $anneeScolaireId,
        'ecole_id' => $ecoleId,
        'eleve_id' => $eleve->id,
        'classe_id' => $request->classe_id,
        'cantine_active' => $request->has('cantine_active'),
        'transport_active' => $request->has('transport_active'),
        //'statut' => 'confirmée', // ou autre statut approprié
        //'date_inscription' => now(),
    ]);

    // Recupérer tous les mois scolaires liés à cette année
    $moisScolaires = MoisScolaire::get();

    // Pour simplicité ici, on enregistre un paiement pour chaque type sur le premier mois de l'année scolaire
    $premierMois = $moisScolaires->sortBy('numero')->first();

    // Créer un paiement Scolarité s'il y a un montant > 0
    if ($request->montant_scolarite > 0) {
        $eleve->paiements()->create([
            'type_frais_id' => TypeFrais::where('nom', 'Scolarité')->first()->id ?? null,
            'mois_id' => $premierMois->id,
            'montant' => $request->montant_scolarite,
            'mode_paiement' => $request->mode_paiement,
            'reference' => null,
            'user_id' => auth()->id() ?? 1,
            'ecole_id' => $ecoleId,
            'created_at' => $request->date_paiement ?? now(),
            'updated_at' => $request->date_paiement ?? now(),
        ]);
    }

    // Même principe pour Transport
    if ($request->montant_transport > 0) {
        $eleve->paiements()->create([
            'type_frais_id' => TypeFrais::where('nom', 'Transport')->first()->id ?? null,
            'mois_id' => $premierMois->id,
            'montant' => $request->montant_transport,
            'mode_paiement' => $request->mode_paiement,
            'reference' => null,
            'user_id' => auth()->id() ?? 1,
            'ecole_id' => $ecoleId,
            'created_at' => $request->date_paiement ?? now(),
            'updated_at' => $request->date_paiement ?? now(),
        ]);
    }

    // Même principe pour Cantine
    if ($request->montant_cantine > 0) {
        $eleve->paiements()->create([
            'type_frais_id' => TypeFrais::where('nom', 'Cantine')->first()->id ?? null,
            'mois_id' => $premierMois->id,
            'montant' => $request->montant_cantine,
            'mode_paiement' => $request->mode_paiement,
            'reference' => null,
            'user_id' => auth()->id() ?? 1,
            'ecole_id' => $ecoleId,
            'created_at' => $request->date_paiement ?? now(),
            'updated_at' => $request->date_paiement ?? now(),
        ]);
    }

    return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès!');
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

        $eleve = Eleve::findOrFail($id);
        $classes = Classe::all();
        return view('dashboard.pages.eleves.edit', compact('eleve', 'classes', 'transports', 'cantines', 'tarifs', 'fraisInscription', 'scolarite'));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $validatedData = $request->validate([
            'photo_path' => 'nullable|image|mimes:jpeg,jpg,png|max:4096', // max 4MB
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:Masculin,Féminin',
            'nationalite' => 'nullable|string|max:255',
            'num_extrait' => 'nullable|string|max:255',
            'parent_nom' => 'required|string|max:255',
            'parent_telephone' => 'required|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'parent_adresse' => 'nullable|string|max:1000',
            'parent_profession' => 'nullable|string|max:255',
            'parent_lien' => 'nullable|string|max:255',
            'classe_id' => 'required|exists:classes,id',
            'reduction' => 'nullable|numeric|min:0',
        ]);

        $eleve = Eleve::findOrFail($id);

        $validated['transport_active'] = $request->has('transport_active');
        $validated['cantine_active']   = $request->has('cantine_active');



        // Gérer l'upload photo si présent
        if ($request->hasFile('photo_path')) {
            $path = $request->file('photo_path')->store('eleves_photos', 'public');
            $validatedData['photo_path'] = $path;

            // Optionnel: supprimer l'ancienne photo ici si nécessaire
        }

        // Pour checkbox, s'assurer que la valeur est booléenne (parfois absent si non cochée)
        $validatedData['transport_active'] = $request->has('transport_active');
        $validatedData['cantine_active'] = $request->has('cantine_active');

        $eleve->update($validatedData);

        return redirect()->route('eleves.index')->with('success', 'Élève mis à jour avec succès');
    }


    public function destroy(Eleve $eleve)
    {
        $eleve->delete();
        return redirect()->route('eleves.index')->with('success', 'Élève supprimé avec succès');
    }


    
}