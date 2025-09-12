<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    protected $fillable = [
        'annee_scolaire_id', 'ecole_id', 'matricule', 'nom', 'prenom','num_extrait', 'sexe', 'naissance', 'lieu_naissance', 'photo_path',
        'infos_medicales', 'parent_nom', 'parent_telephone', 'parent_telephone02', 'code_national'
    ];

    protected $casts = [
        'documents_fournis' => 'array',
        'naissance' => 'date'
    ];
 

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function reinscriptions()
    {
        return $this->hasMany(Reinscription::class);
    }
    

    public function getNomCompletAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }

  

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : asset('assets/img/default-user.png');
    }

   
    
    public function reductions()
    {
        return $this->hasMany(Reduction::class);
    }
    public function scopeActive($query)
{
    return $query->where('is_active', true);
}
public function getNaissanceFormatteeAttribute()
{
    return $this->naissance ? $this->naissance->format('d/m/Y') : 'N/A';
}


}