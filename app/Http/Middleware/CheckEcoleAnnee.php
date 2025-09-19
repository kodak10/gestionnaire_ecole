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
                Log::warning('Middleware CheckEcoleAnnee: valeurs école/année manquantes en session', [
                    'user_id' => Auth::id(),
                ]);

                return redirect()->route('login')
                                 ->with('error', 'Veuillez sélectionner une école et une année scolaire valides.');
            }

            Log::info('Middleware CheckEcoleAnnee - session OK', [
                'user_id' => Auth::id(),
                'current_ecole_id' => $ecoleId,
                'current_annee_scolaire_id' => $anneeId,
            ]);

            // Optionnel : stocker dans les attributs de la requête
            $request->attributes->set('ecole_id', $ecoleId);
            $request->attributes->set('annee_scolaire_id', $anneeId);
        }

        return $next($request);
    }
}
