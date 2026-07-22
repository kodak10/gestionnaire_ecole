<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'pseudo',
        'password',
        'is_active',
        'ecole_id',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relation avec l'école
    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    // Relation avec les années scolaires via l'école
    public function anneesScolaires()
    {
        return $this->hasManyThrough(
            AnneeScolaire::class,
            Ecole::class,
            'id', // clé étrangère sur ecoles
            'ecole_id', // clé étrangère sur annees_scolaires
            'ecole_id', // clé locale sur users
            'id' // clé locale sur ecoles
        );
    }
}