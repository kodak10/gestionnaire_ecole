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
        $ecoleId = auth()->user()->ecole_id ?? 1;

        $niveaux = Niveau::orderBy('nom')->get();

        $tarifs = Tarif::with('typeFrais')
            ->where('ecole_id', $ecoleId) // filtrer par école
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

        $ecoleId = auth()->user()->ecole_id ?? 1;
        $anneeScolaireId = session('annee_scolaire_id') ?? auth()->user()->annee_scolaire_id ?? 1;



        // Si "Appliquer à tous les niveaux" est coché, on récupère tous les niveaux
        if ($request->boolean('apply_to_all')) {
            $niveauIds = Niveau::pluck('id')->toArray();
        } else {
            $niveauIds = $request->niveau_ids ?? [];
        }

        // Si aucun niveau sélectionné et pas appliqué à tous, on crée un tarif sans niveau (niveau_id null)
        if (empty($niveauIds)) {
            Tarif::updateOrCreate(
                [
                    'type_frais_id' => $request->type_frais_id,
                    'obligatoire' => $request->boolean('obligatoire'),
                    'niveau_id' => null,
                    'ecole_id' => $ecoleId, 
                    'annee_scolaire_id' => $anneeScolaireId,
                ],
                [
                    'montant' => $request->montant,
                ]
            );
        } else {
            // On crée un tarif par niveau sélectionné
            foreach ($niveauIds as $niveauId) {
                Tarif::updateOrCreate(
                    [
                        'type_frais_id' => $request->type_frais_id,
                        'obligatoire' => $request->boolean('obligatoire'),
                        'niveau_id' => $niveauId,
                        'ecole_id' => $ecoleId, 
                        'annee_scolaire_id' => $anneeScolaireId,
                    ],
                    [
                        'montant' => $request->montant,
                    ]
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

        $tarif = Tarif::findOrFail($id);
        $ecoleId = auth()->user()->ecole_id ?? 1;

        // On prend le premier niveau ou null
        $niveauIds = $request->niveau_ids ?? [];
        $niveauId = count($niveauIds) > 0 ? $niveauIds[0] : null;

        // Vérifier si un autre tarif avec la même combinaison existe pour éviter les doublons
        $exists = Tarif::where('type_frais_id', $request->type_frais_id)
            ->where('obligatoire', $request->boolean('obligatoire'))
            ->where('niveau_id', $niveauId)
            ->where('ecole_id', $ecoleId)
            ->where('id', '!=', $tarif->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['type_frais_id' => 'Un tarif identique existe déjà pour cette école.'])->withInput();
        }

        $tarif->update([
            'type_frais_id' => $request->type_frais_id,
            'obligatoire' => $request->boolean('obligatoire'),
            'niveau_id' => $niveauId,
            'montant' => $request->montant,
            'ecole_id' => $ecoleId, 
        ]);

        return redirect()->route('tarifs.index')->with('success', 'Tarif mis à jour avec succès.');
    }


    public function destroy($id)
    {
        $tarif = Tarif::findOrFail($id);
        $tarif->delete();

        return redirect()->route('tarifs.index')->with('success', 'Tarif supprimé avec succès.');
    }

   
    
}
