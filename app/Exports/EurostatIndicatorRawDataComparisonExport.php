<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EurostatIndicatorRawDataComparisonExport implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function view(): View
    {
        return view('export.eurostat-indicator-raw-data', $this->data);
    }

    public function title(): string
    {
        return 'Eurostat - Raw Values';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);
        $sheet->getStyle('A')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D:G')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setWrapText(true);
        $sheet->getStyle('E:F')->getAlignment()->setWrapText(true);
    }

    public function columnWidths(): array
    {
        return [
            'B' => 70,
            'E' => 70,
            'F' => 70
        ];
    }
}
