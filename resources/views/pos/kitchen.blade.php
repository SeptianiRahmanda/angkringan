@extends('pos.layout')
@section('content')
<style>[x-cloak]{display:none!important}</style>
<div x-data="kds()" x-init="load();setInterval(load,8000)" class="p-4 grid md:grid-cols-2 gap-4">
 <div x-show="err" x-cloak class="md:col-span-2 p-3 bg-red-100 text-red-700 rounded" x-text="err"></div>
 @foreach(['pending'=>['Pesanan Baru','bg-amber-500'],'cooking'=>['Sedang Dimasak','bg-sky-600']] as $st=>[$t,$c])
 <section><h2 class="font-bold mb-2">{{ $t }} (<span x-text="items.filter(i=>i.status=='{{ $st }}').length"></span>)</h2><div class="space-y-2">
  <template x-for="i in items.filter(i=>i.status=='{{ $st }}')" :key="i.id">
   <div class="bg-white rounded-xl shadow-sm p-3 flex items-center gap-3">
    <div class="flex-1"><div class="font-bold"><span x-text="i.qty+'× '+i.name"></span></div>
     <div class="text-xs text-slate-500"><span x-text="i.table"></span> · <span x-text="i.no"></span> · <span x-text="i.mins"></span> mnt</div>
     <div class="text-xs text-red-600" x-show="i.notes" x-text="i.notes"></div></div>
    <button @click="adv(i.id)" class="px-3 py-2 rounded-lg text-white font-semibold {{ $c }}" x-text="'{{ $st }}'=='pending'?'Masak':'Siap'"></button></div></template></div></section>
 @endforeach</div>
<script>function kds(){return{items:[],err:'',async load(){try{const r=await fetch('/kitchen/data',{headers:{Accept:'application/json'}});if(!r.ok)throw new Error('HTTP '+r.status);const j=await r.json();if(!Array.isArray(j))throw new Error(j.message||'format data salah');this.items=j;this.err=''}catch(e){this.err='Gagal memuat data dapur: '+e.message}},
 async adv(id){await fetch('/kitchen/item/'+id+'/advance',{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}});this.load()}}}</script>@endsection
