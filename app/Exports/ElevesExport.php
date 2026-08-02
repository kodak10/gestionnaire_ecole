<?php
// app/Exports/ElevesExport.php

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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;

class ElevesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $eleves;
    protected $filters;
    protected $columns;
    protected $ecole;

    public function __construct($eleves, $filters = [], $ecole = null)
    {
        $this->eleves = $eleves;
        $this->filters = $filters;
        $this->ecole = $ecole;

        // Si l'école n'est pas passée en paramètre, la récupérer depuis la session
        if (!$this->ecole) {
            $ecoleId = session('current_ecole_id');
            $this->ecole = DB::table('ecoles')->where('id', $ecoleId)->first();
        }

        // Colonnes dynamiques selon les filtres
        $this->columns = [
            'matricule' => true,
            'nom_complet' => true,
            'classe' => true,
            'date_naissance' => true,
            'sexe' => true,
            'parent' => true,
            'telephone_parent' => true,
            'cantine' => true,
            'transport' => true,
            'date_inscription' => true,
        ];
    }

    public function collection()
    {
        return $this->eleves;
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom Complet',
            'Classe',
            'Date de Naissance',
            'Sexe',
            'Parent/Tuteur',
            'Téléphone Parent',
            'Cantine',
            'Transport',
            "Date d'Inscription"
        ];
    }

    public function map($eleve): array
    {
        return [
            $eleve->matricule ?? '',
            ($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? ''),
            $eleve->classe_nom ?? 'Non assigné',
            $eleve->naissance ? date('d/m/Y', strtotime($eleve->naissance)) : '',
            $eleve->sexe ?? '',
            $eleve->parent_nom ?? $eleve->pere_nom ?? '',
            $eleve->parent_telephone ?? $eleve->pere_contact ?? '',
            $eleve->cantine_active ? 'Oui' : 'Non',
            $eleve->transport_active ? 'Oui' : 'Non',
            $eleve->created_at ? date('d/m/Y', strtotime($eleve->created_at)) : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Dernière ligne des données
        $lastRow = $this->eleves->count() + 5;

        // Style de l'en-tête (ligne 5)
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . '5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Bordures pour tout le tableau
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Auto-size des colonnes
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Alternance des couleurs pour les lignes
        $rowIndex = 6;
        foreach ($this->eleves as $index => $eleve) {
            $color = ($index % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
            $sheet->getStyle('A' . $rowIndex . ':' . $sheet->getHighestColumn() . $rowIndex)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color]
                ]
            ]);
            $rowIndex++;
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = $sheet->getHighestColumn();
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);

                // ============================================
                // 1. ENTÊTE AVEC LOGO ET INFOS ÉCOLE
                // ============================================
                
                // Lignes 1-4 pour l'en-tête
                $sheet->insertNewRowBefore(1, 4);

                // --- Ligne 1: Logo + Nom école ---
                $sheet->mergeCells('A1:B1');
                $sheet->setCellValue('A1', '🏫 ' . ($this->ecole->nom_ecole ?? 'École'));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '2C3E50']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Titre du document (centré)
                $sheet->mergeCells('C1:' . $lastColumn . '1');
                $sheet->setCellValue('C1', 'LISTE DES ÉLÈVES');
                $sheet->getStyle('C1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => '2980B9']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // --- Ligne 2: Informations de l'école ---
                $infosEcole = [];
                if ($this->ecole && $this->ecole->adresse) $infosEcole[] = '📌 ' . $this->ecole->adresse;
                if ($this->ecole && $this->ecole->telephone) $infosEcole[] = '📞 ' . $this->ecole->telephone;
                if ($this->ecole && $this->ecole->email) $infosEcole[] = '✉️ ' . $this->ecole->email;

                $sheet->mergeCells('A2:' . $lastColumn . '2');
                $sheet->setCellValue('A2', implode(' | ', $infosEcole));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '7F8C8D']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // --- Ligne 3: Année scolaire et date de génération ---
                $anneeScolaire = session('current_annee_scolaire') ?? date('Y') . '-' . (date('Y') + 1);
                $sheet->mergeCells('A3:' . $lastColumn . '3');
                $sheet->setCellValue('A3', 'Année Scolaire: ' . $anneeScolaire . ' | Généré le: ' . date('d/m/Y à H:i'));
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '7F8C8D']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // --- Ligne 4: Filtres appliqués ---
                if (!empty($this->filters)) {
                    $filtresTexte = [];
                    foreach ($this->filters as $key => $value) {
                        if ($value && $value !== 'Tous' && $value !== 'Toutes' && $value !== null) {
                            $labels = [
                                'classe' => 'Classe',
                                'nom' => 'Nom',
                                'sexe' => 'Sexe',
                                'cantine' => 'Cantine',
                                'transport' => 'Transport'
                            ];
                            $filtresTexte[] = ($labels[$key] ?? ucfirst($key)) . ': ' . $value;
                        }
                    }
                    if (!empty($filtresTexte)) {
                        $sheet->mergeCells('A4:' . $lastColumn . '4');
                        $sheet->setCellValue('A4', 'Filtres: ' . implode(' | ', $filtresTexte));
                        $sheet->getStyle('A4')->applyFromArray([
                            'font' => [
                                'size' => 10,
                                'color' => ['rgb' => '2980B9']
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);
                    }
                }

                // Hauteur des lignes d'en-tête
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                if (!empty($filtresTexte)) {
                    $sheet->getRowDimension(4)->setRowHeight(20);
                }

                // ============================================
                // 2. STYLES SUPPLEMENTAIRES
                // ============================================

                // Pied de page
                $lastRow = $this->eleves->count() + 5;
                $footerRow = $lastRow + 2;

                $sheet->mergeCells('A' . $footerRow . ':' . $lastColumn . $footerRow);
                $sheet->setCellValue('A' . $footerRow, 'Total: ' . $this->eleves->count() . ' élèves');
                $sheet->getStyle('A' . $footerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '2C3E50']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Bandeau de bas de page
                $footerRow2 = $footerRow + 1;
                $sheet->mergeCells('A' . $footerRow2 . ':' . $lastColumn . $footerRow2);
                $sheet->setCellValue('A' . $footerRow2, 'Document généré par ' . config('app.name', 'Gestion Scolaire'));
                $sheet->getStyle('A' . $footerRow2)->applyFromArray([
                    'font' => [
                        'size' => 9,
                        'color' => ['rgb' => '95A5A6']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
            },
        ];
    }
}