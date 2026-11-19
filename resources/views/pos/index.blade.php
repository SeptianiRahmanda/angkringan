@extends('pos.layout')
@section('content')
@unless($shift)<div class="m-4 p-4 bg-amber-100 rounded-xl">Shift belum dibuka. <a class="underline font-semibold" href="/shift">Buka shift</a></div>@endunless
<div x-data="pos()" @keydown.escape.window="modal=''" class="grid lg:grid-cols-3 gap-4 p-4">
 {{-- Toast --}}
 <div x-show="toast.msg" x-transition class="fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow text-white" :class="toast.err?'bg-red-600':'bg-emerald-600'" x-text="toast.msg"></div>

 {{-- Katalog --}}
 <section class="lg:col-span-2">
  <div class="flex gap-2 mb-3 flex-wrap">
   <input x-model="q" placeholder="Cari menu…" class="flex-1 min-w-40 rounded-lg border px-3 py-2">
   <button @click="cat=0" :class="cat==0?'bg-amber-500 text-white':'bg-white'" class="px-3 rounded-lg border">Semua</button>
   @foreach($categories as $c)<button @click="cat={{ $c->id }}" :class="cat=={{ $c->id }}?'bg-amber-500 text-white':'bg-white'" class="px-3 rounded-lg border">{{ $c->name }}</button>@endforeach
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
   <template x-for="p in filtered" :key="p.id">
    <button @click="add(p)" :disabled="p.stock<1" class="bg-white rounded-xl shadow-sm p-3 text-left hover:ring-2 ring-amber-400 disabled:opacity-40">
     <div class="h-20 rounded-lg bg-amber-100 flex items-center justify-center text-3xl mb-2 overflow-hidden"><span x-show="!p.image">🍽️</span><img x-show="p.image" :src="p.image" class="h-20 w-full object-cover"></div>
     <div class="font-semibold text-sm" x-text="p.name"></div>
     <div class="text-amber-600 font-bold" x-text="rp(p.price)"></div><div class="text-xs text-slate-400">Stok <span x-text="p.stock"></span></div>
    </button></template>
  </div>
 </section>

 {{-- Panel order --}}
 <aside class="bg-white rounded-2xl shadow p-4 space-y-3 h-fit lg:sticky lg:top-4">
  <div class="flex gap-2">
   <select x-model="type" class="rounded-lg border p-2 flex-1"><option value="prepaid">Bayar dulu</option><option value="postpaid">Pesan dulu (tab)</option></select>
   <select x-model="table" class="rounded-lg border p-2 flex-1"><option value="">Tanpa meja</option>@foreach($tables as $t)<option value="{{ $t->id }}">{{ $t->table_number }} {{ $t->status=='occupied'?'●':'' }}</option>@endforeach</select>
  </div>
  <div class="space-y-1 max-h-56 overflow-auto"><template x-for="(i,k) in cart" :key="k">
   <div class="flex items-center gap-2 text-sm"><span class="flex-1" x-text="i.name"></span>
    <button @click="i.qty>1?i.qty--:cart.splice(k,1)" class="w-6 rounded bg-stone-200">−</button><b x-text="i.qty"></b><button @click="i.qty++" class="w-6 rounded bg-stone-200">+</button>
    <span class="w-20 text-right" x-text="rp(i.qty*i.price)"></span></div></template>
   <p x-show="!cart.length" class="text-slate-400 text-sm">Keranjang kosong</p></div>
  <div class="flex justify-between font-bold border-t pt-2"><span>Subtotal</span><span x-text="rp(sub)"></span></div>
  <button @click="send()" :disabled="!cart.length||busy" class="w-full py-3 rounded-xl bg-amber-500 text-white font-bold disabled:opacity-40" x-text="type=='prepaid'?'Pesan & Bayar':'Kirim Pesanan ke Dapur'"></button>

  <div x-show="order" x-cloak class="border-t pt-3 space-y-2 text-sm">
   <div class="flex justify-between"><b x-text="order?.order_number"></b><span class="text-xs bg-stone-200 rounded px-2 py-0.5" x-text="order?.status"></span></div>
   <template x-for="i in order?.items||[]"><div class="flex justify-between"><span x-text="i.quantity+'× '+i.product.name"></span><span x-text="rp(i.quantity*i.price)"></span></div></template>
   <div class="flex justify-between text-slate-500"><span>Pajak 10%</span><span x-text="rp(order?.tax)"></span></div>
   <div class="flex justify-between text-slate-500" x-show="paid>0"><span>Sudah dibayar</span><span x-text="rp(paid)"></span></div>
   <div class="flex justify-between text-lg font-bold"><span>Sisa</span><span x-text="rp(remaining)"></span></div>
   <div class="grid grid-cols-2 gap-2">
    <button @click="openPay()" class="col-span-2 py-2 rounded-lg bg-emerald-600 text-white font-bold">Bayar</button>
    <button @click="openSplit()" class="py-2 rounded-lg bg-slate-700 text-white">Split Item</button>
    <button @click="modal='equal'" class="py-2 rounded-lg bg-slate-700 text-white">Bagi Rata</button>
    <button @click="dval=0;modal='disc'" class="py-2 rounded-lg bg-stone-200">Diskon</button>
    <button @click="reason='';modal='void'" class="py-2 rounded-lg bg-red-100 text-red-700">Batalkan</button></div></div>

  <div class="border-t pt-3"><div class="text-xs font-semibold text-slate-500 mb-1">TAB MEJA TERBUKA</div>
   <div class="flex flex-wrap gap-2"><template x-for="o in open" :key="o.id"><button @click="load(o.id)" class="text-sm bg-stone-100 hover:bg-amber-100 rounded px-2 py-1" x-text="o.label+' · '+rp(o.total_amount)"></button></template>
    <span x-show="!open.length" class="text-sm text-slate-400">Tidak ada</span></div>
   <button @click="modal='merge';sel=[]" :disabled="open.length<2" class="mt-2 w-full py-2 rounded-lg bg-slate-700 text-white text-sm disabled:opacity-40">Gabung Tagihan…</button></div>
 </aside>

 {{-- Modal --}}
 <div x-show="modal" x-cloak class="fixed inset-0 z-40 bg-black/50 flex items-center justify-center p-4" @click.self="modal=''">
  <div class="bg-white rounded-2xl w-full max-w-md p-5 space-y-4 max-h-[90vh] overflow-auto">

   <div x-show="modal=='pay'" class="space-y-3"><h3 class="font-bold text-lg">Pembayaran</h3>
    <div class="text-3xl font-bold text-amber-600" x-text="rp(remaining)"></div>
    <div class="grid grid-cols-2 gap-2"><template x-for="m in methods"><button @click="pm=m.k" :class="pm==m.k?'bg-amber-500 text-white':'bg-stone-100'" class="py-2 rounded-lg font-semibold" x-text="m.n"></button></template></div>
    <div x-show="pm=='cash'" class="space-y-2">
     <input type="number" x-model.number="recv" placeholder="Uang diterima" class="w-full border rounded-lg p-2 text-lg">
     <div class="flex gap-2 flex-wrap"><button @click="recv=remaining" class="px-2 py-1 rounded bg-stone-100 text-sm">Pas</button>
      <template x-for="n in [20000,50000,100000]"><button @click="recv=n" class="px-2 py-1 rounded bg-stone-100 text-sm" x-text="rp(n)"></button></template></div>
     <div class="flex justify-between font-semibold"><span>Kembalian</span><span :class="recv>=remaining?'text-emerald-600':'text-red-500'" x-text="rp(Math.max(recv-remaining,0))"></span></div></div>
        <div x-show="pm=='card'" class="p-3 rounded-lg bg-stone-100 text-sm">Proses lewat mesin EDC, lalu konfirmasi.</div>
    <button @click="doPay()" :disabled="busy||(pm=='cash'&&recv<remaining)" class="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold disabled:opacity-40">Konfirmasi & Cetak Struk</button></div>

   <div x-show="modal=='split'" class="space-y-3"><h3 class="font-bold text-lg">Split Bill per Item</h3>
    <template x-for="i in order?.items||[]" :key="i.id"><div class="flex items-center gap-2 text-sm"><span class="flex-1" x-text="i.product.name+' (maks '+i.quantity+')'"></span>
     <button @click="pick[i.id]=Math.max((pick[i.id]||0)-1,0)" class="w-7 rounded bg-stone-200">−</button><b class="w-5 text-center" x-text="pick[i.id]||0"></b>
     <button @click="pick[i.id]=Math.min((pick[i.id]||0)+1,i.quantity)" class="w-7 rounded bg-stone-200">+</button></div></template>
    <div class="flex justify-between font-semibold"><span>Subtotal dipisah</span><span x-text="rp(pickSub)"></span></div>
    <button @click="doSplit()" :disabled="busy||!pickSub" class="w-full py-3 rounded-xl bg-slate-800 text-white font-bold disabled:opacity-40">Buat Tagihan Baru</button></div>

   <div x-show="modal=='equal'" class="space-y-3"><h3 class="font-bold text-lg">Bagi Rata</h3>
    <div class="flex items-center gap-2"><span>Jumlah pembayar</span><button @click="n>2&&n--;fit()" class="w-7 rounded bg-stone-200">−</button><b x-text="n"></b><button @click="n<10&&n++;fit()" class="w-7 rounded bg-stone-200">+</button></div>
    <template x-for="(m,k) in eq" :key="k"><div class="flex items-center gap-2 text-sm"><span class="w-20">Orang <span x-text="k+1"></span></span>
     <select x-model="eq[k]" class="border rounded p-1 flex-1"><template x-for="x in methods"><option :value="x.k" x-text="x.n"></option></template></select>
     <b x-text="rp(k==0?remaining-Math.floor(remaining/n)*(n-1):Math.floor(remaining/n))"></b></div></template>
    <button @click="doEqual()" :disabled="busy" class="w-full py-3 rounded-xl bg-slate-800 text-white font-bold">Proses Semua Pembayaran</button></div>

   <div x-show="modal=='merge'" class="space-y-3"><h3 class="font-bold text-lg">Gabung Tagihan</h3><p class="text-sm text-slate-500">Pilih minimal 2 tagihan belum dibayar. Digabung ke tagihan paling awal.</p>
    <template x-for="o in open.filter(x=>x.status=='unpaid')" :key="o.id"><label class="flex items-center gap-2 p-2 rounded-lg bg-stone-50"><input type="checkbox" :value="o.id" x-model="sel">
     <span class="flex-1" x-text="o.label"></span><b x-text="rp(o.total_amount)"></b></label></template>
    <button @click="doMerge()" :disabled="busy||sel.length<2" class="w-full py-3 rounded-xl bg-slate-800 text-white font-bold disabled:opacity-40">Gabungkan</button></div>

   <div x-show="modal=='disc'" class="space-y-3"><h3 class="font-bold text-lg">Diskon</h3>
    <div class="grid grid-cols-2 gap-2"><button @click="dtype='percent'" :class="dtype=='percent'?'bg-amber-500 text-white':'bg-stone-100'" class="py-2 rounded-lg">Persen %</button>
     <button @click="dtype='nominal'" :class="dtype=='nominal'?'bg-amber-500 text-white':'bg-stone-100'" class="py-2 rounded-lg">Nominal Rp</button></div>
    <input type="number" min="0" x-model.number="dval" class="w-full border rounded-lg p-2 text-lg">
    <button @click="doDisc()" :disabled="busy" class="w-full py-3 rounded-xl bg-slate-800 text-white font-bold">Terapkan</button></div>
   <div x-show="modal=='void'" class="space-y-3"><h3 class="font-bold text-lg text-red-700">Batalkan Tagihan</h3>
    <p class="text-sm text-slate-500">Stok dikembalikan. Alasan wajib diisi.</p>
    <input x-model="reason" placeholder="Alasan pembatalan" class="w-full border rounded-lg p-2">
    <button @click="doVoid()" :disabled="busy||!reason" class="w-full py-3 rounded-xl bg-red-600 text-white font-bold disabled:opacity-40">Batalkan Tagihan</button></div>
   <button @click="modal=''" class="w-full text-sm text-slate-500">Batal (Esc)</button>
  </div></div>
