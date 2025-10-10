<?php

namespace App\Http\Controllers;

use App\Models\ProductCost;
use App\Services\SquareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductCostsExport;
use App\Imports\ProductCostsImport;

class ProductCostController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    /**
     * Display a listing of the product costs.
     */
    public function index()
    {
        try {
            $productCosts = ProductCost::orderBy('product_name')->get();
            
            return view('product-costs.index', compact('productCosts'));
            
        } catch (\Exception $e) {
            Log::error('Error fetching product costs', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Error loading product costs: ' . $e->getMessage());
        }
    }

/**
 * Show bulk edit form for product costs
 */
public function bulkEdit()
{
    try {
        $productCosts = ProductCost::orderBy('product_name')->get();
        
        return view('product-costs.bulk-edit', compact('productCosts'));
        
    } catch (\Exception $e) {
        Log::error('Error loading bulk edit form', [
            'error' => $e->getMessage()
        ]);
        
        return redirect()->route('product-costs.index')
            ->with('error', 'Error loading bulk edit form: ' . $e->getMessage());
    }
}

    /**
     * Show the form for creating a new product cost.
     */
    public function create()
    {
        try {
            // Get unique products from Square to help with manual entry
            $squareProducts = $this->squareService->getUniqueProducts();
            
            return view('product-costs.create', compact('squareProducts'));
            
        } catch (\Exception $e) {
            Log::error('Error loading create form', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('product-costs.index')
                ->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created product cost in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_uid' => 'required|string|max:255|unique:product_costs,product_uid',
                'product_name' => 'required|string|max:255',
                'cost' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            ProductCost::create($request->all());

            // Clear product cost cache
            $this->squareService->clearProductCostCache($request->product_uid);

            Log::info('Product cost created', [
                'product_uid' => $request->product_uid,
                'product_name' => $request->product_name
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', 'Product cost created successfully!');
                
        } catch (\Exception $e) {
            Log::error('Error creating product cost', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error creating product cost: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified product cost.
     */
    public function edit($id)
    {
        try {
            $productCost = ProductCost::findOrFail($id);
            $squareProducts = $this->squareService->getUniqueProducts();
            
            return view('product-costs.edit', compact('productCost', 'squareProducts'));
            
        } catch (\Exception $e) {
            Log::error('Error loading edit form', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('product-costs.index')
                ->with('error', 'Product cost not found.');
        }
    }

    /**
     * Update the specified product cost in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $productCost = ProductCost::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'product_uid' => 'required|string|max:255|unique:product_costs,product_uid,' . $id,
                'product_name' => 'required|string|max:255',
                'cost' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $oldProductUid = $productCost->product_uid;
            $productCost->update($request->all());

            // Clear cache for both old and new product UID if changed
            $this->squareService->clearProductCostCache($oldProductUid);
            if ($oldProductUid !== $request->product_uid) {
                $this->squareService->clearProductCostCache($request->product_uid);
            }

            Log::info('Product cost updated', [
                'id' => $id,
                'product_uid' => $request->product_uid,
                'product_name' => $request->product_name
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', 'Product cost updated successfully!');
                
        } catch (\Exception $e) {
            Log::error('Error updating product cost', [
                'id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error updating product cost: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified product cost from storage.
     */
    public function destroy($id)
    {
        try {
            $productCost = ProductCost::findOrFail($id);
            $productUid = $productCost->product_uid;
            
            $productCost->delete();

            // Clear product cost cache
            $this->squareService->clearProductCostCache($productUid);

            Log::info('Product cost deleted', [
                'id' => $id,
                'product_uid' => $productUid
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', 'Product cost deleted successfully!');
                
        } catch (\Exception $e) {
            Log::error('Error deleting product cost', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error deleting product cost: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update product costs
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $updates = $request->input('updates', []);
            $updatedCount = 0;

            foreach ($updates as $id => $data) {
                if (isset($data['cost']) && is_numeric($data['cost'])) {
                    $productCost = ProductCost::find($id);
                    if ($productCost) {
                        $oldCost = $productCost->cost;
                        $productCost->update(['cost' => $data['cost']]);
                        
                        // Clear cache when cost changes
                        if ($oldCost != $data['cost']) {
                            $this->squareService->clearProductCostCache($productCost->product_uid);
                        }
                        
                        $updatedCount++;
                    }
                }
            }

            Log::info('Bulk update completed', [
                'updated_count' => $updatedCount
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', "Successfully updated {$updatedCount} product costs!");
                
        } catch (\Exception $e) {
            Log::error('Error in bulk update', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error updating product costs: ' . $e->getMessage());
        }
    }

    /**
     * Export product costs to Excel
     */
    public function export()
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "product_costs_{$timestamp}.xlsx";
            
            Log::info('Exporting product costs', [
                'filename' => $filename
            ]);

            return Excel::download(new ProductCostsExport, $filename);
            
        } catch (\Exception $e) {
            Log::error('Error exporting product costs', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error exporting product costs: ' . $e->getMessage());
        }
    }

    /**
     * Import product costs from Excel
     */
    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->with('error', 'Please upload a valid Excel file (xlsx, xls, csv) under 10MB.');
            }

            $import = new ProductCostsImport($this->squareService);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            Log::info('Product costs imported', [
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped']
            ]);

            $message = "Import completed: {$results['imported']} new, {$results['updated']} updated, {$results['skipped']} skipped.";
            
            return redirect()->route('product-costs.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Error importing product costs', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Sync products from Square
     */
    public function syncFromSquare()
    {
        try {
            $result = $this->squareService->syncProductsFromCatalog();

            Log::info('Products synced from Square', [
                'synced' => $result['synced'],
                'updated' => $result['updated'],
                'total' => $result['total']
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', "Successfully synced {$result['synced']} new products and updated {$result['updated']} existing products from Square. Total products: {$result['total']}");
                
        } catch (\Exception $e) {
            Log::error('Error syncing products from Square', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error syncing products from Square: ' . $e->getMessage());
        }
    }

    /**
     * Import products from Square (POST method)
     */
    public function importFromSquare(Request $request)
    {
        try {
            $result = $this->squareService->syncProductsFromCatalog();

            Log::info('Products imported from Square', [
                'synced' => $result['synced'],
                'updated' => $result['updated'],
                'total' => $result['total']
            ]);

            return redirect()->route('product-costs.index')
                ->with('success', "Successfully imported {$result['synced']} new products and updated {$result['updated']} existing products from Square.");
                
        } catch (\Exception $e) {
            Log::error('Error importing products from Square', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error importing products from Square: ' . $e->getMessage());
        }
    }

    /**
     * Show product cost report
     */
    public function report()
    {
        try {
            $productCosts = ProductCost::withCosts()->get();
            $totalProducts = $productCosts->count();
            $productsWithCosts = $productCosts->where('cost', '>', 0)->count();
            $productsWithoutCosts = $totalProducts - $productsWithCosts;
            
            $stats = [
                'total_products' => $totalProducts,
                'products_with_costs' => $productsWithCosts,
                'products_without_costs' => $productsWithoutCosts,
                'completion_percentage' => $totalProducts > 0 ? round(($productsWithCosts / $totalProducts) * 100, 2) : 0
            ];

            return view('product-costs.report', compact('productCosts', 'stats'));
            
        } catch (\Exception $e) {
            Log::error('Error generating product cost report', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error generating report: ' . $e->getMessage());
        }
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');
            
            if (empty($query)) {
                return redirect()->route('product-costs.index');
            }

            $productCosts = ProductCost::where('product_name', 'LIKE', "%{$query}%")
                ->orWhere('product_uid', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->orderBy('product_name')
                ->get();

            return view('product-costs.index', compact('productCosts', 'query'));
            
        } catch (\Exception $e) {
            Log::error('Error searching products', [
                'query' => $request->input('q'),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('product-costs.index')
                ->with('error', 'Error searching products: ' . $e->getMessage());
        }
    }

    /**
     * Get product cost via API
     */
    public function getProductCost($productUid)
    {
        try {
            $productCost = ProductCost::where('product_uid', $productUid)->first();
            
            if (!$productCost) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'product' => $productCost
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching product cost via API', [
                'product_uid' => $productUid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error fetching product cost'
            ], 500);
        }
    }
}
