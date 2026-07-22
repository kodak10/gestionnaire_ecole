<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EcoleController extends Controller
{
    public function __construct()
    {
        // Appliquer le middleware à toutes les méthodes SAUF getAnneesScolaires
        $this->middleware(['role:SuperAdministrateur'])->except(['getAnneesScolaires']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        $ecoleInfos = Ecole::find($ecoleId);

        return view('dashboard.pages.parametrage.ecole', compact('ecoleInfos'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nom_ecole' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'sigle_ecole' => 'required|string|max:10',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email',
            'directeur' => 'required|string|max:255',
            'footer_bulletin' => 'nullable|string',
            'fax' => 'nullable|string|max:20',
            'sms_notification' => 'nullable|boolean',
        ]);

        $ecoleId = session('current_ecole_id');
        $ecole = Ecole::find($ecoleId);

        if (!$ecole) {
            return redirect()->back()->with('error', 'École non trouvée.');
        }

        // Gestion du logo
        if ($request->hasFile('logo')) {
            if ($ecole->logo && file_exists(public_path($ecole->logo))) {
                unlink(public_path($ecole->logo));
            }

            $path = $request->file('logo')->store('ecole/logo', 'public');
            $ecole->logo = 'storage/' . $path;
        }

        // Mise à jour des champs
        $ecole->nom_ecole = $request->nom_ecole;
        $ecole->code = $request->code;
        $ecole->sigle_ecole = $request->sigle_ecole;
        $ecole->adresse = $request->adresse;
        $ecole->telephone = $request->telephone;
        $ecole->email = $request->email;
        $ecole->directeur = $request->directeur;
        $ecole->footer_bulletin = $request->footer_bulletin;
        $ecole->fax = $request->fax;
        $ecole->sms_notification = $request->has('sms_notification') ? $request->sms_notification : false;

        $ecole->save();

        return redirect()->route('ecoles.index')->with('success', 'Paramètres mis à jour avec succès');
    }

    public function getAnneesScolaires($ecoleId)
    {
        try {
            // Vérifier que l'école existe
            $ecole = Ecole::find($ecoleId);
            if (!$ecole) {
                return response()->json(['error' => 'École non trouvée'], 404);
            }

            // Récupérer les années scolaires
            $annees = AnneeScolaire::where('ecole_id', $ecoleId)
                ->orderBy('annee', 'desc')
                ->get();

            return response()->json($annees);
        } catch (\Exception $e) {
            \Log::error('Erreur API EcoleController: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }
}