<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoisScolaire extends Model
{
    protected $fillable = ['nom', 'numero', 'annee_scolaire_id']; // adapte selon ta structure

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }
}
