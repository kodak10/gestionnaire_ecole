<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Eleve extends Model
{
    use LogsActivity;

    protected $fillable = [
        'annee_scolaire_id',
        'ecole_id',
        'classe_id',
        'matricule',
        'code_national',
        'nom',
        'prenom',
        'num_extrait',
        'sexe',
        'naissance',
        'lieu_naissance',
        'nationalite',
        'photo_path',
        'infos_medicales',
        // Père
        'pere_nom',
        'pere_contact',
        'pere_contact02',
        // Mère
        'mere_nom',
        'mere_contact',
        'mere_contact02',
        // Adresse
        'parent_adresse',
        // Options
        'transport_active',
        'cantine_active',
        'is_active',
        // Anciens champs (à conserver)
        'parent_nom',
        'parent_telephone',
        'parent_telephone02'
    ];

    protected $casts = [
        'naissance' => 'date',
        'transport_active' => 'boolean',
        'cantine_active' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly([
            'matricule',
            'code_national',
            'nom',
            'prenom',
            'num_extrait',
            'sexe',
            'naissance',
            'lieu_naissance',
            'nationalite',
            'pere_nom',
            'pere_contact',
            'pere_contact02',
            'mere_nom',
            'mere_contact',
            'mere_contact02',
            'parent_adresse',
            'transport_active',
            'cantine_active',
            'is_active',
            'classe_id',
            'ecole_id',
            'annee_scolaire_id'
        ])
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
            'created' => 'Élève créé',
            'updated' => 'Élève modifié',
            'deleted' => 'Élève supprimé',
            default => "Élève {$eventName}"
        });
}

    // Relations
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function reinscriptions()
    {
        return $this->hasMany(Reinscription::class);
    }

    public function reductions()
    {
        return $this->hasMany(Reduction::class);
    }

    // Accesseurs
    public function getNomCompletAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path && \Storage::disk('public')->exists($this->photo_path)) {
            return asset('storage/' . $this->photo_path);
        }
        //return asset('assets/images/default-avatar.png');
    }

    public function getNaissanceFormatteeAttribute()
    {
        return $this->naissance ? $this->naissance->format('d/m/Y') : 'N/A';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeForAnneeScolaire($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    /**
     * Boot du modèle pour logger automatiquement les événements
     */
    protected static function booted()
    {
        static::created(function ($eleve) {
            activity()
                ->performedOn($eleve)
                ->causedBy(auth()->user())
                ->withProperties([
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom
                ])
                ->log("Nouvel élève inscrit : {$eleve->nom} {$eleve->prenom}");
        });

        static::updated(function ($eleve) {
            $changes = $eleve->getDirty();
            $changedFields = array_keys($changes);
            
            activity()
                ->performedOn($eleve)
                ->causedBy(auth()->user())
                ->withProperties([
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'champs_modifies' => $changedFields,
                    'anciennes_valeurs' => $eleve->getOriginal(),
                    'nouvelles_valeurs' => $changes
                ])
                ->log("Élève modifié : {$eleve->nom} {$eleve->prenom}");
        });

        static::deleted(function ($eleve) {
            activity()
                ->performedOn($eleve)
                ->causedBy(auth()->user())
                ->withProperties([
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom
                ])
                ->log("Élève supprimé : {$eleve->nom} {$eleve->prenom}");
        });
    }
}