<?php

namespace App\Exports;

use App\Models\Classe;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ElevesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $eleves;
    protected $filters;

    public function __construct($eleves, $filters = [])
    {
        $this->eleves = $eleves;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->eleves;
    }

    public function title(): string
    {
        return 'Liste des Élèves';
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom Complet',
            'Classe',
            'Date de Naissance',
            'Sexe',
            'Parent',
            'Téléphone Parent',
            'Cantine',
            'Transport',
            'Date Inscription'
        ];
    }

    public function map($inscription): array
    {
        return [
            $inscription->eleve->code_national ?? $inscription->eleve->matricule,
            $inscription->eleve->nom_complet,
            $inscription->classe->nom,
            $inscription->eleve->naissance_formattee,
            $inscription->eleve->sexe,
            $inscription->eleve->parent_nom,
            $inscription->eleve->parent_telephone,
            $inscription->eleve->cantine_active ? 'Oui' : 'Non',
            $inscription->eleve->transport_active ? 'Oui' : 'Non',
            $inscription->created_at->format('d/m/Y')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styles pour l'en-tête
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Styles pour toutes les cellules
        $sheet->getStyle('A2:J' . ($this->eleves->count() + 1))->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Bordures pour tout le tableau
        $sheet->getStyle('A1:J' . ($this->eleves->count() + 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
            ]
        ]);

        // Largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(15);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ajouter les informations de filtres en en-tête
                $event->sheet->insertNewRowBefore(1, 4);
                
                $event->sheet->setCellValue('A1', 'Liste des Élèves');
                $event->sheet->setCellValue('A2', 'Généré le: ' . date('d/m/Y'));
                
                if (!empty($this->filters)) {
                    $event->sheet->setCellValue('A3', 'Filtres appliqués:');
                    $row = 4;
                    
                    foreach ($this->filters as $key => $value) {
                        $event->sheet->setCellValue('A' . $row, ucfirst($key) . ': ' . $value);
                        $row++;
                    }
                    
                    // Déplacer le tableau vers le bas
                    $event->sheet->getStyle('A' . ($row + 1) . ':J' . ($row + $this->eleves->count() + 1))->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                        ]
                    ]);
                }
            },
        ];
    }
}