<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ElevesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $eleves;
    protected $filters;
    protected $columns;

    public function __construct($eleves, $filters = [])
    {
        $this->eleves = $eleves;
        $this->filters = $filters;

        // Colonnes dynamiques selon les filtres
        $this->columns = [
            'matricule' => true,
            'nom_complet' => true,
            'classe' => $filters['classe'] ?? false,
            'date_naissance' => true,
            'sexe' => $filters['sexe'] ?? false,
            'parent' => true,
            'telephone_parent' => true,
            'cantine' => $filters['cantine'] ?? false,
            'transport' => $filters['transport'] ?? false,
            'date_inscription' => true,
        ];
    }

    public function collection()
    {
        return $this->eleves;
    }

    public function headings(): array
    {
        $headings = [];
        if ($this->columns['matricule']) $headings[] = 'Matricule';
        if ($this->columns['nom_complet']) $headings[] = 'Nom Complet';
        if ($this->columns['classe']) $headings[] = 'Classe';
        if ($this->columns['date_naissance']) $headings[] = 'Date de Naissance';
        if ($this->columns['sexe']) $headings[] = 'Sexe';
        if ($this->columns['parent']) $headings[] = 'Parent';
        if ($this->columns['telephone_parent']) $headings[] = 'Téléphone Parent';
        if ($this->columns['cantine']) $headings[] = 'Cantine';
        if ($this->columns['transport']) $headings[] = 'Transport';
        if ($this->columns['date_inscription']) $headings[] = 'Date Inscription';
        return $headings;
    }

    public function map($inscription): array
    {
        $row = [];
        if ($this->columns['matricule']) $row[] = $inscription->eleve->code_national ?? $inscription->eleve->matricule;
        if ($this->columns['nom_complet']) $row[] = $inscription->eleve->nom_complet;
        if ($this->columns['classe']) $row[] = $inscription->classe->nom;
        if ($this->columns['date_naissance']) $row[] = $inscription->eleve->naissance_formattee;
        if ($this->columns['sexe']) $row[] = $inscription->eleve->sexe;
        if ($this->columns['parent']) $row[] = $inscription->eleve->parent_nom;
        if ($this->columns['telephone_parent']) $row[] = $inscription->eleve->parent_telephone;
        if ($this->columns['cantine']) $row[] = $inscription->cantine_active ? 'Oui' : 'Non';
        if ($this->columns['transport']) $row[] = $inscription->transport_active ? 'Oui' : 'Non';
        if ($this->columns['date_inscription']) $row[] = $inscription->created_at->format('d/m/Y');
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // Styles pour l'en-tête du tableau (ligne 5)
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Bordures pour tout le tableau
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . ($this->eleves->count() + 5))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
            ]
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->insertNewRowBefore(1, 4);

                $event->sheet->setCellValue('A1', 'Liste des Élèves');
                $event->sheet->mergeCells('A1:' . $event->sheet->getHighestColumn() . '1');
                $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $event->sheet->setCellValue('A2', 'Généré le: ' . date('d/m/Y'));
                $event->sheet->mergeCells('A2:' . $event->sheet->getHighestColumn() . '2');

                if (!empty($this->filters)) {
                    $filtresTexte = [];
                    foreach ($this->filters as $key => $value) {
                        if ($value !== 'Tous') { // afficher uniquement si le filtre est appliqué
                            $filtresTexte[] = ucfirst($key) . ': ' . $value;
                        }
                    }
                    if (!empty($filtresTexte)) {
                        $event->sheet->setCellValue('A3', 'Filtres appliqués: ' . implode(' | ', $filtresTexte));
                        $event->sheet->mergeCells('A3:' . $event->sheet->getHighestColumn() . '3');
                    }
                }
            },
        ];
    }
}
