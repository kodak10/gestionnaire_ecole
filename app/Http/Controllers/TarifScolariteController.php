<?php
// app/Http/Controllers/TarifScolariteController.php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TypeFrais;
use App\Models\Tarif;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TarifScolariteController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur');
        $this->tableService = $tableService;
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;
        $annee = session('current_annee_scolaire');

        // Récupérer les niveaux (table dynamique)
        $niveaux = Niveau::where('ecole_id', $ecoleId)
            ->orderBy('ordre', 'asc')
            ->get();

        // Récupérer les tarifs avec les relations
        $tarifs = Tarif::with(['typeFrais', 'niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('type_frais_id')
            ->orderBy('niveau_id')
            ->get();

        // Récupérer les types de frais (statique)
        $typeFrais = TypeFrais::orderBy('nom')->get();

        return view('dashboard.pages.parametrage.scolarite.tarif', compact(
            'niveaux',
            'tarifs',
            'typeFrais'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_frais_id' => 'required|exists:type_frais,id',
            'obligatoire' => 'nullable|boolean',
            'montant' => 'required|numeric|min:0',
            'niveau_ids' => 'nullable|array',
            'niveau_ids.*' => 'exists:niveaux,id',
            'libelle' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

        $niveauIds = $request->niveau_ids ?? [];

        DB::beginTransaction();

        try {
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
                // Pour chaque niveau sélectionné
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
                ->route('scolarite.tarifs.index')
                ->with('success', 'Tarif(s) ajouté(s) avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur création tarif', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de l\'ajout : ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type_frais_id' => 'required|exists:type_frais,id',
            'montant' => 'required|numeric|min:0',
            'niveau_ids' => 'nullable|array',
            'niveau_ids.*' => 'exists:niveaux,id',
            'libelle' => 'nullable|string|max:255',
            'obligatoire' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

        DB::beginTransaction();

        try {
            $tarif = Tarif::where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if (!$tarif) {
                throw new \Exception('Tarif non trouvé.');
            }

            $tarif->update([
                'type_frais_id' => $request->type_frais_id,
                'montant' => $request->montant,
                'obligatoire' => $request->boolean('obligatoire'),
                'libelle' => $request->libelle,
            ]);

            // Mettre à jour le niveau si sélectionné
            if ($request->has('niveau_ids') && !empty($request->niveau_ids)) {
                $tarif->niveau_id = $request->niveau_ids[0];
            } else {
                $tarif->niveau_id = null;
            }
            $tarif->save();

            DB::commit();

            return redirect()
                ->route('scolarite.tarifs.index')
                ->with('success', 'Tarif mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur mise à jour tarif', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

        try {
            $tarif = Tarif::where('id', $id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if (!$tarif) {
                throw new \Exception('Tarif non trouvé.');
            }

            $tarif->delete();

            return redirect()
                ->route('scolarite.tarifs.index')
                ->with('success', 'Tarif supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression tarif', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}