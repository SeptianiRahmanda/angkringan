<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class ReportExport implements FromCollection, WithHeadings
{
    public function __construct(private $rows) {}
    public function collection() {
        return collect($this->rows)->map(fn ($x) => [$x->period, $x->trx, $x->gross, $x->discount, $x->tax, $x->net]);
    }
    public function headings(): array { return ['Periode', 'Transaksi', 'Gross', 'Diskon', 'Pajak', 'Net']; }
}
