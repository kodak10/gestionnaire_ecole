<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mention extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecole_id',
        'nom',
        'description',
        'min_note',
        'max_note',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }
}
