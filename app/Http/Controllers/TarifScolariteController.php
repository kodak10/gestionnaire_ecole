<?php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TarifMensuel;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use App\Models\Tarif;

class TarifScolariteController extends Controller
{
   public function index()
{
    $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
    $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

    $niveaux = Niveau::where('ecole_id', $ecoleId)
        ->orderBy('ordre')
        ->get();

    $tarifs = Tarif::with('typeFrais')
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->get();

    $typeFrais = TypeFrais::orderBy('nom')->get();
    $moisScolaires = MoisScolaire::orderBy('numero')->get();

    return view('dashboard.pages.parametrage.scolarite.tarif', compact('niveaux', 'tarifs','typeFrais', 'moisScolaires'));
}

public function store(Request $request)
{
    $request->validate([
        'type_frais_id' => 'required|exists:type_frais,id',
        'obligatoire' => 'nullable|boolean',
        'montant' => 'required|numeric|min:0',
        'niveau_ids' => 'nullable|array',
        'niveau_ids.*' => 'exists:niveaux,id',
        'apply_to_all' => 'nullable|boolean',
    ]);

    $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
    $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

    $niveauIds = $request->boolean('apply_to_all')
        ? Niveau::where('ecole_id', $ecoleId)->pluck('id')->toArray()
        : ($request->niveau_ids ?? []);

    if (empty($niveauIds)) {
        // Créer un tarif sans niveau
        Tarif::updateOrCreate(
            [
                'type_frais_id' => $request->type_frais_id,
                'obligatoire' => $request->boolean('obligatoire'),
                'niveau_id' => null,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
            ],
            ['montant' => $request->montant]
        );
    } else {
        foreach ($niveauIds as $niveauId) {
            Tarif::updateOrCreate(
                [
                    'type_frais_id' => $request->type_frais_id,
                    'obligatoire' => $request->boolean('obligatoire'),
                    'niveau_id' => $niveauId,
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId,
                ],
                ['montant' => $request->montant]
            );
        }
    }

    return redirect()->route('tarifs.index')->with('success', 'Tarif(s) ajouté(s) avec succès.');
}

public function update(Request $request, $id)
{
    $request->validate([
        'type_frais_id' => 'required|exists:type_frais,id',
        'obligatoire' => 'nullable|boolean',
        'montant' => 'required|numeric|min:0',
        'niveau_ids' => 'nullable|array',
        'niveau_ids.*' => 'exists:niveaux,id',
    ]);

    $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
    $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

    $niveauIds = $request->niveau_ids ?? [];

    // Supprimer les anciens tarifs pour ce type de frais et cette année scolaire
    Tarif::where('type_frais_id', $request->type_frais_id)
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->whereNotIn('niveau_id', $niveauIds) // ne pas supprimer ceux qu'on veut garder
        ->delete();

    // Mettre à jour ou créer un tarif pour chaque niveau sélectionné
    if (empty($niveauIds)) {
        // Aucun niveau sélectionné => créer un tarif sans niveau
        Tarif::updateOrCreate(
            [
                'type_frais_id' => $request->type_frais_id,
                'niveau_id' => null,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
            ],
            [
                'montant' => $request->montant,
                'obligatoire' => $request->boolean('obligatoire'),
            ]
        );
    } else {
        foreach ($niveauIds as $niveauId) {
            Tarif::updateOrCreate(
                [
                    'type_frais_id' => $request->type_frais_id,
                    'niveau_id' => $niveauId,
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId,
                ],
                [
                    'montant' => $request->montant,
                    'obligatoire' => $request->boolean('obligatoire'),
                ]
            );
        }
    }

    return redirect()->route('tarifs.index')->with('success', 'Tarif(s) mis à jour avec succès.');
}




    public function destroy($id)
    {
        $tarif = Tarif::findOrFail($id);
        $tarif->delete();

        return redirect()->route('tarifs.index')->with('success', 'Tarif supprimé avec succès.');
    }

   
    
}
