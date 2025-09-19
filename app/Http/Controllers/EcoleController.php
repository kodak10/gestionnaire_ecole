<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EcoleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        Log::info('Utilisateur connecté : ', ['user_id' => $user->id, 'ecole_id' => $user->ecole_id]);

        if (!$user->ecole_id) {
            Log::warning('Utilisateur sans ecole_id', ['user_id' => $user->id]);
            return redirect()->route('dashboard')->with('error', 'Aucune école assignée à votre compte.');
        }

        $ecoleInfos = Ecole::find($user->ecole_id);

        if (!$ecoleInfos) {
            Log::error('École introuvable', ['ecole_id' => $user->ecole_id]);
            return redirect()->route('dashboard')->with('error', 'École non trouvée.');
        }
        

        Log::info('École trouvée', ['ecole' => $ecoleInfos->toArray()]);

        // dd($ecole);

        return view('dashboard.pages.parametrage.ecole', compact('ecoleInfos'));
    }

    public function update(Request $request)
    {
        Log::info('Update demandé', ['request' => $request->all()]);

        $request->validate([
            'nom_ecole' => 'required|string|max:255',
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

        $ecole = Ecole::find(auth()->user()->ecole_id);
        Log::info('École avant update', ['ecole' => $ecole ? $ecole->toArray() : null]);

        if (!$ecole) {
            Log::error('École introuvable pour update', ['user_id' => auth()->user()->id]);
            return redirect()->back()->with('error', 'École non trouvée.');
        }

        // Gestion du logo
        if ($request->hasFile('logo')) {
            if ($ecole->logo && file_exists(public_path($ecole->logo))) {
                unlink(public_path($ecole->logo));
                Log::info('Ancien logo supprimé');
            }

            $path = $request->file('logo')->store('ecole/logo', 'public');
            $ecole->logo = 'storage/' . $path;
            Log::info('Nouveau logo stocké', ['logo' => $ecole->logo]);
        }

        // Mise à jour des champs
        $ecole->nom_ecole = $request->nom_ecole;
        $ecole->sigle_ecole = $request->sigle_ecole;
        $ecole->adresse = $request->adresse;
        $ecole->telephone = $request->telephone;
        $ecole->email = $request->email;
        $ecole->directeur = $request->directeur;
        $ecole->footer_bulletin = $request->footer_bulletin;
        $ecole->fax = $request->fax;
        $ecole->sms_notification = $request->has('sms_notification') ? $request->sms_notification : false;

        $ecole->save();
        Log::info('École mise à jour avec succès', ['ecole' => $ecole->toArray()]);

        return redirect()->route('ecoles.index')->with('success', 'Paramètres mis à jour avec succès');
    }
}
