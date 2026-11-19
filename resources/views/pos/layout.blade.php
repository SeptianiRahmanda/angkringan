<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><title>Angkringan POS</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script></head>
<body class="bg-stone-100 text-slate-800 min-h-screen">
<nav class="bg-slate-800 text-stone-100 px-4 py-3 flex gap-6 items-center">
  <b class="text-amber-400">🍢 Angkringan POS</b>
  <a href="/pos" class="hover:text-amber-300">Kasir</a><a href="/shift" class="hover:text-amber-300">Shift</a><a href="/history" class="hover:text-amber-300">Riwayat</a><a href="/kitchen" class="hover:text-amber-300">Dapur</a>
  @if(auth()->user()->role==='admin')<a href="/reports" class="hover:text-amber-300">Laporan</a><a href="/admin/products" class="hover:text-amber-300">Produk</a><a href="/admin/categories" class="hover:text-amber-300">Kategori</a><a href="/admin/tables" class="hover:text-amber-300">Meja</a>@endif
  <form method="POST" action="/logout" class="ml-auto">@csrf<button class="text-sm text-stone-300">Keluar</button></form></nav>
@if(session('ok'))<div class="m-4 p-3 bg-emerald-100 text-emerald-700 rounded">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="m-4 p-3 bg-red-100 text-red-700 rounded">{{ $errors->first() }}</div>@endif
@yield('content')</body></html>
