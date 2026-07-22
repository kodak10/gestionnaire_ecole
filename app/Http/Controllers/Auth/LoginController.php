<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        $ecoles = Ecole::all();
        return view('home.auth.login', compact('ecoles'));
    }

    public function username()
    {
        return 'pseudo';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'ecole_id' => 'required|exists:ecoles,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        $ecoleId = $request->input('ecole_id');
        $anneeScolaireId = $request->input('annee_scolaire_id');

        // Vérifier que l'école et l'année scolaire existent
        $ecole = Ecole::find($ecoleId);
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        if (!$ecole || !$anneeScolaire) {
            return false;
        }

        // Vérifier si l'utilisateur existe dans cette école
        $user = User::where('pseudo', $request->pseudo)
            ->where('ecole_id', $ecoleId)
            ->where('is_active', 1)
            ->first();

        if (!$user) {
            return false;
        }

        // Tenter la connexion
        $credentials = $this->credentials($request);
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Stocker les informations en session
            session([
                'current_ecole_id' => $ecoleId,
                'current_annee_scolaire_id' => $anneeScolaireId,
                'current_ecole_nom' => $ecole->nom_ecole,
                'current_annee_scolaire' => $anneeScolaire->annee
            ]);

            // Journaliser la connexion
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ecole' => $ecole->nom_ecole,
                    'annee_scolaire' => $anneeScolaire->annee,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ])
                ->log('Connexion utilisateur');

            return true;
        }

        return false;
    }

    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $ecoleId = $request->input('ecole_id');
        $anneeScolaireId = $request->input('annee_scolaire_id');
        
        // Vérifier l'école
        $ecole = Ecole::find($ecoleId);
        if (!$ecole) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'ecole_id' => 'École non trouvée.',
                ]);
        }

        // Vérifier l'année scolaire
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);
        if (!$anneeScolaire) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'annee_scolaire_id' => 'Année scolaire non trouvée.',
                ]);
        }

        // Vérifier l'utilisateur
        $user = User::where('pseudo', $request->pseudo)
                    ->where('ecole_id', $ecoleId)
                    ->first();

        if (!$user) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'pseudo' => 'Aucun utilisateur trouvé avec ce pseudo pour cette école.',
                ]);
        }

        if ($user->is_active == 0) {
            // Journaliser la tentative de connexion d'un compte inactif
            activity()
                ->withProperties([
                    'pseudo' => $request->pseudo,
                    'ecole' => $ecole->nom_ecole,
                    'ip' => $request->ip()
                ])
                ->log('Tentative de connexion sur compte inactif');

            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'pseudo' => 'Votre compte est désactivé.',
                ]);
        }

        // Journaliser la tentative de connexion échouée
        activity()
            ->withProperties([
                'pseudo' => $request->pseudo,
                'ecole' => $ecole->nom_ecole,
                'ip' => $request->ip()
            ])
            ->log('Tentative de connexion échouée');

        return redirect()->back()
            ->withInput($request->only('pseudo', 'remember'))
            ->withErrors([
                'password' => 'Mot de passe incorrect.',
            ]);
    }

    protected function authenticated(Request $request, $user)
    {
        return redirect()->intended($this->redirectPath());
    }

    public function logout(Request $request)
    {
        // Journaliser la déconnexion
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => $request->ip()
                ])
                ->log('Déconnexion utilisateur');
        }

        session()->forget([
            'current_ecole_id',
            'current_annee_scolaire_id',
            'current_ecole_nom',
            'current_annee_scolaire'
        ]);

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/');
    }
}