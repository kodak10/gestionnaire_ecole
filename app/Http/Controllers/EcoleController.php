<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EcoleController extends Controller
{
    public function index()
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;
        $ecole = Ecole::find($ecoleId);

        return view('dashboard.pages.parametrage.ecole', compact('ecole'));
    }


    public function update(Request $request)
    {
        $request->validate([
            'nom_ecole' => 'required|string|max:255',
            'sigle_ecole' => 'required|string|max:10',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email',
            'directeur' => 'required|string|max:255',
            'footer_bulletin' => 'nullable|string'
        ]);

        $ecole = Ecole::firstOrNew();

        if ($request->hasFile('logo')) {
            if ($ecole->logo && file_exists(public_path($ecole->logo))) {
                unlink(public_path($ecole->logo));
            }

            $path = $request->file('logo')->store('ecole/logo', 'public');
            $ecole->logo = 'storage/' . $path;
        }


        $ecole->nom_ecole = $request->nom_ecole;
        $ecole->adresse = $request->adresse;
        $ecole->telephone = $request->telephone;
        $ecole->email = $request->email;
        $ecole->directeur = $request->directeur;
        $ecole->footer_bulletin = $request->footer_bulletin;
        $ecole->save();

        return redirect()->route('ecoles.index')->with('success', 'Paramètres mis à jour avec succès');
    }
}
