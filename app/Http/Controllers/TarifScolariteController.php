<?php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use App\Models\Tarif;
use Illuminate\Support\Facades\DB;

class TarifScolariteController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur');
    }

public function index()
{
    $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
    $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

    $niveaux = Niveau::where('ecole_id', $ecoleId)
        ->orderBy('ordre')
        ->get();

    // Récupérer tous les tarifs avec leurs relations
    $tarifs = Tarif::with([
            'typeFrais',
            'niveau'
        ])
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->orderBy('type_frais_id')
        ->orderBy('niveau_id')
        ->get();

    // Récupérer tous les types de frais
    $typeFrais = TypeFrais::orderBy('nom')->get();

    return view(
        'dashboard.pages.parametrage.scolarite.tarif',
        compact(
            'niveaux',
            'tarifs',
            'typeFrais'
        )
    );
}

    public function store(Request $request)
    {
        $request->validate([
            'type_frais_id' => 'required|exists:type_frais,id',
            'obligatoire' => 'nullable|boolean',
            'montant' => 'required|numeric|min:0',
            'niveau_ids' => 'nullable|array',
            'niveau_ids.*' => 'exists:niveaux,id',
            'libelle' => 'nullable|string|max:255',
        ]);

        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

        $niveauIds = $request->niveau_ids ?? [];

        DB::beginTransaction();

        try {
            // On ne supprime rien, on ajoute simplement de nouveaux tarifs
            // Cela permet d'avoir plusieurs tarifs pour le même type et le même niveau

            if (empty($niveauIds)) {
                // Tarif pour tous les niveaux (niveau_id = null)
                Tarif::create([
                    'type_frais_id' => $request->type_frais_id,
                    'niveau_id' => null,
                    'montant' => $request->montant,
                    'obligatoire' => $request->boolean('obligatoire'),
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId,
                    'libelle' => $request->libelle,
                ]);
            } else {
                // Pour chaque niveau sélectionné, on crée un tarif
                // Cela permet d'avoir plusieurs tarifs pour le même type ET le même niveau
                foreach ($niveauIds as $niveauId) {
                    Tarif::create([
                        'type_frais_id' => $request->type_frais_id,
                        'niveau_id' => $niveauId,
                        'montant' => $request->montant,
                        'obligatoire' => $request->boolean('obligatoire'),
                        'ecole_id' => $ecoleId,
                        'annee_scolaire_id' => $anneeScolaireId,
                        'libelle' => $request->libelle,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('tarifs.index')
                ->with('success', 'Tarif(s) ajouté(s) avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'ajout : ' . $e->getMessage());
        }
    }

public function update(Request $request, $id)
{
    $request->validate([
        'type_frais_id' => 'required|exists:type_frais,id',
        'montant' => 'required|numeric|min:0',
        'niveau_ids' => 'nullable|array',
        'niveau_ids.*' => 'exists:niveaux,id',
        'libelle' => 'nullable|string|max:255',
        'obligatoire' => 'nullable|boolean',
    ]);

    DB::beginTransaction();

    try {
        $tarif = Tarif::findOrFail($id);
        
        // Mettre à jour le tarif
        $tarif->update([
            'type_frais_id' => $request->type_frais_id,
            'montant' => $request->montant,
            'obligatoire' => $request->boolean('obligatoire'),
            'libelle' => $request->libelle,
        ]);

        // Si un niveau est sélectionné, le mettre à jour
        if ($request->has('niveau_ids') && !empty($request->niveau_ids)) {
            $tarif->niveau_id = $request->niveau_ids[0];
            $tarif->save();
        } else {
            $tarif->niveau_id = null;
            $tarif->save();
        }

        DB::commit();

        return redirect()
            ->route('tarifs.index')
            ->with('success', 'Tarif mis à jour avec succès.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
    }
}

    public function destroy($id)
    {
        $tarif = Tarif::findOrFail($id);
        
        // Supprimer uniquement ce tarif spécifique
        $tarif->delete();

        return redirect()
            ->route('tarifs.index')
            ->with('success', 'Tarif supprimé avec succès.');
    }
}