<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckEcoleAnnee
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
 public function handle(Request $request, Closure $next)
{
    if (auth()->check()) {
        $user = auth()->user();
        

        if (!$user->ecole_id || !$user->annee_scolaire_id) {
            
            return redirect()->route('login')
                             ->with('error', 'Veuillez sélectionner une école et une année scolaire valides.');
        }
    }

    return $next($request);
}



}
