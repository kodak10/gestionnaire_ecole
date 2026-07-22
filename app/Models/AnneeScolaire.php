<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AnneeScolaire extends Model
{
    use LogsActivity;

    protected $fillable = [
        'annee',
        'date_debut',
        'date_fin',
        'est_active',
        'ecole_id'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_active' => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'annee',
                'est_active',
                'ecole_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('annee_scolaire');
    }

    public static function active()
    {
        return self::where('est_active', true)->firstOrFail();
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_annees_scolaires',
            'annee_scolaire_id',
            'user_id'
        )
        ->withPivot('ecole_id')
        ->withTimestamps();
    }
}