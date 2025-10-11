@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Transactions</h1>

    <!-- Date Filter Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex gap-4 items-end">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" 
                       value="{{ $startDate }}" class="border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" 
                       value="{{ $endDate }}" class="border border-gray-300 rounded-md px-3 py-2">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Filter
            </button>
            <a href="{{ route('transactions.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                Reset
            </a>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">Payments ({{ count($transactions) }})</h2>
            <p class="text-sm text-gray-600">Showing payments from {{ $startDate }} to {{ $endDate }}</p>
        </div>

        @if(count($transactions) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Source Type
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ substr($transaction['id'] ?? '', -8) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format(($transaction['amount_money']['amount'] ?? 0) / 100, 2) }}
                                    {{ $transaction['amount_money']['currency'] ?? 'USD' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $transaction['status'] === 'COMPLETED' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $transaction['status'] ?? 'UNKNOWN' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($transaction['created_at'] ?? '')->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $transaction['source_type'] ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-8 text-center">
                <p class="text-gray-500 text-lg">

@if(count($transactions) > 0)
    <!-- Your existing transactions table -->
@else
    <div class="px-6 py-8 text-center">
        <p class="text-gray-500 text-lg">No transactions found for the selected date range.</p>
        <p class="text-gray-400 text-sm mt-2">Try selecting different dates or check if you have recent payments in your Square account.</p>
        
        <!-- Suggested date ranges -->
        <div class="mt-4 space-y-2">
            <p class="text-sm text-gray-600">Try these date ranges:</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('transactions.index', ['start_date' => \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" 
                   class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200">
                    This Month
                </a>
                <a href="{{ route('transactions.index', ['start_date' => \Carbon\Carbon::now()->subDays(7)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" 
                   class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm hover:bg-green-200">
                    Last 7 Days
                </a>
                <a href="{{ route('transactions.index', ['start_date' => \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" 
                   class="bg-purple-100 text-purple-700 px-3 py-1 rounded text-sm hover:bg-purple-200">
                    Last 30 Days
                </a>
            </div>
        </div>
    </div>
@endif



</p>
                <p class="text-gray-400 text-sm mt-2">Try selecting different dates or check if you have recent payments in your Square account.</p>
            </div>
        @endif
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">Orders ({{ count($orders) }})</h2>
        </div>

        @if(count($orders) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                State
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ substr($order['id'] ?? '', -8) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $order['state'] === 'COMPLETED' ? 'bg-green-100 text-green-800' : 
                                           ($order['state'] === 'OPEN' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $order['state'] ?? 'UNKNOWN' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format(($order['total_money']['amount'] ?? 0) / 100, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($order['created_at'] ?? '')->format('M j, Y g:i A') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-8 text-center">
                <p class="text-gray-500">No orders found for the selected date range.</p>
            </div>
        @endif
    </div>
</div>
@endsection