@extends('pos.layout')
@section('content')
<div class="max-w-md mx-auto m-6 bg-white rounded-2xl shadow p-6 space-y-4">
 <h1 class="text-xl font-bold">Shift Kasir</h1>
 @if($result)<div class="p-3 rounded bg-emerald-50 text-sm space-y-1">
  <div>Tunai masuk: Rp {{ number_format($result['cash_sales'],0,',','.') }}</div><div>Saldo seharusnya: Rp {{ number_format($result['expected'],0,',','.') }}</div>
  <div>Saldo fisik: Rp {{ number_format($result['actual'],0,',','.') }}</div>
  <div class="font-bold {{ $result['difference']<0?'text-red-600':'text-emerald-700' }}">Selisih: Rp {{ number_format($result['difference'],0,',','.') }}</div></div>@endif
 @if($shift)
  <p class="text-sm">Dibuka {{ $shift->opened_at->format('d M H:i') }} · modal Rp {{ number_format($shift->start_cash,0,',','.') }}</p>
  <form method="POST" action="/shift/close" class="space-y-3">@csrf
   <input name="actual_cash" type="number" min="0" required placeholder="Uang fisik di laci (Rp)" class="w-full border rounded-lg p-2">
   <button class="w-full py-2 rounded-lg bg-red-600 text-white font-bold">Tutup Shift</button></form>
 @else
  <form method="POST" action="/shift/open" class="space-y-3">@csrf
   <input name="start_cash" type="number" min="0" required placeholder="Modal awal kasir (Rp)" class="w-full border rounded-lg p-2">
   <button class="w-full py-2 rounded-lg bg-amber-500 text-white font-bold">Buka Shift</button></form>
 @endif</div>@endsection
