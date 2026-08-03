<?php
// app/Http/Controllers/TarifMensuelController.php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\AnneeScolaire;
use App\Services\TableService;
use App\Rules\ExistsInDynamicTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TarifMensuelController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->middleware('role:SuperAdministrateur');
        $this->tableService = $tableService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;
        $annee = session('current_annee_scolaire');

        // Récupérer les niveaux (table dynamique via Eloquent)
        $niveaux = Niveau::where('ecole_id', $ecoleId)
            ->orderBy('ordre', 'asc')
            ->get();

        // Récupérer les tarifs avec relations Eloquent
        $tarifs = Tarif::with(['typeFrais', 'niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('type_frais_id')
            ->orderBy('niveau_id')
            ->get();

        // Récupérer les tarifs mensuels avec relations Eloquent
        $tarifsMensuels = TarifMensuel::with(['tarif', 'niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get()
            ->groupBy('tarif_id');

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $moisScolaires = $this->genererMoisScolaires($anneeScolaire);

        $selectedTarifId = $request->get('tarif_id');

        return view(
            'dashboard.pages.parametrage.scolarite.tarif-mensuel',
            compact(
                'niveaux',
                'tarifs',
                'moisScolaires',
                'tarifsMensuels',
                'anneeScolaire',
                'selectedTarifId'
            )
        );
    }

    /**
     * Générer les mois entre date_debut et date_fin
     */
    private function genererMoisScolaires($anneeScolaire)
    {
        if (!$anneeScolaire || !$anneeScolaire->date_debut || !$anneeScolaire->date_fin) {
            return MoisScolaire::orderBy('numero')->get();
        }

        $dateDebut = Carbon::parse($anneeScolaire->date_debut);
        $dateFin = Carbon::parse($anneeScolaire->date_fin);
        
        if ($dateDebut->gt($dateFin)) {
            $temp = $dateDebut;
            $dateDebut = $dateFin;
            $dateFin = $temp;
        }

        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $moisScolaires = MoisScolaire::all()->keyBy('numero');
        
        $moisGenere = [];
        $current = clone $dateDebut;
        
        while ($current->lte($dateFin)) {
            $moisNumero = $current->month;
            $annee = $current->year;
            
            $moisScolaire = $moisScolaires->get($moisNumero);
            $moisId = $moisScolaire ? $moisScolaire->id : $moisNumero;
            $uniqueId = $moisId . '_' . $annee;
            
            $moisGenere[] = (object) [
                'id' => $uniqueId,
                'mois_id' => $moisId,
                'mois' => $moisNumero,
                'nom' => $nomsMois[$moisNumero] . ' ' . $annee,
                'annee' => $annee,
                'numero' => $moisNumero,
                'date_debut' => $current->copy()->startOfMonth()->format('Y-m-d'),
                'date_fin' => $current->copy()->endOfMonth()->format('Y-m-d'),
            ];
            
            $current->addMonth();
        }

        return collect($moisGenere)->sortBy(function($item) {
            if ($item->mois >= 9) {
                return $item->mois - 9;
            } else {
                return $item->mois + 3;
            }
        })->values();
    }

    /**
     * Store a newly created resource in storage.
     * Fonction complète avec validation personnalisée pour les tables dynamiques
     */
    public function store(Request $request)
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;
        $annee = session('current_annee_scolaire');

        // Récupérer le nom de la table des tarifs dynamique
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);

        // Validation avec la règle personnalisée pour vérifier l'existence du tarif
        $validator = Validator::make($request->all(), [
            'tarif_id' => [
                'required',
                new ExistsInDynamicTable(
                    $tarifsTable,           // Nom de la table
                    'id',                   // Colonne à vérifier
                    $ecoleId,               // ID de l'école
                    $anneeScolaireId        // ID de l'année scolaire
                )
            ],
            'montants' => 'required|array',
            'montants.*' => 'numeric|min:0',
        ], [
            'tarif_id.required' => 'Veuillez sélectionner un tarif.',
            'montants.required' => 'Veuillez saisir les montants mensuels.',
            'montants.*.numeric' => 'Les montants doivent être des nombres.',
            'montants.*.min' => 'Les montants ne peuvent pas être négatifs.',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer le nom de la table des tarifs mensuels
        $tarifsMensuelsTable = $this->tableService->getTarifsMensuelsTableName($ecoleId, $annee);

        // Vérifier si la table des tarifs mensuels existe
        if (!Schema::hasTable($tarifsMensuelsTable)) {
            return back()
                ->with('error', 'La table des tarifs mensuels n\'existe pas pour cette année.')
                ->withInput();
        }

        // Démarrer la transaction
        DB::beginTransaction();

        try {
            // Récupérer le tarif depuis la table dynamique
            $tarif = DB::table($tarifsTable)
                ->where('id', $request->tarif_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            // Vérifier si le tarif existe
            if (!$tarif) {
                throw new \Exception('Tarif non trouvé.');
            }

            // Récupérer les montants
            $montants = $request->montants;
            $totalMensuel = array_sum($montants);
            
            // Vérifier si le total mensuel dépasse le montant annuel
            if ($totalMensuel > $tarif->montant) {
                DB::rollBack();
                return back()
                    ->with('error', 'La somme des montants mensuels (' . number_format($totalMensuel, 0, ',', ' ') . ' FCFA) dépasse le montant annuel (' . number_format($tarif->montant, 0, ',', ' ') . ' FCFA) pour ce tarif.')
                    ->withInput();
            }

            // Supprimer les anciens tarifs mensuels
            DB::table($tarifsMensuelsTable)
                ->where('tarif_id', $request->tarif_id)
                ->where('ecole_id', $ecoleId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->delete();

            $nbEnregistre = 0;
            
            // Parcourir les mois et créer les tarifs mensuels
            foreach ($montants as $moisKey => $montant) {
                // Ne créer que si le montant est supérieur à 0
                if ($montant > 0) {
                    // Extraire l'ID du mois depuis la clé (format: "mois_id_annee")
                    $moisId = explode('_', $moisKey)[0];
                    
                    // Vérifier si le mois existe
                    $moisScolaire = MoisScolaire::find($moisId);
                    
                    if (!$moisScolaire) {
                        continue;
                    }
                    
                    // Insérer le tarif mensuel
                    DB::table($tarifsMensuelsTable)->insert([
                        'tarif_id' => $request->tarif_id,
                        'niveau_id' => $tarif->niveau_id,
                        'mois_id' => $moisId,
                        'montant' => $montant,
                        'ecole_id' => $ecoleId,
                        'annee_scolaire_id' => $anneeScolaireId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nbEnregistre++;
                }
            }

            // Valider la transaction
            DB::commit();

            // Construire le message de succès
            $message = 'Tarifs mensuels enregistrés avec succès.';
            if ($nbEnregistre > 0) {
                $message .= ' (' . $nbEnregistre . ' mois enregistrés)';
            } else {
                $message .= ' (Aucun montant saisi)';
            }

            Log::info('✅ Tarifs mensuels enregistrés', [
                'tarif_id' => $request->tarif_id,
                'count' => $nbEnregistre
            ]);

            return redirect()
                ->route('scolarite.tarifs-mensuels.index', ['tarif_id' => $request->tarif_id])
                ->with('success', $message);

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            
            Log::error('❌ Erreur store tarifs mensuels', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return back()
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get tarifs mensuels by tarif_id (AJAX)
     */
    public function getTarifsMensuels(Request $request)
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;
        $annee = session('current_annee_scolaire');

        $tarifId = $request->tarif_id;
        $tarifsTable = $this->tableService->getTarifsTableName($ecoleId, $annee);
        $niveauxTable = $this->tableService->getNiveauxTableName($ecoleId, $annee);
        
        // Récupérer le tarif depuis la table dynamique
        $tarif = DB::table($tarifsTable . ' as t')
            ->leftJoin('type_frais as tf', 't.type_frais_id', '=', 'tf.id')
            ->leftJoin($niveauxTable . ' as n', 't.niveau_id', '=', 'n.id')
            ->where('t.id', $tarifId)
            ->where('t.ecole_id', $ecoleId)
            ->where('t.annee_scolaire_id', $anneeScolaireId)
            ->select(
                't.*',
                'tf.nom as type_frais_nom',
                'n.nom as niveau_nom'
            )
            ->first();

        if (!$tarif) {
            return response()->json([
                'tarifs' => [],
                'tarif_annuel' => null,
                'libelle' => null,
            ]);
        }

        // Récupérer les tarifs mensuels
        $tarifsMensuelsTable = $this->tableService->getTarifsMensuelsTableName($ecoleId, $annee);
        $tarifsMensuels = DB::table($tarifsMensuelsTable)
            ->where('tarif_id', $tarifId)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        $tarifsAvecCle = [];
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        $annee = $anneeScolaire ? date('Y', strtotime($anneeScolaire->date_debut)) : date('Y');
        
        foreach ($tarifsMensuels as $tarifMensuel) {
            $moisScolaire = MoisScolaire::find($tarifMensuel->mois_id);
            if ($moisScolaire) {
                $cle = $tarifMensuel->mois_id . '_' . $annee;
                $tarifsAvecCle[$cle] = $tarifMensuel;
            }
        }

        return response()->json([
            'tarifs' => $tarifsAvecCle,
            'tarif_annuel' => $tarif->montant,
            'libelle' => $tarif->libelle,
            'obligatoire' => $tarif->obligatoire,
            'type_frais_nom' => $tarif->type_frais_nom ?? null,
            'niveau_nom' => $tarif->niveau_nom ?? null
        ]);
    }
}