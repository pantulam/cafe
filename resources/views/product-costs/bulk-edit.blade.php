@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Bulk Edit Product Costs</h1>
            <p class="text-gray-600 mt-2">Update costs for multiple products at once</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('product-costs.index') }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Bulk Edit Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('product-costs.bulk-update') }}" method="POST" id="bulkEditForm">
            @csrf
            
            <!-- Instructions -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <p class="text-blue-700">
                        Update costs below. Changes will be saved when you submit the form. 
                        You can use the "Set All" feature to apply the same cost to all products.
                    </p>
                </div>
            </div>

            <!-- Bulk Action Controls -->
            <div class="mb-6 flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2">
                    <span class="text-gray-700 font-medium">Set All Costs to:</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-500">$</span>
                        <input type="number" 
                               id="setAllCost" 
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               class="w-24 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="button" 
                            onclick="setAllCosts()"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-200">
                        Apply to All
                    </button>
                </div>
                <div class="flex-1"></div>
                <button type="button" 
                        onclick="resetAllCosts()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-undo mr-2"></i>Reset All
                </button>
            </div>

            <!-- Products Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-4 px-4 text-left font-semibold text-gray-700">Product Name</th>
                            <th class="py-4 px-4 text-left font-semibold text-gray-700">Product ID</th>
                            <th class="py-4 px-4 text-left font-semibold text-gray-700">Current Cost</th>
                            <th class="py-4 px-4 text-left font-semibold text-gray-700">New Cost</th>
                            <th class="py-4 px-4 text-left font-semibold text-gray-700">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productCosts as $index => $product)
                        <tr class="border-b hover:bg-gray-50 transition duration-150 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900">{{ $product->product_name }}</div>
                                @if($product->description)
                                    <div class="text-sm text-gray-500 mt-1">{{ Str::limit($product->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <code class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ Str::limit($product->product_uid, 15) }}</code>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-mono text-gray-700">${{ number_format($product->cost, 2) }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">$</span>
                                    <input type="number" 
                                           name="updates[{{ $product->id }}][cost]" 
                                           value="{{ $product->cost }}"
                                           step="0.01"
                                           min="0"
                                           data-original-cost="{{ $product->cost }}"
                                           class="cost-input w-32 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="change-indicator text-sm font-medium" id="change-{{ $product->id }}">
                                    <i class="fas fa-equals text-gray-400"></i>
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center text-gray-500">
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

            <!-- Summary and Submit -->
            <div class="mt-6 flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    <span id="modifiedCount">0</span> of {{ $productCosts->count() }} products modified
                </div>
                <div class="space-x-2">
                    <button type="button" 
                            onclick="window.location.href='{{ route('product-costs.index') }}'" 
                            class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition duration-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="submitBtn"
                            class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition duration-200">
                        <i class="fas fa-save mr-2"></i>Save All Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Set all costs to a specific value
    function setAllCosts() {
        const setAllInput = document.getElementById('setAllCost');
        const costValue = parseFloat(setAllInput.value);
        
        if (isNaN(costValue) || costValue < 0) {
            alert('Please enter a valid cost amount.');
            return;
        }

        if (confirm(`Set all product costs to $${costValue.toFixed(2)}?`)) {
            const inputs = document.querySelectorAll('.cost-input');
            inputs.forEach(input => {
                input.value = costValue.toFixed(2);
                updateChangeIndicator(input);
            });
            updateModifiedCount();
        }
    }

    // Reset all costs to original values
    function resetAllCosts() {
        if (confirm('Reset all costs to their original values?')) {
            const inputs = document.querySelectorAll('.cost-input');
            inputs.forEach(input => {
                const originalCost = parseFloat(input.getAttribute('data-original-cost'));
                input.value = originalCost.toFixed(2);
                updateChangeIndicator(input);
            });
            updateModifiedCount();
        }
    }

    // Update change indicator for a single input
    function updateChangeIndicator(input) {
        const productId = input.name.match(/\[(\d+)\]/)[1];
        const originalCost = parseFloat(input.getAttribute('data-original-cost'));
        const newCost = parseFloat(input.value);
        const indicator = document.getElementById(`change-${productId}`);
        
        if (newCost > originalCost) {
            indicator.innerHTML = '<i class="fas fa-arrow-up text-red-500"></i>';
            indicator.title = `Increase: $${(newCost - originalCost).toFixed(2)}`;
        } else if (newCost < originalCost) {
            indicator.innerHTML = '<i class="fas fa-arrow-down text-green-500"></i>';
            indicator.title = `Decrease: $${(originalCost - newCost).toFixed(2)}`;
        } else {
            indicator.innerHTML = '<i class="fas fa-equals text-gray-400"></i>';
            indicator.title = 'No change';
        }
    }

    // Update modified count
    function updateModifiedCount() {
        const inputs = document.querySelectorAll('.cost-input');
        let modifiedCount = 0;
        
        inputs.forEach(input => {
            const originalCost = parseFloat(input.getAttribute('data-original-cost'));
            const newCost = parseFloat(input.value);
            if (newCost !== originalCost) {
                modifiedCount++;
            }
        });
        
        document.getElementById('modifiedCount').textContent = modifiedCount;
        
        // Update submit button state
        const submitBtn = document.getElementById('submitBtn');
        if (modifiedCount > 0) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            submitBtn.classList.add('bg-green-500', 'hover:bg-green-600');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
            submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
        }
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.cost-input');
        
        // Initialize change indicators
        inputs.forEach(input => {
            updateChangeIndicator(input);
            
            // Add input event listeners
            input.addEventListener('input', function() {
                updateChangeIndicator(this);
                updateModifiedCount();
            });
            
            // Add validation
            input.addEventListener('change', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
                if (this.value > 10000) {
                    this.value = 10000;
                }
            });
        });
        
        // Initialize modified count
        updateModifiedCount();
        
        // Form submission validation
        const form = document.getElementById('bulkEditForm');
        form.addEventListener('submit', function(e) {
            const modifiedCount = parseInt(document.getElementById('modifiedCount').textContent);
            if (modifiedCount === 0) {
                e.preventDefault();
                alert('No changes detected. Please modify at least one cost before saving.');
            }
        });
    });
</script>
@endpush
