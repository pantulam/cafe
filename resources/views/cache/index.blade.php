@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-database me-2"></i>Cache Management
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Pre-cache Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Pre-cache transactions to improve performance. This will cache transactions for the specified number of days.
                                    </p>
                                    <form id="preCacheForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="days" class="form-label">Number of Days to Cache</label>
                                            <input type="number" class="form-control" id="days" name="days" value="7" min="1" max="30">
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="preCacheBtn">
                                            <i class="fas fa-bolt me-2"></i>Pre-cache Transactions
                                        </button>
                                    </form>
                                    <div id="preCacheResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-broom me-2"></i>Clear Cache
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Clear specific caches or all caches. This will force fresh data to be loaded from Square.
                                    </p>
                                    <form id="clearCacheForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="cacheType" class="form-label">Cache Type</label>
                                            <select class="form-select" id="cacheType" name="type">
                                                <option value="all">All Caches</option>
                                                <option value="transactions">Transactions Only</option>
                                                <option value="products">Product Costs Only</option>
                                                <option value="kitchen">Kitchen Screen Only</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-warning" id="clearCacheBtn">
                                            <i class="fas fa-broom me-2"></i>Clear Cache
                                        </button>
                                    </form>
                                    <div id="clearCacheResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Cache Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <button class="btn btn-info" id="checkStatusBtn">
                                        <i class="fas fa-sync me-2"></i>Check Status
                                    </button>
                                    <div id="statusResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pre-cache form
    document.getElementById('preCacheForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('preCacheBtn');
        const result = document.getElementById('preCacheResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Caching...';
        
        fetch('{{ route("cache.pre-cache") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                days: document.getElementById('days').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            } else {
                result.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }
        })
        .catch(error => {
            result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-2"></i>Pre-cache Transactions';
        });
    });
    
    // Clear cache form
    document.getElementById('clearCacheForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('clearCacheBtn');
        const result = document.getElementById('clearCacheResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Clearing...';
        
        fetch('{{ route("cache.clear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                type: document.getElementById('cacheType').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            } else {
                result.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }
        })
        .catch(error => {
            result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broom me-2"></i>Clear Cache';
        });
    });
    
    // Check status
    document.getElementById('checkStatusBtn').addEventListener('click', function() {
        const btn = document.getElementById('checkStatusBtn');
        const result = document.getElementById('statusResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
        
        fetch('{{ route("cache.status") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let statusHtml = '<div class="alert alert-success"><h6>Cache Status:</h6><ul class="mb-0">';
                for (const [key, value] of Object.entries(data.status)) {
                    statusHtml += `<li><strong>${key}:</strong> ${value}</li>`;
                }
                statusHtml += '</ul></div>';
                result.innerHTML = statusHtml;
            } else {
                result.innerHTML = `<div class="alert alert-danger">${data.status.error}</div>`;
            }
        })
        .catch(error => {
            result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-2"></i>Check Status';
        });
    });
});
</script>
@endpush
