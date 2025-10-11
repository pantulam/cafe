<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SquareService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    public function index(Request $request)
    {
        try {
            // Default to current month dates instead of future dates
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            Log::info('TransactionController - Date range:', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Get transactions (payments) from Square
            $transactionsData = $this->squareService->getTransactions($startDate, $endDate);
            $transactions = $transactionsData['payments'] ?? [];

            // Also get orders for additional context
            $ordersData = $this->squareService->getDetailedOrders($startDate, $endDate);
            $orders = $ordersData['orders'] ?? [];

            return view('transactions.index', compact('transactions', 'orders', 'startDate', 'endDate'));
        } catch (\Exception $e) {
            Log::error('Transaction Controller Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch transactions: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $transaction = $this->squareService->getTransaction($id);
            return view('transactions.show', compact('transaction'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
