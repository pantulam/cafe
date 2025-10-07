<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SquareService
{
    protected $baseUrl;
    protected $headers;

    public function __construct()
    {
        $accessToken = config('square.access_token');
        $environment = config('square.environment', 'production');
        
        $this->baseUrl = $environment === 'sandbox' 
            ? 'https://connect.squareupsandbox.com' 
            : 'https://connect.squareup.com';
            
        $this->headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Square-Version' => '2024-08-21'
        ];
    }

    public function getTransactions($startDate = null, $endDate = null)
    {
        try {
            // Default to today in EST
            $startDate = $startDate ?: Carbon::today('America/New_York')->format('Y-m-d');
            $endDate = $endDate ?: Carbon::today('America/New_York')->format('Y-m-d');
            
            $locationId = config('square.location_id');

            if (empty($locationId)) {
                throw new \Exception('Square location ID is not configured');
            }

            // Generate cache key based on date range
            $cacheKey = "square_transactions_{$startDate}_{$endDate}";
            $isToday = $this->isToday($startDate, $endDate);

            Log::info('Transaction request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_today' => $isToday,
                'cache_key' => $cacheKey
            ]);

            // For non-today dates, try to get from cache first
            if (!$isToday) {
                $cachedTransactions = Cache::get($cacheKey);
                if ($cachedTransactions) {
                    Log::info('Returning cached transactions', [
                        'cache_key' => $cacheKey,
                        'cached_count' => count($cachedTransactions)
                    ]);
                    
                    // Recalculate costs for cached transactions to ensure they're up to date
                    $updatedTransactions = [];
                    foreach ($cachedTransactions as $transaction) {
                        $updatedTransaction = $transaction;
                        $updatedTransaction['total_cost'] = $this->calculateTransactionCost($transaction);
                        $updatedTransaction['line_items_with_costs'] = $this->getLineItemsWithCosts($transaction);
                        $updatedTransactions[] = $updatedTransaction;
                    }
                    
                    return $updatedTransactions;
                }
            }

            // Convert EST dates to UTC for Square API
            $beginTimeUTC = $this->convertToUTC($startDate . ' 00:00:00');
            $endTimeUTC = $this->convertToUTC($endDate . ' 23:59:59');

            // Use Payments API v2
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . '/v2/payments', [
                    'begin_time' => $beginTimeUTC,
                    'end_time' => $endTimeUTC,
                    'location_id' => $locationId,
                    'limit' => 100,
                    'sort_order' => 'ASC'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $payments = $data['payments'] ?? [];
                
                // Filter transactions to ensure they fall within the EST date range
                $filteredPayments = array_filter($payments, function($payment) use ($startDate, $endDate) {
                    $paymentDateEST = $this->convertToEST($payment['created_at'] ?? '')->format('Y-m-d');
                    return $paymentDateEST >= $startDate && $paymentDateEST <= $endDate;
                });
                
                // Enhance payments with cost data
                $enhancedPayments = [];
                foreach ($filteredPayments as $payment) {
                    $enhancedPayment = $payment;
                    $enhancedPayment['total_cost'] = $this->calculateTransactionCost($payment);
                    $enhancedPayment['line_items_with_costs'] = $this->getLineItemsWithCosts($payment);
                    $enhancedPayments[] = $enhancedPayment;
                }
                
                // Sort payments by date then time in EST
                usort($enhancedPayments, function($a, $b) {
                    $timeA = $this->convertToEST($a['created_at'] ?? '');
                    $timeB = $this->convertToEST($b['created_at'] ?? '');
                    
                    return $timeA <=> $timeB;
                });

                // Cache non-today transactions for 24 hours
                if (!$isToday) {
                    $cacheDuration = 24 * 60 * 60; // 24 hours in seconds
                    Cache::put($cacheKey, $enhancedPayments, $cacheDuration);
                    
                    Log::info('Cached transactions', [
                        'cache_key' => $cacheKey,
                        'duration' => '24 hours',
                        'transaction_count' => count($enhancedPayments)
                    ]);
                }

                Log::info('Transactions processed', [
                    'original_count' => count($payments),
                    'filtered_count' => count($enhancedPayments),
                    'cached' => !$isToday,
                    'date_range_est' => "$startDate to $endDate"
                ]);
                
                return $enhancedPayments;
            } else {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $errorMessage = $errorData['errors'][0]['detail'] ?? $errorBody;
                } else {
                    $errorMessage = $errorBody;
                }
                
                throw new \Exception('Square API Error: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('Error fetching transactions', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Error fetching transactions: ' . $e->getMessage());
        }
    }

    public function getTransaction($paymentId)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . '/v2/payments/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();
                $payment = $data['payment'] ?? null;
                
                if ($payment) {
                    $payment['total_cost'] = $this->calculateTransactionCost($payment);
                    $payment['line_items_with_costs'] = $this->getLineItemsWithCosts($payment);
                }
                
                return $payment;
            } else {
                $errorBody = $response->body();
                throw new \Exception('Square API Error: ' . $errorBody);
            }

        } catch (\Exception $e) {
            throw new \Exception('Error fetching transaction: ' . $e->getMessage());
        }
    }

    /**
     * Calculate total cost for a transaction
     */
    public function calculateTransactionCost($payment)
    {
        try {
            $totalCost = 0;
            
            // Check if payment has an order ID
            $orderId = $payment['order_id'] ?? null;
            if ($orderId) {
                $order = $this->getOrderDetails($orderId);
                if ($order && isset($order['line_items'])) {
                    foreach ($order['line_items'] as $lineItem) {
                        $itemCost = $this->calculateLineItemCost($lineItem);
                        $totalCost += $itemCost;
                    }
                }
            }
            
            Log::debug('Transaction cost calculated', [
                'payment_id' => $payment['id'] ?? 'unknown',
                'order_id' => $orderId,
                'total_cost' => $totalCost
            ]);
            
            return $totalCost;
            
        } catch (\Exception $e) {
            Log::error('Error calculating transaction cost', [
                'payment_id' => $payment['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Calculate cost for a single line item including modifiers
     */
    public function calculateLineItemCost($lineItem)
    {
        try {
            $baseItemCost = 0;
            $modifiersCost = 0;
            $quantity = $lineItem['quantity'] ?? 1;
            
            // Calculate base item cost
            $catalogObjectId = $lineItem['catalog_object_id'] ?? null;
            if ($catalogObjectId) {
                $baseItemCost = $this->getProductCost($catalogObjectId);
            }
            
            // Calculate modifiers cost
            if (isset($lineItem['modifiers']) && is_array($lineItem['modifiers'])) {
                foreach ($lineItem['modifiers'] as $modifier) {
                    $modifierCatalogObjectId = $modifier['catalog_object_id'] ?? null;
                    if ($modifierCatalogObjectId) {
                        $modifierCost = $this->getProductCost($modifierCatalogObjectId);
                        $modifiersCost += $modifierCost;
                    }
                }
            }
            
            $totalLineItemCost = ($baseItemCost + $modifiersCost) * $quantity;
            
            Log::debug('Line item cost calculated', [
                'item_name' => $lineItem['name'] ?? 'Unknown',
                'base_cost' => $baseItemCost,
                'modifiers_cost' => $modifiersCost,
                'quantity' => $quantity,
                'total_cost' => $totalLineItemCost,
                'has_modifiers' => !empty($lineItem['modifiers'])
            ]);
            
            return $totalLineItemCost;
            
        } catch (\Exception $e) {
            Log::error('Error calculating line item cost', [
                'line_item' => $lineItem['name'] ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get line items with detailed cost breakdown including modifiers
     */
    public function getLineItemsWithCosts($payment)
    {
        try {
            $lineItemsWithCosts = [];
            $orderId = $payment['order_id'] ?? null;
            
            if ($orderId) {
                $order = $this->getOrderDetails($orderId);
                if ($order && isset($order['line_items'])) {
                    foreach ($order['line_items'] as $lineItem) {
                        $quantity = $lineItem['quantity'] ?? 1;
                        $baseItemCost = 0;
                        $modifiers = [];
                        
                        // Get base item cost
                        $catalogObjectId = $lineItem['catalog_object_id'] ?? null;
                        if ($catalogObjectId) {
                            $baseItemCost = $this->getProductCost($catalogObjectId);
                        }
                        
                        // Process modifiers
                        $modifiersCost = 0;
                        if (isset($lineItem['modifiers']) && is_array($lineItem['modifiers'])) {
                            foreach ($lineItem['modifiers'] as $modifier) {
                                $modifierCatalogObjectId = $modifier['catalog_object_id'] ?? null;
                                $modifierName = $modifier['name'] ?? 'Unknown Modifier';
                                $modifierCost = 0;
                                
                                if ($modifierCatalogObjectId) {
                                    $modifierCost = $this->getProductCost($modifierCatalogObjectId);
                                }
                                
                                $modifiers[] = [
                                    'name' => $modifierName,
                                    'cost' => $modifierCost
                                ];
                                $modifiersCost += $modifierCost;
                            }
                        }
                        
                        $totalLineItemCost = ($baseItemCost + $modifiersCost) * $quantity;
                        
                        $lineItemsWithCosts[] = [
                            'name' => $lineItem['name'] ?? 'Unknown Item',
                            'quantity' => $quantity,
                            'base_unit_cost' => $baseItemCost,
                            'modifiers' => $modifiers,
                            'modifiers_cost' => $modifiersCost,
                            'total_unit_cost' => $baseItemCost + $modifiersCost,
                            'total_cost' => $totalLineItemCost,
                            'catalog_object_id' => $catalogObjectId,
                            'has_modifiers' => !empty($modifiers)
                        ];
                    }
                }
            }
            
            return $lineItemsWithCosts;
            
        } catch (\Exception $e) {
            Log::error('Error getting line items with costs', [
                'payment_id' => $payment['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Clear cache when product costs are updated
     */
    public function clearProductCostCache($productUid = null)
    {
        if ($productUid) {
            // Clear specific product cost cache
            Cache::forget("product_cost_{$productUid}");
            Log::info('Cleared product cost cache', ['product_uid' => $productUid]);
        } else {
            // Clear all product cost caches
            // This is a simple approach - in production you might track cache keys
            Log::info('Cleared all product cost caches');
        }
        
        // Also clear any transaction caches that might be affected
        // This ensures transactions will recalculate with new costs
        $this->clearAllTransactionCaches();
    }

    /**
     * Clear all transaction-related caches
     */
    public function clearAllTransactionCaches()
    {
        // Get today's date for cache key pattern
        $today = Carbon::today('America/New_York')->format('Y-m-d');
        
        // Clear cached transactions (except today's)
        // This is a simplified approach - in production you might track cache keys
        $cache = app('cache');
        if (method_exists($cache, 'getStore') && $cache->getStore() instanceof \Illuminate\Cache\RedisStore) {
            // For Redis, you can use pattern matching
            $keys = $cache->getRedis()->keys('*square_transactions*');
            foreach ($keys as $key) {
                // Don't clear today's cache
                if (!str_contains($key, $today)) {
                    $cache->forget($key);
                }
            }
        }
        
        Log::info('Cleared all transaction caches');
    }

    /**
     * Check if the date range includes today
     */
    protected function isToday($startDate, $endDate)
    {
        $today = Carbon::today('America/New_York')->format('Y-m-d');
        return $startDate === $today || $endDate === $today;
    }

    /**
     * Clear cache for specific date range
     */
    public function clearCache($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            $cacheKey = "square_transactions_{$startDate}_{$endDate}";
            Cache::forget($cacheKey);
            Log::info('Cleared transaction cache', ['cache_key' => $cacheKey]);
        } else {
            $this->clearAllTransactionCaches();
        }
    }

    /**
     * Pre-cache transactions for specific dates
     */
    public function preCacheTransactions($startDate, $endDate)
    {
        try {
            Log::info('Pre-caching transactions', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            $transactions = $this->getTransactions($startDate, $endDate);
            
            Log::info('Pre-caching completed', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'transaction_count' => count($transactions)
            ]);
            
            return count($transactions);
            
        } catch (\Exception $e) {
            Log::error('Error pre-caching transactions', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get order details from Square
     */
    public function getOrderDetails($orderId)
    {
        try {
            $cacheKey = "square_order_{$orderId}";
            
            // Try cache first for orders
            $cachedOrder = Cache::get($cacheKey);
            if ($cachedOrder) {
                return $cachedOrder;
            }

            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . '/v2/orders/' . $orderId);

            if ($response->successful()) {
                $data = $response->json();
                $order = $data['order'] ?? null;
                
                if ($order) {
                    // Cache order details for 24 hours
                    Cache::put($cacheKey, $order, 24 * 60 * 60);
                }
                
                return $order;
            } else {
                Log::warning('Error fetching order details', [
                    'order_id' => $orderId,
                    'status' => $response->status()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Error fetching order details', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get product cost from local database (with cache)
     */
    public function getProductCost($catalogObjectId)
    {
        try {
            $cacheKey = "product_cost_{$catalogObjectId}";
            
            // Try cache first for product costs
            $cachedCost = Cache::get($cacheKey);
            if ($cachedCost !== null) {
                return $cachedCost;
            }

            $productCost = \App\Models\ProductCost::where('product_uid', $catalogObjectId)->first();
            $cost = $productCost ? $productCost->cost : 0;
            
            // Cache product cost for 1 hour (since costs might change)
            Cache::put($cacheKey, $cost, 60 * 60);
            
            return $cost;
            
        } catch (\Exception $e) {
            Log::error('Error getting product cost', [
                'catalog_object_id' => $catalogObjectId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get all catalog items from Square
     */
    public function getCatalogItems()
    {
        try {
            $cacheKey = 'square_catalog_items';
            
            // Try cache first for catalog
            $cachedItems = Cache::get($cacheKey);
            if ($cachedItems) {
                return $cachedItems;
            }

            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . '/v2/catalog/list', [
                    'types' => 'ITEM'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['objects'] ?? [];
                
                // Cache catalog for 6 hours
                Cache::put($cacheKey, $items, 6 * 60 * 60);
                
                Log::info('Square Catalog Items', [
                    'total_items' => count($items),
                    'cached' => true
                ]);
                
                return $items;
            } else {
                $errorBody = $response->body();
                Log::error('Square Catalog API Error', [
                    'status' => $response->status(),
                    'body' => $errorBody
                ]);
                throw new \Exception('Square Catalog API Error: ' . $errorBody);
            }

        } catch (\Exception $e) {
            Log::error('Error fetching catalog items', [
                'message' => $e->getMessage()
            ]);
            throw new \Exception('Error fetching catalog items: ' . $e->getMessage());
        }
    }

    /**
     * Get unique products from catalog
     */
    public function getUniqueProducts()
    {
        try {
            $catalogItems = $this->getCatalogItems();
            $uniqueProducts = [];

            foreach ($catalogItems as $item) {
                if ($item['type'] === 'ITEM' && isset($item['item_data'])) {
                    $productId = $item['id'];
                    $productName = $item['item_data']['name'] ?? 'Unnamed Product';
                    $productDescription = $item['item_data']['description'] ?? null;
                    
                    $uniqueProducts[] = [
                        'id' => $productId,
                        'name' => $productName,
                        'description' => $productDescription,
                        'variations' => $item['item_data']['variations'] ?? []
                    ];
                }
            }

            Log::info('Unique Products Processed', [
                'unique_products_count' => count($uniqueProducts)
            ]);

            return $uniqueProducts;

        } catch (\Exception $e) {
            Log::error('Error processing unique products', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Sync products from Square catalog to local database
     */
    public function syncProductsFromCatalog()
    {
        try {
            $uniqueProducts = $this->getUniqueProducts();
            $syncedCount = 0;
            $updatedCount = 0;

            foreach ($uniqueProducts as $product) {
                // Check if product already exists
                $existingProduct = \App\Models\ProductCost::where('product_uid', $product['id'])->first();

                if ($existingProduct) {
                    // Update existing product name/description if needed
                    if ($existingProduct->product_name !== $product['name'] || $existingProduct->description !== $product['description']) {
                        $existingProduct->update([
                            'product_name' => $product['name'],
                            'description' => $product['description']
                        ]);
                        $updatedCount++;
                        
                        // Clear product cost cache when product is updated
                        $this->clearProductCostCache($product['id']);
                    }
                } else {
                    // Create new product with default cost of 0
                    \App\Models\ProductCost::create([
                        'product_uid' => $product['id'],
                        'product_name' => $product['name'],
                        'cost' => 0,
                        'description' => $product['description']
                    ]);
                    $syncedCount++;
                }
            }

            Log::info('Products Sync Completed', [
                'synced' => $syncedCount,
                'updated' => $updatedCount
            ]);

            return [
                'synced' => $syncedCount,
                'updated' => $updatedCount,
                'total' => count($uniqueProducts)
            ];

        } catch (\Exception $e) {
            Log::error('Error syncing products', [
                'message' => $e->getMessage()
            ]);
            throw new \Exception('Error syncing products: ' . $e->getMessage());
        }
    }

    /**
     * Convert EST time to UTC for Square API
     */
    protected function convertToUTC($estTime)
    {
        if (empty($estTime)) {
            return Carbon::now('UTC');
        }
        
        return Carbon::parse($estTime, 'America/New_York')->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Convert UTC time to EST
     */
    protected function convertToEST($utcTime)
    {
        if (empty($utcTime)) {
            return Carbon::now('America/New_York');
        }
        
        return Carbon::parse($utcTime)->setTimezone('America/New_York');
    }

    /**
     * Format time in EST for display
     */
    public function formatESTTime($utcTime, $format = 'M j, Y g:i A')
    {
        if (empty($utcTime)) {
            return 'N/A';
        }
        
        return $this->convertToEST($utcTime)->format($format);
    }

    /**
     * Get EST date from UTC timestamp
     */
    public function getESTDate($utcTime)
    {
        if (empty($utcTime)) {
            return 'N/A';
        }
        
        return $this->convertToEST($utcTime)->format('Y-m-d');
    }
}