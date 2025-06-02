@extends('layouts.admin')

@section('title', 'Daftar Pesanan')

@section('page-title', 'Daftar Pesanan')

@section('styles')
<style>
    .status-badge {
        padding: 8px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.3px;
        white-space: nowrap;
        text-transform: uppercase;
    }
    
    .order-card {
        transition: all 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        border: none;
    }
    
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    .stats-card {
        border-radius: 15px;
        padding: 20px;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    
    .stats-icon i {
        font-size: 24px;
        color: white;
    }
    
    .stats-info h3 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--dark-text);
    }
    
    .stats-info p {
        color: var(--light-text);
        margin: 0;
        font-size: 14px;
        font-weight: 500;
    }
    
    .stats-decoration {
        position: absolute;
        right: -20px;
        bottom: -20px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        z-index: 0;
    }
    
    .table-hover tbody tr {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 135, 178, 0.05);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .order-row td {
        vertical-align: middle;
        padding: 15px 10px;
    }
    
    .order-id {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .order-customer {
        font-weight: 500;
    }
    
    .order-amount {
        font-weight: 600;
        color: #333;
    }
    
    .filter-card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .filter-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border: none;
        color: white;
        font-weight: 600;
    }
    
    .filter-btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 72px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state p {
        font-size: 18px;
        color: #888;
        margin-bottom: 30px;
    }
    
    .pagination {
        margin-top: 30px;
    }
    
    .pagination .page-item .page-link {
        border-radius: 8px;
        margin: 0 3px;
        color: var(--primary-color);
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
    }
    
    .search-input {
        border-radius: 50px;
        padding-left: 20px;
        padding-right: 20px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .search-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(255, 135, 178, 0.2);
    }
    
    .form-select, .form-control {
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        padding: 10px 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    
    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(255, 135, 178, 0.2);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Order Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $waitingForPaymentCount }}</h3>
                    <p>Menunggu Pembayaran</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon bg-primary">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $processingCount }}</h3>
                    <p>Sedang Diproses</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon bg-info">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $shippingCount }}</h3>
                    <p>Sedang Dikirim</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $deliveredCount }}</h3>
                    <p>Selesai</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $cancelledCount }}</h3>
                    <p>Dibatalkan</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="stats-card bg-white">
                <div class="stats-icon" style="background: linear-gradient(45deg, #FF87B2, #D46A9F);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $orders->total() }}</h3>
                    <p>Total Pesanan</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card filter-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filter & Pencarian</h5>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-sync-alt me-1"></i> Reset
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control search-input border-start-0" id="search" name="search" value="{{ request('search') }}" placeholder="ID, Nama, Email...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status Pesanan</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="waiting_for_payment" {{ request('status') == 'waiting_for_payment' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Sedang Diproses</option>
                        <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Sedang Dikirim</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="payment_status" class="form-label">Status Pembayaran</label>
                    <select class="form-select" id="payment_status" name="payment_status">
                        <option value="">Semua</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Dibayar</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">Semua</option>
                        <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="midtrans" {{ request('payment_method') == 'midtrans' ? 'selected' : '' }}>Midtrans</option>
                        <option value="cod" {{ request('payment_method') == 'cod' ? 'selected' : '' }}>COD</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_range" class="form-label">Rentang Tanggal</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                        <span class="input-group-text bg-light">s/d</span>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary filter-btn">
                        <i class="fas fa-search me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card order-card">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white;">
            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Daftar Pesanan</h5>
            <span class="badge bg-white text-primary">{{ $orders->total() }} Pesanan</span>
        </div>
        <div class="card-body p-0">
            @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="py-3">Pelanggan</th>
                            <th class="py-3">Total</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Pembayaran</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3 text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr class="order-row" onclick="window.location.href='{{ route('admin.orders.show', $order->id) }}'">
                            <td class="px-4">
                                <span class="order-id">#{{ $order->id }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <div style="width: 35px; height: 35px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="order-customer">{{ $order->user ? $order->user->name : 'Pelanggan' }}</span>
                                        @if($order->user && $order->user->email)
                                            <div class="small text-muted">{{ $order->user->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="order-amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </td>
                            <td>
                                @php
                                    $statusClass = [
                                        'waiting_for_payment' => 'bg-warning text-dark',
                                        'processing' => 'bg-primary text-white',
                                        'shipping' => 'bg-info text-white',
                                        'delivered' => 'bg-success text-white',
                                        'cancelled' => 'bg-danger text-white'
                                    ][$order->status] ?? 'bg-secondary text-white';
                                    
                                    $statusLabel = [
                                        'waiting_for_payment' => 'Menunggu Pembayaran',
                                        'processing' => 'Sedang Diproses',
                                        'shipping' => 'Sedang Dikirim',
                                        'delivered' => 'Selesai',
                                        'cancelled' => 'Dibatalkan'
                                    ][$order->status] ?? 'Unknown';
                                    
                                    $statusIcon = [
                                        'waiting_for_payment' => 'fa-clock',
                                        'processing' => 'fa-box-open',
                                        'shipping' => 'fa-shipping-fast',
                                        'delivered' => 'fa-check-circle',
                                        'cancelled' => 'fa-times-circle'
                                    ][$order->status] ?? 'fa-question-circle';
                                @endphp
                                <span class="status-badge {{ $statusClass }}">
                                    <i class="fas {{ $statusIcon }} me-1"></i> {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $paymentClass = [
                                        'paid' => 'bg-success text-white',
                                        'pending' => 'bg-warning text-dark',
                                        'failed' => 'bg-danger text-white',
                                        'expired' => 'bg-secondary text-white',
                                        'refunded' => 'bg-info text-white'
                                    ][$order->payment_status] ?? 'bg-secondary text-white';
                                    
                                    $paymentLabel = [
                                        'paid' => 'Dibayar',
                                        'pending' => 'Pending',
                                        'failed' => 'Gagal',
                                        'expired' => 'Kedaluwarsa',
                                        'refunded' => 'Dikembalikan'
                                    ][$order->payment_status] ?? 'Unknown';
                                    
                                    $paymentIcon = [
                                        'paid' => 'fa-check',
                                        'pending' => 'fa-clock',
                                        'failed' => 'fa-times',
                                        'expired' => 'fa-calendar-times',
                                        'refunded' => 'fa-undo'
                                    ][$order->payment_status] ?? 'fa-question';
                                @endphp
                                <span class="status-badge {{ $paymentClass }}">
                                    <i class="fas {{ $paymentIcon }} me-1"></i> {{ $paymentLabel }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $order->created_at->format('d M Y') }}</div>
                                <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" onclick="event.stopPropagation();">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="d-flex justify-content-center py-4">
                {{ $orders->links() }}
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Tidak ada pesanan yang ditemukan</p>
                @if(request()->has('search') || request()->has('status') || request()->has('payment_status') || request()->has('payment_method') || request()->has('start_date') || request()->has('end_date'))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-sync-alt me-1"></i> Reset Filter
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animasi untuk card stats
        const statsCards = document.querySelectorAll('.stats-card');
        statsCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Auto-submit form when select filters change
        document.querySelectorAll('select[name="status"], select[name="payment_status"], select[name="payment_method"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
        
        // Date range validation
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && this.value > endDateInput.value) {
                endDateInput.value = this.value;
            }
        });
        
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && this.value < startDateInput.value) {
                startDateInput.value = this.value;
            }
        });
    });
</script>
@endpush 