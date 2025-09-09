<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    // Surcharger la méthode showRegistrationForm
    public function showRegistrationForm()
    {
        return view('home.auth.register');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'pseudo' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'ecole_id' => ['nullable', 'exists:ecoles,id'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'pseudo' => $data['pseudo'],
            'password' => Hash::make($data['password']),
            'ecole_id' => $data['ecole_id'] ?? null,
            'annee_scolaire_id' => $data['annee_scolaire_id'] ?? null,
            'is_active' => true, // Activer l'utilisateur par défaut
        ]);
    }

    // Personnaliser la réponse après inscription réussie
    protected function registered(Request $request, $user)
    {
        return redirect($this->redirectPath())
            ->with('success', 'Inscription réussie! Bienvenue ' . $user->name);
    }
}