@extends('pos.layout')
@section('content')
@php $f=fn($n)=>'Rp '.number_format($n,0,',','.'); @endphp
<div class="p-4 space-y-4">
 <form class="flex flex-wrap gap-2 items-end bg-white p-3 rounded-xl shadow-sm">
  <select name="preset" class="border rounded p-2">@foreach(['today'=>'Hari ini','week'=>'Minggu ini','month'=>'Bulan ini','custom'=>'Kustom'] as $k=>$v)<option value="{{ $k }}" @selected($preset==$k)>{{ $v }}</option>@endforeach</select>
  <input type="date" name="from" value="{{ $from }}" class="border rounded p-2"><input type="date" name="to" value="{{ $to }}" class="border rounded p-2">
  <select name="group" class="border rounded p-2">@foreach(['day'=>'Harian','week'=>'Mingguan','month'=>'Bulanan'] as $k=>$v)<option value="{{ $k }}" @selected($group==$k)>{{ $v }}</option>@endforeach</select>
  <button class="px-4 py-2 rounded bg-amber-500 text-white font-semibold">Terapkan</button>
  @foreach(['xlsx'=>'Excel','pdf'=>'PDF','csv'=>'CSV'] as $k=>$v)<a href="/reports/export?{{ http_build_query(array_merge(request()->query(),['format'=>$k])) }}" class="px-4 py-2 rounded bg-slate-700 text-white">{{ $v }}</a>@endforeach</form>
 <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
  @foreach(['Transaksi'=>$rev['totals']['trx'],'Gross'=>$f($rev['totals']['gross']),'Diskon'=>$f($rev['totals']['discount']),'Pajak'=>$f($rev['totals']['tax']),'Net'=>$f($rev['totals']['net'])] as $k=>$v)
  <div class="bg-white rounded-xl p-3 shadow-sm"><div class="text-xs text-slate-500">{{ $k }}</div><div class="text-lg font-bold">{{ $v }}</div></div>@endforeach</div>
 <div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-xl p-4 shadow-sm"><canvas id="c1" height="110"></canvas></div>
  <div class="bg-white rounded-xl p-4 shadow-sm"><div class="font-semibold mb-2">Metode Pembayaran</div>
   @foreach($rev['methods'] as $m=>$t)<div class="flex justify-between py-1"><span>{{ strtoupper($m) }}</span><b>{{ $f($t) }}</b></div>@endforeach</div></div>
 <div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-white rounded-xl p-4 shadow-sm"><div class="font-semibold mb-2">Item Terlaris</div><canvas id="c2" height="160"></canvas>
   <table class="w-full text-sm mt-3">@foreach($top['items'] as $i)<tr class="border-t"><td>{{ $i->name }}</td><td class="text-right">{{ $i->qty }}</td><td class="text-right">{{ $f($i->revenue) }}</td></tr>@endforeach</table></div>
  <div class="bg-white rounded-xl p-4 shadow-sm"><div class="font-semibold mb-2">Kategori Favorit</div>
   <table class="w-full text-sm">@foreach($top['categories'] as $c)<tr class="border-t"><td>{{ $c->name }}</td><td class="text-right">{{ $c->qty }}</td><td class="text-right">{{ $f($c->revenue) }}</td></tr>@endforeach</table></div></div>
 <div class="bg-white rounded-xl p-4 shadow-sm overflow-x-auto"><div class="font-semibold mb-2">Laporan Per Kasir / Shift</div>
  <table class="w-full text-sm"><tr class="text-left text-slate-500"><th>Kasir</th><th>Buka</th><th>Tutup</th><th>Trx</th><th>Omset</th><th>Jam sibuk</th><th>Seharusnya</th><th>Fisik</th><th>Selisih</th></tr>
  @foreach($shifts as $s)<tr class="border-t"><td>{{ $s->cashier }}</td><td>{{ $s->opened_at }}</td><td>{{ $s->closed_at ?? '-' }}</td><td>{{ $s->trx }}</td><td>{{ $f($s->revenue) }}</td>
   <td>{{ $s->peak_hour!==null ? sprintf('%02d:00',$s->peak_hour) : '-' }}</td><td>{{ $s->end_cash!==null?$f($s->end_cash):'-' }}</td><td>{{ $s->actual_cash!==null?$f($s->actual_cash):'-' }}</td>
   <td class="font-bold {{ ($s->difference??0)<0?'text-red-600':'' }}">{{ $s->difference!==null?$f($s->difference):'-' }}</td></tr>@endforeach</table></div></div>
<script>
new Chart(c1,{type:'bar',data:{labels:@json($rev['rows']->pluck('period')),datasets:[{label:'Net',data:@json($rev['rows']->pluck('net')),backgroundColor:'#f59e0b'}]},options:{plugins:{legend:{display:false}}}});
new Chart(c2,{type:'bar',data:{labels:@json($top['items']->pluck('name')),datasets:[{label:'Qty',data:@json($top['items']->pluck('qty')),backgroundColor:'#334155'}]},options:{indexAxis:'y',plugins:{legend:{display:false}}}});
</script>@endsection
