<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifMensuel extends Model
{
    use HasFactory;

    protected $table = 'tarifs_mensuels';

    protected $fillable = [
        'tarif_id',      // Nouveau champ
        'niveau_id',
        'mois_id',
        'montant',
        'ecole_id',
        'annee_scolaire_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    // Relation avec le tarif annuel
    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function moisScolaire()
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}