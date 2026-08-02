<?php
// app/Models/Eleve.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    protected $fillable = [
        'annee_scolaire_id', 'ecole_id', 'classe_id', 'matricule',
        'code_national', 'nom', 'prenom', 'sexe', 'naissance',
        'lieu_naissance', 'nationalite', 'num_extrait', 'photo_path',
        'infos_medicales', 'parent_nom', 'parent_telephone',
        'parent_telephone02', 'parent_email', 'pere_nom', 'pere_contact',
        'pere_contact02', 'mere_nom', 'mere_contact', 'mere_contact02',
        'parent_adresse', 'transport_active', 'transport_tarif_id',
        'transport_start_date', 'cantine_active', 'cantine_tarif_id',
        'cantine_start_date', 'statut', 'is_active'
    ];

    public function getTableName(int $ecoleId, string $annee): string
    {
        $sigle = $this->getEcoleSigle($ecoleId);
        return 'eleves_' . $sigle . '_' . str_replace('-', '_', $annee);
    }

    private function getEcoleSigle(int $ecoleId): string
    {
        $ecole = \DB::table('ecoles')->where('id', $ecoleId)->first();
        if (!$ecole) {
            return 'ecole';
        }
        if (!empty($ecole->sigle_ecole)) {
            return strtoupper(trim($ecole->sigle_ecole));
        }
        return 'ecole';
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
}