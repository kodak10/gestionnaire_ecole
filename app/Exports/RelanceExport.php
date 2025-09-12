<?php

namespace App\Exports;

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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RelanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $data;
    protected $filters;

    public function __construct($data, $filters = [])
    {
        $this->data = $data;
        $this->filters = $filters;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function title(): string
    {
        return 'Relance des Paiements';
    }

    public function headings(): array
    {
        return [
            'Élève',
            'Classe',
            'Niveau',
            'Total Attendu',
            'Total Payé',
            'Reste à Payer',
            'Statut',
            'En Retard Depuis'
        ];
    }

    public function map($eleve): array
    {
        return [
            $eleve['eleve'],
            $eleve['classe'],
            $eleve['niveau'],
            $eleve['total_attendu'],
            $eleve['total_paye'],
            $eleve['reste_a_payer'],
            $eleve['statut'],
            $eleve['en_retard_depuis'] ?? 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styles pour l'en-tête
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3498DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Styles pour toutes les cellules
        $sheet->getStyle('A2:H' . (count($this->data) + 1))->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Bordures pour tout le tableau
        $sheet->getStyle('A1:H' . (count($this->data) + 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
            ]
        ]);

        // Largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ajouter les informations de filtres en en-tête
                $event->sheet->insertNewRowBefore(1, 4);
                
                $event->sheet->setCellValue('A1', 'Relance des Paiements');
                $event->sheet->setCellValue('A2', 'Généré le: ' . date('d/m/Y'));
                
                if (!empty($this->filters)) {
                    $event->sheet->setCellValue('A3', 'Filtres appliqués:');
                    $row = 4;
                    
                    foreach ($this->filters as $key => $value) {
                        $event->sheet->setCellValue('A' . $row, ucfirst($key) . ': ' . $value);
                        $row++;
                    }
                    
                    // Déplacer le tableau vers le bas
                    $event->sheet->getStyle('A' . ($row + 1) . ':H' . ($row + count($this->data) + 1))->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                        ]
                    ]);
                }
            },
        ];
    }
}