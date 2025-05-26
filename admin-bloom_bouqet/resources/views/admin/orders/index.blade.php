@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Pesanan</h3>
                <p class="text-muted">Kelola pesanan pelanggan Anda</p>
            </div>
        </div>
    </div>

    <!-- Order Stats -->
    <div class="row mb-4 dashboard-stats">
        <div class="col-md-2 mb-4">
            <div class="card stat-card pending-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container pending-icon">
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value waiting-count">{{ $waitingForPaymentCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Menunggu Pembayaran</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'waiting_for_payment']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-4">
            <div class="card stat-card processing-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container processing-icon">
                        <i class="fas fa-spinner stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value processing-count">{{ $processingCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Pesanan Diproses</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-4">
            <div class="card stat-card shipping-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container shipping-icon">
                        <i class="fas fa-truck stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value shipping-count">{{ $shippingCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Dalam Pengiriman</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'shipping']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-4">
            <div class="card stat-card completed-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container completed-icon">
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value delivered-count">{{ $deliveredCount ?? 0 }}</h2>
                        <p class="stat-label mb-0">Pesanan Selesai</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-4">
            <div class="card stat-card cancelled-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container cancelled-icon">
                        <i class="fas fa-times-circle stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value cancelled-count">{{ $cancelledCount ?? 0 }}</h2>
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
                                            <span class="customer-name">{{ $order->user->username ?? ($order->user->name ?? 'Pelanggan') }}</span>
                                            <span class="customer-email">{{ $order->user->email != 'guest@example.com' ? $order->user->email : '' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="order-amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></td>
                                    <td>
                                        <select class="form-select form-select-sm order-status-select"
                                                data-order-id="{{ $order->id }}"
                                                {{ $order->payment_status != 'paid' && $order->status != 'cancelled' ? 'disabled' : '' }}>
                                            <option value="waiting_for_payment" {{ $order->status == 'waiting_for_payment' ? 'selected' : '' }}>
                                                Menunggu Pembayaran
                                            </option>
                                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>
                                                Pesanan Diproses
                                            </option>
                                            <option value="shipping" {{ $order->status == 'shipping' ? 'selected' : '' }}>
                                                Dalam Pengiriman
                                            </option>
                                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>
                                                Pesanan Selesai
                                            </option>
                                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>
                                                Dibatalkan
                                            </option>
                                        </select>
                                        @if($order->payment_status != 'paid' && $order->status != 'cancelled')
                                            <div class="small text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Status dapat diubah setelah pembayaran
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="payment-info">
                                            <span class="payment-method">{{ ucfirst($order->payment_method) }}</span>
                                            <span class="payment-badge badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                                                {{ $order->payment_status == 'paid' ? 'Lunas' : 'Belum Lunas' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="order-date">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn action-btn detail-btn order-details-btn" 
                                                   onclick="loadOrderDetail({{ $order->id }})" title="Lihat Detail">
                                                <i class="fas fa-info-circle me-1"></i> Detail
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5>Tidak ada pesanan yang ditemukan</h5>
                    <p class="text-muted mb-4">Belum ada pesanan yang tercatat dalam sistem</p>
                </div>
            @endif
        </div>
    </div>
</div>

@include('admin.orders.order_detail_modal')

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
    
    .shipping-icon {
        background-color: #9C27B0; /* Purple color for shipping */
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
        width: auto;
        min-width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        color: white;
        margin: 0 2px;
        padding: 0 10px;
    }
    
    .detail-btn {
        background-color: var(--pink-dark);
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .detail-btn:hover {
        background-color: var(--pink-primary);
        color: white;
        transform: translateY(-2px);
    }
    
    .quick-view-btn {
        background-color: #6c757d;
    }
    
    .quick-view-btn:hover {
        background-color: #5a6268;
        color: white;
        transform: translateY(-2px);
    }
    
    .action-buttons {
        display: flex;
        align-items: center;
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

    /* Styling for specific status cards */
    .pending-card::before {
        background: linear-gradient(45deg, #FFC107, #FF9800);
    }
    
    .processing-card::before {
        background: linear-gradient(45deg, #17A2B8, #0097A7);
    }
    
    .shipping-card::before {
        background: linear-gradient(45deg, #9C27B0, #7B1FA2);
    }
    
    .completed-card::before {
        background: linear-gradient(45deg, #28A745, #218838);
    }
    
    .cancelled-card::before {
        background: linear-gradient(45deg, #DC3545, #C82333);
    }

    /* Animation for new orders */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .new-order-indicator {
        animation: pulse 1.5s infinite;
        background-color: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
    
    /* Unread order highlighting */
    tr.unread-order {
        background-color: rgba(255, 135, 178, 0.1) !important;
        font-weight: 500;
        position: relative;
    }
    
    tr.unread-order::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: var(--primary-color);
    }

    /* Modal detail styling */
    .modal-dialog-scrollable {
        max-height: 90vh;
    }
    
    .bg-pink-gradient {
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
    }
    
    .order-detail-heading {
        color: var(--pink-dark);
        font-weight: 600;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        margin-bottom: 15px;
    }
    
    .order-detail-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .order-info-item {
        display: flex;
        margin-bottom: 10px;
        font-size: 0.95rem;
    }
    
    .order-info-item .label {
        flex: 0 0 140px;
        color: #6c757d;
        font-weight: 500;
    }
    
    .order-info-item .value {
        flex: 1;
    }
    
    .text-pink {
        color: var(--pink-dark) !important;
    }
    
    .btn-pink {
        background-color: var(--pink-primary);
        color: white;
    }
    
    .btn-pink:hover {
        background-color: var(--pink-dark);
        color: white;
    }
    
    /* Additional styling for updating state */
    tr.updating {
        background-color: rgba(0, 123, 255, 0.05) !important;
        transition: background-color 0.3s;
    }
    
    tr.updating select,
    tr.updating button {
        opacity: 0.7;
        pointer-events: none;
    }
    
    /* Notification animation */
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    #notification-container .alert {
        animation: slideInRight 0.3s forwards;
    }

    /* Enhanced modal styling */
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .modal-header.bg-pink-gradient {
        background: linear-gradient(45deg, #FF69B4, #FF1493);
        padding: 1.25rem 1.5rem;
    }

    .modal-title {
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .order-detail-card {
        transition: all 0.3s;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .order-detail-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }

    .order-detail-heading {
        color: var(--pink-dark);
        font-weight: 600;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(255,105,180,0.2);
        margin-bottom: 15px;
    }

    .order-info-item {
        display: flex;
        margin-bottom: 12px;
        font-size: 0.95rem;
        align-items: flex-start;
    }

    .order-info-item .label {
        flex: 0 0 140px;
        color: #6c757d;
        font-weight: 500;
    }

    .order-info-item .value {
        flex: 1;
        line-height: 1.4;
    }

    .spinner-grow {
        width: 3rem;
        height: 3rem;
    }

    /* Enhanced button styling */
    .action-btn.detail-btn {
        background: linear-gradient(45deg, #FF69B4, #FF1493);
        border: none;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(255,105,180,0.3);
        transition: all 0.3s ease;
    }

    .action-btn.detail-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(255,105,180,0.4);
    }

    .action-btn.detail-btn:active {
        transform: translateY(-1px);
    }

    /* Animation for modal content */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #orderDetailsContent {
        animation: fadeInUp 0.4s ease-out;
    }

    /* Table styling in modal */
    .modal .table {
        margin-bottom: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .modal .table th {
        background-color: rgba(255,105,180,0.1);
        color: var(--pink-dark);
        font-weight: 600;
        border: none;
    }

    .modal .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,0.02);
    }

    .modal .table-hover tbody tr:hover {
        background-color: rgba(255,105,180,0.05);
    }

    .modal tfoot {
        font-weight: 500;
    }

    .modal tfoot tr:last-child {
        font-weight: 700;
    }

    /* Button in modal footer */
    .btn-pink {
        background: linear-gradient(45deg, #FF69B4, #FF1493);
        border: none;
        color: white;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(255,105,180,0.3);
        transition: all 0.3s ease;
    }

    .btn-pink:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255,105,180,0.4);
        color: white;
    }

    .btn-pink:active {
        transform: translateY(0);
    }

    /* Fix modal display issues */
    .modal {
        z-index: 9999 !important;
    }
    
    .modal-backdrop {
        z-index: 9998 !important;
    }
    
    /* Override any conflicting styles */
    #orderDetailModal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: none;
    }
    
    #orderDetailModal.show {
        display: block !important;
    }
    
    .modal-open {
        overflow: hidden;
        padding-right: 0 !important;
    }

    /* Debug outline for modal elements */
    .debug-outline {
        outline: 2px solid red !important;
    }

    /* Enhanced Section Styling */
    .order-detail-section {
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--pink-dark);
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    /* Enhanced Card Styling */
    .order-detail-card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: none;
        transition: box-shadow 0.3s ease;
    }

    .order-detail-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .order-detail-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: rgba(255,105,180,0.03);
    }

    /* Enhanced Info Item Styling */
    .order-info-item {
        display: flex;
        margin-bottom: 12px;
        font-size: 0.95rem;
    }

    .order-info-item .label {
        flex: 0 0 140px;
        color: #6c757d;
        font-weight: 500;
    }

    .order-info-item .value {
        flex: 1;
        line-height: 1.4;
    }

    /* Status Badge Styling */
    #modal-order-status-badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.7rem;
        border-radius: 6px;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .order-info-item {
            flex-direction: column;
        }
        
        .order-info-item .label {
            flex: 0 0 100%;
            margin-bottom: 4px;
        }
    }
</style>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug function to check modal visibility
        function debugModal(modalElement) {
            console.group('Modal Debug Info');
            console.log('Modal element:', modalElement);
            console.log('Display:', window.getComputedStyle(modalElement).display);
            console.log('Z-index:', window.getComputedStyle(modalElement).zIndex);
            console.log('Position:', window.getComputedStyle(modalElement).position);
            console.log('Visibility:', window.getComputedStyle(modalElement).visibility);
            console.log('Opacity:', window.getComputedStyle(modalElement).opacity);
            console.groupEnd();
        }

        // Order status update
        const statusSelects = document.querySelectorAll('.order-status-select');
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const status = this.value;
                const originalValue = this.getAttribute('data-original-value') || this.value;
                
                // Get CSRF token from meta tag
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Show loading indicator
                const row = this.closest('tr');
                row.classList.add('updating');
                
                // Send AJAX request to update status
                fetch(`/admin/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Error updating status');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Store original value
                        this.setAttribute('data-original-value', status);
                        
                        // Show success notification
                        showNotification('success', 'Status pesanan berhasil diperbarui');
                        
                        // Update dashboard stats
                        refreshOrderStats();
                    } else {
                        // Revert to original value on error
                        this.value = originalValue;
                        showNotification('error', data.message || 'Gagal memperbarui status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Revert to original value on error
                    this.value = originalValue;
                    showNotification('error', error.message || 'Terjadi kesalahan saat memperbarui status');
                })
                .finally(() => {
                    // Remove loading indicator
                    row.classList.remove('updating');
                });
            });
            
            // Store original value on load
            select.setAttribute('data-original-value', select.value);
        });
        
        // Real-time order updates using polling
        setInterval(checkForNewOrders, 10000); // Check every 10 seconds
        
        // Function to check for new orders and payment status changes
        function checkForNewOrders() {
            fetch('{{ route('admin.orders.check-new') }}')
                .then(response => response.json())
                .then(data => {
                    let shouldReload = false;
                    
                    if (data.new_orders_count > 0) {
                        // Show notification
                        showNotification('info', `Ada ${data.new_orders_count} pesanan baru`, true);
                        shouldReload = true;
                    }
                    
                    if (data.payment_status_changed_count > 0) {
                        // Show notification about payment status changes
                        showNotification('success', `${data.payment_status_changed_count} pesanan telah dibayar dan diproses`, true);
                        shouldReload = true;
                    }
                    
                    if (shouldReload) {
                        // Refresh stats
                        refreshOrderStats();
                        
                        // Play notification sound if available
                        try {
                            const notificationSound = new Audio('{{ asset('sounds/notification.mp3') }}');
                            notificationSound.play();
                        } catch (e) {
                            console.error('Error playing notification sound:', e);
                        }
                        
                        // Reload the page to show updates
                        setTimeout(() => window.location.reload(), 3000);
                    }
                })
                .catch(error => {
                    console.error('Error checking for new orders:', error);
                });
        }
        
        // Function to refresh order stats
        function refreshOrderStats() {
            fetch('{{ route('admin.orders.stats') }}')
                .then(response => response.json())
                .then(data => {
                    // Update stats if elements exist
                    if (document.querySelector('.waiting-count')) {
                        document.querySelector('.waiting-count').textContent = data.waiting_for_payment_orders;
                    }
                    if (document.querySelector('.processing-count')) {
                        document.querySelector('.processing-count').textContent = data.processing_orders;
                    }
                    if (document.querySelector('.shipping-count')) {
                        document.querySelector('.shipping-count').textContent = data.shipping_orders;
                    }
                    if (document.querySelector('.delivered-count')) {
                        document.querySelector('.delivered-count').textContent = data.delivered_orders;
                    }
                    if (document.querySelector('.cancelled-count')) {
                        document.querySelector('.cancelled-count').textContent = data.cancelled_orders;
                    }
                })
                .catch(error => {
                    console.error('Error refreshing order stats:', error);
                });
        }
        
        // Function to show notification
        function showNotification(type, message, isAutoHide = true) {
            // Create notification container if it doesn't exist
            if (!document.getElementById('notification-container')) {
                const container = document.createElement('div');
                container.id = 'notification-container';
                container.style.position = 'fixed';
                container.style.top = '20px';
                container.style.right = '20px';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
            }
            
            // Create notification
            const notificationId = 'notification-' + Date.now();
            const notification = document.createElement('div');
            notification.id = notificationId;
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.style.minWidth = '300px';
            notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            notification.style.marginBottom = '10px';
            
            // Set icon based on type
            let icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            if (type === 'error' || type === 'danger') {
                icon = 'exclamation-circle';
                type = 'danger';
            }
            if (type === 'warning') icon = 'exclamation-triangle';
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${icon} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Add to container
            document.getElementById('notification-container').appendChild(notification);
            
            // Auto-hide after 5 seconds if specified
            if (isAutoHide) {
                setTimeout(() => {
                    const alert = document.getElementById(notificationId);
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            }
        }
        
        // Order details modal functionality
        const orderDetailsBtns = document.querySelectorAll('.order-details-btn');
        const orderDetailsModal = document.getElementById('orderDetailsModal');
        
        if (orderDetailsModal && orderDetailsBtns.length > 0) {
            // Debug modal element
            console.log('Order details modal found:', orderDetailsModal);
            debugModal(orderDetailsModal);
            
            // Make sure Bootstrap is available
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap is not loaded. Using direct DOM manipulation for modal.');
                
                // Manual modal handling without Bootstrap
                orderDetailsBtns.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const orderId = this.getAttribute('data-order-id');
                        console.log('Opening modal for order ID:', orderId);
                        
                        // Show modal manually
                        orderDetailsModal.style.display = 'block';
                        orderDetailsModal.classList.add('show');
                        document.body.classList.add('modal-open');
                        
                        // Create backdrop if it doesn't exist
                        if (!document.querySelector('.modal-backdrop')) {
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                        }
                        
                        // Continue with loading data...
                        handleOrderDetailsDisplay(orderId);
                    });
                });
                
                // Close button functionality
                const closeButtons = orderDetailsModal.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        orderDetailsModal.style.display = 'none';
                        orderDetailsModal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                        
                        // Remove backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                    });
                });
                
                // Close on click outside
                orderDetailsModal.addEventListener('click', function(e) {
                    if (e.target === orderDetailsModal) {
                        orderDetailsModal.style.display = 'none';
                        orderDetailsModal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                        
                        // Remove backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                    }
                });
            } else {
                console.log('Using Bootstrap for modal handling');
                // Initialize Bootstrap modal
                let bsModal;
                try {
                    bsModal = new bootstrap.Modal(orderDetailsModal);
                    console.log('Bootstrap modal initialized successfully');
                } catch (error) {
                    console.error('Error initializing Bootstrap modal:', error);
                }
                
                orderDetailsBtns.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const orderId = this.getAttribute('data-order-id');
                        console.log('Opening modal for order ID:', orderId);
                        
                        // Show modal using Bootstrap if available
                        if (bsModal) {
                            bsModal.show();
                            console.log('Modal shown using Bootstrap');
                        } else {
                            // Fallback if Bootstrap modal object isn't available
                            orderDetailsModal.classList.add('show');
                            orderDetailsModal.style.display = 'block';
                            document.body.classList.add('modal-open');
                            
                            // Create backdrop
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                            
                            console.log('Modal shown using fallback method');
                        }
                        
                        // Debug modal after showing
                        setTimeout(() => {
                            debugModal(orderDetailsModal);
                        }, 100);
                        
                        // Continue with loading data...
                        handleOrderDetailsDisplay(orderId);
                    });
                });
            }
            
            // Handle modal events
            orderDetailsModal.addEventListener('hidden.bs.modal', function () {
                const orderDetailsContent = document.getElementById('orderDetailsContent');
                const orderDetailsLoading = document.getElementById('orderDetailsLoading');
                
                if (orderDetailsContent && orderDetailsLoading) {
                    // Reset modal content when closed
                    orderDetailsContent.style.display = 'none';
                    orderDetailsLoading.style.display = 'flex';
                }
            });
        } else {
            console.error('Order details modal or buttons not found');
        }
        
        // Function to handle order details display
        function handleOrderDetailsDisplay(orderId) {
            const orderDetailsLoading = document.getElementById('orderDetailsLoading');
            const orderDetailsContent = document.getElementById('orderDetailsContent');
            const viewFullOrderBtn = document.getElementById('viewFullOrderBtn');
            
            if (!orderDetailsLoading || !orderDetailsContent) {
                console.error('Modal content elements not found');
                return;
            }
            
            // Show loading, hide content
            orderDetailsLoading.style.display = 'flex';
            orderDetailsContent.style.display = 'none';
            
            // Update view full order button URL
            if (viewFullOrderBtn) {
                viewFullOrderBtn.href = `/admin/orders/${orderId}`;
            }
            
            // Set order number in title
            const orderNumberSpan = document.getElementById('modal-order-number');
            if (orderNumberSpan) {
                orderNumberSpan.textContent = orderId;
            }
            
            // Fetch order details
            const apiUrl = `/admin/orders/${orderId}/api`;
            console.log('Fetching order details from:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Network response error: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Order data received:', data);
                    
                    // Fill modal with order details
                    populateOrderDetailsModal(data);
                    
                    // Hide loading, show content with slight delay for animation
                    setTimeout(() => {
                        orderDetailsLoading.style.display = 'none';
                        orderDetailsContent.style.display = 'block';
                        console.log('Order details content displayed');
                    }, 300);
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    orderDetailsLoading.style.display = 'none';
                    orderDetailsContent.innerHTML = `
                        <div class="alert alert-danger m-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Terjadi kesalahan saat memuat detail pesanan: ${error.message}
                        </div>
                    `;
                    orderDetailsContent.style.display = 'block';
                });
        }
        
        // Function to populate order details modal
        function populateOrderDetailsModal(data) {
            try {
                // Check if required elements exist
                const requiredElements = [
                    'modal-order-id', 
                    'modal-order-date', 
                    'modal-order-status',
                    'modal-order-status-badge',
                    'modal-payment-method',
                    'modal-payment-status',
                    'modal-order-total',
                    'modal-customer-username',
                    'modal-customer-name',
                    'modal-customer-email',
                    'modal-customer-phone',
                    'modal-shipping-address',
                    'modal-order-items',
                    'modal-order-totals'
                ];
                
                let missingElements = [];
                for (const elementId of requiredElements) {
                    const element = document.getElementById(elementId);
                    if (!element) {
                        console.error(`Required element #${elementId} not found in the modal`);
                        missingElements.push(elementId);
                    }
                }
                
                if (missingElements.length > 0) {
                    throw new Error(`Missing required elements: ${missingElements.join(', ')}`);
                }
                
                // Set order details
                document.getElementById('modal-order-id').textContent = data.order_id || data.id;
                document.getElementById('modal-order-date').textContent = data.created_at;
                
                // Set status with appropriate badge
                let statusBadgeClass = 'secondary';
                switch(data.status) {
                    case 'waiting_for_payment': statusBadgeClass = 'warning'; break;
                    case 'processing': statusBadgeClass = 'info'; break;
                    case 'shipping': statusBadgeClass = 'primary'; break;
                    case 'delivered': statusBadgeClass = 'success'; break;
                    case 'cancelled': statusBadgeClass = 'danger'; break;
                }
                document.getElementById('modal-order-status').innerHTML = 
                    `<span class="badge bg-${statusBadgeClass}">${data.status_label}</span>`;
                    
                // Also set the header badge
                const statusBadge = document.getElementById('modal-order-status-badge');
                statusBadge.className = `badge bg-${statusBadgeClass}`;
                statusBadge.textContent = data.status_label;
                
                // Set payment details
                document.getElementById('modal-payment-method').textContent = 
                    data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1) : '-';
                
                let paymentStatusBadgeClass = 'secondary';
                switch(data.payment_status) {
                    case 'pending': paymentStatusBadgeClass = 'warning'; break;
                    case 'paid': paymentStatusBadgeClass = 'success'; break;
                    case 'failed': paymentStatusBadgeClass = 'danger'; break;
                    case 'expired': paymentStatusBadgeClass = 'secondary'; break;
                    case 'refunded': paymentStatusBadgeClass = 'info'; break;
                }
                document.getElementById('modal-payment-status').innerHTML = 
                    `<span class="badge bg-${paymentStatusBadgeClass}">${data.payment_status_label || (data.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas')}</span>`;
                
                // Customer info - always show username
                document.getElementById('modal-customer-username').textContent = data.user.username || 'Pelanggan';
                document.getElementById('modal-customer-name').textContent = data.user.full_name || '-';
                document.getElementById('modal-customer-email').textContent = data.user.email || '-';
                document.getElementById('modal-customer-phone').textContent = data.user.phone || data.phone_number || '-';
                document.getElementById('modal-shipping-address').textContent = data.shipping_address || 'N/A';
                
                // Order items
                const itemsContainer = document.getElementById('modal-order-items');
                itemsContainer.innerHTML = '';
                
                // Order totals calculation
                let subtotal = 0;
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        const itemTotal = item.subtotal || (parseFloat(item.price) * item.quantity);
                        subtotal += itemTotal;
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <div class="d-flex align-items-center">
                                    ${item.image ? `<img src="${item.image}" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">` : ''}
                                    <div class="product-name fw-medium">${item.name}</div>
                                </div>
                            </td>
                            <td>Rp ${parseFloat(item.price).toLocaleString('id-ID')}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">Rp ${itemTotal.toLocaleString('id-ID')}</td>
                        `;
                        itemsContainer.appendChild(row);
                    });
                    
                    // Add totals to footer
                    const totalsFooter = document.getElementById('modal-order-totals');
                    const shippingCost = parseFloat(data.shipping_cost || 0);
                    
                    totalsFooter.innerHTML = `
                        <tr>
                            <td colspan="3" class="text-end fw-medium">Subtotal:</td>
                            <td class="text-end">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-medium">Biaya Pengiriman:</td>
                            <td class="text-end">Rp ${shippingCost.toLocaleString('id-ID')}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td class="text-end fw-bold text-pink">Rp ${parseFloat(data.total_amount).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                } else {
                    itemsContainer.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-box-open text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">Tidak ada item dalam pesanan ini</p>
                            </td>
                        </tr>
                    `;
                    
                    // Empty footer
                    document.getElementById('modal-order-totals').innerHTML = '';
                }
                
                document.getElementById('modal-order-total').textContent = 
                    `Rp ${parseFloat(data.total_amount).toLocaleString('id-ID')}`;
                
                console.log('Modal populated successfully');
            } catch (error) {
                console.error('Error populating modal:', error);
                document.getElementById('orderDetailsContent').innerHTML = `
                    <div class="alert alert-danger m-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Terjadi kesalahan saat memuat detail pesanan: ${error.message}
                    </div>
                `;
            }
        }
    });
</script>
@endpush 