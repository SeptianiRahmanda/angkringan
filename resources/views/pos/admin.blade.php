@extends('pos.layout')
@section('content')
@php
$F=['products'=>[['category_id','Kategori','select'],['name','Nama','text'],['price','Harga','number'],['cost_price','HPP','number'],['stock','Stok','number'],['image','Foto','file'],['is_available','Tersedia','check']],
'categories'=>[['name','Nama','text']],'tables'=>[['table_number','No. Meja','text']]][$res];
$title=['products'=>'Produk','categories'=>'Kategori','tables'=>'Meja'][$res];
@endphp
<div class="p-4 grid lg:grid-cols-3 gap-4" x-data="{row:{}}">
 <form method="POST" :action="'/admin/{{ $res }}/'+(row.id||'')" enctype="multipart/form-data" class="bg-white rounded-2xl shadow p-4 space-y-3 h-fit">@csrf
  <h2 class="font-bold" x-text="row.id?'Edit {{ $title }}':'Tambah {{ $title }}'"></h2>
  @foreach($F as [$n,$l,$t])
   <label class="block text-sm">{{ $l }}
   @if($t=='select')<select name="{{ $n }}" x-model="row.{{ $n }}" required class="w-full border rounded-lg p-2"><option value="">Pilih…</option>@foreach($cats as $id=>$nm)<option value="{{ $id }}">{{ $nm }}</option>@endforeach</select>
   @elseif($t=='check')<input type="checkbox" name="{{ $n }}" value="1" x-model="row.{{ $n }}" class="ml-2">
   @elseif($t=='file')<input type="file" name="{{ $n }}" accept="image/*" class="w-full text-sm">
   @else<input type="{{ $t }}" name="{{ $n }}" x-model="row.{{ $n }}" {{ in_array($n,['cost_price'])?'':'required' }} class="w-full border rounded-lg p-2">@endif</label>
  @endforeach
  <div class="flex gap-2"><button class="flex-1 py-2 rounded-lg bg-amber-500 text-white font-bold">Simpan</button>
   <button type="button" @click="row={}" class="px-3 rounded-lg bg-stone-200">Baru</button></div>
 </form>
 <div class="lg:col-span-2 bg-white rounded-2xl shadow p-4 overflow-x-auto"><table class="w-full text-sm">
  <tr class="text-left text-slate-500">@foreach($F as [$n,$l,$t])@if($t!='file')<th>{{ $l }}</th>@endif @endforeach<th></th></tr>
  @foreach($rows as $r)<tr class="border-t">
   @foreach($F as [$n,$l,$t])@if($t!='file')<td class="py-2">{{ $t=='select'?($cats[$r->$n]??'-'):($t=='check'?($r->$n?'Ya':'Tidak'):$r->$n) }}</td>@endif @endforeach
   <td class="text-right whitespace-nowrap"><button type="button" @click="row={{ Illuminate\Support\Js::from(array_merge($r->toArray(),['is_available'=>(bool)($r->is_available??false)])) }}" class="text-amber-600">Edit</button>
    <form method="POST" action="/admin/{{ $res }}/{{ $r->id }}/delete" class="inline" onsubmit="return confirm('Hapus?')">@csrf<button class="text-red-600 ml-2">Hapus</button></form></td></tr>@endforeach
 </table></div></div>@endsection
