<?php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TarifMensuelController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur');
    }
    
    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Filtres
        $niveau_id = $request->input('niveau_id');
        $type_frais_id = $request->input('type_frais_id');
        $mois_id = $request->input('mois_id');

        $niveaux = Niveau::orderBy('nom')->get();
        $typeFrais = TypeFrais::orderBy('nom')->get();
        $moisScolaires = MoisScolaire::orderBy('numero')->get();

        // Requête filtrée
        $query = TarifMensuel::with(['typeFrais', 'niveau', 'mois']);

        if ($niveau_id) $query->where('niveau_id', $niveau_id);
        if ($type_frais_id) $query->where('type_frais_id', $type_frais_id);
        if ($mois_id) $query->where('mois_id', $mois_id);

        $tarifs = $query->where('ecole_id', $ecoleId)
                         ->where('annee_scolaire_id', $anneeScolaireId)
                         ->get();

        // Préparer les événements pour FullCalendar
        $events = [];
        foreach ($tarifs as $tarif) {
            $startDate = date('Y') . '-' . str_pad($tarif->mois->numero, 2, '0', STR_PAD_LEFT) . '-01';

            $events[] = [
                'id' => $tarif->id,
                'title' => $tarif->typeFrais->nom . ' : ' . number_format($tarif->montant, 0, ',', ' ') . ' FCFA',
                'start' => $startDate,
                'allDay' => true,
                'className' => $this->getCssClassForTypeFrais($tarif->type_frais_id),
                'extendedProps' => [
                    'niveau' => $tarif->niveau->nom,
                    'niveau_id' => $tarif->niveau->id,
                    'mois' => $tarif->mois->nom,
                    'mois_id' => $tarif->mois->id,
                    'type_frais_id' => $tarif->type_frais_id,
                    'montant' => $tarif->montant,
                ],
                'url' => route('tarifs-mensuels.edit', $tarif->id)
            ];
        }

        return view('dashboard.pages.parametrage.scolarite.tarifs_calendar', [
            'niveaux' => $niveaux,
            'typeFrais' => $typeFrais,
            'moisScolaires' => $moisScolaires,
            'events' => $events,
            'filters' => compact('niveau_id', 'type_frais_id', 'mois_id'),
            'selectedTarif' => $request->has('edit') ? TarifMensuel::find($request->edit) : null
        ]);
    }

    private function getCssClassForTypeFrais(int $typeFraisId): string
    {
        $classes = [
            1 => 'fc-event-inscription',
            2 => 'fc-event-scolarite',
            3 => 'fc-event-cantine',
            4 => 'fc-event-transport',
        ];
        return $classes[$typeFraisId] ?? 'fc-event-default';
    }

   

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_frais_id' => 'required|exists:type_frais,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'mois_id' => 'required|exists:mois_scolaires,id',
            'montant' => 'required|numeric|min:0',
        ]);

        // Récupérer l'école et l'année scolaire
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');


        $validated['ecole_id'] = $ecoleId;
        $validated['annee_scolaire_id'] = $anneeScolaireId;

        // Vérification des doublons pour cette école
        $existingTarif = TarifMensuel::where('ecole_id', $ecoleId)
            ->where('type_frais_id', $validated['type_frais_id'])
            ->where('niveau_id', $validated['niveau_id'])
            ->where('mois_id', $validated['mois_id'])
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if ($existingTarif) {
            return redirect()->back()
                ->withErrors(['duplicate' => 'Un tarif existe déjà pour cette combinaison type/niveau/mois dans votre école.'])
                ->withInput();
        }

        TarifMensuel::create($validated);

        return redirect()->route('tarifs-mensuels.index')
            ->with('success', 'Tarif mensuel ajouté.');
    }

    public function edit($id)
    {
        return redirect()->route('tarifs-mensuels.index', ['edit' => $id]);
    }

    public function update(Request $request, $id)
    {
        $tarif = TarifMensuel::findOrFail($id);

        $validated = $request->validate([
            'type_frais_id' => 'required|exists:type_frais,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'mois_id' => 'required|exists:mois_scolaires,id',
            'montant' => 'required|numeric|min:0',
        ]);

        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Vérification des doublons (exclure l'enregistrement actuel)
        $existingTarif = TarifMensuel::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('type_frais_id', $validated['type_frais_id'])
            ->where('niveau_id', $validated['niveau_id'])
            ->where('mois_id', $validated['mois_id'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingTarif) {
            return redirect()->back()->withErrors(['duplicate' => 'Un tarif existe déjà pour cette combinaison type/niveau/mois.'])->withInput();
        }

        $tarif->update($validated);

        return redirect()->route('tarifs-mensuels.index')->with('success', 'Tarif mis à jour.');
    }

    public function destroy($id)
    {
        $tarif = TarifMensuel::findOrFail($id);
        $tarif->delete();

        return redirect()->route('tarifs-mensuels.index')->with('success', 'Tarif supprimé.');
    }


    public function getNiveauxByTypeFrais(Request $request): JsonResponse
    {
        $type_frais_id = $request->input('type_frais_id');
        
        // Récupérer l'ecole_id de l'utilisateur authentifié
        $ecoleId = session('current_ecole_id'); 
        $anneeScolaireId = session('current_annee_scolaire_id');

        // Récupérer les niveaux qui ont déjà des tarifs pour ce type de frais
        $niveaux = Niveau::whereHas('tarifs', function($query) use ($type_frais_id, $ecoleId) {
                $query->where('type_frais_id', $type_frais_id)
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->where('ecole_id', $ecoleId);
            })
            ->orderBy('nom')
            ->get();
        
        return response()->json(['niveaux' => $niveaux]);
    }
   

    // public function checkExistingTarif(Request $request): JsonResponse
    // {
    //     $type_frais_id = $request->input('type_frais_id');
    //     $niveau_id = $request->input('niveau_id');
    //     $mois_id = $request->input('mois_id');
    //     $exclude_id = $request->input('exclude_id');

    //     // Récupérer l'ecole_id de l'utilisateur authentifié
    //     $ecoleId = session('current_ecole_id'); 
    //     $anneeScolaireId = session('current_annee_scolaire_id');

    //     $query = TarifMensuel::where('ecole_id', $ecoleId)
    //         ->where('annee_scolaire_id', $anneeScolaireId)
    //         ->where('type_frais_id', $type_frais_id)
    //         ->where('niveau_id', $niveau_id)
    //         ->where('mois_id', $mois_id);

    //     if ($exclude_id) {
    //         $query->where('id', '!=', $exclude_id);
    //     }

    //     $exists = $query->exists();

    //     return response()->json(['exists' => $exists]);
    // }

    // public function getTarifsByTypeAndNiveau(Request $request): JsonResponse
    // {
    //     $type_frais_id = $request->input('type_frais_id');
    //     $niveau_id = $request->input('niveau_id');

    //     // Récupérer l'ecole_id de l'utilisateur authentifié
    //     $ecoleId = session('current_ecole_id'); 
    //     $anneeScolaireId = session('current_annee_scolaire_id');

    //     $tarifs = TarifMensuel::with('mois')
    //         ->where('ecole_id', $ecoleId)
    //         ->where('annee_scolaire_id', $anneeScolaireId)
    //         ->where('type_frais_id', $type_frais_id)
    //         ->where('niveau_id', $niveau_id)
    //         ->get()
    //         ->map(function($tarif) {
    //             return [
    //                 'mois_id' => $tarif->mois_id,
    //                 'mois_nom' => $tarif->mois->nom,
    //                 'montant' => $tarif->montant
    //             ];
    //         });

    //     return response()->json(['tarifs' => $tarifs]);
    // }

    

    // public function syncFilters(Request $request): JsonResponse
    // {
    //     $type_frais_id = $request->input('type_frais_id');
    //     $niveau_id = $request->input('niveau_id');
    //     $mois_id = $request->input('mois_id');

    //     return response()->json([
    //         'type_frais_id' => $type_frais_id,
    //         'niveau_id' => $niveau_id,
    //         'mois_id' => $mois_id
    //     ]);
    // }
    
}