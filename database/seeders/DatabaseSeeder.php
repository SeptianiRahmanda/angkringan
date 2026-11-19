<?php
namespace Database\Seeders;
use App\Models\{Category, Product, RestoTable, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void {
        User::unguarded(function () {
            User::updateOrCreate(['email' => 'admin@angkringan.test'], ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin']);
            User::updateOrCreate(['email' => 'kasir@angkringan.test'], ['name' => 'Kasir Utama', 'password' => Hash::make('password'), 'role' => 'cashier']);
        });
        for ($i = 1; $i <= 10; $i++) RestoTable::firstOrCreate(['table_number' => "M$i"]);
        $menu = ['Makanan' => [['Nasi Kucing Teri', 3000, 1800], ['Nasi Kucing Oseng', 3500, 2000], ['Sate Usus', 2500, 1200], ['Sate Telur Puyuh', 3000, 1500], ['Gorengan Tempe', 1500, 700]],
                 'Minuman' => [['Teh Jahe', 5000, 1500], ['Kopi Joss', 7000, 2500], ['Es Teh Manis', 4000, 1000], ['Wedang Uwuh', 8000, 3000]],
                 'Lauk' => [['Ayam Bakar', 15000, 9000], ['Bakwan Jagung', 2000, 800], ['Tahu Bacem', 2000, 900]]];
        foreach ($menu as $cat => $items) {
            $c = Category::firstOrCreate(['name' => $cat]);
            foreach ($items as [$n, $p, $cp]) Product::firstOrCreate(['name' => $n], ['category_id' => $c->id, 'price' => $p, 'cost_price' => $cp]);
        }
    }
}
