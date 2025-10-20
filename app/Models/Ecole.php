<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    protected $fillable = [
        'nom_ecole','sigle_ecole','code' ,'logo', 'adresse', 'telephone', 'fax', 'email', 'directeur', 'footer_bulletin', 'sms_notification'
    ];

    public function getNomAttribute()
    {
        return $this->nom_ecole;
    }

}
