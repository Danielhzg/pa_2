@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Pesanan</h3>
                <p class="text-muted">Kelola pesanan pelanggan Anda</p>
            </div>
            <button type="button" class="btn add-new-btn" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter me-2"></i> Filter Pesanan
            </button>
        </div>
    </div>

    <!-- Filter Summary -->
    @if(request('status') || request('start_date') || request('end_date'))
    <div class="alert custom-alert alert-info fade show mb-4" role="alert">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-2"></i>
                <strong>Filter aktif:</strong>
                @if(request('status'))
                    <span class="badge filter-badge me-2">Status: {{ ucfirst(request('status')) }}</span>
                @endif
                @if(request('start_date') || request('end_date'))
                    <span class="badge filter-badge">
                        Tanggal: 
                        {{ request('start_date', 'awal') }} 
                        sampai 
                        {{ request('end_date', 'sekarang') }}
                    </span>
                @endif
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i> Hapus Filter
            </a>
        </div>
    </div>
    @endif

    <!-- Order Stats -->
    <div class="row mb-4 dashboard-stats">
        <div class="col-md-3 mb-4">
            <div class="card stat-card pending-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container pending-icon">
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $pendingCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Menunggu</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card stat-card processing-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container processing-icon">
                        <i class="fas fa-spinner stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $processingCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Diproses</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card stat-card completed-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container completed-icon">
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $completedCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Selesai</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card stat-card cancelled-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container cancelled-icon">
                        <i class="fas fa-times-circle stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $cancelledCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Dibatalkan</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card table-card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Daftar Pesanan</h5>
                </div>
                <div class="col-auto">
                    <div class="d-flex">
                        <div class="search-box me-2">
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari pesanan...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(count($orders) > 0)
                <div class="table-responsive">
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr class="order-item">
                                    <td><span class="order-id">{{ $order->id }}</span></td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name">{{ $order->user->name ?? 'Guest' }}</span>
                                            <span class="customer-email">{{ $order->user->email ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="order-amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></td>
                                    <td>
                                        <select class="form-select form-select-sm order-status-select"
                                                data-order-id="{{ $order->id }}">
                                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>
                                                Menunggu
                                            </option>
                                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>
                                                Diproses
                                            </option>
                                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>
                                                Dikirim
                                            </option>
                                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>
                                                Selesai
                                            </option>
                                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>
                                                Dibatalkan
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="payment-info">
                                            <span class="payment-method">{{ ucfirst($order->payment_method) }}</span>
                                            <span class="payment-badge badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                                                {{ $order->payment_status == 'paid' ? 'Lunas' : 'Belum Lunas' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="order-date">{{ $order->created_at->format('d M Y H:i') }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn action-btn view-btn" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5>Tidak ada pesanan yang ditemukan</h5>
                    <p class="text-muted">Belum ada pesanan yang sesuai dengan filter yang Anda pilih</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.orders.index') }}" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Diproses</option>
                            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Dikirim</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                        </div>
                        <div class="col">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn add-new-btn">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Styling for order page */
    .content-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        color: var(--pink-dark);
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .add-new-btn {
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .add-new-btn:hover {
        background: linear-gradient(45deg, var(--pink-dark), var(--pink-primary));
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
    }
    
    .custom-alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        padding: 1rem;
    }
    
    .alert-info {
        background-color: rgba(0, 123, 255, 0.1);
        color: #0d6efd;
    }
    
    .filter-badge {
        background-color: var(--pink-dark);
        padding: 6px 12px;
        border-radius: 30px;
        font-weight: normal;
        font-size: 12px;
    }
    
    .stat-card {
        position: relative;
        transition: all 0.3s ease;
        background: white;
        border: 1px solid rgba(126, 87, 194, 0.1);
        border-radius: 15px;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--gradient-1);
    }
    
    .stat-icon-container {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Colored icon containers with white icons */
    .pending-icon {
        background-color: #FFC107;
    }
    
    .processing-icon {
        background-color: #17A2B8;
    }
    
    .completed-icon {
        background-color: #28A745;
    }
    
    .cancelled-icon {
        background-color: #DC3545;
    }
    
    .stat-icon {
        color: white;
        font-size: 24px;
    }
    
    .stat-value {
        color: var(--pink-dark);
        font-weight: 600;
        font-size: 1.8rem;
        margin-bottom: 0;
    }
    
    .stat-label {
        color: var(--text-secondary);
        font-size: 14px;
    }
    
    .table-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1rem 1.5rem;
    }
    
    .card-title {
        color: var(--pink-dark);
        font-weight: 600;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }
    
    .search-box input {
        padding-right: 30px;
        border-radius: 20px;
        border: 1px solid rgba(255,105,180,0.2);
    }
    
    .search-box input:focus {
        border-color: var(--pink-primary);
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .order-table {
        margin-bottom: 0;
    }
    
    .order-table thead th {
        background-color: rgba(255,105,180,0.05);
        color: var(--pink-dark);
        font-weight: 600;
        border: none;
        padding: 1rem 1.5rem;
    }
    
    .order-item {
        transition: all 0.2s;
    }
    
    .order-item:hover {
        background-color: rgba(255,105,180,0.03);
    }
    
    .order-id {
        font-weight: 600;
        color: var(--pink-dark);
    }
    
    .customer-info {
        display: flex;
        flex-direction: column;
    }
    
    .customer-name {
        font-weight: 500;
    }
    
    .customer-email {
        font-size: 12px;
        color: var(--text-secondary);
    }
    
    .order-amount {
        font-weight: 600;
        color: var(--pink-dark);
    }
    
    .payment-info {
        display: flex;
        flex-direction: column;
    }
    
    .payment-method {
        font-weight: 500;
    }
    
    .payment-badge {
        display: inline-block;
        margin-top: 5px;
        font-size: 11px;
        font-weight: normal;
    }
    
    .order-date {
        color: var(--text-secondary);
        font-size: 13px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        color: white;
    }
    
    .view-btn {
        background-color: var(--pink-dark);
    }
    
    .view-btn:hover {
        background-color: var(--pink-primary);
        color: white;
        transform: translateY(-2px);
    }
    
    .form-select {
        border-radius: 10px;
        border: 1px solid rgba(255,105,180,0.2);
    }
    
    .form-select:focus {
        border-color: var(--pink-primary);
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .order-status-select {
        padding: 5px 10px;
        font-size: 13px;
        border-radius: 5px;
        min-width: 120px;
    }
    
    .empty-state {
        padding: 3rem;
    }
    
    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,105,180,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 32px;
        color: var(--pink-dark);
    }
    
    /* Modal styling */
    .modal-content {
        border-radius: 15px;
        border: none;
    }
    
    .modal-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .modal-footer {
        border-top: 1px solid rgba(0,0,0,0.05);
    }
</style>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Order status update
        const statusSelects = document.querySelectorAll('.order-status-select');
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const status = this.value;
                
                // Get CSRF token from meta tag
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Send AJAX request to update status
                fetch(`/admin/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success notification
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert custom-alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <span>Status pesanan berhasil diperbarui menjadi <strong>${status}</strong></span>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        
                        // Insert alert before the first child of container-fluid
                        const container = document.querySelector('.container-fluid');
                        container.insertBefore(alertDiv, container.firstChild);
                        
                        // Automatically remove after 5 seconds
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 5000);
                    } else {
                        alert('Gagal memperbarui status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memperbarui status');
                });
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                const searchText = this.value.toLowerCase();
                const rows = document.querySelectorAll('.order-item');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush 