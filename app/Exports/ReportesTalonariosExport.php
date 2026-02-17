<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportesTalonariosExport implements FromArray, ShouldAutoSize
{
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }
}
