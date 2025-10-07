@extends('layouts.app')

@section('title', 'Import Products from Square')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Import Products from Square</h1>
        <div class="space-x-2">
            <a href="{{ route('product-costs.index') }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-blue-50 border-b">
            <p class="text-blue-800">
                Found {{ count($uniqueProducts) }} products in your Square catalog.
                Products already in your system are marked.
            </p>
        </div>

        <form action="{{ route('product-costs.bulk-import') }}" method="POST">
            @csrf
            <div class="p-6">
                @if(count($uniqueProducts) > 0)
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <button type="button" onclick="selectAll()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                                Select All
                            </button>
                            <button type="button" onclick="deselectAll()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 ml-2">
                                Deselect All
                            </button>
                        </div>
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Import Selected Products
                        </button>
                    </div>

                    <div class="overflow-y-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Import</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($uniqueProducts as $product)
                                    @php
                                        $isExisting = in_array($product['id'], $existingProductUids);
                                    @endphp
                                    <tr class="{{ $isExisting ? 'bg-gray-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(!$isExisting)
                                                <input type="checkbox" name="products[]" value="{{ $product['id'] }}" 
                                                       class="product-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            @else
                                                <span class="text-gray-400">✓</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ substr($product['id'], 0, 8) }}...
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $product['name'] }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $product['description'] ?? 'No description' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($isExisting)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Already Imported
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    New
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500">No products found in your Square catalog.</p>
                        <p class="text-sm text-gray-400 mt-2">Make sure you have products in your Square inventory and the API permissions are set correctly.</p>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
    function selectAll() {
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
    }

    function deselectAll() {
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
    }
</script>
@endsection
