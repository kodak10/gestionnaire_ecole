<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'libelle',
        'description',
        'montant',
        'date_depense',
        'depense_category_id', // 🔹 au lieu de categorie
        'mode_paiement',
        'beneficiaire',
        'reference',
        'justificatif',
        'annee_scolaire_id'
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant' => 'decimal:2'
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function category()
    {
        return $this->belongsTo(DepenseCategorie::class, 'depense_category_id');
    }
}
