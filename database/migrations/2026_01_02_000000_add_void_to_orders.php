<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) { $t->string('void_reason')->nullable(); $t->timestamp('voided_at')->nullable(); });
    }
    public function down(): void {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['void_reason', 'voided_at']));
    }
};
