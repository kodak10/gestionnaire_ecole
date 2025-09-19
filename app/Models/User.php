<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; 

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'pseudo',
        'password',
        'is_active',
        'ecole_id',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relations
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    // public function getAuthIdentifierName()
    // {
    //     return 'pseudo';
    // }

    public function findForPassport($username)
    {
        return $this->where('pseudo', $username)->first();
    }

    /**
     * Accessors pour les informations de session
     */
    protected $appends = ['current_ecole_id', 'current_annee_scolaire_id', 'current_ecole_nom', 'current_annee_scolaire'];

    public function getCurrentEcoleIdAttribute()
    {
        return session('current_ecole_id');
    }

    public function getCurrentAnneeScolaireIdAttribute()
    {
        return session('current_annee_scolaire_id');
    }

    public function getCurrentEcoleNomAttribute()
    {
        return session('current_ecole_nom');
    }

    public function getCurrentAnneeScolaireAttribute()
    {
        return session('current_annee_scolaire');
    }

    /**
     * Scope pour filtrer par école
     */
    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }
}