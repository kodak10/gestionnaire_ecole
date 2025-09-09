<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementCantine extends Model
{
    protected $table = 'paiement_cantines';


    protected $fillable = [
        'eleve_id', 'type_frais_id', 'mois_id', 'montant', 'mode_paiement', 'reference', 'user_id','annee_scolaire_id'
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function mois()
    {
        return $this->belongsToMany(MoisScolaire::class, 'paiement_detail_cantines', 'paiement_cantine_id', 'mois_id')
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

}
