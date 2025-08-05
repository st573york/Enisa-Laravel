<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IndexDataImport implements WithMultipleSheets
{
    protected $configuration;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function sheets(): array
    {
        return [
            'Results.FullScore' => new IndexDataFirstSheetImport($this->configuration),
            'Effective.Weights' => new IndexDataSecondSheetImport($this->configuration)
        ];
    }
}
