<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class SurveyInfoTemplateExport implements FromView, WithTitle, WithStyles, WithColumnWidths, WithEvents, WithDrawings
{
    private $indicators;

    public function __construct($indicators)
    {
        $this->indicators = $indicators;
    }

    public function view(): View
    {
        return view(
            'export.survey-info-template',
            ['indicators' => $this->indicators]
        );
    }

    public function title(): string
    {
        return 'Info';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:B')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B1')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B1')->getAlignment()
              ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 27,
            'B' => 100,
            'C' => 20
        ];
    }

    public function registerEvents(): array
    {
        return [
            // handle by a closure.
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(105);
            }
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('ENISA');
        $drawing->setPath(public_path('/images/enisa_logo.png'));
        $drawing->setHeight(50);
        $drawing->setWidth(200);
        $drawing->setCoordinates('A1');

        return $drawing;
    }
}
