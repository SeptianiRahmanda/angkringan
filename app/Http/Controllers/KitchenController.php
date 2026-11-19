<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;

class KitchenController extends Controller
{
    public function index() { return view('pos.kitchen'); }
    /** Prepaid baru masuk dapur setelah lunas; postpaid langsung. */
    public function data() {
        return OrderItem::with('product', 'order.table')->whereIn('status', ['pending', 'cooking'])
            ->whereHas('order', fn ($q) => $q->where(fn ($w) => $w->where('order_type', 'postpaid')->whereIn('status', ['unpaid', 'split', 'paid']))
                ->orWhere(fn ($w) => $w->where('order_type', 'prepaid')->where('status', 'paid')))
            ->oldest()->get()->map(fn ($i) => ['id' => $i->id, 'name' => $i->product->name, 'qty' => $i->quantity, 'notes' => $i->notes,
                'status' => $i->status, 'table' => $i->order->table->table_number ?? 'Take away', 'no' => $i->order->order_number,
                'mins' => (int) $i->created_at->diffInMinutes()]);
    }
    public function advance(OrderItem $item) {
        $item->update(['status' => $item->status === 'pending' ? 'cooking' : 'served']);
        return ['ok' => true];
    }
}
