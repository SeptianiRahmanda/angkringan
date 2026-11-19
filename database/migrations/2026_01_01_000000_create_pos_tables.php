<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $t) => $t->string('role')->default('cashier'));
        Schema::create('shifts', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained();
            $t->decimal('start_cash', 14, 2)->default(0); $t->decimal('end_cash', 14, 2)->nullable(); // end_cash = saldo seharusnya
            $t->decimal('actual_cash', 14, 2)->nullable();
            $t->string('status')->default('open'); $t->timestamp('opened_at'); $t->timestamp('closed_at')->nullable(); $t->timestamps();
        });
        Schema::create('tables', function (Blueprint $t) {
            $t->id(); $t->string('table_number')->unique(); $t->string('status')->default('empty'); $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->foreignId('category_id')->constrained(); $t->string('name');
            $t->decimal('price', 12, 2); $t->decimal('cost_price', 12, 2)->default(0);
            $t->string('image')->nullable(); $t->integer('stock')->default(100); $t->boolean('is_available')->default(true); $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('shift_id')->constrained(); $t->foreignId('table_id')->nullable()->constrained('tables');
            $t->string('order_number')->unique(); $t->string('order_type');
            $t->string('status')->default('unpaid');
            $t->decimal('subtotal', 14, 2)->default(0); $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('tax', 14, 2)->default(0); $t->decimal('total_amount', 14, 2)->default(0);
            $t->string('payment_method')->nullable();
            $t->foreignId('parent_id')->nullable(); $t->foreignId('merged_into')->nullable(); $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('order_id')->constrained()->cascadeOnDelete(); $t->foreignId('product_id')->constrained();
            $t->integer('quantity'); $t->decimal('price', 12, 2); $t->decimal('cost_price', 12, 2)->default(0);
            $t->string('notes')->nullable(); $t->string('status')->default('pending'); $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('order_id')->constrained(); $t->foreignId('shift_id')->constrained(); $t->foreignId('user_id')->constrained();
            $t->decimal('amount_paid', 14, 2); $t->string('payment_method'); $t->string('split_bill_id')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['payments','order_items','orders','products','categories','tables','shifts'] as $x) Schema::dropIfExists($x);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
