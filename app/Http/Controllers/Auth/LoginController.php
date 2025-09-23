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

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Récupérer toutes les années scolaires actives avec les écoles associées
        $anneesScolaires = AnneeScolaire::with('ecole')->get();

        return view('home.auth.login', compact('anneesScolaires'));
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'pseudo';
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'user_ecole_annee' => 'required|string',
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
   

    protected function attemptLogin(Request $request)
    {
        // Valider et extraire les informations d'école et année scolaire
        $userEcoleAnnee = $request->input('user_ecole_annee');
        if (!str_contains($userEcoleAnnee, '_')) {
            return false;
        }

        list($ecoleId, $anneeScolaireId) = explode('_', $userEcoleAnnee);

        // Vérifier que l'école et l'année scolaire existent
        $ecole = Ecole::find($ecoleId);
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        if (!$ecole || !$anneeScolaire) {
            return false;
        }

        // Vérifier si l'utilisateur existe
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

            return true;
        }

        return false;
    }


    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // Utiliser seulement pseudo et password pour l'authentification
        return $request->only($this->username(), 'password');
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $userEcoleAnnee = $request->input('user_ecole_annee');
        
        // Vérifier le format user_ecole_annee
        if (!str_contains($userEcoleAnnee, '_')) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'user_ecole_annee' => 'Sélection invalide d\'école et année scolaire.',
                ]);
        }

        list($ecoleId, $anneeScolaireId) = explode('_', $userEcoleAnnee);

        // Vérifier l'école et l'année scolaire
        $ecole = Ecole::find($ecoleId);
        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        if (!$ecole) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'user_ecole_annee' => 'École non trouvée.',
                ]);
        }

        if (!$anneeScolaire) {
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'user_ecole_annee' => 'Année scolaire non trouvée.',
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
            return redirect()->back()
                ->withInput($request->only('pseudo', 'remember'))
                ->withErrors([
                    'pseudo' => 'Votre compte est désactivé.',
                ]);
        }

        // Si tout est bon mais que le mot de passe est incorrect
        return redirect()->back()
            ->withInput($request->only('pseudo', 'remember'))
            ->withErrors([
                'password' => 'Mot de passe incorrect.',
            ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Nettoyer les informations spécifiques avant la déconnexion
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