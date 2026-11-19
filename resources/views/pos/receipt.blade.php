<!doctype html><html><head><meta charset="utf-8"><title>{{ $o->order_number }}</title>
<style>@page{size:{{ $w }}mm auto;margin:2mm}body{font:12px monospace;width:{{ $w==58?'54':'76' }}mm;margin:0}.r{display:flex;justify-content:space-between}hr{border:0;border-top:1px dashed #000}</style></head>
<body onload="print()"><center><b>ANGKRINGAN</b><br>{{ $o->created_at->format('d/m/Y H:i') }}<br>{{ $o->order_number }} {{ $o->table?->table_number }}</center><hr>
@foreach($o->items as $i)<div>{{ $i->product->name }}</div><div class="r"><span>{{ $i->quantity }} x {{ number_format($i->price,0,',','.') }}</span><span>{{ number_format($i->quantity*$i->price,0,',','.') }}</span></div>@endforeach<hr>
<div class="r"><span>Subtotal</span><span>{{ number_format($o->subtotal,0,',','.') }}</span></div>
<div class="r"><span>Pajak</span><span>{{ number_format($o->tax,0,',','.') }}</span></div>
<div class="r"><b>TOTAL</b><b>{{ number_format($o->total_amount,0,',','.') }}</b></div><hr>
@foreach($o->payments as $p)<div class="r"><span>{{ strtoupper($p->payment_method) }}</span><span>{{ number_format($p->amount_paid,0,',','.') }}</span></div>@endforeach
<center>Terima kasih 🙏</center></body></html>
