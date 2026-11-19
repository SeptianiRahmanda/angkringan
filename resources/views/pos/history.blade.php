@extends('pos.layout')
@section('content')
<div class="p-4"><div class="bg-white rounded-2xl shadow p-4 overflow-x-auto"><h1 class="font-bold mb-3">Riwayat Transaksi (60 terakhir)</h1>
<table class="w-full text-sm"><tr class="text-left text-slate-500"><th>Waktu</th><th>No</th><th>Meja</th><th>Tipe</th><th>Status</th><th class="text-right">Total</th><th></th></tr>
@foreach($orders as $o)@php $can = $o->status!='void' && ($o->status!='paid' || auth()->user()->role==='admin'); @endphp
<tr class="border-t align-top"><td class="py-2">{{ $o->created_at->format('d/m H:i') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->table->table_number ?? '-' }}</td><td>{{ $o->order_type }}</td>
<td><span class="px-2 rounded text-xs {{ ['paid'=>'bg-emerald-100','void'=>'bg-red-100','unpaid'=>'bg-amber-100','split'=>'bg-sky-100'][$o->status] ?? 'bg-stone-100' }}">{{ $o->status }}</span>@if($o->void_reason)<div class="text-xs text-slate-400">{{ $o->void_reason }}</div>@endif</td>
<td class="text-right">Rp {{ number_format($o->total_amount,0,',','.') }}</td>
<td class="text-right whitespace-nowrap"><a href="/pos/receipt/{{ $o->id }}?w=80" target="_blank" class="text-amber-600">Struk 80</a> · <a href="/pos/receipt/{{ $o->id }}?w=58" target="_blank" class="text-amber-600">58</a>
@if($can)<form method="POST" action="/pos/order/{{ $o->id }}/void" class="inline-flex gap-1 ml-2" onsubmit="return confirm('Batalkan tagihan ini?')">@csrf
<input name="reason" required placeholder="Alasan" class="border rounded px-2 py-1 w-28"><button class="text-red-600">Batalkan</button></form>@endif</td></tr>@endforeach
</table></div></div>@endsection
