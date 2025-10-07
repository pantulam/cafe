@extends('layouts.app')

@section('title', 'Product Costs - COGS Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Product Costs Management</h1>
        <div class="space-x-2">
            <a href="{{ route('product-costs.import-from-square') }}" 
               class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                Import from Square
            </a>
            <form action="{{ route('product-costs.sync-from-square') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Sync from Square
                </button>
            </form>
            <a href="{{ route('product-costs.bulk-edit') }}" 
               class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Bulk Edit
            </a>
            <a href="{{ route('product-costs.create') }}" 
               class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Add Product Cost
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

    <!-- Status Messages for AJAX updates -->
    <div id="ajax-success" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <span id="success-message"></span>
    </div>
    <div id="ajax-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <span id="error-message"></span>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <p class="text-gray-600">
                Total Products: {{ $productCosts->count() }}
                <span class="text-sm text-gray-500 ml-2">(Click on cost to edit, changes save automatically)</span>
            </p>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($productCosts as $productCost)
                    <tr class="product-row" data-product-id="{{ $productCost->id }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $productCost->product_uid }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $productCost->product_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="cost-cell">
                                <span class="cost-display text-sm text-gray-900 cursor-pointer hover:bg-gray-100 px-2 py-1 rounded">
                                    $<span class="cost-value">{{ number_format($productCost->cost, 2) }}</span>
                                </span>
                                <input type="number" 
                                       step="0.01"
                                       min="0"
                                       value="{{ $productCost->cost }}"
                                       class="cost-input hidden w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                       data-product-id="{{ $productCost->id }}">
                                <div class="cost-loading hidden inline-block ml-2">
                                    <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $productCost->description ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('product-costs.edit', $productCost) }}" 
                               class="text-blue-600 hover:text-blue-900">Edit</a>
                            <form action="{{ route('product-costs.destroy', $productCost) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('Are you sure you want to delete this product cost?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            No product costs found. 
                            <a href="{{ route('product-costs.import-from-square') }}" class="text-blue-600 hover:text-blue-900">Import products from Square</a>
                            or
                            <a href="{{ route('product-costs.create') }}" class="text-blue-600 hover:text-blue-900">add your first product cost manually</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Show success message
    function showSuccess(message) {
        const successDiv = document.getElementById('ajax-success');
        const messageSpan = document.getElementById('success-message');
        messageSpan.textContent = message;
        successDiv.classList.remove('hidden');
        
        // Hide after 3 seconds
        setTimeout(() => {
            successDiv.classList.add('hidden');
        }, 3000);
    }

    // Show error message
    function showError(message) {
        const errorDiv = document.getElementById('ajax-error');
        const messageSpan = document.getElementById('error-message');
        messageSpan.textContent = message;
        errorDiv.classList.remove('hidden');
        
        // Hide after 5 seconds
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 5000);
    }

    // Make cost field editable
    document.querySelectorAll('.cost-display').forEach(display => {
        display.addEventListener('click', function() {
            const cell = this.closest('.cost-cell');
            const display = cell.querySelector('.cost-display');
            const input = cell.querySelector('.cost-input');
            const value = cell.querySelector('.cost-value');
            
            display.classList.add('hidden');
            input.classList.remove('hidden');
            input.focus();
            input.select();
        });
    });

    // Handle cost input blur (save on focus out)
    document.querySelectorAll('.cost-input').forEach(input => {
        input.addEventListener('blur', function() {
            const cell = this.closest('.cost-cell');
            const display = cell.querySelector('.cost-display');
            const valueDisplay = cell.querySelector('.cost-value');
            const loading = cell.querySelector('.cost-loading');
            const productId = this.getAttribute('data-product-id');
            const newCost = parseFloat(this.value);

            // Validate input
            if (isNaN(newCost) || newCost < 0) {
                // Reset to original value if invalid
                this.value = parseFloat(valueDisplay.textContent);
                this.classList.add('hidden');
                display.classList.remove('hidden');
                showError('Invalid cost value. Please enter a valid number.');
                return;
            }

            // Show loading
            this.classList.add('hidden');
            loading.classList.remove('hidden');

            // Make AJAX request
            fetch(`/product-costs/${productId}/update-cost`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cost: newCost
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.classList.add('hidden');
                
                if (data.success) {
                    // Update display value
                    valueDisplay.textContent = data.new_cost;
                    display.classList.remove('hidden');
                    showSuccess('Cost updated successfully!');
                } else {
                    // Show error and reset to original value
                    this.value = parseFloat(valueDisplay.textContent);
                    display.classList.remove('hidden');
                    showError(data.message || 'Error updating cost');
                }
            })
            .catch(error => {
                loading.classList.add('hidden');
                this.value = parseFloat(valueDisplay.textContent);
                display.classList.remove('hidden');
                showError('Network error. Please try again.');
                console.error('Error:', error);
            });
        });

        // Also save on Enter key
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });

        // Handle Escape key to cancel
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const cell = this.closest('.cost-cell');
                const display = cell.querySelector('.cost-display');
                const valueDisplay = cell.querySelector('.cost-value');
                
                this.value = parseFloat(valueDisplay.textContent);
                this.classList.add('hidden');
                display.classList.remove('hidden');
            }
        });
    });
});
</script>

<style>
.cost-display {
    transition: background-color 0.2s ease;
}

.cost-display:hover {
    background-color: #f3f4f6;
}

.cost-input {
    border: 1px solid #d1d5db;
    padding: 0.25rem 0.5rem;
}

.cost-input:focus {
    outline: none;
    border-color: #3b82f6;
    ring: 2px;
}
</style>
@endsection
