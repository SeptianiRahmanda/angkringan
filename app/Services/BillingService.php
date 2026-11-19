<?php
namespace App\Services;
use App\Models\{Order, OrderItem, Payment, Product, RestoTable, Shift, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingService
{
    const TAX = 0.10;

    public function currentShift(User $u): Shift {
        $s = Shift::where('user_id', $u->id)->where('status', 'open')->latest()->first();
        if (!$s) throw ValidationException::withMessages(['shift' => 'Buka shift kasir terlebih dahulu.']);
        return $s;
    }
    public function openShift(User $u, float $startCash): Shift {
        if (Shift::where('user_id', $u->id)->where('status', 'open')->exists())
            throw ValidationException::withMessages(['shift' => 'Shift masih terbuka.']);
        return Shift::create(['user_id' => $u->id, 'start_cash' => $startCash, 'opened_at' => now()]);
    }
    /** end_cash = saldo seharusnya (modal + tunai masuk); selisih = actual - end_cash */
    public function closeShift(Shift $s, float $actual): array {
        $cash = (float) $s->payments()->where('payment_method', 'cash')->sum('amount_paid');
        $expected = (float) $s->start_cash + $cash;
        $s->update(['end_cash' => $expected, 'actual_cash' => $actual, 'status' => 'closed', 'closed_at' => now()]);
        return ['expected' => $expected, 'actual' => $actual, 'difference' => $actual - $expected, 'cash_sales' => $cash];
    }

    public function recalc(Order $o): Order {
        $o->load('items');
        $sub = $o->items->sum(fn ($i) => $i->quantity * $i->price);
        $tax = round(max($sub - $o->discount, 0) * self::TAX);
        $o->update(['subtotal' => $sub, 'tax' => $tax, 'total_amount' => $sub - $o->discount + $tax]);
        return $o->fresh(['items.product', 'payments']);
    }

    /** Postpaid + meja yang punya tab terbuka: item ditambahkan ke tab tsb. */
    public function placeOrder(User $u, ?int $tableId, string $type, array $items): Order {
        return DB::transaction(function () use ($u, $tableId, $type, $items) {
            $shift = $this->currentShift($u);
            $o = null;
            if ($type === 'postpaid' && $tableId)
                $o = Order::where('table_id', $tableId)->where('order_type', 'postpaid')->whereIn('status', ['unpaid', 'split'])->first();
            $o ??= Order::create(['shift_id' => $shift->id, 'table_id' => $tableId, 'order_type' => $type,
                'order_number' => 'INV' . now()->format('ymdHis') . rand(10, 99)]);
            foreach ($items as $it) {
                $p = Product::lockForUpdate()->findOrFail($it['id']);
                if (!$p->is_available || $p->stock < $it['qty']) throw ValidationException::withMessages(['stock' => "Stok {$p->name} tidak cukup."]);
                $p->decrement('stock', $it['qty']);
                $o->items()->create(['product_id' => $p->id, 'quantity' => $it['qty'], 'price' => $p->price,
                    'cost_price' => $p->cost_price, 'notes' => $it['notes'] ?? null]);
            }
            if ($tableId) RestoTable::whereKey($tableId)->update(['status' => 'occupied']);
            return $this->recalc($o);
        });
    }

    public function pay(Order $o, User $u, string $method, ?float $amount = null, ?string $splitId = null): Order {
        return DB::transaction(function () use ($o, $u, $method, $amount, $splitId) {
            $o->refresh();
            if (in_array($o->status, ['paid', 'merged'])) throw ValidationException::withMessages(['order' => 'Tagihan sudah selesai/digabung.']);
            $remaining = $o->total_amount - $o->payments()->sum('amount_paid');
            $amount = min($amount ?? $remaining, $remaining);
            Payment::create(['order_id' => $o->id, 'shift_id' => $this->currentShift($u)->id, 'user_id' => $u->id,
                'amount_paid' => $amount, 'payment_method' => $method, 'split_bill_id' => $splitId]);
            $o->update($amount >= $remaining - 0.01 ? ['status' => 'paid', 'payment_method' => $method] : ['status' => 'split']);
            if ($o->status === 'paid') $this->releaseTable($o->table_id);
            return $o->fresh(['items.product', 'payments']);
        });
    }

    /** Split per item: [order_item_id => qty] dipindah ke tagihan baru. */
    public function splitByItems(Order $o, array $map): Order {
        return DB::transaction(function () use ($o, $map) {
            $new = Order::create(['shift_id' => $o->shift_id, 'table_id' => $o->table_id, 'order_type' => $o->order_type,
                'order_number' => $o->order_number . '-S' . (Order::where('parent_id', $o->id)->count() + 1), 'parent_id' => $o->id]);
            foreach ($map as $itemId => $qty) {
                $qty = (int) $qty; if ($qty < 1) continue;
                $it = OrderItem::where('order_id', $o->id)->findOrFail($itemId);
                if ($qty > $it->quantity) throw ValidationException::withMessages(['qty' => 'Qty split melebihi qty item.']);
                if ($qty == $it->quantity) $it->update(['order_id' => $new->id]);
                else { $it->decrement('quantity', $qty); $new->items()->create($it->only(['product_id','price','cost_price','notes','status']) + ['quantity' => $qty]); }
            }
            if (!$new->items()->count()) throw ValidationException::withMessages(['items' => 'Pilih minimal satu item.']);
            $this->recalc($o);
            return $this->recalc($new);
        });
    }

    /** Bagi rata: satu pembayaran per orang, sisa pembulatan masuk pembayaran pertama. */
    public function splitEqual(Order $o, User $u, array $methods): Order {
        $n = count($methods);
        $remaining = $o->total_amount - $o->payments()->sum('amount_paid');
        $share = floor($remaining / $n); $sid = (string) Str::uuid();
        foreach ($methods as $i => $m)
            $o = $this->pay($o, $u, $m, $i === 0 ? $remaining - $share * ($n - 1) : $share, $sid);
        return $o;
    }

    /** Gabung beberapa tagihan unpaid ke tagihan pertama. */
    public function merge(array $ids): Order {
        return DB::transaction(function () use ($ids) {
            $orders = Order::whereIn('id', $ids)->where('status', 'unpaid')->orderBy('id')->lockForUpdate()->get();
            if ($orders->count() < 2) throw ValidationException::withMessages(['merge' => 'Pilih minimal 2 tagihan belum dibayar.']);
            $target = $orders->shift();
            foreach ($orders as $o) {
                OrderItem::where('order_id', $o->id)->update(['order_id' => $target->id]);
                $o->update(['status' => 'merged', 'merged_into' => $target->id, 'subtotal' => 0, 'tax' => 0, 'total_amount' => 0]);
                $this->releaseTable($o->table_id);
            }
            return $this->recalc($target);
        });
    }

    /** Diskon persen/nominal; hanya sebelum ada pembayaran. */
    public function setDiscount(Order $o, string $type, float $v): Order {
        if ($o->payments()->exists() || in_array($o->status, ['paid', 'merged', 'void']))
            throw ValidationException::withMessages(['discount' => 'Diskon hanya untuk tagihan yang belum ada pembayaran.']);
        $sub = $o->items()->get()->sum(fn ($i) => $i->quantity * $i->price);
        $o->update(['discount' => $type === 'percent' ? round($sub * min($v, 100) / 100) : min($v, $sub)]);
        return $this->recalc($o);
    }

    /** Batalkan tagihan: stok kembali; jika sudah dibayar (hanya admin) dibuat pembayaran negatif (refund) di shift aktif. */
    public function void(Order $o, User $u, string $reason): Order {
        return DB::transaction(function () use ($o, $u, $reason) {
            $o->refresh();
            if (in_array($o->status, ['merged', 'void'])) throw ValidationException::withMessages(['void' => 'Tagihan tidak dapat dibatalkan.']);
            $pays = $o->payments()->where('amount_paid', '>', 0)->get();
            if ($pays->isNotEmpty() && $u->role !== 'admin') throw ValidationException::withMessages(['void' => 'Hanya admin yang boleh membatalkan tagihan yang sudah dibayar.']);
            foreach ($o->items as $i) Product::whereKey($i->product_id)->increment('stock', $i->quantity);
            if ($pays->isNotEmpty()) {
                $shift = $this->currentShift($u);
                foreach ($pays as $p) Payment::create(['order_id' => $o->id, 'shift_id' => $shift->id, 'user_id' => $u->id,
                    'amount_paid' => -$p->amount_paid, 'payment_method' => $p->payment_method, 'split_bill_id' => 'refund-' . $o->id]);
            }
            $o->items()->update(['status' => 'served']);
            $o->update(['status' => 'void', 'void_reason' => $reason, 'voided_at' => now()]);
            $this->releaseTable($o->table_id);
            return $o->fresh(['items.product', 'payments']);
        });
    }

    private function releaseTable(?int $id): void {
        if ($id && !Order::where('table_id', $id)->whereIn('status', ['unpaid', 'split'])->exists())
            RestoTable::whereKey($id)->update(['status' => 'empty']);
    }
}
