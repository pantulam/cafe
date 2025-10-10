<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SquareService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    public function index()
    {
        return view('cache.index');
    }

    public function preCache(Request $request)
    {
        try {
            $days = $request->input('days', 7);
            $startDate = now()->subDays($days)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            Log::info('Pre-caching transactions', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days
            ]);

            $transactionCount = $this->squareService->preCacheTransactions($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => "Successfully pre-cached {$transactionCount} transactions for the last {$days} days.",
                'transaction_count' => $transactionCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error pre-caching transactions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error pre-caching transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCache(Request $request)
    {
        try {
            $cacheType = $request->input('type', 'all');

            switch ($cacheType) {
                case 'transactions':
                    $this->squareService->clearAllTransactionCaches();
                    $message = 'Transaction cache cleared successfully';
                    break;
                
                case 'products':
                    $this->squareService->clearProductCostCache();
                    $message = 'Product cost cache cleared successfully';
                    break;
                
                case 'kitchen':
                    $this->squareService->clearKitchenCache();
                    $message = 'Kitchen cache cleared successfully';
                    break;
                
                case 'all':
                default:
                    $this->squareService->clearAllTransactionCaches();
                    $this->squareService->clearProductCostCache();
                    $this->squareService->clearKitchenCache();
                    Cache::flush();
                    $message = 'All caches cleared successfully';
                    break;
            }

            Log::info('Cache cleared', ['type' => $cacheType]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'cache_type' => $cacheType
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing cache', [
                'type' => $cacheType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cacheStatus()
    {
        try {
            // Check if we can get some basic transaction data to verify Square connection
            $today = now()->format('Y-m-d');
            $transactions = $this->squareService->getTransactions($today, $today);
            
            $status = [
                'square_connection' => 'connected',
                'transactions_today' => count($transactions),
                'cache_driver' => config('cache.default'),
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => [
                    'square_connection' => 'disconnected',
                    'error' => $e->getMessage(),
                    'cache_driver' => config('cache.default'),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        }
    }
}
