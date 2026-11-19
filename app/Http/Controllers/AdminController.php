<?php
namespace App\Http\Controllers;
use App\Models\{Category, Product, RestoTable};
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function model(string $res): string {
        return ['products' => Product::class, 'categories' => Category::class, 'tables' => RestoTable::class][$res] ?? abort(404);
    }
    private function rules(string $res, $id): array {
        return [
            'products' => ['category_id' => 'required|exists:categories,id', 'name' => 'required|max:100', 'price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0', 'stock' => 'required|integer|min:0', 'image' => 'nullable|image|max:2048'],
            'categories' => ['name' => 'required|max:60'],
            'tables' => ['table_number' => 'required|max:20|unique:tables,table_number,' . ($id ?: 'NULL')],
        ][$res];
    }
    public function index(string $res) {
        $m = $this->model($res);
        return view('pos.admin', ['res' => $res, 'rows' => $m::orderBy('id')->get(), 'cats' => Category::pluck('name', 'id')]);
    }
    public function save(Request $r, string $res, ?int $id = null) {
        $m = $this->model($res);
        $d = $r->validate($this->rules($res, $id));
        if ($res === 'products') {
            $d['cost_price'] = $d['cost_price'] ?? 0;
            $d['is_available'] = $r->boolean('is_available');
            if ($r->hasFile('image')) $d['image'] = '/storage/' . $r->file('image')->store('products', 'public');
            else unset($d['image']);
        }
        $id ? $m::findOrFail($id)->update($d) : $m::create($d);
        return back()->with('ok', 'Tersimpan.');
    }
    public function destroy(string $res, int $id) {
        try { $this->model($res)::findOrFail($id)->delete(); }
        catch (QueryException) { return back()->withErrors(['x' => 'Data sudah dipakai transaksi/produk; nonaktifkan saja.']); }
        return back()->with('ok', 'Dihapus.');
    }
}
