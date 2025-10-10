<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SquareService;

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
            
            return view('kitchen.index', compact('orders'));
            
        } catch (\Exception $e) {
            return view('kitchen.index', [
                'orders' => [], 
                'error' => $e->getMessage()
            ]);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $hoursBack = $request->get('hours', 24);
            $orders = $this->squareService->getKitchenOrders($hoursBack);
            
            return response()->json([
                'success' => true,
                'orders' => $orders,
                'last_updated' => now()->format('M j, Y g:i A'),
                'orders_count' => count($orders)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clearCache()
    {
        try {
            $this->squareService->clearKitchenCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Kitchen cache cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
