<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckEcoleAnnee
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Vérifier que les valeurs sont présentes en session
            $ecoleId = session('current_ecole_id');
            $anneeId = session('current_annee_scolaire_id');

            if (!$ecoleId || !$anneeId) {
                return redirect()->route('login')->with('error', 'Veuillez sélectionner une école et une année scolaire valides.');
            }

            // Optionnel : stocker dans les attributs de la requête
            // $request->attributes->set('ecole_id', $ecoleId);
            // $request->attributes->set('annee_scolaire_id', $anneeId);
        }

        return $next($request);
    }
}
