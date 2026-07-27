<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'montant', 
        'mode_paiement', 
        'reference', 
        'user_id', 
        'annee_scolaire_id', 
        'ecole_id'
    ];

    protected $casts = [
        'montant' => 'decimal:2'
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function details()
    {
        return $this->hasMany(PaiementDetail::class);
    }

    public function getTotalPayeAttribute()
    {
        return $this->details->sum('montant');
    }

    public function getResteAPayerAttribute()
    {
        return max(0, $this->montant - $this->total_paye);
    }
}