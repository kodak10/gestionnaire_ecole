<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeFrais extends Model
{
    protected $fillable = ['nom', 'obligatoire'];

    public function tarifs()
    {
        return $this->hasMany(Tarif::class);
    }

    public function tarifsMensuels()
    {
        return $this->hasMany(TarifMensuel::class);
    }

    public function anneeScolaire() 
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}