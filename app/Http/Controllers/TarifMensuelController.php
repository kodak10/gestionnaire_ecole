<?php

namespace App\Http\Controllers;

use App\Models\MoisScolaire;
use App\Models\Niveau;
use App\Models\TypeFrais;
use Illuminate\Http\Request;
use App\Models\Tarif;
use App\Models\TarifMensuel;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TarifMensuelController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdministrateur');
    }

    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
    {
        $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
        $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        
        $moisScolaires = $this->genererMoisScolaires($anneeScolaire);
        
        $niveaux = Niveau::where('ecole_id', $ecoleId)->orderBy('ordre')->get();
        
        $tarifs = Tarif::with(['typeFrais', 'niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('type_frais_id')
            ->get();

        // Récupérer les tarifs mensuels avec la nouvelle relation
        $tarifsMensuels = TarifMensuel::with(['tarif', 'niveau'])
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get()
            ->groupBy('tarif_id');

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

    // Récupérer tous les mois scolaires
    $moisScolaires = MoisScolaire::all()->keyBy('numero');
    
    $moisGenere = [];
    $current = clone $dateDebut;
    
    while ($current->lte($dateFin)) {
        $moisNumero = $current->month;
        $annee = $current->year;
        
        // Récupérer l'ID du mois depuis la table mois_scolaires
        $moisScolaire = $moisScolaires->get($moisNumero);
        $moisId = $moisScolaire ? $moisScolaire->id : $moisNumero;
        
        // Créer un ID unique avec l'année pour éviter les doublons
        $uniqueId = $moisId . '_' . $annee;
        
        $moisGenere[] = (object) [
            'id' => $uniqueId,  // ID unique : "11_2026", "11_2027", etc.
            'mois_id' => $moisId,  // L'ID réel du mois (1-11)
            'mois' => $moisNumero,
            'nom' => $nomsMois[$moisNumero] . ' ' . $annee,
            'annee' => $annee,
            'numero' => $moisNumero,
            'date_debut' => $current->copy()->startOfMonth()->format('Y-m-d'),
            'date_fin' => $current->copy()->endOfMonth()->format('Y-m-d'),
        ];
        
        $current->addMonth();
    }

    // Trier les mois dans l'ordre chronologique de l'année scolaire
    return collect($moisGenere)->sortBy(function($item) {
        // Pour une année scolaire qui commence en Septembre
        if ($item->mois >= 9) {
            return $item->mois - 9;
        } else {
            return $item->mois + 3;
        }
    })->values();
}

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    \Log::info('Store Tarifs Mensuels - Données reçues:', $request->all());

    $request->validate([
        'tarif_id' => 'required|exists:tarifs,id',
        'montants' => 'required|array',
        'montants.*' => 'numeric|min:0',
    ]);

    $ecoleId = session('current_ecole_id') ?? auth()->user()->ecole_id;
    $anneeScolaireId = session('current_annee_scolaire_id') ?? auth()->user()->annee_scolaire_id;

    DB::beginTransaction();

    try {
        $tarif = Tarif::findOrFail($request->tarif_id);
        $montants = $request->montants;

        $totalMensuel = array_sum($montants);
        
        if ($totalMensuel > $tarif->montant) {
            DB::rollBack();
            \Log::warning('Dépassement du montant annuel - Total: ' . $totalMensuel . ' / Annuel: ' . $tarif->montant);
            return back()
                ->with('error', 'La somme des montants mensuels (' . number_format($totalMensuel, 0, ',', ' ') . ' FCFA) dépasse le montant annuel (' . number_format($tarif->montant, 0, ',', ' ') . ' FCFA) pour ce tarif.')
                ->withInput();
        }

        // Supprimer les anciens tarifs mensuels pour ce tarif
        TarifMensuel::where('tarif_id', $tarif->id)
            ->where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->delete();

        $nbEnregistre = 0;
        
        // Créer les nouveaux tarifs mensuels
        foreach ($montants as $moisKey => $montant) {
            if ($montant > 0) {
                // Extraire l'ID du mois depuis la clé (format: "11_2026")
                $moisId = explode('_', $moisKey)[0];
                
                $moisScolaire = MoisScolaire::find($moisId);
                
                if (!$moisScolaire) {
                    \Log::warning('Mois non trouvé ID: ' . $moisId . ' (clé: ' . $moisKey . ')');
                    continue;
                }
                
                TarifMensuel::create([
                    'tarif_id' => $tarif->id,
                    'niveau_id' => $tarif->niveau_id,
                    'mois_id' => $moisId,
                    'montant' => $montant,
                    'ecole_id' => $ecoleId,
                    'annee_scolaire_id' => $anneeScolaireId,
                ]);
                $nbEnregistre++;
                
                \Log::info('Mois ID ' . $moisId . ' enregistré avec montant: ' . $montant);
            }
        }

        DB::commit();

        \Log::info('Total enregistré: ' . $nbEnregistre . ' mois');

        $message = 'Tarifs mensuels enregistrés avec succès pour le tarif : ' . ($tarif->libelle ?? $tarif->typeFrais->nom ?? '');
        if ($nbEnregistre > 0) {
            $message .= ' (' . $nbEnregistre . ' mois enregistrés)';
        } else {
            $message .= ' (Aucun montant saisi)';
        }

        return redirect()
            ->route('tarifs-mensuels.index', ['tarif_id' => $request->tarif_id])
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Erreur store tarifs mensuels: ' . $e->getMessage());
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

    $tarifId = $request->tarif_id;
    
    $tarif = Tarif::with(['typeFrais', 'niveau'])->find($tarifId);
    
    if (!$tarif) {
        return response()->json([
            'tarifs' => [],
            'tarif_annuel' => null,
            'libelle' => null,
        ]);
    }

    // Récupérer les tarifs mensuels pour ce tarif
    $tarifsMensuels = TarifMensuel::where('tarif_id', $tarifId)
        ->where('ecole_id', $ecoleId)
        ->where('annee_scolaire_id', $anneeScolaireId)
        ->get();

    // Créer un tableau avec les clés au format "mois_id_annee"
    $tarifsAvecCle = [];
    $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
    $annee = $anneeScolaire ? date('Y', strtotime($anneeScolaire->date_debut)) : date('Y');
    
    foreach ($tarifsMensuels as $tarifMensuel) {
        $moisScolaire = MoisScolaire::find($tarifMensuel->mois_id);
        if ($moisScolaire) {
            // Créer la clé au format "mois_id_annee" (ex: "2_2026" pour Septembre 2026)
            $cle = $tarifMensuel->mois_id . '_' . $annee;
            $tarifsAvecCle[$cle] = $tarifMensuel;
        }
    }

    return response()->json([
        'tarifs' => $tarifsAvecCle,
        'tarif_annuel' => $tarif->montant,
        'libelle' => $tarif->libelle,
        'obligatoire' => $tarif->obligatoire,
        'type_frais_nom' => $tarif->typeFrais->nom ?? null,
        'niveau_nom' => $tarif->niveau->nom ?? null
    ]);
}

}