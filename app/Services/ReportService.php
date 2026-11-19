<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private function paid($from, $to) {
        return DB::table('orders')->where('orders.status', 'paid')->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
    }
    /** $group: day | week | month (MySQL/MariaDB) */
    public function revenue(string $from, string $to, string $group = 'day'): array {
        $lite = DB::getDriverName() === 'sqlite';
        $fmt = ['day' => '%Y-%m-%d', 'week' => $lite ? '%Y-W%W' : '%x-W%v', 'month' => '%Y-%m'][$group] ?? '%Y-%m-%d';
        $per = $lite ? "strftime('$fmt',created_at)" : "DATE_FORMAT(created_at,'$fmt')";
        $rows = $this->paid($from, $to)->selectRaw("$per period, COUNT(*) trx, SUM(subtotal) gross,
            SUM(discount) discount, SUM(tax) tax, SUM(total_amount) net")->groupBy('period')->orderBy('period')->get();
        $methods = DB::table('payments')->join('orders', 'orders.id', '=', 'payments.order_id')->where('orders.status', 'paid')
            ->whereBetween('payments.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('payments.payment_method m, SUM(amount_paid) total')->groupBy('m')->pluck('total', 'm');
        return ['rows' => $rows, 'methods' => $methods, 'totals' => [
            'trx' => $rows->sum('trx'), 'gross' => $rows->sum('gross'), 'discount' => $rows->sum('discount'),
            'tax' => $rows->sum('tax'), 'net' => $rows->sum('net')]];
    }
    public function topItems(string $from, string $to, int $limit = 10): array {
        $base = $this->paid($from, $to)->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_items.product_id');
        return [
            'items' => (clone $base)->selectRaw('products.name, SUM(order_items.quantity) qty, SUM(order_items.quantity*order_items.price) revenue')
                ->groupBy('products.id', 'products.name')->orderByDesc('qty')->limit($limit)->get(),
            'categories' => (clone $base)->join('categories', 'categories.id', '=', 'products.category_id')
                ->selectRaw('categories.name, SUM(order_items.quantity) qty, SUM(order_items.quantity*order_items.price) revenue')
                ->groupBy('categories.id', 'categories.name')->orderByDesc('qty')->get(),
        ];
    }
    public function shifts(string $from, string $to) {
        $h = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H',created_at) AS INTEGER)" : 'HOUR(created_at)';
        return DB::table('shifts')->join('users', 'users.id', '=', 'shifts.user_id')
            ->whereBetween('opened_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw("shifts.*, users.name cashier,
              (SELECT COUNT(DISTINCT order_id) FROM payments WHERE shift_id=shifts.id) trx,
              (SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE shift_id=shifts.id) revenue,
              (actual_cash - end_cash) difference,
              (SELECT {$h} FROM payments WHERE shift_id=shifts.id GROUP BY {$h} ORDER BY COUNT(*) DESC LIMIT 1) peak_hour")
            ->orderByDesc('opened_at')->get();
    }
}
