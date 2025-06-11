@extends('layouts.admin')

@section('title', 'Daftar Pesanan')

@section('page-title', 'Daftar Pesanan')

@section('styles')
<style>
    :root {
        --primary-color: #FF87B2;
        --primary-dark: #D46A9F;
        --primary-light: #FFE5EE;
        --dark-text: #5a5c69;
        --light-text: #858796;
        --waiting-color: #f6c23e;
        --processing-color: #FF87B2;
        --shipping-color: #36b9cc;
        --delivered-color: #1cc88a;
        --cancelled-color: #e74a3b;
        --total-color: #4e73df;
    }

    /* Status Box Styles */
    .stats-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .status-box {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        flex: 1;
        min-width: 200px;
        position: relative;
        overflow: hidden;
    }
    
    .status-box::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
    }
    
    .status-box.waiting::before {
        background-color: var(--waiting-color);
    }
    
    .status-box.processing::before {
        background-color: var(--processing-color);
    }
    
    .status-box.shipping::before {
        background-color: var(--shipping-color);
    }
    
    .status-box.delivered::before {
        background-color: var(--delivered-color);
    }
    
    .status-box.cancelled::before {
        background-color: var(--cancelled-color);
    }
    
    .status-box.total::before {
        background-color: var(--total-color);
    }
    
    .status-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .status-box.waiting .status-icon {
        background-color: rgba(246, 194, 62, 0.2);
        color: var(--waiting-color);
    }
    
    .status-box.processing .status-icon {
        background-color: var(--primary-light);
        color: var(--processing-color);
    }
    
    .status-box.shipping .status-icon {
        background-color: rgba(54, 185, 204, 0.2);
        color: var(--shipping-color);
    }
    
    .status-box.delivered .status-icon {
        background-color: rgba(28, 200, 138, 0.2);
        color: var(--delivered-color);
    }
    
    .status-box.cancelled .status-icon {
        background-color: rgba(231, 74, 59, 0.2);
        color: var(--cancelled-color);
    }
    
    .status-box.total .status-icon {
        background-color: rgba(78, 115, 223, 0.2);
        color: var(--total-color);
    }
    
    .status-icon i {
        font-size: 24px;
    }
    
    .status-info {
        flex-grow: 1;
    }
    
    .status-count {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark-text);
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .status-label {
        font-size: 14px;
        color: var(--light-text);
        margin: 0;
    }
    
    /* Table Styles */
    .order-table-container {
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .order-table-header {
        background-color: var(--primary-color);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .order-table-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .order-table-title i {
        margin-right: 10px;
    }
    
    .order-count-badge {
        background-color: white;
        color: var(--primary-color);
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 14px;
    }
    
    .order-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .order-table th {
        background-color: #f8f9fc;
        color: var(--dark-text);
        font-weight: 600;
        text-align: left;
        padding: 15px 20px;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .order-table td {
        padding: 15px 20px;
        border-bottom: 1px solid #e3e6f0;
        vertical-align: middle;
    }
    
    .order-table tr:hover {
        background-color: #f8f9fc;
    }
    
    .order-id {
        color: var(--primary-color);
        font-weight: 600;
    }
    
    .customer-info {
        width: 100%;
    }
    
    .customer-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 3px;
        line-height: 1.2;
    }
    
    .customer-email {
        font-size: 12px;
        color: var(--light-text);
        margin: 0;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 12px;
        text-align: center;
    }
    
    .badge-waiting {
        background-color: var(--waiting-color);
        color: #fff;
    }
    
    .badge-processing {
        background-color: var(--processing-color);
        color: #fff;
    }
    
    .badge-shipping {
        background-color: var(--shipping-color);
        color: #fff;
    }
    
    .badge-delivered {
        background-color: var(--delivered-color);
        color: #fff;
    }
    
    .badge-cancelled {
        background-color: var(--cancelled-color);
        color: #fff;
    }
    
    .badge-pending {
        background-color: var(--waiting-color);
        color: #fff;
    }
    
    .badge-paid {
        background-color: var(--delivered-color);
        color: #fff;
    }
    
    .action-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .action-btn:hover {
        background-color: var(--primary-dark);
        color: white;
        text-decoration: none;
    }
    
    .action-btn i {
        margin-right: 5px;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px 20px;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #d1d3e2;
        margin-bottom: 20px;
    }
    
    .empty-state p {
        color: #858796;
        margin-bottom: 20px;
        font-size: 18px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Status Boxes -->
    <div class="stats-container">
        <div class="status-box waiting">
            <div class="status-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $waitingForPaymentCount }}</div>
                <div class="status-label">Menunggu Pembayaran</div>
            </div>
        </div>
        
        <div class="status-box processing">
            <div class="status-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $processingCount }}</div>
                <div class="status-label">Sedang Diproses</div>
            </div>
        </div>
        
        <div class="status-box shipping">
            <div class="status-icon">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $shippingCount }}</div>
                <div class="status-label">Sedang Dikirim</div>
            </div>
        </div>
        
        <div class="status-box delivered">
            <div class="status-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $deliveredCount }}</div>
                <div class="status-label">Selesai</div>
            </div>
        </div>
        
        <div class="status-box cancelled">
            <div class="status-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $cancelledCount }}</div>
                <div class="status-label">Dibatalkan</div>
            </div>
        </div>
        
        <div class="status-box total">
            <div class="status-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="status-info">
                <div class="status-count">{{ $orders->total() }}</div>
                <div class="status-label">Total Pesanan</div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="order-table-container">
        <div class="order-table-header">
            <h5 class="order-table-title">
                <i class="fas fa-shopping-cart"></i> Daftar Pesanan
            </h5>
            <span class="order-count-badge">{{ $orders->total() }} Pesanan</span>
        </div>
        
        @if($orders->count() > 0)
        <div class="table-responsive">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Tanggal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr onclick="window.location.href='{{ route('admin.orders.show', $order->id) }}'" style="cursor: pointer;">
                        <td>
                            <span class="order-id">#{{ $order->id }}</span>
                        </td>
                        <td>
                            <div class="customer-info">
                                @if($order->customer_name || $order->customer_email)
                                    {{-- Use customer info from order table --}}
                                    <div class="customer-name">{{ $order->customer_name ?? 'Customer' }}</div>
                                    @if($order->customer_email)
                                        <div class="customer-email">{{ $order->customer_email }}</div>
                                    @endif
                                @elseif($order->user)
                                    {{-- Fallback to user relationship --}}
                                    <div class="customer-name">{{ $order->user->name ?? $order->user->username ?? 'Customer' }}</div>
                                    <div class="customer-email">{{ $order->user->email }}</div>
                                @else
                                    {{-- Fallback to shipping address --}}
                                    @php
                                        $shippingAddress = is_string($order->shipping_address)
                                            ? json_decode($order->shipping_address, true)
                                            : $order->shipping_address;
                                    @endphp
                                    <div class="customer-name">
                                        {{ $shippingAddress['name'] ?? 'Guest Customer' }}
                                    </div>
                                    @if(isset($shippingAddress['email']))
                                        <div class="customer-email">{{ $shippingAddress['email'] }}</div>
                                    @else
                                        <div class="customer-email">Guest Order</div>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td>
                            <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'waiting_for_payment' => 'badge-waiting',
                                    'processing' => 'badge-processing',
                                    'shipping' => 'badge-shipping',
                                    'delivered' => 'badge-delivered',
                                    'cancelled' => 'badge-cancelled'
                                ];
                                
                                $statusLabels = [
                                    'waiting_for_payment' => 'Menunggu Pembayaran',
                                    'processing' => 'Sedang Diproses',
                                    'shipping' => 'Sedang Dikirim',
                                    'delivered' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                
                                $statusClass = $statusClasses[$order->status] ?? '';
                                $statusLabel = $statusLabels[$order->status] ?? 'Unknown';
                            @endphp
                            
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            @php
                                $paymentClasses = [
                                    'paid' => 'badge-paid',
                                    'pending' => 'badge-pending',
                                    'failed' => 'badge-cancelled',
                                    'expired' => 'badge-cancelled',
                                    'refunded' => 'badge-shipping'
                                ];
                                
                                $paymentLabels = [
                                    'paid' => 'Dibayar',
                                    'pending' => 'Pending',
                                    'failed' => 'Gagal',
                                    'expired' => 'Kedaluwarsa',
                                    'refunded' => 'Dikembalikan'
                                ];
                                
                                $paymentClass = $paymentClasses[$order->payment_status] ?? '';
                                $paymentLabel = $paymentLabels[$order->payment_status] ?? 'Unknown';
                            @endphp
                            
                            <span class="status-badge {{ $paymentClass }}">
                                {{ $paymentLabel }}
                            </span>
                        </td>
                        <td>
                            <div>{{ $order->created_at->format('d M Y') }}</div>
                            <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="action-btn" onclick="event.stopPropagation();">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center py-3">
            {{ $orders->links() }}
        </div>
        @else
        <div class="empty-state">
            <i class="fas fa-shopping-cart"></i>
            <p>Tidak ada pesanan yang ditemukan</p>
            <a href="{{ route('admin.orders.index') }}" class="action-btn">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
        @endif
    </div>
</div>
@endsection 