<?php
namespace App\Http\Controllers;
use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private ReportService $s) {}
    private function data(Request $r): array {
        $p = $r->get('preset', 'month');
        [$f, $t] = match ($p) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'custom' => [$r->get('from', now()->toDateString()), $r->get('to', now()->toDateString())],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
        $g = $r->get('group', 'day');
        return ['from' => $f, 'to' => $t, 'preset' => $p, 'group' => $g, 'rev' => $this->s->revenue($f, $t, $g),
            'top' => $this->s->topItems($f, $t), 'shifts' => $this->s->shifts($f, $t)];
    }
    public function index(Request $r) { return view('pos.reports', $this->data($r)); }
    /** ?format=xlsx|pdf|csv */
    public function export(Request $r) {
        $d = $this->data($r); $name = "laporan_{$d['from']}_{$d['to']}";
        return match ($r->get('format', 'xlsx')) {
            'pdf' => Pdf::loadView('pos.report_pdf', $d)->setPaper('a4', 'landscape')->download("$name.pdf"),
            'csv' => Excel::download(new ReportExport($d['rev']['rows']), "$name.csv", \Maatwebsite\Excel\Excel::CSV),
            default => Excel::download(new ReportExport($d['rev']['rows']), "$name.xlsx"),
        };
    }
}
