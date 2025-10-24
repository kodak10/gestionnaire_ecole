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
    $headings = [
        'Élève',
        'Classe',
        'Niveau',
        'Total Attendu',
        'Total Payé',
        'Reste à Payer',
    ];

    if (isset($this->filters['cantine'])) {
        array_splice($headings, 7, 0, 'Cantine');
    }
    if (isset($this->filters['transport'])) {
        array_splice($headings, 8, 0, 'Transport');
    }

    return $headings;
}

public function map($eleve): array
{
    $row = [
        $eleve['eleve'],
        $eleve['classe'],
        $eleve['niveau'],
        $eleve['total_attendu'],       // montant attendu
        $eleve['total_paye'],          // montant payé
        $eleve['reste_total'],         // clé corrigée
    ];

    if (isset($this->filters['cantine'])) {
        array_splice($row, 7, 0, $eleve['cantine'] ?? 'N/A');
    }
    if (isset($this->filters['transport'])) {
        array_splice($row, 8, 0, $eleve['transport'] ?? 'N/A');
    }

    // Format des montants avec séparateur de milliers
    $row[3] = number_format($row[3], 0, ',', ' ') . ' FCFA';
    $row[4] = number_format($row[4], 0, ',', ' ') . ' FCFA';
    $row[5] = number_format($row[5], 0, ',', ' ') . ' FCFA';

    return $row;
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
            // Nombre de lignes à insérer pour le titre et filtres
            $offset = 4;
            $event->sheet->insertNewRowBefore(1, $offset);

            // Titre
            $event->sheet->setCellValue('A1', 'Relance des Paiements');
            $event->sheet->mergeCells("A1:H1");
            $event->sheet->getStyle('A1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $event->sheet->getStyle('A1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('3498DB');
            $event->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Date
            $event->sheet->setCellValue('A2', 'Généré le: ' . date('d/m/Y'));
            $event->sheet->mergeCells("A2:H2");

            // Filtres appliqués
            if (!empty($this->filters)) {
                $event->sheet->setCellValue('A3', 'Filtres appliqués:');
                $row = 4;
                foreach ($this->filters as $key => $value) {
                    $event->sheet->setCellValue('A' . $row, ucfirst($key) . ': ' . $value);
                    $event->sheet->mergeCells("A{$row}:H{$row}");
                    $row++;
                }
            }

            // Déplacer le tableau principal après les filtres
            $startTableRow = $row ?? $offset; // si pas de filtres, on commence à la ligne 5
            $endTableRow = $startTableRow + count($this->data);
            $event->sheet->getStyle('A' . $startTableRow . ':H' . $endTableRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                ]
            ]);
        },
    ];
}

}