</div>
<script>
function pos(){return{products:@json($products),open:@json($openList),
 dtype:'percent',dval:0,reason:'',cart:[],q:'',cat:0,type:'prepaid',table:'',order:null,modal:'',busy:false,toast:{msg:'',err:false},
 methods:[{k:'cash',n:'Tunai'},{k:'card',n:'Kartu'}],pm:'cash',recv:0,pick:{},n:2,eq:['cash','cash'],sel:[],
 rp(n){return 'Rp '+Number(n||0).toLocaleString('id-ID')},
 say(m,err=false){this.toast={msg:m,err};setTimeout(()=>this.toast.msg='',3000)},
 get filtered(){return this.products.filter(p=>(!this.cat||p.category_id==this.cat)&&p.name.toLowerCase().includes(this.q.toLowerCase()))},
 get sub(){return this.cart.reduce((a,i)=>a+i.qty*i.price,0)},
 get paid(){return (this.order?.payments||[]).reduce((a,p)=>a+Number(p.amount_paid),0)},
 get remaining(){return Math.max(Number(this.order?.total_amount||0)-this.paid,0)},
 get pickSub(){return (this.order?.items||[]).reduce((a,i)=>a+(this.pick[i.id]||0)*i.price,0)},
 add(p){const x=this.cart.find(i=>i.id==p.id);x?x.qty++:this.cart.push({id:p.id,name:p.name,price:p.price,qty:1})},
 fit(){this.eq=Array.from({length:this.n},(_,k)=>this.eq[k]||'cash')},
 async api(u,b,m='POST'){this.busy=true;try{const r=await fetch(u,{method:m,headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:m=='GET'?null:JSON.stringify(b||{})});
  const j=await r.json();if(!r.ok){const e=j.errors?Object.values(j.errors)[0][0]:j.message;this.say(e||'Terjadi kesalahan',true);throw j}return j}finally{this.busy=false}},
 async load(id){this.order=await this.api('/pos/order/'+id,null,'GET')},
 async send(){this.order=await this.api('/pos/order',{table_id:this.table||null,type:this.type,items:this.cart});this.cart=[];
  if(this.type=='prepaid')this.openPay();else{this.say('Pesanan masuk ke tab '+this.order.order_number);setTimeout(()=>location.reload(),900)}},
 openPay(){this.pm='cash';this.recv=0;this.modal='pay'},
 async doPay(){const o=await this.api(`/pos/order/${this.order.id}/pay`,{method:this.pm,amount:this.pm=='cash'?Math.min(this.recv,this.remaining):this.remaining});
  window.open('/pos/receipt/'+o.id+'?w=80','_blank');this.say('Pembayaran berhasil');setTimeout(()=>location.reload(),800)},
 openSplit(){this.pick={};this.modal='split'},
 async doSplit(){const items={};Object.entries(this.pick).forEach(([k,v])=>v>0&&(items[k]=v));
  this.order=await this.api(`/pos/order/${this.order.id}/split-items`,{items});this.modal='';this.say('Tagihan baru dibuat: '+this.order.order_number)},
 async doEqual(){const o=await this.api(`/pos/order/${this.order.id}/split-equal`,{methods:this.eq});window.open('/pos/receipt/'+o.id,'_blank');setTimeout(()=>location.reload(),800)},
 async doDisc(){this.order=await this.api(`/pos/order/${this.order.id}/discount`,{type:this.dtype,value:this.dval||0});this.modal='';this.say('Diskon diterapkan')},
 async doVoid(){await this.api(`/pos/order/${this.order.id}/void`,{reason:this.reason});this.say('Tagihan dibatalkan');setTimeout(()=>location.reload(),800)},
 async doMerge(){this.order=await this.api('/pos/merge',{ids:this.sel});this.say('Digabung ke '+this.order.order_number);setTimeout(()=>location.reload(),900)}}}
</script>
<style>[x-cloak]{display:none!important}</style>@endsection
