<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class IndexExcelExport implements FromView, WithTitle, WithStyles, WithColumnWidths
{
    private $index;
    private $type;
    private $configurationFlag;

    public function __construct($index, $type, $configurationFlag = false)
    {
        $this->index = $index;
        $this->type = $type;
        $this->configurationFlag = $configurationFlag;
    }

    public function view(): View
    {
        $configurationText = '';
        if ($this->configurationFlag) {
            $configurationText = '-configuration';
        }
        if ($this->type  == 'areas') {
            return view('export.areas' . $configurationText, ['index' => $this->index]);
        }
        if ($this->type  == 'subareas') {
            return view('export.subareas' . $configurationText, ['index' => $this->index]);
        }
        if ($this->type  == 'indicators') {
            return view('export.indicators' . $configurationText, ['index' => $this->index]);
        }
    }

    public function title(): string
    {
        $sheetType = Str::ucfirst($this->type);
        if (!$this->configurationFlag) {
            $title = $this->index->country->name . '_' . $this->index->configuration->year . '_' . $sheetType;
        }
        else {
            $title = $sheetType;
        }
        
        return $title;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:S')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('A:S')->getAlignment()->setWrapText(true);
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
            'M' => 30,
            'N' => 30,
            'O' => 30,
            'P' => 30,
            'Q' => 30,
            'R' => 30,
            'S' => 30
        ];
    }
}
