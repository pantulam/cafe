@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-utensils me-2"></i>Kitchen Display
                            </h3>
                            <p class="mb-0 opacity-75">Campus Cafe - Real-time Order Management</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <div id="currentTime" class="fw-bold fs-5 text-white"></div>
                                <div class="bg-white text-dark px-3 py-2 rounded-pill">
                                    <i class="fas fa-store me-2"></i>Active
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Controls Bar -->
                    <div class="controls-bar bg-light p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3">
                                    <button id="refreshBtn" class="btn btn-success">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh Orders
                                    </button>
                                    <div class="time-filter d-flex gap-2">
                                        <button class="btn btn-outline-primary time-btn active" data-hours="6">Last 6 Hours</button>
                                        <button class="btn btn-outline-primary time-btn" data-hours="12">Last 12 Hours</button>
                                        <button class="btn btn-outline-primary time-btn" data-hours="24">Last 24 Hours</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div id="lastUpdated" class="last-updated bg-white px-3 py-2 rounded">
                                    <i class="fas fa-clock me-2"></i>Last updated: {{ now()->format('M j, Y g:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Container -->
                    <div id="ordersContainer" class="p-4">
                        @if(isset($error))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong><i class="fas fa-exclamation-triangle me-2"></i>Error:</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(count($orders) > 0)
                            <div class="orders-grid">
                                @foreach($orders as $order)
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h5 class="mb-1">
                                                        <i class="fas fa-receipt me-2"></i>Order #{{ substr($order['order_id'], -8) }}
                                                    </h5>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>{{ $order['created_at_est'] }}
                                                    </small>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <span class="badge bg-success fs-6">{{ $order['total_money_display'] }}</span>
                                                </div>
                                            </div>
                                            <div class="order-meta mt-2">
                                                <span class="customer-badge">{{ $order['customer_name'] }}</span>
                                                <span class="status-badge ms-2">{{ $order['tender_type'] }}</span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-body">
                                            @foreach($order['items'] as $item)
                                                <div class="item-card">
                                                    <div class="row align-items-start">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-1">
                                                                <span class="quantity-badge">{{ $item['quantity'] }}</span>
                                                                {{ $item['name'] }}
                                                                @if($item['variation_name'])
                                                                    <small class="text-muted">({{ $item['variation_name'] }})</small>
                                                                @endif
                                                            </h6>
                                                            
                                                            <!-- Square Item Description -->
                                                            @if($item['has_item_description'])
                                                                <div class="square-description mb-2">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-info-circle me-1"></i>
                                                                        {{ $item['item_description'] }}
                                                                    </small>
                                                                </div>
                                                            @endif
                                                            
                                                            @if($item['has_modifiers'])
                                                                <div class="modifiers mt-1">
                                                                    @foreach($item['modifiers'] as $modifier)
                                                                        <span class="modifier-badge">
                                                                            <i class="fas fa-plus me-1"></i>{{ $modifier['display_text'] }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            
                                                            @if($item['has_note'])
                                                                <div class="description-text mt-2">
                                                                    <strong><i class="fas fa-sticky-note me-1"></i>Note:</strong> 
                                                                    {{ $item['note_description'] }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <span class="text-success fw-bold">
                                                                @if(isset($item['base_price_display']))
                                                                    {{ $item['base_price_display'] }}
                                                                @else
                                                                    $0.00
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="no-orders text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                                <h4>No Recent Orders</h4>
                                <p class="text-muted">No completed orders found in the selected time period.</p>
                                <button id="refreshNowBtn" class="btn btn-success mt-3">
                                    <i class="fas fa-sync-alt me-2"></i>Check for New Orders
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px;
    }
    
    .order-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-left: 6px solid #28a745;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .order-header {
        background: #f8f9fa;
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
        border-left: 4px solid #007bff;
    }
    
    .quantity-badge {
        background: #dc3545;
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
    
    .modifier-badge {
        background: #6c757d;
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
        border-left: 3px solid #dc3545;
        margin-top: 0.5rem;
    }
    
    .square-description {
        color: #4b5563;
        font-size: 1.4rem;
        line-height: 1.4;
        background: #f0f9ff;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        border-left: 3px solid #3b82f6;
        margin-bottom: 0.5rem;
        border: 1px solid #e0f2fe;
    }
    
    .square-description small {
        display: block;
        max-width: 100%;
        word-wrap: break-word;
    }
    
    .square-description i.fa-info-circle {
        color: #3b82f6;
    }
    
    .controls-bar {
        background: #f8f9fa !important;
    }
    
    .last-updated {
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #495057;
        border: 2px solid #e9ecef;
    }
    
    .no-orders {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .customer-badge {
        background: #495057;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .status-badge {
        background: #28a745;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.8rem;
    }
    
    .time-btn.active {
        background-color: #007bff;
        color: white;
    }
    
    @media (max-width: 768px) {
        .orders-grid {
            grid-template-columns: 1fr;
        }
        
        .controls-bar .row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .time-filter {
            justify-content: center;
        }
        
        .order-header .row,
        .item-card .row {
            flex-direction: column;
        }
        
        .item-card .col-md-4.text-end {
            text-align: left !important;
            margin-top: 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
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
                    <div class="no-orders text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                        <h4>No Recent Orders</h4>
                        <p class="text-muted">No completed orders found in the selected time period.</p>
                        <button id="refreshNowBtn" class="btn btn-success mt-3" onclick="refreshOrders()">
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
                        modifiersHtml = '<div class="modifiers mt-1">';
                        item.modifiers.forEach(modifier => {
                            modifiersHtml += `
                                <span class="modifier-badge">
                                    <i class="fas fa-plus me-1"></i>${modifier.display_text}
                                </span>
                            `;
                        });
                        modifiersHtml += '</div>';
                    }
                    
                    const squareDescriptionHtml = item.has_item_description ? 
                        `<div class="square-description mb-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                ${item.item_description}
                            </small>
                        </div>` : '';
                    
                    const noteHtml = item.has_note ? 
                        `<div class="description-text mt-2">
                            <strong><i class="fas fa-sticky-note me-1"></i>Note:</strong> 
                            ${item.note_description}
                        </div>` : '';
                    
                    const priceHtml = item.base_price_display ? 
                        item.base_price_display : '$0.00';
                    
                    itemsHtml += `
                        <div class="item-card">
                            <div class="row align-items-start">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <span class="quantity-badge">${item.quantity}</span>
                                        ${item.name}
                                        ${item.variation_name ? `<small class="text-muted">(${item.variation_name})</small>` : ''}
                                    </h6>
                                    ${squareDescriptionHtml}
                                    ${modifiersHtml}
                                    ${noteHtml}
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-success fw-bold">${priceHtml}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                ordersHtml += `
                    <div class="order-card">
                        <div class="order-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <i class="fas fa-receipt me-2"></i>Order #${order.order_id.substring(order.order_id.length - 8)}
                                    </h5>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>${order.created_at_est}
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-success fs-6">${order.total_money_display}</span>
                                </div>
                            </div>
                            <div class="order-meta mt-2">
                                <span class="customer-badge">${order.customer_name}</span>
                                <span class="status-badge ms-2">${order.tender_type}</span>
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
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
        
        // Make refreshOrders available globally for onclick handlers
        window.refreshOrders = refreshOrders;
    });
</script>
@endpush
