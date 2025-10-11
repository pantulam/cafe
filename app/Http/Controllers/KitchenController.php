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
            // Get kitchen orders for TODAY only
            $orders = $this->squareService->getKitchenOrders();
            
            // If no orders found, try to get today's transactions as fallback
            $transactions = [];
            if (empty($orders)) {
                $transactionsData = $this->squareService->getTransactionsWithOrders(
                    now()->startOfDay()->format('Y-m-d'), 
                    now()->format('Y-m-d')
                );
                $transactions = $transactionsData;
                
                Log::info('Kitchen Controller - Using today\'s transactions as fallback:', [
                    'transactions_count' => count($transactions)
                ]);
            }

            Log::info('Kitchen Controller - Today\'s Results:', [
                'orders_count' => count($orders),
                'transactions_count' => count($transactions),
                'current_time' => now()->format('Y-m-d H:i:s'),
                'date_range' => [
                    'start' => now()->startOfDay()->format('Y-m-d H:i:s'),
                    'end' => now()->format('Y-m-d H:i:s')
                ]
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
            $orders = $this->squareService->getKitchenOrders();
            
            // Fallback to today's transactions if no orders
            $transactions = [];
            if (empty($orders)) {
                $transactions = $this->squareService->getTransactionsWithOrders(
                    now()->startOfDay()->format('Y-m-d'), 
                    now()->format('Y-m-d')
                );
            }
            
            return response()->json([
                'success' => true,
                'orders' => $orders,
                'transactions' => $transactions,
                'last_updated' => now()->setTimezone('America/New_York')->format('M j, Y g:i A') . ' EST',
                'count' => count($orders) + count($transactions),
                'date_range' => 'Today (' . now()->setTimezone('America/New_York')->format('M j, Y') . ')'
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