@extends('layouts.app')

@section('title', 'Add Product Cost')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Add Product Cost</h1>
        <a href="{{ route('product-costs.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to List
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('product-costs.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product UID *</label>
                    <input type="text" name="product_uid" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('product_uid')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" name="product_name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('product_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cost *</label>
                    <input type="number" name="cost" step="0.01" min="0" value="0" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-2">
                <a href="{{ route('product-costs.index') }}" 
                   class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Add Product Cost
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
