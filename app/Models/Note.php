<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'inscription_id',
        'matiere_id',
        'classe_id',
        'annee_scolaire_id',
        'ecole_id',
        'valeur',
        'coefficient',
        'appreciation',
        'user_id',
        'mois_id',
    ];

    protected $casts = [
        'valeur' => 'decimal:2'
    ];

    public function mois()
{
    return $this->belongsTo(MoisScolaire::class, 'mois_id');
}

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }


    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, $request)
    {
        if ($request->filled('eleve_id')) {
            $query->where('eleve_id', $request->eleve_id);
        }

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        if ($request->filled('date_evaluation')) {
            $query->whereDate('date_evaluation', $request->date_evaluation);
        }
    }
}
