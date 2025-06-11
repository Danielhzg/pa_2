@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/order-detail.css') }}">
<style>
    /* Admin-specific styles */
    .admin-order-detail-page .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        border: none !important;
        margin-bottom: 25px !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
    }
    
    .admin-order-detail-page .card:hover {
        transform: translateY(-5px) !important;
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2) !important;
    }
    
    .admin-order-detail-page .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border: none !important;
    }
    
    .admin-order-detail-page .card-header i {
        margin-right: 8px !important;
        opacity: 0.8 !important;
    }
    
    .admin-order-detail-page .card-title {
        margin-bottom: 0 !important;
        font-weight: 600 !important;
        font-size: 1.1rem !important;
    }
    
    .admin-order-detail-page .order-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 15px !important;
        padding: 30px !important;
        box-shadow: 0 10px 25px rgba(78, 115, 223, 0.2) !important;
    }
    
    .admin-order-detail-page .action-button {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.2);
        border-radius: 8px !important;
        padding: 12px 20px !important;
        font-weight: 600 !important;
        transition: all 0.3s !important;
    }
    
    .admin-order-detail-page .action-button:hover {
        box-shadow: 0 8px 20px rgba(78, 115, 223, 0.3);
        transform: translateY(-3px) !important;
    }
    
    .admin-order-detail-page .timeline-marker {
        border-color: #4e73df;
    }
    
    .admin-order-detail-page .timeline-marker.active {
        background-color: #4e73df;
    }
    
    .admin-order-detail-page .product-price {
        color: #4e73df;
        font-size: 1.1rem !important;
        font-weight: 600 !important;
    }
    
    .admin-order-detail-page .summary-total .summary-value {
        color: #4e73df;
        font-size: 1.2rem !important;
        font-weight: 700 !important;
    }
    
    .admin-order-detail-page .status-update-form {
        margin-top: 15px;
    }
    
    /* Dropdown styling */
    .admin-order-detail-page .form-select {
        padding: 12px !important;
        border-radius: 10px !important;
        font-size: 1rem !important;
        border: 1px solid #e3e6f0 !important;
        transition: all 0.3s !important;
        background-size: 16px 12px !important;
    }
    
    .admin-order-detail-page .form-select:focus {
        border-color: #4e73df !important;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25) !important;
    }
    
    .admin-order-detail-page .form-label {
        font-weight: 600 !important;
        color: #333 !important;
    }
    
    .admin-order-detail-page .additional-info-container {
        background-color: #f8f9fc !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 10px !important;
    }
    
    .admin-order-detail-page .customer-info-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }
    
    .admin-order-detail-page .customer-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #4e73df;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 15px;
    }
    
    .admin-order-detail-page .payment-info-box {
        background-color: #f0f9ff;
        border-left: 4px solid #4e73df;
        padding: 20px !important;
        border-radius: 10px !important;
        margin-bottom: 20px;
    }
    
    .admin-order-detail-page .shipping-info-box {
        background-color: #f8f9fc;
        border-left: 4px solid #36b9cc;
        padding: 20px !important;
        border-radius: 10px !important;
    }
    
    .admin-order-detail-page .product-item {
        border-radius: 8px !important;
        padding: 15px !important;
        margin-bottom: 15px !important;
        background-color: #f9f9f9 !important;
        transition: all 0.3s !important;
    }
    
    .admin-order-detail-page .product-item:hover {
        background-color: #f0f0f0 !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important;
    }
    
    .admin-order-detail-page .product-image {
        border-radius: 8px !important;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1) !important;
    }
    
    .admin-order-detail-page .info-group {
        margin-bottom: 20px !important;
    }
    
    .admin-order-detail-page .info-label {
        font-size: 0.9rem !important;
        color: #6c757d !important;
        margin-bottom: 8px !important;
        font-weight: 500 !important;
    }
    
    .admin-order-detail-page .info-value {
        font-weight: 600 !important;
        color: #333 !important;
    }
    
    .admin-order-detail-page .order-progress {
        margin: 30px 0 !important;
        position: relative !important;
        height: 5px !important;
        background-color: #e9ecef !important;
        border-radius: 10px !important;
        overflow: hidden !important;
    }
    
    .admin-order-detail-page .order-progress-bar {
        height: 100% !important;
        background: linear-gradient(90deg, #4e73df, #224abe) !important;
        border-radius: 10px !important;
        transition: width 0.5s ease !important;
    }
    
    /* Status Option Cards Styling */
    .status-options-container {
        margin-bottom: 20px !important;
    }
    
    .status-option-card {
        position: relative;
        border: 2px solid #e3e6f0;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        height: 100%;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background-color: #fff;
    }
    
    .status-option-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .status-option-card.active {
        border-color: #4e73df;
        background-color: #f8f9fc;
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.2);
    }
    
    .status-radio {
        position: absolute;
        opacity: 0;
        top: 0;
        left: 0;
    }
    
    .status-label {
        cursor: pointer;
        display: block;
        width: 100%;
        margin-bottom: 0;
    }
    
    .status-icon {
        font-size: 24px;
        margin-bottom: 10px;
        color: #4e73df;
    }
    
    .status-option-card.active .status-icon {
        color: #2e59d9;
    }
    
    .status-text {
        font-weight: 600;
        color: #5a5c69;
    }
    
    .status-option-card.active .status-text {
        color: #2e59d9;
    }
    
    .status-option-card[data-status="processing"] .status-icon {
        color: #4e73df;
    }
    
    .status-option-card[data-status="shipping"] .status-icon {
        color: #36b9cc;
    }
    
    .status-option-card[data-status="delivered"] .status-icon {
        color: #1cc88a;
    }
    
    .status-option-card[data-status="cancelled"] .status-icon {
        color: #e74a3b;
    }
    
    /* Button styling to match product page */
    .text-emphasis {
        color: #ffffff;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        position: relative;
        transition: all 0.3s ease;
    }
    
    /* Add a subtle underline animation on hover */
    .text-emphasis::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: #FFE5EE;
        transition: width 0.3s ease;
    }
    
    .save-btn:hover .text-emphasis::after {
        width: 100%;
    }
    
    .save-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .save-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
    }
