<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // Middleware Spatie appliqué à toutes les méthodes
        // sauf profil et updateProfile
        $this->middleware('role:SuperAdministrateur')->except(['profile', 'updateProfile']);
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ecoleId = auth()->user()->ecole_id;
        $users = User::with('ecole', 'roles')
        ->where('ecole_id', $ecoleId)
        ->get();
        $roles = Role::all();
        
        return view('dashboard.pages.parametrage.users.index', compact('users', 'roles'));
    }

    /**
 * Activer/désactiver un utilisateur
 */
    public function toggleStatus(Request $request, User $user)
    {
        $user->is_active = $request->is_active;
        $user->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(User $user)
    {
        $user->password = Hash::make('password'); // Mot de passe par défaut
        $user->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ecoles = Ecole::all();
        $roles = Role::all();
        return view('users.create', compact('ecoles', 'anneesScolaires', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pseudo' => 'required|string|max:255|unique:users',
            'role' => 'required|exists:roles,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $ecoleId = auth()->user()->ecole_id;

        $user = User::create([
            'name' => $request->name,
            'pseudo' => $request->pseudo,
            'password' => Hash::make('password'), // Mot de passe par défaut
            'is_active' => 1,
            'ecole_id' => $ecoleId,
        ]);
        $role = Role::find($request->role); // utilise find standard Eloquent
        if ($role) {
            $user->assignRole($role->name); // assignRole attend le nom
        }

        // Assigner le rôle
        $role = Role::findById($request->role);
        $user->assignRole($role);

        // Gérer l'upload de la photo
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profiles', 'public');
            $user->photo = $photoPath;
            $user->save();
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $ecoles = Ecole::all();
        $roles = Role::all();
        return view('users.edit', compact('user', 'ecoles', 'anneesScolaires', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pseudo' => 'required|string|max:255|unique:users,pseudo,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user->name = $request->name;
        $user->pseudo = $request->pseudo;
        $user->is_active = $request->has('is_active');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Mettre à jour le rôle
        $role = Role::findById($request->role);
        $user->syncRoles([$role]);

        // Gérer l'upload de la photo
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            
            $photoPath = $request->file('photo')->store('profiles', 'public');
            $user->photo = $photoPath;
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Supprimer la photo si elle existe
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }
        
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function profile()
    {
        $user = User::find(auth()->id());
        return view('dashboard.pages.parametrage.users.profil', compact('user'));
    }

    /**
     * Mettre à jour le profil de l'utilisateur connecté
     */
    public function updateProfile(Request $request)
    {
        $user = User::find(auth()->id());

        if ($request->input('update_type') === 'profile') {
            // Validation du profil complet
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'pseudo' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                        $query->where('ecole_id', $user->ecole_id);
                    }),
                ],
                'password' => 'nullable|string|min:8|confirmed',
            ]);
        } elseif ($request->input('update_type') === 'photo') {
            // Validation uniquement pour la photo
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Mettre à jour les champs si présents
        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        if ($request->filled('pseudo')) {
            $user->pseudo = $request->pseudo;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Photo
        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            $photoPath = $request->file('photo')->store('profiles', 'public');
            $user->photo = $photoPath;
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profil mis à jour avec succès.');
    }

}