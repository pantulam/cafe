<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\ProductCostController;
use App\Http\Controllers\SquareSyncController;
use App\Http\Controllers\CacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home and basic routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Transaction routes
Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/transactions', [TransactionController::class, 'getTransactions'])->name('transactions.get');
Route::get('/transactions/{paymentId}', [TransactionController::class, 'show'])->name('transactions.show');

// Kitchen screen routes
Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
Route::post('/kitchen/refresh', [KitchenController::class, 'refresh'])->name('kitchen.refresh');
Route::post('/kitchen/clear-cache', [KitchenController::class, 'clearCache'])->name('kitchen.clear-cache');

// Product cost management routes
Route::prefix('product-costs')->group(function () {
    Route::get('/', [ProductCostController::class, 'index'])->name('product-costs.index');
    Route::get('/create', [ProductCostController::class, 'create'])->name('product-costs.create');
    Route::post('/', [ProductCostController::class, 'store'])->name('product-costs.store');
    Route::get('/{id}/edit', [ProductCostController::class, 'edit'])->name('product-costs.edit');
    Route::put('/{id}', [ProductCostController::class, 'update'])->name('product-costs.update');
    Route::delete('/{id}', [ProductCostController::class, 'destroy'])->name('product-costs.destroy');
    Route::post('/bulk-update', [ProductCostController::class, 'bulkUpdate'])->name('product-costs.bulk-update');
    Route::get('/export', [ProductCostController::class, 'export'])->name('product-costs.export');
    Route::post('/import', [ProductCostController::class, 'import'])->name('product-costs.import');
    Route::post('/import-from-square', [ProductCostController::class, 'importFromSquare'])->name('product-costs.import-from-square'); // ADDED THIS ROUTE
    Route::get('/sync', [ProductCostController::class, 'syncFromSquare'])->name('product-costs.sync');
});

// Square synchronization routes
Route::prefix('square-sync')->group(function () {
    Route::get('/', [SquareSyncController::class, 'index'])->name('square-sync.index');
    Route::post('/sync-products', [SquareSyncController::class, 'syncProducts'])->name('square-sync.sync-products');
    Route::post('/clear-cache', [SquareSyncController::class, 'clearCache'])->name('square-sync.clear-cache');
    Route::post('/pre-cache', [SquareSyncController::class, 'preCacheTransactions'])->name('square-sync.pre-cache');
    Route::get('/sync-status', [SquareSyncController::class, 'syncStatus'])->name('square-sync.status');
});

// Cache management routes
Route::prefix('cache')->group(function () {
    Route::get('/', [CacheController::class, 'index'])->name('cache.index');
    Route::post('/pre-cache', [CacheController::class, 'preCache'])->name('cache.pre-cache');
    Route::post('/clear', [CacheController::class, 'clearCache'])->name('cache.clear');
    Route::get('/status', [CacheController::class, 'cacheStatus'])->name('cache.status');
});

// Reports and analytics routes
Route::prefix('reports')->group(function () {
    Route::get('/profit-loss', function () {
        return view('reports.profit-loss');
    })->name('reports.profit-loss');
    
    Route::get('/sales', function () {
        return view('reports.sales');
    })->name('reports.sales');
    
    Route::get('/inventory', function () {
        return view('reports.inventory');
    })->name('reports.inventory');
    
    Route::get('/transactions', [TransactionController::class, 'report'])->name('reports.transactions');
});

// Settings and configuration routes
Route::prefix('settings')->group(function () {
    Route::get('/square', function () {
        return view('settings.square');
    })->name('settings.square');
    
    Route::get('/general', function () {
        return view('settings.general');
    })->name('settings.general');
});

// API webhook routes (if needed for Square webhooks)
Route::prefix('webhooks')->group(function () {
    Route::post('/square', function (Illuminate\Http\Request $request) {
        // Handle Square webhooks
        Log::info('Square webhook received', $request->all());
        return response()->json(['status' => 'success']);
    })->name('webhooks.square');
});

// Health check and status routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'square_configured' => !empty(config('square.access_token'))
    ]);
});

// Quick access routes for common actions
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    
    return redirect()->back()->with('success', 'All caches cleared successfully!');
})->name('clear-cache');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return redirect()->back()->with('success', 'Storage link created successfully!');
})->name('storage-link');

// Fallback route for 404 pages
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
