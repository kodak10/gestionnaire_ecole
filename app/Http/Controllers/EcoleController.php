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
            'ville' => 'nullable|string|max:255',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email',
            'directeur' => 'required|string|max:255',
            'entete_document' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sous_entete_document' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'footer_bulletin' => 'nullable|string',
            'fax' => 'nullable|string|max:20',
            'sms_notification' => 'nullable|boolean',
            'arrondi_moyenne' => 'nullable|in:coupe,arrondi,arrondi_superieur',

            'iepp' => 'nullable|string|max:255',
            'secteur_pedagogique' => 'nullable|string|max:255',
            'sous_prefecture' => 'nullable|string|max:255',
            'circonscription_primaire' => 'nullable|string|max:255',
            'num_registre' => 'nullable|string|max:255',
            'directeur_etudes' => 'nullable|string|max:255',

            'logo_republique' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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

        // Entête du document
        if ($request->hasFile('entete_document')) {
            if (
                $ecole->entete_document &&
                file_exists(public_path($ecole->entete_document))
            ) {
                unlink(public_path($ecole->entete_document));
            }

            $path = $request->file('entete_document')->store('ecole/entetes', 'public');
            $ecole->entete_document = 'storage/' . $path;
        }

        // Sous-entête du document
        if ($request->hasFile('sous_entete_document')) {
            if (
                $ecole->sous_entete_document &&
                file_exists(public_path($ecole->sous_entete_document))
            ) {
                unlink(public_path($ecole->sous_entete_document));
            }

            $path = $request->file('sous_entete_document')->store('ecole/entetes', 'public');
            $ecole->sous_entete_document = 'storage/' . $path;
        }

        // Mise à jour des champs
        $ecole->nom_ecole = $request->nom_ecole;
        $ecole->code = $request->code;
        $ecole->sigle_ecole = $request->sigle_ecole;
        $ecole->adresse = $request->adresse;
        $ecole->ville = $request->ville;
        $ecole->telephone = $request->telephone;
        $ecole->email = $request->email;
        $ecole->directeur = $request->directeur;
        $ecole->footer_bulletin = $request->footer_bulletin;
        $ecole->fax = $request->fax;
        $ecole->sms_notification = $request->has('sms_notification') ? $request->sms_notification : false;
        $ecole->arrondi_moyenne = $request->arrondi_moyenne ?? 'coupe';

        $ecole->iepp = $request->iepp;
        $ecole->secteur_pedagogique = $request->secteur_pedagogique;
        $ecole->sous_prefecture = $request->sous_prefecture;
        $ecole->circonscription_primaire = $request->circonscription_primaire;
        $ecole->num_registre = $request->num_registre;
        $ecole->directeur_etudes = $request->directeur_etudes;

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