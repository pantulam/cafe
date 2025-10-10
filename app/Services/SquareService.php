<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProductCost;

class SquareService
{
    protected $accessToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.square.access_token');
        $this->baseUrl = config('services.square.base_url', 'https://connect.squareupsandbox.com');
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

    public function getProductCosts()
    {
        $catalog = $this->getCatalog();
        $productCosts = [];

        if ($catalog && isset($catalog['objects'])) {
            foreach ($catalog['objects'] as $object) {
                if ($object['type'] === 'ITEM') {
                    $productId = $object['id'];
                    $cost = 0; // Default cost

                    // Extract cost from variations if available
                    if (isset($object['item_data']['variations'])) {
                        foreach ($object['item_data']['variations'] as $variation) {
                            if (isset($variation['item_variation_data']['price_money']['amount'])) {
                                $cost = $variation['item_variation_data']['price_money']['amount'] / 100;
                                break;
                            }
                        }
                    }

                    $productCosts[] = new ProductCost($productId, $cost);
                }
            }
        }

        return $productCosts;
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
            }

            if (!$locationId) {
                throw new \Exception('No location ID available');
            }

            $query = [
                'location_id' => $locationId,
            ];

            if ($startDate) {
                $query['begin_time'] = $startDate;
            }
            if ($endDate) {
                $query['end_time'] = $endDate;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v2/payments', $query);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Square Payments API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching transactions: ' . $e->getMessage());
            return null;
        }
    }
}