<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnneeScolaire extends Model
{
    protected $fillable = [
        'annee', 'date_debut', 'date_fin', 'est_active'
    ];
    
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_active' => 'boolean'
    ];

    public static function active()
    {
        return self::where('est_active', true)->firstOrFail();
    }
    
    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

}
