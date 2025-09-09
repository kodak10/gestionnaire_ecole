<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    protected $fillable = [
        'nom_ecole','sigle_ecole', 'logo', 'adresse', 'telephone', 'email', 'directeur', 'footer_bulletin'
    ];
}
