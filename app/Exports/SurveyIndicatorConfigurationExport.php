<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyIndicatorConfigurationExport implements FromView, WithTitle, WithStyles, WithColumnWidths
{
    private $indicatorData;

    public function __construct($indicatorData)
    {
        $this->indicatorData = $indicatorData;
    }

    public function view(): View
    {
        return view(
            'export.survey-indicator-configuration',
            ['data' => $this->indicatorData]
        );
    }

    public function title(): string
    {
        return $this->indicatorData['identifier'] . '. ' . $this->indicatorData['name'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:L')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('A:L')->getAlignment()->setWrapText(true);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 30,
            'C' => 30,
            'D' => 30,
            'E' => 30,
            'F' => 30,
            'G' => 30,
            'H' => 30,
            'I' => 30,
            'J' => 30,
            'K' => 30,
            'L' => 30,
        ];
    }
}
