<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SquareService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class KitchenController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

public function index()
{
    try {
        // Get kitchen orders for last 24 hours
        $orders = $this->squareService->getKitchenOrders(24);
        
        // If no orders found, get transactions with order details
        $transactions = [];
        if (empty($orders)) {
            $transactions = $this->squareService->getTransactionsWithOrders(
                now()->subDays(1)->format('Y-m-d'), 
                now()->format('Y-m-d')
            );
            
            Log::info('Kitchen Controller - Using transactions with orders:', [
                'transactions_count' => count($transactions)
            ]);
        }

        Log::info('Kitchen Controller - Results:', [
            'orders_count' => count($orders),
            'transactions_count' => count($transactions),
            'current_time' => now()->format('Y-m-d H:i:s')
        ]);

        return view('kitchen.index', compact('orders', 'transactions'));
    } catch (\Exception $e) {
        Log::error('Kitchen Controller Error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed to fetch kitchen data: ' . $e->getMessage());
    }
}

    public function updateOrder(Request $request, $orderId)
    {
        try {
            $state = $request->input('state');
            Log::info('Updating order state:', ['order_id' => $orderId, 'state' => $state]);
            
            $result = $this->squareService->updateOrderState($orderId, $state);
            
            if ($result) {
                return redirect()->back()->with('success', 'Order updated successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to update order');
            }
        } catch (\Exception $e) {
            Log::error('Error updating order: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating order: ' . $e->getMessage());
        }
    }

    public function getOrders()
    {
        try {
            $orders = $this->squareService->getKitchenOrders(24);
            
            // Fallback to transactions if no orders
            $transactions = [];
            if (empty($orders)) {
                $transactionsData = $this->squareService->getTransactions(
                    now()->subDays(1)->format('Y-m-d'), 
                    now()->format('Y-m-d')
                );
                $transactions = $transactionsData['payments'] ?? [];
            }
            
            return response()->json([
                'success' => true,
                'orders' => $orders,
                'transactions' => $transactions,
                'last_updated' => now()->format('M j, Y g:i A'),
                'count' => count($orders) + count($transactions)
            ]);
        } catch (\Exception $e) {
            Log::error('Kitchen AJAX Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}