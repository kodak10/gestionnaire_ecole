<?php

namespace App\Exports;

use App\Models\Classe;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Classe::with(['niveau', 'enseignant'])->get()->map(function ($classe) {
            return [
                'Niveau' => $classe->niveau->nom ?? '',
                'Nom' => $classe->nom,
                'Capacité' => $classe->capacite,
                'Enseignant' => $classe->enseignant->nom ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Niveau',
            'Nom',
            'Capacité',
            'Enseignant',
        ];
    }
}
