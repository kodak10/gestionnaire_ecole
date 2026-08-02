<?php
// app/Http/Controllers/MentionController.php

namespace App\Http\Controllers;

use App\Models\Mention;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MentionController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur|Administrateur|Directeur');
        $this->tableService = $tableService;
    }
    
    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        Log::info('📋 CHARGEMENT MENTIONS', [
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'annee' => $annee
        ]);

        // Récupérer le nom de la table des mentions
        $mentionsTable = $this->tableService->getMentionsTableName($ecoleId, $annee);

        Log::info('📋 Table dynamique', [
            'mentions' => $mentionsTable
        ]);

        // Vérifier si la table existe
        if (!Schema::hasTable($mentionsTable)) {
            Log::warning('⚠️ Table des mentions non trouvée', ['table' => $mentionsTable]);
            $mentions = collect();
        } else {
            // Récupérer les mentions
            $mentions = DB::table($mentionsTable)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->orderBy('min_note', 'asc')
                ->get();
        }

        Log::info('📊 Mentions trouvées', ['count' => $mentions->count()]);

        return view('dashboard.pages.parametrage.mention', [
            'mentions' => $mentions,
            'annee_active' => [
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeScolaireId,
                'ecole_nom' => session('current_ecole_nom'),
                'annee_scolaire' => $annee,
                'sigle' => $this->tableService->getEcoleSigle($ecoleId)
            ]
        ]);
    }

    public function store(Request $request)
    {
        Log::info('📝 CRÉATION MENTION', ['data' => $request->all()]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $validator = Validator::make($request->all(), [
            'nom' => [
                'required',
                'string',
                'max:255',
            ],
            'min_note' => 'nullable|integer|min:0|max:20',
            'max_note' => 'nullable|integer|min:0|max:20|gte:min_note',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $mentionsTable = $this->tableService->getMentionsTableName($ecoleId, $annee);

        // Si la table n'existe pas, la créer ou retourner une erreur
        if (!Schema::hasTable($mentionsTable)) {
            Log::error('❌ Table des mentions non trouvée', ['table' => $mentionsTable]);
            return redirect()->back()
                ->with('error', 'La table des mentions n\'existe pas pour cette année.')
                ->withInput();
        }

        // Vérifier si une mention avec le même nom existe déjà
        $exists = DB::table($mentionsTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Une mention avec ce nom existe déjà.')
                ->withInput();
        }

        // Insérer la mention
        $id = DB::table($mentionsTable)->insertGetId([
            'ecole_id' => $ecoleId,
            'annee_scolaire_id' => $anneeScolaireId,
            'nom' => $request->nom,
            'min_note' => $request->min_note,
            'max_note' => $request->max_note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('✅ Mention créée', [
            'id' => $id,
            'nom' => $request->nom
        ]);

        return redirect()->route('mentions.index')
            ->with('success', 'Mention créée avec succès.');
    }

    public function update(Request $request, $id)
    {
        Log::info('📝 MISE À JOUR MENTION', [
            'id' => $id,
            'data' => $request->all()
        ]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $validator = Validator::make($request->all(), [
            'nom' => [
                'required',
                'string',
                'max:255',
            ],
            'min_note' => 'nullable|integer|min:0|max:20',
            'max_note' => 'nullable|integer|min:0|max:20|gte:min_note',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $mentionsTable = $this->tableService->getMentionsTableName($ecoleId, $annee);

        if (!Schema::hasTable($mentionsTable)) {
            return redirect()->back()
                ->with('error', 'La table des mentions n\'existe pas pour cette année.');
        }

        // Vérifier si la mention existe
        $mention = DB::table($mentionsTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if (!$mention) {
            return redirect()->back()
                ->with('error', 'Mention non trouvée.');
        }

        // Vérifier si une autre mention a le même nom
        $exists = DB::table($mentionsTable)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('nom', $request->nom)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Une autre mention porte déjà ce nom.')
                ->withInput();
        }

        // Mettre à jour
        DB::table($mentionsTable)
            ->where('id', $id)
            ->update([
                'nom' => $request->nom,
                'min_note' => $request->min_note,
                'max_note' => $request->max_note,
                'updated_at' => now(),
            ]);

        Log::info('✅ Mention mise à jour', [
            'id' => $id,
            'nom' => $request->nom
        ]);

        return redirect()->route('mentions.index')
            ->with('success', 'Mention mise à jour avec succès.');
    }

    public function destroy($id)
    {
        Log::info('🗑️ SUPPRESSION MENTION', ['id' => $id]);

        $ecoleId = session('current_ecole_id');
        $anneeScolaireId = session('current_annee_scolaire_id');
        $annee = session('current_annee_scolaire');

        $mentionsTable = $this->tableService->getMentionsTableName($ecoleId, $annee);

        if (!Schema::hasTable($mentionsTable)) {
            return redirect()->back()
                ->with('error', 'La table des mentions n\'existe pas pour cette année.');
        }

        // Vérifier si la mention existe
        $mention = DB::table($mentionsTable)
            ->where('id', $id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if (!$mention) {
            return redirect()->back()
                ->with('error', 'Mention non trouvée.');
        }

        // Supprimer la mention
        DB::table($mentionsTable)
            ->where('id', $id)
            ->delete();

        Log::info('✅ Mention supprimée', ['id' => $id]);

        return redirect()->route('mentions.index')
            ->with('success', 'Mention supprimée avec succès.');
    }
}