@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Product Costs Management</h1>
        <div class="space-x-2">
            <form action="{{ route('product-costs.sync-from-square') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Sync from Square
                </button>
            </form>
            <a href="{{ route('product-costs.bulk-edit') }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                Bulk Edit
            </a>
            <a href="{{ route('product-costs.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Add Product
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-3 px-4 text-left">Product Name</th>
                        <th class="py-3 px-4 text-left">Product ID</th>
                        <th class="py-3 px-4 text-left">Type</th>
                        <th class="py-3 px-4 text-left">Description</th>
                        <th class="py-3 px-4 text-left">Cost</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productCosts as $product)
                        <!-- Main Product Row -->
                        <tr class="border-b bg-blue-50">
                            <td class="py-3 px-4 font-semibold">
                                <div class="flex items-center">
                                    <i class="fas fa-cube mr-2 text-blue-500"></i>
                                    {{ $product->product_name }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <code class="text-sm bg-blue-100 px-2 py-1 rounded">{{ $product->product_uid }}</code>
                            </td>
                            <td class="py-3 px-4">
                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Main Product</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($product->description)
                                    <span class="text-sm text-gray-600">{{ Str::limit($product->description, 50) }}</span>
                                @else
                                    <span class="text-sm text-gray-400">No description</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono">${{ number_format($product->cost, 2) }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <a href="{{ route('product-costs.edit', $product->id) }}" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('product-costs.destroy', $product->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Variations -->
                        @if($product->variations && $product->variations->count() > 0)
                            @foreach($product->variations as $variation)
                                <tr class="border-b bg-gray-50">
                                    <td class="py-3 px-4 pl-8">
                                        <div class="flex items-center">
                                            <i class="fas fa-arrow-right mr-2 text-gray-400"></i>
                                            {{ $variation->product_name }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $variation->product_uid }}</code>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="bg-gray-500 text-white px-2 py-1 rounded text-xs">Variation</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($variation->description)
                                            <span class="text-sm text-gray-600">{{ Str::limit($variation->description, 50) }}</span>
                                        @else
                                            <span class="text-sm text-gray-400">No description</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono">${{ number_format($variation->cost, 2) }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('product-costs.edit', $variation->id) }}" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('product-costs.destroy', $variation->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                <i class="fas fa-box-open text-4xl mb-2 block"></i>
                                No products found. 
                                <a href="{{ route('product-costs.sync-from-square') }}" class="text-blue-500 hover:text-blue-700 underline">
                                    Sync from Square
                                </a> to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">

{{-- Remove or comment out this line --}}
{{-- {{ $productCosts->links() }} --}}

{{-- Optional: Add a simple message instead --}}
<div class="mt-4 text-center text-gray-600">
    Showing all {{ count($productCosts) }} products
</div>            


        </div>
    </div>
</div>
@endsection