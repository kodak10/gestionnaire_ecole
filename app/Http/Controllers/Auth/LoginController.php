<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\User;
use App\Models\UserAnneeScolaire;
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

    // Affiche le formulaire de login avec écoles et années scolaires
    public function showLoginForm()
    {
        $anneesScolaires = AnneeScolaire::where('est_active', true)->get();
        $ecoles = Ecole::get();
        
        return view('home.auth.login', compact('anneesScolaires', 'ecoles'));
    }

    protected function credentials(Request $request)
    {
        return $request->only('pseudo', 'password');
    }

    // Validation du login
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'pseudo' => 'required|string',
            'password' => 'required|string',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'ecole_id' => 'required|exists:ecoles,id',
        ]);
    }

    // Tentative de login
    public function login(Request $request)
    {
        $this->validateLogin($request);

        Log::info('Tentative de login', $request->only('pseudo', 'ecole_id', 'annee_scolaire_id'));

        // Récupérer l'utilisateur
        $user = User::where('pseudo', $request->pseudo)
            ->where('ecole_id', $request->ecole_id)
            ->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            Log::warning('Utilisateur introuvable ou mot de passe incorrect', $request->only('pseudo', 'ecole_id', 'annee_scolaire_id'));
            return $this->sendFailedLoginResponse($request);
        }

        // Connexion réussie
        if ($this->attemptLogin($request)) {
            Log::info('Connexion réussie', ['user_id' => $user->id]);

            // Créer ou mettre à jour user_annees_scolaires
            UserAnneeScolaire::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'ecole_id' => $request->ecole_id,
                ],
                [
                    'annee_scolaire_id' => $request->annee_scolaire_id
                ]
            );

            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    protected function authenticated(Request $request, $user)
    {
        // Stocker les infos dans la session
        session([
            'ecole_id' => $user->ecole_id,
            'annee_scolaire_id' => $user->annee_scolaire_id,
            'annee_scolaire' => AnneeScolaire::find($user->annee_scolaire_id),
        ]);

        return redirect()->route('dashboard')->with('success', 'Connexion réussie! Bienvenue ' . $user->name);
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Vous avez été déconnecté avec succès.');
    }
}
