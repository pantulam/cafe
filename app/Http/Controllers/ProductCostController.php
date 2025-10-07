<?php

namespace App\Http\Controllers;

use App\Models\ProductCost;
use App\Services\SquareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductCostController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    public function index()
    {
        $productCosts = ProductCost::orderBy('product_name')->get();
        
        return view('product-costs.index', compact('productCosts'));
    }

    public function create()
    {
        return view('product-costs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_uid' => 'required|string|unique:product_costs,product_uid',
            'product_name' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        ProductCost::create($request->all());

        return redirect()->route('product-costs.index')
            ->with('success', 'Product cost added successfully.');
    }

    public function edit(ProductCost $productCost)
    {
        return view('product-costs.edit', compact('productCost'));
    }

    public function update(Request $request, ProductCost $productCost)
    {
        $request->validate([
            'product_uid' => 'required|string|unique:product_costs,product_uid,' . $productCost->id,
            'product_name' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $productCost->update($request->all());

        return redirect()->route('product-costs.index')
            ->with('success', 'Product cost updated successfully.');
    }

    public function destroy(ProductCost $productCost)
    {
        $productCost->delete();

        return redirect()->route('product-costs.index')
            ->with('success', 'Product cost deleted successfully.');
    }

    public function bulkEdit()
    {
        $productCosts = ProductCost::orderBy('product_name')->get();
        return view('product-costs.bulk-edit', compact('productCosts'));
    }

    public function bulkUpdate(Request $request)
    {
        $costs = $request->input('costs', []);

        foreach ($costs as $productId => $costData) {
            $productCost = ProductCost::find($productId);
            if ($productCost && isset($costData['cost'])) {
                $productCost->update([
                    'cost' => $costData['cost']
                ]);
            }
        }

        return redirect()->route('product-costs.index')
            ->with('success', 'Product costs updated successfully.');
    }

    /**
     * Sync products from Square catalog
     */
    public function syncFromSquare()
    {
        try {
            $result = $this->squareService->syncProductsFromCatalog();

            return redirect()->route('product-costs.index')
                ->with('success', "Products synced successfully! {$result['synced']} new products added, {$result['updated']} existing products updated.");

        } catch (\Exception $e) {
            return redirect()->route('product-costs.index')
                ->with('error', 'Error syncing products: ' . $e->getMessage());
        }
    }

    /**
     * Show products from Square catalog for initial import
     */
    public function importFromSquare()
    {
        try {
            $uniqueProducts = $this->squareService->getUniqueProducts();
            $existingProductUids = ProductCost::pluck('product_uid')->toArray();

            return view('product-costs.import', compact('uniqueProducts', 'existingProductUids'));

        } catch (\Exception $e) {
            return redirect()->route('product-costs.index')
                ->with('error', 'Error fetching products from Square: ' . $e->getMessage());
        }
    }

    /**
     * Bulk import products from Square catalog
     */
    public function bulkImport(Request $request)
    {
        try {
            $selectedProducts = $request->input('products', []);
            $importedCount = 0;

            foreach ($selectedProducts as $productUid) {
                // Check if product already exists
                $existingProduct = ProductCost::where('product_uid', $productUid)->first();
                
                if (!$existingProduct) {
                    // Get product details from Square
                    $uniqueProducts = $this->squareService->getUniqueProducts();
                    $productDetails = collect($uniqueProducts)->firstWhere('id', $productUid);
                    
                    if ($productDetails) {
                        ProductCost::create([
                            'product_uid' => $productUid,
                            'product_name' => $productDetails['name'],
                            'cost' => 0, // Default cost
                            'description' => $productDetails['description']
                        ]);
                        $importedCount++;
                    }
                }
            }

            return redirect()->route('product-costs.index')
                ->with('success', "Successfully imported {$importedCount} products from Square.");

        } catch (\Exception $e) {
            return redirect()->route('product-costs.index')
                ->with('error', 'Error importing products: ' . $e->getMessage());
        }
    }




/**
 * AJAX update for dynamic cost field
 */
public function updateCost(Request $request, ProductCost $productCost)
{
    try {
        $request->validate([
            'cost' => 'required|numeric|min:0'
        ]);

        $oldCost = $productCost->cost;
        $productCost->update([
            'cost' => $request->cost
        ]);

        // Clear caches to ensure transactions show updated costs
        $squareService = app(\App\Services\SquareService::class);
        $squareService->clearProductCostCache($productCost->product_uid);

        Log::info('Product cost updated via AJAX', [
            'product_id' => $productCost->id,
            'product_uid' => $productCost->product_uid,
            'old_cost' => $oldCost,
            'new_cost' => $request->cost,
            'caches_cleared' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cost updated successfully',
            'new_cost' => number_format($productCost->cost, 2)
        ]);

    } catch (\Exception $e) {
        Log::error('Error updating product cost via AJAX', [
            'product_id' => $productCost->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error updating cost: ' . $e->getMessage()
        ], 500);
    }
}}
