<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display - Campus Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="30">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Kitchen & Order Display</h1>
            <p class="text-xl text-gray-600">Campus Cafe - Real-time Management</p>
            <div class="mt-4 flex justify-center items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-2"></div>
                    <span class="text-green-600 font-semibold">Live</span>
                </div>
                <div class="text-gray-500">
                    Last updated: {{ now()->setTimezone('America/New_York')->format('M j, Y g:i A') }} EST
                </div>
            </div>
        </div>

        <!-- Debug Links -->
        <div class="flex justify-center space-x-4 mb-6">
            <a href="/debug-kitchen-orders" class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600" target="_blank">
                Debug Orders
            </a>
            <a href="/debug-square" class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600" target="_blank">
                Debug Square
            </a>
            <a href="/transactions" class="bg-purple-500 text-white px-4 py-2 rounded text-sm hover:bg-purple-600">
                View Transactions
            </a>
        </div>

        @if(count($orders) > 0)
            <!-- Statistics -->
            @php
                $activeOrders = collect($orders)->whereIn('state', ['OPEN', 'IN_PROGRESS']);
                $completedOrders = collect($orders)->where('state', 'COMPLETED');
                $canceledOrders = collect($orders)->where('state', 'CANCELED');
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ count($orders) }}</div>
                    <div class="text-sm text-gray-600">Total Orders (24h)</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $activeOrders->count() }}</div>
                    <div class="text-sm text-gray-600">Active Orders</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $completedOrders->count() }}</div>
                    <div class="text-sm text-gray-600">Completed</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $canceledOrders->count() }}</div>
                    <div class="text-sm text-gray-600">Canceled</div>
                </div>
            </div>

            <!-- Active Orders Section -->
            @if($activeOrders->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Active Orders</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($activeOrders as $order)
                            <div class="bg-white rounded-lg shadow-lg border-l-4 
                                {{ $order['state'] === 'OPEN' ? 'border-blue-500' : 'border-yellow-500' }}">
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-lg">Order #{{ substr($order['id'], -8) }}</h3>
                                            <p class="text-sm text-gray-600">{{ $order['customer_name'] }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded
                                            {{ $order['state'] === 'OPEN' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $order['state'] }}
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($order['created_at'])->setTimezone('America/New_York')->format('M j, g:i A') }} EST
                                        </p>
                                        @if($order['note'])
                                            <p class="text-sm text-red-600 mt-1">Note: {{ $order['note'] }}</p>
                                        @endif
                                    </div>

                                    <div class="space-y-2 mb-4">
                                        @foreach($order['line_items'] as $item)
                                            <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                                <div class="flex-1">
                                                    <div class="flex items-start">
                                                        <span class="font-medium bg-gray-100 px-2 py-1 rounded text-sm mr-2">
                                                            {{ $item['quantity'] }}x
                                                        </span>
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-800">{{ $item['name'] }}</div>
                                                            @if($item['description'] ?? false)
                                                                <div class="text-xs text-gray-600 mt-1">{{ $item['description'] }}</div>
                                                            @endif
                                                            @if($item['variation_name'])
                                                                <div class="text-sm text-gray-600">({{ $item['variation_name'] }})</div>
                                                            @endif
                                                            @if($item['note'])
                                                                <div class="text-sm text-red-600 mt-1">Note: {{ $item['note'] }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="pt-3 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="font-semibold">Total: ${{ number_format(($order['total_money']['amount'] ?? 0) / 100, 2) }}</span>
                                            @if($order['state'] === 'OPEN')
                                                <form action="{{ route('kitchen.orders.update', $order['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="state" value="IN_PROGRESS">
                                                    <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                                                        Start Cooking
                                                    </button>
                                                </form>
                                            @elseif($order['state'] === 'IN_PROGRESS')
                                                <form action="{{ route('kitchen.orders.update', $order['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="state" value="COMPLETED">
                                                    <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                                        Complete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recent Completed Orders Section -->
            @if($completedOrders->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Recently Completed</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($completedOrders->take(12) as $order)
                            <div class="bg-white rounded-lg shadow border-l-4 border-green-500 opacity-80">
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-lg">Order #{{ substr($order['id'], -8) }}</h3>
                                            <p class="text-sm text-gray-600">{{ $order['customer_name'] }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">
                                            COMPLETED
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-500">
                                            Completed: {{ \Carbon\Carbon::parse($order['completed_at'] ?? $order['created_at'])->setTimezone('America/New_York')->format('M j, g:i A') }} EST
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($order['line_items'] as $item)
                                            <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                                <div class="flex-1">
                                                    <div class="flex items-start">
                                                        <span class="font-medium bg-gray-100 px-2 py-1 rounded text-sm mr-2">
                                                            {{ $item['quantity'] }}x
                                                        </span>
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-800">{{ $item['name'] }}</div>
                                                            @if($item['description'] ?? false)
                                                                <div class="text-xs text-gray-600 mt-1">{{ $item['description'] }}</div>
                                                            @endif
                                                            @if($item['variation_name'])
                                                                <div class="text-sm text-gray-600">({{ $item['variation_name'] }})</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <span class="font-semibold">Total: ${{ number_format(($order['total_money']['amount'] ?? 0) / 100, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- No Active Orders Message -->
            @if($activeOrders->count() === 0 && $completedOrders->count() > 0)
                <div class="text-center py-8 bg-yellow-50 rounded-lg">
                    <h3 class="text-xl font-semibold text-yellow-800 mb-2">No Active Orders</h3>
                    <p class="text-yellow-600">All orders from the last 24 hours have been completed.</p>
                </div>
            @endif

        @elseif(count($transactions) > 0)
            <!-- Transactions Display (Fallback) -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Latest Orders & Payments</h2>
                <p class="text-gray-600 mb-4">Showing recent transactions from Square</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach(array_slice($transactions, 0, 12) as $transactionData)
                        @php
                            $transaction = $transactionData['payment'];
                            $order = $transactionData['order'] ?? null;
                            $lineItems = $transactionData['line_items'] ?? [];
                            $hasOrderDetails = !empty($lineItems);
                        @endphp
                        
                        <div class="bg-white rounded-lg shadow-lg border-l-4 
                            {{ $transaction['status'] === 'COMPLETED' ? 'border-green-500' : 'border-yellow-500' }}">
                            <div class="p-4">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-lg">
                                            @if($hasOrderDetails)
                                                Order #{{ substr($order['order']['id'] ?? $transaction['id'], -8) }}
                                            @else
                                                Payment #{{ substr($transaction['id'] ?? 'unknown', -8) }}
                                            @endif
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            {{ $transaction['source_type'] ?? 'Unknown Source' }}
                                            @if($hasOrderDetails)
                                                • {{ $order['order']['state'] ?? '' }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-semibold rounded
                                        {{ $transaction['status'] === 'COMPLETED' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $transaction['status'] ?? 'UNKNOWN' }}
                                    </span>
                                </div>
                                
                                <!-- Time and Customer -->
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaction['created_at'] ?? '')->setTimezone('America/New_York')->format('M j, g:i A') }} EST
                                    </p>
                                    @if($transaction['customer_id'] ?? false)
                                        <p class="text-sm text-blue-600 mt-1">
                                            Customer: {{ substr($transaction['customer_id'], -8) }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Order Items -->
                                @if($hasOrderDetails && count($lineItems) > 0)
                                    <div class="space-y-2 mb-4">
                                        <h4 class="font-medium text-gray-700 border-b pb-1">Items:</h4>
                                        @foreach($lineItems as $item)
                                            <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                                <div class="flex-1">
                                                    <div class="flex items-start">
                                                        <span class="font-medium bg-gray-100 px-2 py-1 rounded text-sm mr-2">
                                                            {{ $item['quantity'] ?? 1 }}x
                                                        </span>
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-800">{{ $item['name'] ?? 'Unknown Item' }}</div>
                                                            @if($item['description'] ?? false)
                                                                <div class="text-xs text-gray-600 mt-1">{{ $item['description'] }}</div>
                                                            @endif
                                                            @if($item['variation_name'] ?? false)
                                                                <div class="text-sm text-gray-600">({{ $item['variation_name'] }})</div>
                                                            @endif
                                                            @if($item['note'] ?? false)
                                                                <div class="text-sm text-red-600 mt-1">Note: {{ $item['note'] }}</div>
                                                            @endif
                                                            @if(($item['total_money']['amount'] ?? 0) > 0)
                                                                <div class="text-sm text-green-600">
                                                                    ${{ number_format(($item['total_money']['amount'] ?? 0) / 100, 2) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- No order details available -->
                                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                                        <p class="text-yellow-800 text-sm">
                                            <span class="font-medium">No order details available</span><br>
                                            <span class="text-xs">Payment only - no item information</span>
                                        </p>
                                    </div>
                                @endif

                                <!-- Payment Summary -->
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                        <span class="font-medium">Amount:</span>
                                        <span class="font-semibold">
                                            ${{ number_format(($transaction['amount_money']['amount'] ?? 0) / 100, 2) }}
                                        </span>
                                    </div>
                                    @if($transaction['tip_money']['amount'] ?? 0 > 0)
                                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                            <span class="font-medium text-sm text-green-600">Tip:</span>
                                            <span class="text-sm text-green-600 font-semibold">
                                                +${{ number_format(($transaction['tip_money']['amount'] ?? 0) / 100, 2) }}
                                            </span>
                                        </div>
                                    @endif
                                    @if($transaction['processing_fee']['amount'] ?? 0 > 0)
                                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                            <span class="font-medium text-sm text-red-600">Fee:</span>
                                            <span class="text-sm text-red-600 font-semibold">
                                                -${{ number_format(($transaction['processing_fee']['amount'] ?? 0) / 100, 2) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Net Amount -->
                                <div class="pt-3 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-700">Net Amount:</span>
                                        <span class="font-bold text-lg text-blue-600">
                                            @php
                                                $amount = ($transaction['amount_money']['amount'] ?? 0) / 100;
                                                $tip = ($transaction['tip_money']['amount'] ?? 0) / 100;
                                                $fee = ($transaction['processing_fee']['amount'] ?? 0) / 100;
                                                $net = $amount + $tip - $fee;
                                            @endphp
                                            ${{ number_format($net, 2) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Order Note if available -->
                                @if($order && ($order['order']['note'] ?? false))
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <p class="text-sm text-red-600">
                                            <span class="font-medium">Order Note:</span> {{ $order['order']['note'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Statistics for Transactions -->
            @php
                $totalAmount = collect($transactions)->sum(function($t) { 
                    return ($t['payment']['amount_money']['amount'] ?? 0) / 100; 
                });
                $totalTips = collect($transactions)->sum(function($t) { 
                    return ($t['payment']['tip_money']['amount'] ?? 0) / 100; 
                });
                $completedCount = collect($transactions)->where('payment.status', 'COMPLETED')->count();
                $ordersWithDetails = collect($transactions)->filter(function($t) {
                    return !empty($t['line_items']);
                })->count();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ count($transactions) }}</div>
                    <div class="text-sm text-gray-600">Total Transactions</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">${{ number_format($totalAmount, 2) }}</div>
                    <div class="text-sm text-gray-600">Total Sales</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $ordersWithDetails }}</div>
                    <div class="text-sm text-gray-600">Orders with Details</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">${{ number_format($totalTips, 2) }}</div>
                    <div class="text-sm text-gray-600">Total Tips</div>
                </div>
            </div>

        @else
            <!-- No Data Message -->
            <div class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No Data Found</h3>
                <p class="text-gray-500 mb-4">No orders or transactions found in the last 24 hours.</p>
                <p class="text-sm text-gray-400 mb-6">
                    This could mean there are no recent orders, or there might be an issue with the Square connection.
                </p>
                
                <div class="space-x-4">
                    <a href="{{ route('kitchen.index') }}" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                        Refresh Page
                    </a>
                    <a href="/debug-kitchen-orders" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600" target="_blank">
                        Debug Data
                    </a>
                </div>
            </div>
        @endif
    </div>

    <footer class="mt-12 text-center text-gray-500">
        <p>&copy; {{ now()->setTimezone('America/New_York')->format('Y') }} Campus Cafe Kitchen Display</p>
        <p class="text-sm">Auto-refreshes every 30 seconds • 
            @if(count($orders) > 0)
                Showing {{ count($orders) }} orders
            @elseif(count($transactions) > 0)
                Showing {{ count($transactions) }} transactions
            @else
                No data available
            @endif
        </p>
    </footer>
</body>
</html>