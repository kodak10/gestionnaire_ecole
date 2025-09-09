<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // Utiliser le pseudo au lieu de l'email
    public function username()
    {
        return 'pseudo';
    }

    // Surcharger la méthode showLoginForm pour passer les données aux vues
    public function showLoginForm()
    {
        $anneesScolaires = AnneeScolaire::where('est_active', true)->get();
        $ecoles = Ecole::get();
        
        return view('home.auth.login', compact('anneesScolaires', 'ecoles'));
    }

    // Personnaliser les credentials avec année scolaire et école
    // protected function credentials(Request $request)
    // {
    //     return [
    //         'pseudo' => $request->pseudo,
    //         'password' => $request->password,
    //         'annee_scolaire_id' => $request->annee_scolaire_id,
    //         'ecole_id' => $request->ecole_id,
    //         'is_active' => true,
    //     ];
    // }

    protected function credentials(Request $request)
{
    return $request->only('pseudo', 'password');
}


    // Ajouter la validation personnalisée
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'pseudo' => 'required|string',
            'password' => 'required|string',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'ecole_id' => 'required|exists:ecoles,id',
        ]);
    }

    // Surcharger la tentative de login pour gérer année scolaire et école
    public function login(Request $request)
{
    $this->validateLogin($request);
    Log::info('Tentative de login', $request->only('pseudo', 'ecole_id', 'annee_scolaire_id'));

    // Vérifier si l'utilisateur existe avec cette école et année scolaire
    $user = User::where('pseudo', $request->pseudo)
        ->where('ecole_id', $request->ecole_id)
        ->where('annee_scolaire_id', $request->annee_scolaire_id)
        ->first();

    if (!$user) {
        Log::warning('Utilisateur introuvable avec ces critères', $request->only('pseudo', 'ecole_id', 'annee_scolaire_id'));
        return $this->sendFailedLoginResponse($request);
    }

    Log::info('Utilisateur trouvé', ['user_id' => $user->id, 'pseudo' => $user->pseudo]);

    // Tenter la connexion
    if ($this->attemptLogin($request)) {
        Log::info('Connexion réussie pour l’utilisateur', ['user_id' => $user->id]);
        return $this->sendLoginResponse($request);
    }

    Log::error('Échec de la tentative de login malgré utilisateur trouvé', ['user_id' => $user->id]);
    return $this->sendFailedLoginResponse($request);
}


// protected function authenticated(Request $request, $user)
// {
//     session(['ecole_id' => $user->ecole_id, 'annee_scolaire_id' => $user->annee_scolaire_id]);
//     return redirect()->route('dashboard')->with('success', 'Connexion réussie! Bienvenue ' . $user->name);
// }

protected function authenticated(Request $request, $user)
{
    session(['ecole_id' => $user->ecole_id, 'annee_scolaire_id' => $user->annee_scolaire_id]);

    // récupérer l'année scolaire complète
    $annee = AnneeScolaire::find($user->annee_scolaire_id);
    session(['annee_scolaire' => $annee]);

    return redirect()->route('dashboard')->with('success', 'Connexion réussie! Bienvenue ' . $user->name);
}



    // Personnaliser la réponse après déconnexion
    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('success', 'Vous avez été déconnecté avec succès.');
    }
}