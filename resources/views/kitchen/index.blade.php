<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Screen - Campus Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .kitchen-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .kitchen-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: 5px solid var(--accent-color);
        }
        
        .kitchen-body {
            padding: 0;
            min-height: 70vh;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 6px solid var(--success-color);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .order-header {
            background: var(--light-bg);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .item-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .item-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .modifier-badge {
            background: var(--warning-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        
        .description-text {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
            background: white;
            padding: 0.5rem;
            border-radius: 5px;
            border-left: 3px solid var(--accent-color);
            margin-top: 0.5rem;
        }
        
        .quantity-badge {
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            margin-right: 0.8rem;
        }
        
        .controls-bar {
            background: var(--light-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
            border: none;
            border-radius: 25px;
            padding: 0.7rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }
        
        .time-filter {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .time-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .time-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .last-updated {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--primary-color);
            border: 2px solid var(--light-bg);
        }
        
        .no-orders {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .no-orders i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .customer-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-left: 0.5rem;
        }
        
        .order-meta {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .status-badge {
            background: var(--success-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: auto;
        }
        
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }
            
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .time-filter {
                justify-content: center;
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="kitchen-container">
        <div class="kitchen-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-utensils me-3"></i>Kitchen Display
                    </h1>
                    <p class="mb-0 opacity-75">Campus Cafe - Real-time Order Management</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <div id="currentTime" class="fw-bold fs-5"></div>
                        <div class="bg-white text-dark px-3 py-2 rounded-pill">
                            <i class="fas fa-store me-2"></i>Active
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="controls-bar">
            <div class="d-flex align-items-center gap-3">
                <button id="refreshBtn" class="refresh-btn">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Orders
                </button>
                <div class="time-filter">
                    <button class="time-btn active" data-hours="6">Last 6 Hours</button>
                    <button class="time-btn" data-hours="12">Last 12 Hours</button>
                    <button class="time-btn" data-hours="24">Last 24 Hours</button>
                </div>
            </div>
            <div id="lastUpdated" class="last-updated">
                <i class="fas fa-clock me-2"></i>Last updated: {{ now()->format('M j, Y g:i A') }}
            </div>
        </div>

        <div class="kitchen-body">
            @if(isset($error))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    <strong><i class="fas fa-exclamation-triangle me-2"></i>Error:</strong> {{ $error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div id="ordersContainer">
                @if(count($orders) > 0)
                    <div class="orders-grid">
                        @foreach($orders as $order)
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">
                                                <i class="fas fa-receipt me-2"></i>Order #{{ substr($order['order_id'], -8) }}
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>{{ $order['created_at_est'] }}
                                            </small>
                                        </div>
                                        <span class="status-badge">{{ $order['tender_type'] }}</span>
                                    </div>
                                    <div class="order-meta">
                                        <span>{{ $order['customer_name'] }}</span>
                                        <span class="ms-2 fw-bold">{{ $order['total_money_display'] }}</span>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    @foreach($order['items'] as $item)
                                        <div class="item-card">
                                            <div class="item-name">
                                                <span class="quantity-badge">{{ $item['quantity'] }}</span>
                                                {{ $item['name'] }}
                                            </div>
                                            
                                            @if($item['has_modifiers'])
                                                <div class="modifiers mt-2">
                                                    @foreach($item['modifiers'] as $modifier)
                                                        <span class="modifier-badge">
                                                            <i class="fas fa-plus me-1"></i>{{ $modifier['display_text'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            @if($item['has_note'])
                                                <div class="description-text">
                                                    <strong><i class="fas fa-sticky-note me-1"></i>Note:</strong> 
                                                    {{ $item['description'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="no-orders">
                        <i class="fas fa-clipboard-list"></i>
                        <h3 class="mt-3">No Recent Orders</h3>
                        <p class="text-muted">No completed orders found in the selected time period.</p>
                        <button id="refreshNowBtn" class="refresh-btn mt-3">
                            <i class="fas fa-sync-alt me-2"></i>Check for New Orders
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshBtn = document.getElementById('refreshBtn');
            const refreshNowBtn = document.getElementById('refreshNowBtn');
            const ordersContainer = document.getElementById('ordersContainer');
            const lastUpdated = document.getElementById('lastUpdated');
            const currentTime = document.getElementById('currentTime');
            const timeButtons = document.querySelectorAll('.time-btn');
            let currentHours = 6;
            let isRefreshing = false;

            // Update current time
            function updateTime() {
                const now = new Date();
                currentTime.textContent = now.toLocaleTimeString('en-US', {
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }
            
            updateTime();
            setInterval(updateTime, 1000);

            // Time filter buttons
            timeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    timeButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentHours = parseInt(this.dataset.hours);
                    refreshOrders();
                });
            });

            // Refresh orders function
            function refreshOrders() {
                if (isRefreshing) return;
                
                isRefreshing = true;
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';
                
                if (refreshNowBtn) {
                    refreshNowBtn.disabled = true;
                    refreshNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
                }

                fetch('{{ route("kitchen.refresh") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ hours: currentHours })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateOrdersDisplay(data.orders);
                        lastUpdated.innerHTML = `<i class="fas fa-clock me-2"></i>Last updated: ${data.last_updated}`;
                    } else {
                        showError('Error refreshing orders: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error refreshing orders. Please try again.');
                })
                .finally(() => {
                    isRefreshing = false;
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Refresh Orders';
                    
                    if (refreshNowBtn) {
                        refreshNowBtn.disabled = false;
                        refreshNowBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Check for New Orders';
                    }
                });
            }

            // Update orders display
            function updateOrdersDisplay(orders) {
                if (!orders || orders.length === 0) {
                    ordersContainer.innerHTML = `
                        <div class="no-orders">
                            <i class="fas fa-clipboard-list"></i>
                            <h3 class="mt-3">No Recent Orders</h3>
                            <p class="text-muted">No completed orders found in the selected time period.</p>
                            <button id="refreshNowBtn" class="refresh-btn mt-3" onclick="refreshOrders()">
                                <i class="fas fa-sync-alt me-2"></i>Check for New Orders
                            </button>
                        </div>
                    `;
                    return;
                }

                let ordersHtml = '<div class="orders-grid">';
                
                orders.forEach(order => {
                    let itemsHtml = '';
                    
                    order.items.forEach(item => {
                        let modifiersHtml = '';
                        if (item.has_modifiers && item.modifiers.length > 0) {
                            modifiersHtml = '<div class="modifiers mt-2">';
                            item.modifiers.forEach(modifier => {
                                modifiersHtml += `
                                    <span class="modifier-badge">
                                        <i class="fas fa-plus me-1"></i>${modifier.display_text}
                                    </span>
                                `;
                            });
                            modifiersHtml += '</div>';
                        }
                        
                        const noteHtml = item.has_note ? 
                            `<div class="description-text">
                                <strong><i class="fas fa-sticky-note me-1"></i>Note:</strong> 
                                ${item.description}
                            </div>` : '';
                        
                        itemsHtml += `
                            <div class="item-card">
                                <div class="item-name">
                                    <span class="quantity-badge">${item.quantity}</span>
                                    ${item.name}
                                </div>
                                ${modifiersHtml}
                                ${noteHtml}
                            </div>
                        `;
                    });
                    
                    ordersHtml += `
                        <div class="order-card">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="fas fa-receipt me-2"></i>Order #${order.order_id.substring(order.order_id.length - 8)}
                                        </h5>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>${order.created_at_est}
                                        </small>
                                    </div>
                                    <span class="status-badge">${order.tender_type}</span>
                                </div>
                                <div class="order-meta">
                                    <span>${order.customer_name}</span>
                                    <span class="ms-2 fw-bold">${order.total_money_display}</span>
                                </div>
                            </div>
                            <div class="order-body">
                                ${itemsHtml}
                            </div>
                        </div>
                    `;
                });
                
                ordersHtml += '</div>';
                ordersContainer.innerHTML = ordersHtml;
            }

            // Show error message
            function showError(message) {
                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Error:</strong> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                ordersContainer.innerHTML = alertHtml + ordersContainer.innerHTML;
            }

            // Event listeners
            refreshBtn.addEventListener('click', refreshOrders);
            if (refreshNowBtn) {
                refreshNowBtn.addEventListener('click', refreshOrders);
            }

            // Auto-refresh every 30 seconds
            setInterval(refreshOrders, 30000);
        });
    </script>
</body>
</html>
