<?php
namespace App\Http\Controllers;
use App\Models\{Category, Order, Product, RestoTable, Shift};
use App\Services\BillingService as B;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(private B $b) {}
    private function load(Order $o) { return $o->fresh(['items.product', 'payments', 'table']); }

    public function index(Request $r) {
        return view('pos.index', [
            'categories' => Category::all(), 'products' => Product::where('is_available', true)->get(),
            'tables' => RestoTable::orderBy('table_number')->get(),
            'openList' => Order::with('table')->whereIn('status', ['unpaid', 'split'])->where('order_type', 'postpaid')->get()->map(fn ($o) => ['id' => $o->id, 'label' => ($o->table->table_number ?? '-') . ' · ' . $o->order_number, 'total_amount' => $o->total_amount, 'status' => $o->status])->values(),
            'shift' => Shift::where('user_id', $r->user()->id)->where('status', 'open')->first(),
        ]);
    }
    public function order(Order $order) { return $this->load($order); }
    public function store(Request $r) {
        $d = $r->validate(['table_id' => 'nullable|exists:tables,id', 'type' => 'required|in:prepaid,postpaid',
            'items' => 'required|array|min:1', 'items.*.id' => 'required|exists:products,id', 'items.*.qty' => 'required|integer|min:1']);
        return $this->b->placeOrder($r->user(), $d['table_id'] ?? null, $d['type'], $d['items']);
    }
    public function pay(Request $r, Order $order) {
        $d = $r->validate(['method' => 'required|in:cash,card', 'amount' => 'nullable|numeric|min:1']);
        return $this->b->pay($order, $r->user(), $d['method'], $d['amount'] ?? null);
    }
    public function splitItems(Request $r, Order $order) {
        return $this->b->splitByItems($order, $r->validate(['items' => 'required|array'])['items']);
    }
    public function splitEqual(Request $r, Order $order) {
        return $this->b->splitEqual($order, $r->user(), $r->validate(['methods' => 'required|array|min:2', 'methods.*' => 'in:cash,card'])['methods']);
    }
    public function merge(Request $r) {
        return $this->b->merge($r->validate(['ids' => 'required|array|min:2'])['ids']);
    }
    public function discount(Request $r, Order $order) {
        $d = $r->validate(['type' => 'required|in:percent,nominal', 'value' => 'required|numeric|min:0']);
        return $this->b->setDiscount($order, $d['type'], $d['value']);
    }
    public function void(Request $r, Order $order) {
        $o = $this->b->void($order, $r->user(), $r->validate(['reason' => 'required|max:200'])['reason']);
        return $r->expectsJson() ? $o : back()->with('ok', 'Tagihan ' . $o->order_number . ' dibatalkan.');
    }
    public function history() {
        return view('pos.history', ['orders' => Order::with('table')->where('status', '!=', 'merged')->latest()->limit(60)->get()]);
    }
    public function receipt(Order $order) {
        return view('pos.receipt', ['o' => $this->load($order), 'w' => request('w', '80')]);
    }
}
