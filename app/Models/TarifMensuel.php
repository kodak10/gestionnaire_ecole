<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifMensuel extends Model
{
    protected $table = 'tarifs_mensuels';
    
    protected $fillable = ['annee_scolaire_id', 'ecole_id', 'type_frais_id', 'niveau_id', 'mois_id', 'montant'];

    public function typeFrais() { 
        return $this->belongsTo(TypeFrais::class); 
    }
    public function niveau() 
    { 
        return $this->belongsTo(Niveau::class); 
    }
    public function mois() 
    { 
        return $this->belongsTo(MoisScolaire::class); 
    }
    public function ecole() 
    { 
        return $this->belongsTo(Ecole::class); 
    }
    
    // protected $fillable = ['type_frais_id', 'niveau_id', 'mois_id', 'montant'];

    

    // public function typeFrais()
    // {
    //     return $this->belongsTo(TypeFrais::class);
    // }

    // public function niveau()
    // {
    //     return $this->belongsTo(Niveau::class);
    // }

    // public function mois()
    // {
    //     return $this->belongsTo(MoisScolaire::class);
    // }
}
