<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\DepenseCategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DepenseController extends Controller
{
    public function index()
    {
        $anneesScolaires = AnneeScolaire::all();
        $categories = DepenseCategorie::all();
        
        return view('dashboard.pages.depenses.index', compact('anneesScolaires', 'categories'));
    }

    public function getDepensesData(Request $request)
    {
        $request->validate([
            'depense_category_id' => 'nullable|exists:depense_categories,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date'
        ]);

        $anneeScolaireId = session('current_annee_scolaire_id');

        try {
            $query = Depense::with(['anneeScolaire', 'category'])
                ->where('annee_scolaire_id', $anneeScolaireId);
                
            if ($request->depense_category_id) {
                $query->where('depense_category_id', $request->depense_category_id);
            }
            
            if ($request->date_debut) {
                $query->where('date_depense', '>=', $request->date_debut);
            }
            
            if ($request->date_fin) {
                $query->where('date_depense', '<=', $request->date_fin);
            }
            
            $depenses = $query->orderBy('date_depense', 'desc')->get();
            
            $totalDepenses = $depenses->sum('montant');
            
            // Statistiques par catégorie
            $statsParCategorie = Depense::where('depenses.annee_scolaire_id', $anneeScolaireId)
                ->join('depense_categories', 'depenses.depense_category_id', '=', 'depense_categories.id')
                ->select('depense_categories.nom as categorie', DB::raw('SUM(depenses.montant) as total'))
                ->groupBy('depense_categories.nom')
                ->get();

                
            return response()->json([
                'success' => true,
                'depenses' => $depenses,
                'total_depenses' => $totalDepenses,
                'stats_categories' => $statsParCategorie
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
{
    $request->validate([
        'libelle' => 'required|string|max:255',
        'description' => 'nullable|string',
        'montant' => 'required|numeric|min:1',
        'date_depense' => 'required|date',
        'depense_category_id' => 'required|exists:depense_categories,id',
        'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
        'beneficiaire' => 'required|string|max:255',
        'reference' => 'nullable|string|max:100',
        'justificatif' => 'nullable|string',
    ]);

    $anneeScolaireId = session('current_annee_scolaire_id');
    $ecoleId = session('current_ecole_id');

    try {
        $data = $request->all();
        $data['annee_scolaire_id'] = $anneeScolaireId;
        $data['ecole_id'] = $ecoleId; // ajouter l'ecole_id depuis l'utilisateur

        $depense = Depense::create($data);
        
        return response()->json([
            'success' => true,
            'message' => 'Dépense enregistrée avec succès',
            'depense' => $depense
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement de la dépense: ' . $e->getMessage()
        ]);
    }
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'required|numeric|min:1',
            'date_depense' => 'required|date',
            'depense_category_id' => 'required|exists:depense_categories,id',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'beneficiaire' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'justificatif' => 'nullable|string'
        ]);

        try {
            $depense = Depense::findOrFail($id);
            $depense->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Dépense modifiée avec succès',
                'depense' => $depense
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la dépense: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
{
    try {
        // On récupère la dépense avec sa catégorie et son année scolaire
        $depense = Depense::with('category', 'anneeScolaire')->findOrFail($id);

        // On renvoie la dépense au format JSON
        return response()->json($depense);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération de la dépense: ' . $e->getMessage()
        ], 500);
    }
}


    public function destroy($id)
    {
        try {
            $depense = Depense::findOrFail($id);
            $depense->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Dépense supprimée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la dépense: ' . $e->getMessage()
            ]);
        }
    }
}