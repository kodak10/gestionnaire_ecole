<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
    }

    // Surcharger pour utiliser notre vue personnalisée
    public function showLinkRequestForm()
    {
        return view('home.auth.passwords.email');
    }

    // Personnaliser la validation
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    }

    // Personnaliser la réponse après envoi du lien
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', trans($response));
    }
}