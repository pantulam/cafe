@extends('layouts.app')

@section('title', 'Bulk Edit Product Costs')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Bulk Edit Product Costs</h1>
        <div class="space-x-2">
            <a href="{{ route('product-costs.index') }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>

    <form action="{{ route('product-costs.bulk-update') }}" method="POST">
        @csrf
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <p class="text-gray-600">Update costs for multiple products at once</p>
            </div>
            
            <div class="p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product UID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Cost</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($productCosts as $productCost)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $productCost->product_uid }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $productCost->product_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($productCost->cost, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" 
                                           name="costs[{{ $productCost->id }}][cost]" 
                                           value="{{ $productCost->cost }}"
                                           step="0.01"
                                           min="0"
                                           class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No product costs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($productCosts) > 0)
            <div class="px-6 py-4 bg-gray-50 border-t">
                <div class="flex justify-end space-x-2">
                    <a href="{{ route('product-costs.index') }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Update All Costs
                    </button>
                </div>
            </div>
            @endif
        </div>
    </form>
</div>
@endsection