</style>
@endpush

@section('content')
<div class="container-fluid admin-order-detail-page">
    @if(!isset($order) || !$order)
    <div class="alert alert-danger">
        Pesanan tidak ditemukan atau telah dihapus.
    </div>
    @else
    
    <!-- Order Header -->
    <div class="order-header">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center">
                    <div>
                        <h1 class="order-id mb-1 text-white">Pesanan #{{ $order->id }}</h1>
                        <p class="order-date mb-0 text-white-50">Dibuat pada {{ $order->created_at->format('d F Y, H:i') }} WIB</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <span class="order-status-badge bg-{{ 
                    $order->status == 'waiting_for_payment' ? 'warning' : 
                    ($order->status == 'processing' ? 'primary' : 
                    ($order->status == 'shipping' ? 'info' : 
                    ($order->status == 'delivered' ? 'success' : 
                    ($order->status == 'cancelled' ? 'danger' : 'secondary')))) 
                }}">
                    <i class="fas {{ 
                        $order->status == 'waiting_for_payment' ? 'fa-clock' : 
                        ($order->status == 'processing' ? 'fa-box-open' : 
                        ($order->status == 'shipping' ? 'fa-truck' : 
                        ($order->status == 'delivered' ? 'fa-check-circle' : 
                        ($order->status == 'cancelled' ? 'fa-times-circle' : 'fa-info-circle')))) 
                    }} me-1"></i>
                    {{ $order->status_label }}
                </span>
                <div class="mt-2">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Pesanan
                    </a>
                </div>
                        </div>
                        </div>
        
        <!-- Order Progress Bar -->
        @if($order->status != 'cancelled')
        <div class="mt-4">
            <div class="row mb-2">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between text-white-50">
                        <span class="small">Menunggu Pembayaran</span>
                        <span class="small">Diproses</span>
                        <span class="small">Pengiriman</span>
                        <span class="small">Selesai</span>
                    </div>
                </div>
            </div>
            <div class="order-progress">
                <div class="order-progress-bar" style="width: 
                    {{ $order->status == 'waiting_for_payment' ? '25%' : 
                    ($order->status == 'processing' ? '50%' : 
                    ($order->status == 'shipping' ? '75%' : 
                    ($order->status == 'delivered' ? '100%' : '0%'))) }}">
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <div class="row">
        <!-- Order Items & Summary -->
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        <i class="fas fa-shopping-bag me-2"></i> Item Pesanan
                    </h5>
                    <span class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                        <i class="fas {{ $order->payment_status == 'paid' ? 'fa-check-circle' : 'fa-clock' }} me-1"></i>
                        {{ $order->payment_status_label }}
                    </span>
                </div>
                <div class="card-body">
                    @php
                        // Gunakan $orderItems yang dikirim dari controller
                        // $orderItems = $order->getFormattedItems();
                    @endphp
                    
                    @if(count($orderItems) > 0)
                        @foreach($orderItems as $item)
                            <div class="product-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        @if(isset($item['product']) && $item['product'] && method_exists($item['product'], 'getPrimaryImage') && $item['product']->getPrimaryImage())
                                            <img src="{{ asset('storage/' . $item['product']->getPrimaryImage()) }}" 
                                                alt="{{ $item['product']->name }}" class="product-image" width="80" height="80">
                                        @elseif(isset($item['image']) && $item['image'])
                                            <img src="{{ asset('storage/' . $item['image']) }}" 
                                                alt="{{ $item['name'] ?? 'Produk' }}" class="product-image" width="80" height="80">
                                        @else
                                            <div class="empty-image-placeholder" style="width: 80px; height: 80px;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="col">
                                        <div class="product-details">
                                            <h5 class="product-name">{{ $item['name'] ?? 'Produk tidak tersedia' }}</h5>
                                            <div class="product-price">Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</div>
                                            <div class="product-quantity">Jumlah: {{ $item['quantity'] ?? 1 }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-auto">
                                        <div class="product-subtotal text-end">
                                            <div class="mb-1 text-muted small">Subtotal:</div>
                                            <strong>Rp {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="order-summary mt-4 p-4 bg-light rounded">
                            <div class="summary-item d-flex justify-content-between mb-3">
                                <div class="summary-label">Subtotal</div>
                                <div class="summary-value">Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="summary-item d-flex justify-content-between mb-3">
                                <div class="summary-label">Biaya Pengiriman</div>
                                <div class="summary-value">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="summary-item summary-total d-flex justify-content-between border-top pt-3">
                                <div class="summary-label">Total</div>
                                <div class="summary-value">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                            </div>
                            <h5>Tidak ada item dalam pesanan ini</h5>
                            <p class="text-muted">Pesanan ini tidak memiliki item yang tercatat</p>
                        </div>
                    @endif
                        </div>
                    </div>
                    
            <!-- Order Timeline -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-history me-2"></i> Status Pesanan
                    </h5>
                </div>
                <div class="card-body">
                    @if($order->status == 'cancelled')
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <div class="cancelled-status-icon me-3">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Pesanan Dibatalkan</h5>
                                <p class="mb-0">Pesanan ini telah dibatalkan dan tidak dapat diproses lebih lanjut.</p>
                                @if($order->status_updated_at)
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Dibatalkan pada: 
                                        @if(is_string($order->status_updated_at))
                                            {{ $order->status_updated_at }}
                                        @elseif(is_object($order->status_updated_at) && method_exists($order->status_updated_at, 'format'))
                                            {{ $order->status_updated_at->format('d M Y, H:i') }}
                                        @else
                                            {{ $order->updated_at->format('d M Y, H:i') }}
                                        @endif
                                    </small>
                                @endif
                            </div>
                        </div>
                    @else
                    <div class="delivery-progress mb-4">
                        <div class="progress-timeline">
                            <div class="progress-line" style="width: 
                                {{ $order->status == 'waiting_for_payment' ? '20%' : 
                                ($order->status == 'processing' ? '40%' : 
                                ($order->status == 'shipping' ? '70%' : 
                                ($order->status == 'delivered' ? '100%' : '0%'))) }}">
                            </div>
                            
                            <div class="timeline-node {{ in_array($order->status, ['waiting_for_payment', 'processing', 'shipping', 'delivered']) ? 'completed' : '' }}">
                                <div class="node-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="node-label">Pesanan Dibuat</div>
                            </div>
                            
                            <div class="timeline-node {{ in_array($order->status, ['processing', 'shipping', 'delivered']) ? 'completed' : ($order->status == 'waiting_for_payment' ? 'active' : '') }}">
                                <div class="node-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="node-label">Diproses</div>
                            </div>
                            
                            <div class="timeline-node {{ in_array($order->status, ['shipping', 'delivered']) ? 'completed' : ($order->status == 'processing' ? 'active' : '') }}">
                                <div class="node-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="node-label">Dikirim</div>
                            </div>
                            
                            <div class="timeline-node {{ $order->status == 'delivered' ? 'completed' : ($order->status == 'shipping' ? 'active' : '') }}">
                                <div class="node-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="node-label">Sampai Tujuan</div>
                            </div>
                            
                            <div class="timeline-node {{ $order->status == 'delivered' ? 'completed' : '' }}">
                                <div class="node-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="node-label">Selesai</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="shopee-timeline mt-5">
                        <h6 class="mb-4">Perubahan Status Pesanan</h6>
                        <div class="timeline-container">
                            @php
                                // Helper function to ensure date is properly formatted
                                function formatDate($date) {
                                    if (is_object($date) && method_exists($date, 'format')) {
                                        return $date->format('d M Y, H:i');
                                    } elseif (is_string($date)) {
                                        return $date;
                                    }
                                    return null;
                                }

                                $statusTimeline = [
                                    [
                                        'status' => 'waiting_for_payment',
                                        'icon' => 'fa-credit-card',
                                        'color' => 'warning',
                                        'title' => 'Menunggu Pembayaran',
                                        'description' => 'Pesanan dibuat dan menunggu pembayaran',
                                        'date' => $order->created_at
                                    ],
                                    [
                                        'status' => 'processing',
                                        'icon' => 'fa-box-open',
                                        'color' => 'primary',
                                        'title' => 'Pesanan Diproses',
                                        'description' => 'Pembayaran diterima, pesanan sedang diproses',
                                        'date' => $order->status == 'processing' ? ($order->status_updated_at ?? $order->updated_at) : null
                                    ],
                                    [
                                        'status' => 'shipping',
                                        'icon' => 'fa-shipping-fast',
                                        'color' => 'info',
                                        'title' => 'Dalam Pengiriman',
                                        'description' => 'Pesanan telah dikirim',
                                        'date' => $order->status == 'shipping' ? ($order->status_updated_at ?? $order->updated_at) : null
                                    ],
                                    [
                                        'status' => 'delivered',
                                        'icon' => 'fa-check-circle',
                                        'color' => 'success',
                                        'title' => 'Pesanan Selesai',
                                        'description' => 'Pesanan telah diterima pelanggan',
                                        'date' => $order->status == 'delivered' ? ($order->status_updated_at ?? $order->updated_at) : null
                                    ],
                                ];
                                
                                $currentStatusIndex = array_search($order->status, array_column($statusTimeline, 'status'));
                                if ($currentStatusIndex === false) $currentStatusIndex = 0;
                            @endphp
                            
                            @foreach($statusTimeline as $index => $timeline)
                                @php
                                    $isActive = $index <= $currentStatusIndex;
                                    $isCurrent = $index == $currentStatusIndex;
                                @endphp
                                <div class="timeline-item {{ $isActive ? 'completed' : '' }} {{ $isCurrent ? 'current' : '' }}">
                                    <div class="timeline-marker">
                                        <div class="marker-icon bg-{{ $timeline['color'] }}">
                                            <i class="fas {{ $timeline['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">{{ $timeline['title'] }}</h6>
                                        <p class="timeline-text">{{ $timeline['description'] }}</p>
                                        @if($timeline['date'])
                                            <div class="timeline-date">
                                                <i class="fas fa-calendar-alt me-1"></i> 
                                                @if(is_string($timeline['date']))
                                                    {{ $timeline['date'] }}
                                                @else
                                                    {{ $timeline['date']->format('d M Y, H:i') }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($order->status == 'cancelled')
                                <div class="timeline-item completed current cancelled-timeline-item">
                                    <div class="timeline-marker">
                                        <div class="marker-icon bg-danger">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Pesanan Dibatalkan</h6>
                                        <p class="timeline-text">Pesanan telah dibatalkan</p>
                                        @if($order->status_updated_at)
                                            <div class="timeline-date">
                                                <i class="fas fa-calendar-alt me-1"></i> 
                                                @if(is_string($order->status_updated_at))
                                                    {{ $order->status_updated_at }}
                                                @elseif(is_object($order->status_updated_at) && method_exists($order->status_updated_at, 'format'))
                                                    {{ $order->status_updated_at->format('d M Y, H:i') }}
                                                @else
                                                    {{ $order->updated_at->format('d M Y, H:i') }}
                                                @endif
                                            </div>
                                @else
                                            <div class="timeline-date">
                                                <i class="fas fa-calendar-alt me-1"></i> 
                                                {{ $order->updated_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                        </div>
                    </div>
                    @endif
                    
                    <h6 class="mt-5 mb-3">Ubah Status Pesanan</h6>
                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="status-update-form" id="update-status-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-exchange-alt me-2 text-primary"></i>
                                        Status Pesanan
                                    </label>
                                    <div class="status-options-container mt-2">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="status-option-card {{ $order->status == 'processing' ? 'active' : '' }}" data-status="processing">
                                                    <input type="radio" name="status" value="processing" id="status-processing" {{ $order->status == 'processing' ? 'checked' : '' }} class="status-radio">
                                                    <label for="status-processing" class="status-label">
                                                        <div class="status-icon">
                                                            <i class="fas fa-box-open"></i>
                                                        </div>
                                                        <div class="status-text">Diproses</div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="status-option-card {{ $order->status == 'shipping' ? 'active' : '' }}" data-status="shipping">
                                                    <input type="radio" name="status" value="shipping" id="status-shipping" {{ $order->status == 'shipping' ? 'checked' : '' }} class="status-radio">
                                                    <label for="status-shipping" class="status-label">
                                                        <div class="status-icon">
                                                            <i class="fas fa-shipping-fast"></i>
                                                        </div>
                                                        <div class="status-text">Dikirim</div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="status-option-card {{ $order->status == 'delivered' ? 'active' : '' }}" data-status="delivered">
                                                    <input type="radio" name="status" value="delivered" id="status-delivered" {{ $order->status == 'delivered' ? 'checked' : '' }} class="status-radio">
                                                    <label for="status-delivered" class="status-label">
                                                        <div class="status-icon">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                        <div class="status-text">Selesai</div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="status-option-card {{ $order->status == 'cancelled' ? 'active' : '' }}" data-status="cancelled">
                                                    <input type="radio" name="status" value="cancelled" id="status-cancelled" {{ $order->status == 'cancelled' ? 'checked' : '' }} class="status-radio">
                                                    <label for="status-cancelled" class="status-label">
                                                        <div class="status-icon">
                                                            <i class="fas fa-times-circle"></i>
                                                        </div>
                                                        <div class="status-text">Dibatalkan</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="additional-info-container p-4 mt-3 rounded">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="status_notes" class="form-label">
                                            <i class="fas fa-sticky-note me-2 text-primary"></i>
                                            Catatan Perubahan Status (Opsional)
                                        </label>
                                        <textarea class="form-control" id="status_notes" name="status_notes" rows="2" 
                                            placeholder="Tambahkan catatan tentang perubahan status ini"></textarea>
                                        <small class="form-text text-muted">
                                            Catatan ini hanya tersimpan di sistem dan tidak ditampilkan ke pelanggan
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" value="1" checked>
                                        <label class="form-check-label" for="send_notification">
                                            <i class="fas fa-bell me-2 text-primary"></i>
                                            Kirim notifikasi ke pelanggan
                                        </label>
                                        <small class="d-block form-text text-muted">
                                            Pelanggan akan menerima notifikasi di aplikasi Flutter
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn save-btn mt-3" id="submit-status-btn">
                            <i class="fas fa-save me-2"></i><span class="text-emphasis">Simpan Perubahan Status</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order Info -->
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-user me-2"></i> Informasi Pelanggan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="customer-profile-container p-3">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <div class="customer-avatar mb-2">
                                    <i class="fas fa-user"></i>
                        </div>
                    </div>
                            <div class="col-md-9">
                                <div class="customer-identity">
                                    <h5 class="customer-name mb-1">{{ $order->user->full_name ?? ($order->user->username ?? 'Pelanggan') }}</h5>
                                    <span class="customer-badge badge bg-{{ !str_contains($order->user->email ?? '', '@guestgmail.com') ? 'primary' : 'secondary' }} mb-2">
                                        {{ !str_contains($order->user->email ?? '', '@guestgmail.com') ? 'Pelanggan Terdaftar' : 'Tamu' }}
                                    </span>
                                    
                                    <div class="customer-contact mt-3">
                                        <div class="contact-item d-flex align-items-center mb-2">
                                            <div class="contact-icon me-2">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </div>
                                            <div class="contact-info">
                                                <div class="contact-label small text-muted">Email</div>
                                                <div class="contact-value">{{ $order->user->email ?? 'Tidak tersedia' }}</div>
                                            </div>
                    </div>
                    
                                        <div class="contact-item d-flex align-items-center">
                                            <div class="contact-icon me-2">
                                                <i class="fas fa-phone text-primary"></i>
                                            </div>
                                            <div class="contact-info">
                                                <div class="contact-label small text-muted">Nomor Telepon</div>
                                                <div class="contact-value">{{ $order->phone_number ?? ($order->user->phone ?? 'Tidak tersedia') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                    
                        @if($order->user && !str_contains($order->user->email ?? '', '@guestgmail.com'))
                        <div class="customer-history mt-4 pt-3 border-top">
                            <h6 class="text-muted mb-3"><i class="fas fa-history me-2"></i> Riwayat Pelanggan</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="history-item d-flex align-items-center mb-3">
                                        <div class="history-icon me-3">
                                            <i class="fas fa-shopping-bag bg-light text-success p-2 rounded-circle"></i>
                                        </div>
                                        <div class="history-info">
                                            <div class="history-label small text-muted">Total Pesanan</div>
                                            <div class="history-value fw-bold">
                                                {{ \App\Models\Order::where('user_id', $order->user->id)->count() }} pesanan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="history-item d-flex align-items-center mb-3">
                                        <div class="history-icon me-3">
                                            <i class="fas fa-calendar-alt bg-light text-info p-2 rounded-circle"></i>
                                        </div>
                                        <div class="history-info">
                                            <div class="history-label small text-muted">Terdaftar Sejak</div>
                                            <div class="history-value fw-bold">
                                                {{ $order->user->created_at ? $order->user->created_at->format('d F Y') : 'Tidak tersedia' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                        @endif
            </div>
        </div>
    </div>

            <!-- Payment Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-credit-card me-2"></i> Informasi Pembayaran
                    </h5>
        </div>
        <div class="card-body">
                    <div class="payment-info-box mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group d-flex">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Metode Pembayaran</div>
                                        <div class="info-value">{{ ucfirst($order->payment_method) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group d-flex">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-check-circle {{ $order->payment_status == 'paid' ? 'text-success' : 'text-warning' }}"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Status Pembayaran</div>
                                        <div class="info-value">
                                            <span class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                                                {{ $order->payment_status_label }}
                    </span>
                </div>
                                    </div>
                                </div>
                            </div>
            </div>
                    
                        <div class="row mt-3">
                            @if($order->payment_status == 'pending' && $order->payment_deadline)
                                <div class="col-md-12">
                                    <div class="info-group d-flex">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-clock text-danger"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Batas Waktu Pembayaran</div>
                                            <div class="info-value text-danger">
                                                {{ $order->payment_deadline->format('d F Y, H:i') }} WIB
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($order->payment_status == 'paid' && $order->paid_at)
                                <div class="col-md-12">
                                    <div class="info-group d-flex">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-calendar-check text-success"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Tanggal Pembayaran</div>
                                            <div class="info-value">
                                                {{ $order->paid_at->format('d F Y, H:i') }} WIB
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-shipping-fast me-2"></i> Informasi Pengiriman
                    </h5>
                </div>
                <div class="card-body">
                    <div class="shipping-info-box">
                        @if(is_array($shippingAddress))
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="info-group d-flex mb-3">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-user text-info"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Nama Penerima</div>
                                            <div class="info-value">{{ $shippingAddress['name'] ?? 'Tidak tersedia' }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="info-group d-flex mb-3">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-phone text-info"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Nomor Telepon</div>
                                            <div class="info-value">{{ $order->phone_number ?? 'Tidak tersedia' }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="info-group d-flex">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-map-marker-alt text-info"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Alamat Lengkap</div>
                                            <div class="info-value">
                                                {{ $shippingAddress['address'] ?? '' }}<br>
                                                @if(isset($shippingAddress['district']) && $shippingAddress['district'])
                                                    {{ $shippingAddress['district'] }}, 
                    @endif
                                                {{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['province'] ?? '' }}<br>
                                                {{ $shippingAddress['postal_code'] ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ $shippingAddress ?? 'Alamat pengiriman tidak tersedia' }}
            </div>
            @endif
            </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle status card selection
        $('.status-option-card').on('click', function() {
            // Remove active class from all cards
            $('.status-option-card').removeClass('active');
            
            // Add active class to clicked card
            $(this).addClass('active');
            
            // Check the corresponding radio button
            $(this).find('input[type="radio"]').prop('checked', true);
        });
        
        // Confirm status change before submitting
        $('#update-status-form').on('submit', function(e) {
            e.preventDefault();

            const newStatus = $('input[name="status"]:checked').val();
            const currentStatus = '{{ $order->status }}';
            const paymentStatus = '{{ $order->payment_status }}';

            // Check if payment is required for status change
            if (newStatus !== 'cancelled' && paymentStatus !== 'paid' && newStatus !== 'waiting_for_payment') {
                Swal.fire({
                    title: 'Pembayaran Belum Selesai',
                    text: 'Status pesanan tidak dapat diubah karena pembayaran belum selesai. Pastikan customer telah menyelesaikan pembayaran terlebih dahulu.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Mengerti'
                });
                return;
            }

            // If status didn't change, show warning
            if (newStatus === currentStatus) {
                    Swal.fire({
                    title: 'Status Tidak Berubah',
                    text: 'Anda tidak mengubah status pesanan. Apakah Anda ingin melanjutkan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitForm();
                    }
                });
                return;
            }
            
            // Show confirmation dialog
            const statusLabels = {
                'processing': 'Diproses',
                'shipping': 'Dikirim',
                'delivered': 'Selesai',
                'cancelled': 'Dibatalkan',
                'waiting_for_payment': 'Menunggu Pembayaran'
            };
            
            Swal.fire({
                title: 'Konfirmasi Perubahan Status',
                text: `Apakah Anda yakin ingin mengubah status pesanan menjadi "${statusLabels[newStatus]}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah Status',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm();
                }
            });
        });
        
        function submitForm() {
            // Show loading
            $('#submit-status-btn').html('<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan...');
            $('#submit-status-btn').prop('disabled', true);
            
            // Get form data
            const formData = $('#update-status-form').serialize();
            const formAction = $('#update-status-form').attr('action');
            
            // Submit the form using AJAX
            $.ajax({
                url: formAction,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            // Reload the page to show updated status
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: response.message || 'Terjadi kesalahan saat memperbarui status pesanan.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#submit-status-btn').html('<i class="fas fa-save me-2"></i> Simpan Perubahan Status');
                        $('#submit-status-btn').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Terjadi kesalahan saat memperbarui status pesanan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        title: 'Gagal!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                    $('#submit-status-btn').html('<i class="fas fa-save me-2"></i> Simpan Perubahan Status');
                    $('#submit-status-btn').prop('disabled', false);
                }
            });
        }
    });
</script>
@endpush 