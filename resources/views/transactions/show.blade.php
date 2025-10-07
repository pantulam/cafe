@extends('layouts.app')

@section('title', 'Transaction Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-4">
        <a href="{{ route('transactions.index') }}" class="text-blue-600 hover:text-blue-900">? Back to Transactions</a>
    </div>
    
    <h1 class="text-3xl font-bold mb-8">Transaction Details</h1>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if(isset($transaction) && !empty($transaction))
        @php
            $squareService = app(\App\Services\SquareService::class);
            $estTime = $squareService->formatESTTime($transaction['created_at'] ?? '');
            
            $amount = isset($transaction['amount_money']) ? $transaction['amount_money']['amount'] / 100 : 0;
            $cost = $transaction['total_cost'] ?? 0;
            $profit = $amount - $cost;
            $profitPercentage = $amount > 0 ? ($profit / $amount) * 100 : 0;
        @endphp
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Payment Information</h2>
                <p class="text-sm text-gray-500 mt-1">All times in EST</p>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Transaction ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['id'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @php
                                $status = $transaction['status'] ?? 'UNKNOWN';
                                $statusColor = $status === 'COMPLETED' ? 'green' : 
                                             ($status === 'APPROVED' ? 'blue' : 
                                             ($status === 'PENDING' ? 'yellow' : 'gray'));
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                {{ $status }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At (EST)</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $estTime }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if(isset($transaction['amount_money']))
                                ${{ number_format($amount, 2) }} 
                                {{ $transaction['amount_money']['currency'] ?? 'USD' }}
                            @else
                                $0.00
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Cost</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            ${{ number_format($cost, 2) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Profit</dt>
                        <dd class="mt-1 text-sm font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($profit, 2) }} 
                            @if($amount > 0)
                                ({{ number_format($profitPercentage, 1) }}%)
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Source Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['source_type'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Location ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['location_id'] ?? 'N/A' }}</dd>
                    </div>
                    @if(isset($transaction['customer_id']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['customer_id'] }}</dd>
                    </div>
                    @endif
                    @if(isset($transaction['order_id']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['order_id'] }}</dd>
                    </div>
                    @endif
                    @if(isset($transaction['reference_id']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Reference ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction['reference_id'] }}</dd>
                    </div>
                    @endif
                </dl>

                <!-- Line Items with Costs and Modifiers -->
                @if(isset($transaction['line_items_with_costs']) && count($transaction['line_items_with_costs']) > 0)
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-medium mb-4">Line Items & Costs</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Base Cost</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Modifiers</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($transaction['line_items_with_costs'] as $item)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        <div class="font-medium">{{ $item['name'] }}</div>
                                        @if($item['has_modifiers'])
                                            <div class="text-xs text-gray-500 mt-1">
                                                @foreach($item['modifiers'] as $modifier)
                                                    <div>+ {{ $modifier['name'] }} (${{ number_format($modifier['cost'], 2) }})</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">${{ number_format($item['base_unit_cost'], 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        @if($item['has_modifiers'])
                                            ${{ number_format($item['modifiers_cost'], 2) }}
                                        @else
                                            <span class="text-gray-400">None</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900">${{ number_format($item['total_cost'], 2) }}</td>
                                </tr>
                                @endforeach
                                <!-- Total Row -->
                                <tr class="bg-gray-100 font-semibold">
                                    <td class="px-4 py-2 text-sm text-gray-900" colspan="3">Total</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        ${{ number_format(array_sum(array_column($transaction['line_items_with_costs'], 'modifiers_cost')), 2) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        ${{ number_format(array_sum(array_column($transaction['line_items_with_costs'], 'total_cost')), 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Payment Details -->
                @if(isset($transaction['card_details']))
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-medium mb-4">Card Details</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Card Brand</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $transaction['card_details']['card']['card_brand'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last 4 Digits</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $transaction['card_details']['card']['last_4'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
                @endif

                <!-- Refunds -->
                @if(isset($transaction['refunds']) && count($transaction['refunds']) > 0)
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-medium mb-4">Refunds</h3>
                    <div class="space-y-4">
                        @foreach($transaction['refunds'] as $refund)
                        <div class="border rounded-lg p-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Refund ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $refund['id'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Refund Amount</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if(isset($refund['amount_money']))
                                            ${{ number_format($refund['amount_money']['amount'] / 100, 2) }}
                                        @else
                                            $0.00
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        @php
                                            $refundStatus = $refund['status'] ?? 'UNKNOWN';
                                            $refundStatusColor = $refundStatus === 'COMPLETED' ? 'green' : 'yellow';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            bg-{{ $refundStatusColor }}-100 text-{{ $refundStatusColor }}-800">
                                            {{ $refundStatus }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Reason</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $refund['reason'] ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <!-- Raw JSON (for debugging) -->
                <div class="mt-6 border-t pt-6">
                    <details>
                        <summary class="cursor-pointer text-sm font-medium text-gray-500">Raw Data</summary>
                        <pre class="mt-2 p-4 bg-gray-100 rounded text-xs overflow-auto max-h-96">{{ json_encode($transaction, JSON_PRETTY_PRINT) }}</pre>
                    </details>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-500">Transaction not found.</p>
            <p class="text-sm text-gray-400 mt-2">The transaction ID may be invalid or you may not have permission to view it.</p>
        </div>
    @endif
</div>
@endsection