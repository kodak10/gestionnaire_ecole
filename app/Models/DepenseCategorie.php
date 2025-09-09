<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepenseCategorie extends Model
{
    use HasFactory;

    protected $fillable = ['nom'];

    public function depenses()
    {
        return $this->hasMany(Depense::class, 'depense_category_id');
    }

    public function anneeScolaire() 
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}
