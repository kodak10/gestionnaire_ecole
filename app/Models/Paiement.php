<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'inscription_id', 'type_frais_id', 'mois_id', 'montant', 'mode_paiement', 
        'reference', 'user_id', 'annee_scolaire_id', 'est_frais_inscription', 'ecole_id'
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }
    
    

    public function mois()
    {
        return $this->belongsToMany(MoisScolaire::class, 'paiement_details', 'paiement_id', 'mois_id')
                    ->withPivot('montant')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }
    
    // public function getTotalPayeAttribute()
    // {
    //     return $this->mois->sum(function ($mois) {
    //         return $mois->pivot->montant;
    //     });
    // }
    public function getTotalPayeAttribute()
    {
        return $this->details->sum('montant');
    }

    // Ajoutez cette relation pour les détails de paiement
    public function details()
    {
        return $this->hasMany(PaiementDetail::class);
    }

    public function getResteAPayerAttribute()
    {
        return max(0, $this->montant - $this->total_paye);
    }
    
    // Nouvelle méthode pour déterminer le type de frais
    public function getTypePaiementAttribute()
    {
        return $this->est_frais_inscription ? 'Inscription' : 'Scolarité';
    }
}