<?php
use App\Http\Controllers\{AdminController, KitchenController, PosController, ReportController, ShiftController};
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pos');
Route::get('/dashboard', fn () => redirect('/pos'))->name('dashboard'); // tujuan redirect login Breeze
Route::middleware('auth')->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/order', [PosController::class, 'store']);
    Route::get('/pos/order/{order}', [PosController::class, 'order']);
    Route::post('/pos/order/{order}/pay', [PosController::class, 'pay']);
    Route::post('/pos/order/{order}/split-items', [PosController::class, 'splitItems']);
    Route::post('/pos/order/{order}/split-equal', [PosController::class, 'splitEqual']);
    Route::post('/pos/merge', [PosController::class, 'merge']);
    Route::get('/pos/receipt/{order}', [PosController::class, 'receipt']);
    Route::get('/shift', [ShiftController::class, 'index'])->name('shift');
    Route::post('/shift/open', [ShiftController::class, 'open']);
    Route::post('/shift/close', [ShiftController::class, 'close']);
    Route::post('/pos/order/{order}/discount', [PosController::class, 'discount']);
    Route::post('/pos/order/{order}/void', [PosController::class, 'void']);
    Route::get('/history', [PosController::class, 'history'])->name('history');
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen');
    Route::get('/kitchen/data', [KitchenController::class, 'data']);
    Route::post('/kitchen/item/{item}/advance', [KitchenController::class, 'advance']);

    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [ReportController::class, 'export']);
        Route::get('/admin/{res}', [AdminController::class, 'index'])->name('admin');
        Route::post('/admin/{res}/{id?}', [AdminController::class, 'save'])->whereNumber('id');
        Route::post('/admin/{res}/{id}/delete', [AdminController::class, 'destroy'])->whereNumber('id');
    });
});
require __DIR__ . '/auth.php'; // dari Laravel Breeze
