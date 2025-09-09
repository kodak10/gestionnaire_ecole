<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    // Surcharger pour utiliser notre vue personnalisée
    public function showResetForm(Request $request, $token = null)
    {
        return view('home.auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    // Personnaliser les règles de validation
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    // Personnaliser la réponse après réinitialisation
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect($this->redirectPath())
            ->with('status', trans($response))
            ->with('success', 'Votre mot de passe a été réinitialisé avec succès!');
    }
}