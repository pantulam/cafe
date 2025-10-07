@extends('layouts.app')

@section('title', 'Square Transactions')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Square Transactions</h1>
    
    <!-- Date Filter Form -->
    <form method="GET" action="{{ route('transactions.index') }}" class="mb-6 bg-white p-6 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Start Date (EST)</label>
                <input type="date" name="start_date" value="{{ $startDate ?? request('start_date', \Carbon\Carbon::today('America/New_York')->format('Y-m-d')) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">End Date (EST)</label>
                <input type="date" name="end_date" value="{{ $endDate ?? request('end_date', \Carbon\Carbon::today('America/New_York')->format('Y-m-d')) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Filter Transactions
                </button>
            </div>
        </div>
        <div class="mt-2 text-sm text-gray-500">
            <p>All dates and times displayed in EST timezone</p>
        </div>
    </form>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Cache Status -->
    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-blue-800">Cache Status</h3>
                <p class="text-sm text-blue-600">
                    @php
                        $today = \Carbon\Carbon::today('America/New_York')->format('Y-m-d');
                        $isToday = ($startDate ?? '') === $today || ($endDate ?? '') === $today;
                    @endphp
                    @if($isToday)
                        <span class="font-medium">Live Data</span> - Today's transactions are always fetched fresh
                    @else
                        <span class="font-medium">Cached Data</span> - Historical data is cached for 24 hours
                    @endif
                </p>
            </div>
            <div class="space-x-2">
                <a href="{{ route('cache.pre-cache') }}" 
                   class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    Pre-cache Week
                </a>
                <a href="{{ route('cache.clear') }}" 
                   class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600"
                   onclick="return confirm('Clear all caches? This will force fresh API calls.')">
                    Clear Cache
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if(isset($transactions) && count($transactions) > 0)
            <div class="px-6 py-4 bg-green-50 border-b">
                <p class="text-green-800">
                    Found {{ count($transactions) }} transactions for 
                    {{ isset($startDate) ? \Carbon\Carbon::parse($startDate)->format('M j, Y') : '' }}
                    @if(isset($endDate) && $endDate !== $startDate)
                        to {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                    @endif
                    (EST timezone)
                </p>
            </div>
        @endif
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date (EST)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time (EST)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if(isset($transactions) && count($transactions) > 0)
                    @foreach($transactions as $payment)
                        @php
                            $squareService = app(\App\Services\SquareService::class);
                            $estTime = $squareService->formatESTTime($payment['created_at'] ?? '');
                            $estDate = $squareService->formatESTTime($payment['created_at'] ?? '', 'M j, Y');
                            $estTimeOnly = $squareService->formatESTTime($payment['created_at'] ?? '', 'g:i A');
                            
                            $amount = isset($payment['amount_money']) ? $payment['amount_money']['amount'] / 100 : 0;
                            $cost = $payment['total_cost'] ?? 0;
                            $profit = $amount - $cost;
                            $profitPercentage = $amount > 0 ? ($profit / $amount) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ substr($payment['id'] ?? '', 0, 8) }}...
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $estDate }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $estTimeOnly }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($cost, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <span class="{{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($profit, 2) }}
                                </span>
                                @if($amount > 0)
                                    <br>
                                    <span class="text-xs {{ $profitPercentage >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        ({{ number_format($profitPercentage, 1) }}%)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $status = $payment['status'] ?? 'UNKNOWN';
                                    $statusColor = $status === 'COMPLETED' ? 'green' : 
                                                 ($status === 'APPROVED' ? 'blue' : 
                                                 ($status === 'PENDING' ? 'yellow' : 'gray'));
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $payment['source_type'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('transactions.show', $payment['id'] ?? '') }}" 
                                   class="text-blue-600 hover:text-blue-900">View Details</a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                            <div class="space-y-2">
                                <p>No transactions found for the selected period.</p>
                                <p class="text-xs text-gray-400">
                                    @if(isset($startDate) && isset($endDate))
                                        Date range: {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} 
                                        @if($endDate !== $startDate)
                                            to {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                                        @endif
                                        (EST)
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Summary Section -->
    @if(isset($transactions) && count($transactions) > 0)
        @php
            $totalAmount = 0;
            $totalCost = 0;
            $totalProfit = 0;
            
            foreach ($transactions as $payment) {
                $amount = isset($payment['amount_money']) ? $payment['amount_money']['amount'] / 100 : 0;
                $cost = $payment['total_cost'] ?? 0;
                $totalAmount += $amount;
                $totalCost += $cost;
                $totalProfit += ($amount - $cost);
            }
            
            $totalProfitPercentage = $totalAmount > 0 ? ($totalProfit / $totalAmount) * 100 : 0;
        @endphp
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Revenue</h3>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Cost</h3>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($totalCost, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Profit</h3>
                <p class="text-2xl font-bold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ${{ number_format($totalProfit, 2) }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-700">Profit Margin</h3>
                <p class="text-2xl font-bold {{ $totalProfitPercentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($totalProfitPercentage, 1) }}%
                </p>
            </div>
        </div>
    @endif
</div>
@endsection
