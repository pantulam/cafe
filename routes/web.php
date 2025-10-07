<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProductCostController;
use Carbon\Carbon;

// Apply basic auth to all routes
Route::middleware([])->group(function () {
//Route::middleware(['auth.basic'])->group(function () {


    // Default route - redirect to transactions
    Route::get('/', function () {
        return redirect()->route('transactions.index');
    });

    // Transactions Routes
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');

    // Product Costs Routes
    Route::prefix('product-costs')->name('product-costs.')->group(function () {
        Route::get('/', [ProductCostController::class, 'index'])->name('index');
        Route::get('/create', [ProductCostController::class, 'create'])->name('create');
        Route::post('/', [ProductCostController::class, 'store'])->name('store');
        Route::get('/{productCost}/edit', [ProductCostController::class, 'edit'])->name('edit');
        Route::put('/{productCost}', [ProductCostController::class, 'update'])->name('update');
        Route::delete('/{productCost}', [ProductCostController::class, 'destroy'])->name('destroy');
        Route::get('/bulk-edit', [ProductCostController::class, 'bulkEdit'])->name('bulk-edit');
        Route::post('/bulk-update', [ProductCostController::class, 'bulkUpdate'])->name('bulk-update');
        
        // Square sync routes
        Route::post('/sync-from-square', [ProductCostController::class, 'syncFromSquare'])->name('sync-from-square');
        Route::get('/import-from-square', [ProductCostController::class, 'importFromSquare'])->name('import-from-square');
        Route::post('/bulk-import', [ProductCostController::class, 'bulkImport'])->name('bulk-import');
        
        // AJAX update route
        Route::post('/{productCost}/update-cost', [ProductCostController::class, 'updateCost'])->name('update-cost');
    });

    // Cache management routes
    Route::prefix('cache')->name('cache.')->group(function () {
        Route::get('/clear', function() {
            try {
                $squareService = app(\App\Services\SquareService::class);
                $squareService->clearCache();
                return redirect()->back()->with('success', 'All caches cleared successfully.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error clearing cache: ' . $e->getMessage());
            }
        })->name('clear');

        Route::get('/pre-cache', function() {
            try {
                $squareService = app(\App\Services\SquareService::class);
                
                // Pre-cache last 7 days
                $today = Carbon::today('America/New_York');
                $cachedDays = 0;
                
                for ($i = 1; $i <= 7; $i++) {
                    $date = $today->copy()->subDays($i)->format('Y-m-d');
                    $count = $squareService->preCacheTransactions($date, $date);
                    if ($count > 0) {
                        $cachedDays++;
                    }
                }
                
                return redirect()->back()->with('success', "Pre-cached $cachedDays days of transactions.");
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error pre-caching: ' . $e->getMessage());
            }
        })->name('pre-cache');
    });

    // Test route for authentication
    Route::get('/test-auth', function() {
        return response()->json([
            'message' => 'Authentication successful!',
            'username' => request()->getUser(),
            'authenticated' => true
        ]);
    });
});

// Temporary test route (optional - can be removed in production)
Route::get('/test-square-config', function() {
    try {
        $accessToken = config('square.access_token');
        $environment = config('square.environment');
        $locationId = config('square.location_id');
        
        return response()->json([
            'access_token_exists' => !empty($accessToken),
            'environment' => $environment,
            'location_id_exists' => !empty($locationId),
            'square_client_initialized' => true
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
