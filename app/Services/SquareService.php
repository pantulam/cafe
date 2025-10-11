<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProductCost;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class SquareService
{
    protected $accessToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.square.access_token');
        
        // Set base URL based on environment
        $environment = config('services.square.environment', 'production');
        $this->baseUrl = $environment === 'sandbox' 
            ? 'https://connect.squareupsandbox.com' 
            : 'https://connect.squareup.com';
        
        // Log configuration for debugging
        Log::info('Square Service Configuration:', [
            'environment' => $environment,
            'base_url' => $this->baseUrl,
            'has_access_token' => !empty($this->accessToken)
        ]);
    }

    public function getCatalog()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/catalog/list');

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Square API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching catalog: ' . $e->getMessage());
            return null;
        }
    }

    public function getOrders($startDate = null, $endDate = null)
    {
        try {
            $query = [];
            if ($startDate) {
                $query['start_date'] = $startDate;
            }
            if ($endDate) {
                $query['end_date'] = $endDate;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/orders', $query);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Square Orders API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching orders: ' . $e->getMessage());
            return null;
        }
    }

    public function getKitchenOrders($hours = 24)
    {
        try {
            // Ensure we're using current dates, not future dates
            $startDate = now()->subHours($hours);
            $endDate = now();

            Log::info('=== KITCHEN ORDERS DEBUG ===', [
                'current_time' => now()->format('Y-m-d H:i:s'),
                'current_year' => now()->year,
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
                'hours_parameter' => $hours
            ]);

            $query = [
                'start_date' => $startDate->toISOString(),
                'end_date' => $endDate->toISOString(),
            ];

            Log::info('Square API Request - Kitchen Orders:', [
                'query' => $query,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/orders', $query);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Square API Response - Kitchen Orders:', [
                    'http_status' => $response->status(),
                    'total_orders_in_response' => count($data['orders'] ?? [])
                ]);

                // Filter and format orders for kitchen display
                $kitchenOrders = [];
                if (isset($data['orders']) && is_array($data['orders'])) {
                    foreach ($data['orders'] as $order) {
                        // Include ALL order states: OPEN, IN_PROGRESS, COMPLETED
                        $lineItems = [];
                        if (isset($order['line_items']) && is_array($order['line_items'])) {
                            foreach ($order['line_items'] as $item) {
                                $lineItems[] = [
                                    'name' => $item['name'] ?? 'Unknown Item',
                                    'quantity' => $item['quantity'] ?? 1,
                                    'note' => $item['note'] ?? '',
                                    'variation_name' => $item['variation_name'] ?? '',
                                    'total_money' => $item['total_money'] ?? ['amount' => 0, 'currency' => 'USD'],
                                    'description' => $this->getItemDescription($item)
                                ];
                            }
                        }

                        $kitchenOrders[] = [
                            'id' => $order['id'] ?? 'unknown',
                            'created_at' => $order['created_at'] ?? '',
                            'line_items' => $lineItems,
                            'state' => $order['state'] ?? 'UNKNOWN',
                            'customer_name' => $this->getCustomerNameFromOrder($order),
                            'note' => $order['note'] ?? '',
                            'total_money' => $order['total_money'] ?? ['amount' => 0, 'currency' => 'USD'],
                            'source' => $order['source'] ?? ['name' => 'Unknown'],
                            'completed_at' => $order['closed_at'] ?? null
                        ];
                    }
                }

                // Sort orders: active first, then completed by most recent
                usort($kitchenOrders, function($a, $b) {
                    $stateOrder = ['OPEN' => 1, 'IN_PROGRESS' => 2, 'COMPLETED' => 3, 'CANCELED' => 4];
                    $aState = $stateOrder[$a['state']] ?? 5;
                    $bState = $stateOrder[$b['state']] ?? 5;
                    
                    if ($aState !== $bState) {
                        return $aState - $bState;
                    }
                    
                    // If same state, sort by creation date (newest first)
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                Log::info('Kitchen Orders Final Results:', [
                    'total_orders' => count($kitchenOrders),
                    'by_state' => array_count_values(array_column($kitchenOrders, 'state'))
                ]);
                
                return $kitchenOrders;
            } else {
                Log::error('Square Kitchen Orders API Error:', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_query' => $query
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching kitchen orders: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get item description from catalog data
     */
    private function getItemDescription($item)
    {
        try {
            // If the item has a catalog object ID, we can fetch its details
            $catalogObjectId = $item['catalog_object_id'] ?? null;
            
            if ($catalogObjectId) {
                // You might want to cache catalog data to avoid multiple API calls
                $catalogData = $this->getCatalogObject($catalogObjectId);
                
                if ($catalogData && isset($catalogData['object'])) {
                    $object = $catalogData['object'];
                    
                    // Get description based on object type
                    if ($object['type'] === 'ITEM') {
                        $description = $object['item_data']['description'] ?? '';
                    } elseif ($object['type'] === 'ITEM_VARIATION') {
                        $description = $object['item_variation_data']['item_id'] ? 
                            $this->getItemDescriptionFromItem($object['item_variation_data']['item_id']) : '';
                    }
                    
                    // Limit to 100 characters
                    if (!empty($description)) {
                        return strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
                    }
                }
            }
            
            // Fallback: try to get description from item metadata
            return $this->limitDescription($item['metadata']['description'] ?? '');
            
        } catch (\Exception $e) {
            Log::error('Error getting item description: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get description from parent item
     */
    private function getItemDescriptionFromItem($itemId)
    {
        try {
            $catalogData = $this->getCatalogObject($itemId);
            
            if ($catalogData && isset($catalogData['object']) && $catalogData['object']['type'] === 'ITEM') {
                return $catalogData['object']['item_data']['description'] ?? '';
            }
        } catch (\Exception $e) {
            Log::error('Error getting item description from parent: ' . $e->getMessage());
        }
        
        return '';
    }

    /**
     * Get specific catalog object
     */
    private function getCatalogObject($objectId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/catalog/object/' . $objectId);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching catalog object: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Limit description to 100 characters
     */
    private function limitDescription($description)
    {
        if (empty($description)) {
            return '';
        }
        
        return strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
    }

    private function getCustomerNameFromOrder($order)
    {
        if (isset($order['customer_id'])) {
            return $this->getCustomerName($order['customer_id']) ?? 'Walk-in';
        }
        
        return $order['fulfillments'][0]['pickup_details']['recipient']['display_name'] ?? 
               $order['fulfillments'][0]['delivery_details']['recipient']['display_name'] ?? 
               'Walk-in';
    }

    public function getProductCosts($perPage = 10, $page = null)
    {
        $catalog = $this->getCatalog();
        $productCosts = [];

        if ($catalog && isset($catalog['objects'])) {
            foreach ($catalog['objects'] as $object) {
                if ($object['type'] === 'ITEM') {
                    $productId = $object['id'];
                    $productName = $object['item_data']['name'] ?? 'Unknown Product';
                    $description = $this->limitDescription($object['item_data']['description'] ?? '');
                    $cost = 0;

                    if (isset($object['item_data']['variations'])) {
                        foreach ($object['item_data']['variations'] as $variation) {
                            if (isset($variation['item_variation_data']['price_money']['amount'])) {
                                $cost = $variation['item_variation_data']['price_money']['amount'] / 100;
                                break;
                            }
                        }
                    }

                    $productCosts[] = new ProductCost($productId, $cost, $productName, $description);
                }
            }
        }

        // Manual pagination
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = collect($productCosts);
        $paginatedItems = $items->slice(($page - 1) * $perPage, $perPage)->all();
        
        return new LengthAwarePaginator(
            $paginatedItems, 
            $items->count(), 
            $perPage, 
            $page, 
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    public function getLocation()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/locations');

            if ($response->successful()) {
                $data = $response->json();
                return $data['locations'][0] ?? null;
            } else {
                Log::error('Square Locations API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching location: ' . $e->getMessage());
            return null;
        }
    }

    public function getTransactions($startDate = null, $endDate = null, $locationId = null)
    {
        try {
            if (!$locationId) {
                $location = $this->getLocation();
                $locationId = $location['id'] ?? null;
                
                // Debug location
                Log::info('Square Location:', ['location' => $location]);
            }

            if (!$locationId) {
                throw new \Exception('No location ID available');
            }

            $query = [
                'location_id' => $locationId,
            ];

            // Convert dates to proper ISO format with time
            if ($startDate) {
                $startDate = Carbon::parse($startDate)->startOfDay()->toISOString();
                $query['begin_time'] = $startDate;
            }

            if ($endDate) {
                $endDate = Carbon::parse($endDate)->endOfDay()->toISOString();
                $query['end_time'] = $endDate;
            }

            // Debug the API request
            Log::info('Square Transactions API Request Details:', [
                'location_id' => $locationId,
                'begin_time' => $query['begin_time'] ?? 'not set',
                'end_time' => $query['end_time'] ?? 'not set',
                'full_query' => $query
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/payments', $query);

            // Debug the response
            Log::info('Square API Response Status:', ['status' => $response->status()]);
            Log::info('Square API Response Headers:', ['headers' => $response->headers()]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Square Transactions API Response Data:', [
                    'payments_count' => count($data['payments'] ?? []),
                    'has_more' => $data['has_more'] ?? false,
                    'cursor' => $data['cursor'] ?? 'none'
                ]);
                
                // Log first few payments for debugging
                if (isset($data['payments']) && count($data['payments']) > 0) {
                    Log::info('Sample Payments:', array_slice($data['payments'], 0, 3));
                }
                
                return $data;
            } else {
                Log::error('Square Payments API Error:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
                return ['payments' => []];
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching transactions:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['payments' => []];
        }
    }

    public function updateOrderState($orderId, $state)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl . '/v2/orders/' . $orderId, [
                'order' => [
                    'state' => $state
                ]
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Square Update Order API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception updating order state: ' . $e->getMessage());
            return null;
        }
    }

    public function getDetailedOrders($startDate = null, $endDate = null)
    {
        try {
            $query = [];
            
            if ($startDate) {
                $startDate = Carbon::parse($startDate)->startOfDay()->toISOString();
                $query['start_date'] = $startDate;
            }

            if ($endDate) {
                $endDate = Carbon::parse($endDate)->endOfDay()->toISOString();
                $query['end_date'] = $endDate;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/orders', $query);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Square Detailed Orders API Error: ' . $response->body());
                return ['orders' => []];
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching detailed orders: ' . $e->getMessage());
            return ['orders' => []];
        }
    }

    public function getCustomerName($customerId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/customers/' . $customerId);

            if ($response->successful()) {
                $data = $response->json();
                $customer = $data['customer'] ?? [];
                return $customer['given_name'] ?? $customer['company_name'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching customer: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get order details for a specific payment
     */
    public function getOrderForPayment($paymentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/payments/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();
                $payment = $data['payment'] ?? [];
                
                // Get order ID from payment if available
                $orderId = $payment['order_id'] ?? null;
                
                if ($orderId) {
                    return $this->getOrderDetails($orderId);
                }
                
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error getting payment details: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get detailed order information
     */
    public function getOrderDetails($orderId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/orders/' . $orderId);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error getting order details: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get transactions with order details
     */
    public function getTransactionsWithOrders($startDate = null, $endDate = null, $locationId = null)
    {
        $transactionsData = $this->getTransactions($startDate, $endDate, $locationId);
        $transactions = $transactionsData['payments'] ?? [];
        
        $enhancedTransactions = [];
        foreach ($transactions as $transaction) {
            $orderDetails = null;
            
            // Try to get order details for this payment
            if (isset($transaction['order_id'])) {
                $orderDetails = $this->getOrderDetails($transaction['order_id']);
            }
            
            $enhancedTransactions[] = [
                'payment' => $transaction,
                'order' => $orderDetails,
                'line_items' => $this->enhanceLineItemsWithDescriptions($orderDetails['order']['line_items'] ?? [])
            ];
        }
        
        return $enhancedTransactions;
    }

    /**
     * Enhance line items with descriptions
     */
    private function enhanceLineItemsWithDescriptions($lineItems)
    {
        $enhancedItems = [];
        
        foreach ($lineItems as $item) {
            $enhancedItems[] = [
                'name' => $item['name'] ?? 'Unknown Item',
                'quantity' => $item['quantity'] ?? 1,
                'note' => $item['note'] ?? '',
                'variation_name' => $item['variation_name'] ?? '',
                'total_money' => $item['total_money'] ?? ['amount' => 0, 'currency' => 'USD'],
                'description' => $this->getItemDescription($item)
            ];
        }
        
        return $enhancedItems;
    }

    /**
     * Get all locations
     */
    public function getAllLocations()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/locations');

            if ($response->successful()) {
                $data = $response->json();
                Log::info('All Square Locations:', $data);
                return $data;
            } else {
                Log::error('Square Locations API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching all locations: ' . $e->getMessage());
            return null;
        }
    }
}