<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['annee_scolaire_id', 'ecole_id', 'nom'];
    
    public function niveaux()
    {
        return $this->belongsToMany(Niveau::class, 'niveau_matiere')
                    ->withPivot('coefficient', 'ordre')
                    ->withTimestamps();
    }


}
