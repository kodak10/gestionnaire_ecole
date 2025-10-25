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
                    ->withPivot('coefficient', 'ordre', 'denominateur')
                    ->withTimestamps();
    }

    public function classes($ecoleId = null, $anneeScolaireId = null)
    {
        return $this->niveaux->flatMap(function ($niveau) use ($ecoleId, $anneeScolaireId) {
            return $niveau->classes
                          ->filter(function ($classe) use ($ecoleId, $anneeScolaireId) {
                              return (!$ecoleId || $classe->ecole_id == $ecoleId)
                                  && (!$anneeScolaireId || $classe->annee_scolaire_id == $anneeScolaireId);
                          });
        });
    }

    


}
