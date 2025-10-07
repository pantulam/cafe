<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SquareService;
use Carbon\Carbon;

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
            // Get dates in EST timezone
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            // If no dates provided, default to today in EST
            if (!$startDate) {
                $startDate = Carbon::today('America/New_York')->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = Carbon::today('America/New_York')->format('Y-m-d');
            }
            
            // Validate that end date is not before start date
            if ($endDate < $startDate) {
                return back()->with('error', 'End date cannot be before start date.');
            }
            
            $transactions = $this->squareService->getTransactions($startDate, $endDate);
            
            return view('transactions.index', compact('transactions', 'startDate', 'endDate'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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
