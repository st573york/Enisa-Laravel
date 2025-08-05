<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IndicatorValueComparisonExport implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    private $sources;
    private $data;

    public function __construct($sources, $data)
    {
        $this->sources = $sources;
        $this->data = $data;
    }

    public function view(): View
    {
        return view('export.indicator-values', $this->data);
    }

    public function title(): string
    {
        return 'Indicator Values';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);
        $sheet->getStyle('A')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C:F')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        if (count($this->sources) > 1)
        {
            $sheet->getStyle('G')->getFont()->setBold(true);
            $sheet->getStyle('G')->getAlignment()
                  ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        $sheet->getStyle('B:D')->getAlignment()->setWrapText(true);
    }

    public function columnWidths(): array
    {
        return [
            'B' => 70,
            'C' => 70,
            'D' => 70
        ];
    }
}
