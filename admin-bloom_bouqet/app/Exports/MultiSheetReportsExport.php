<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetReportsExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary Report
        $sheets[] = new ReportsExport($this->startDate, $this->endDate, 'summary');

        // Sheet 2: Orders Report
        $sheets[] = new ReportsExport($this->startDate, $this->endDate, 'orders');

        // Sheet 3: Products Report
        $sheets[] = new ReportsExport($this->startDate, $this->endDate, 'products');

        return $sheets;
    }
}
