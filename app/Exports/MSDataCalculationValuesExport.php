<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MSDataCalculationValuesExport implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('export.ms-data-calculation-values', $this->data);
    }

    public function title(): string
    {
        return 'Data Calculation Values';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);
        $sheet->getStyle(2)->getFont()->setBold(true);
        $sheet->getStyle('C:O')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setWrapText(true);
        $sheet->mergeCells('C1:D1');
        $sheet->mergeCells('E1:F1');
        $sheet->mergeCells('G1:H1');
        $sheet->mergeCells('I1:J1');
        $sheet->mergeCells('K1:L1');
        $sheet->mergeCells('N1:O1');
    }

    public function columnWidths(): array
    {
        return [
            'B' => 70
        ];
    }
}
