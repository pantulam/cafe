<?php

namespace App\Imports;

use App\Models\ProductCost;
use App\Services\SquareService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ProductCostsImport implements ToCollection, WithHeadingRow
{
    protected $squareService;
    protected $results = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0
    ];

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $productUid = $row['product_uid'] ?? null;
                $productName = $row['product_name'] ?? null;
                $cost = $row['cost'] ?? 0;

                if (!$productUid || !$productName) {
                    $this->results['skipped']++;
                    continue;
                }

                $existingProduct = ProductCost::where('product_uid', $productUid)->first();

                if ($existingProduct) {
                    // Update existing
                    $existingProduct->update([
                        'product_name' => $productName,
                        'cost' => $cost,
                        'description' => $row['description'] ?? $existingProduct->description
                    ]);
                    $this->results['updated']++;
                    
                    // Clear cache
                    $this->squareService->clearProductCostCache($productUid);
                } else {
                    // Create new
                    ProductCost::create([
                        'product_uid' => $productUid,
                        'product_name' => $productName,
                        'cost' => $cost,
                        'description' => $row['description'] ?? null
                    ]);
                    $this->results['imported']++;
                }

            } catch (\Exception $e) {
                Log::error('Error importing product cost row', [
                    'row' => $row,
                    'error' => $e->getMessage()
                ]);
                $this->results['skipped']++;
            }
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
