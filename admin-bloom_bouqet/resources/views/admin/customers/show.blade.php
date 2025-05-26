@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title"><span class="text-pink">Detail Pelanggan</span></h3>
                <p class="text-muted">Informasi lengkap mengenai pelanggan</p>
            </div>
            <div>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-light back-btn">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-4 mb-4">
            <div class="card customer-profile-card h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="customer-avatar mb-3">
                            <span>{{ substr($customer->full_name ?? $customer->username ?? 'U', 0, 1) }}</span>
                        </div>
                        <h4 class="customer-name">{{ $customer->full_name ?? $customer->username }}</h4>
                        <p class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Member sejak {{ \Carbon\Carbon::parse($customer->created_at)->format('d M Y') }}
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="customer-detail mb-3">
                        <h6 class="detail-label">
                            <i class="fas fa-envelope me-2"></i> Email
                        </h6>
                        <p class="detail-value">{{ $customer->email }}</p>
                    </div>
                    
                    <div class="customer-detail mb-3">
                        <h6 class="detail-label">
                            <i class="fas fa-phone me-2"></i> Nomor Telepon
                        </h6>
                        <p class="detail-value">{{ $customer->phone ?? 'Tidak tersedia' }}</p>
                    </div>
                    
                    <div class="customer-detail mb-3">
                        <h6 class="detail-label">
                            <i class="fas fa-map-marker-alt me-2"></i> Alamat
                        </h6>
                        <p class="detail-value">{{ $customer->address ?? 'Tidak tersedia' }}</p>
                    </div>

                    <div class="customer-detail mb-3">
                        <h6 class="detail-label">
                            <i class="fas fa-birthday-cake me-2"></i> Tanggal Lahir
                        </h6>
                        <p class="detail-value">{{ isset($customer->birth_date) ? \Carbon\Carbon::parse($customer->birth_date)->format('d M Y') : 'Tidak tersedia' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Statistics -->
        <div class="col-md-8 mb-4">
            <div class="row">
                <div class="col-sm-6 mb-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Total Pesanan</h6>
                                    <h3 class="fw-bold">{{ $stats['total_orders'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 mb-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Total Belanja</h6>
                                    <h3 class="fw-bold">Rp{{ number_format($stats['total_spent'] ?? 0, 0, ',', '.') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 mb-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3" style="background: linear-gradient(45deg, #fd7e14, #ffc107);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Rata-rata Belanja</h6>
                                    <h3 class="fw-bold">Rp{{ number_format($stats['avg_order_value'] ?? 0, 0, ',', '.') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 mb-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3" style="background: linear-gradient(45deg, #0dcaf0, #6610f2);">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Pesanan Terakhir</h6>
                                    <h3 class="fw-bold">
                                        {{ isset($stats['last_order_date']) ? \Carbon\Carbon::parse($stats['last_order_date'])->format('d M Y') : 'Belum ada' }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Activity Chart -->
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="card-title">Aktivitas Pembelian (12 Bulan Terakhir)</h5>
                </div>
                <div class="card-body">
                    @if(isset($monthlyStats) && count($monthlyStats) > 0)
                        <canvas id="monthlyOrdersChart" height="250"></canvas>
                    @else
                        <div class="empty-state text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h5>Belum Ada Data Aktivitas</h5>
                            <p class="text-muted">Pelanggan belum memiliki catatan aktivitas pembelian</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Orders -->
    <div class="card table-card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Riwayat Pesanan</h5>
            </div>
        </div>
        <div class="card-body">
            @if(count($orders) > 0)
                <div class="table-responsive">
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Metode Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr class="order-item">
                                    <td><span class="order-id">#{{ $order->id }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</td>
                                    <td>
                                        @php
                                            $statusClass = 'secondary';
                                            switch($order->status) {
                                                case 'waiting_for_payment': $statusClass = 'warning'; break;
                                                case 'processing': $statusClass = 'info'; break;
                                                case 'shipping': $statusClass = 'primary'; break;
                                                case 'delivered': $statusClass = 'success'; break;
                                                case 'cancelled': $statusClass = 'danger'; break;
                                            }
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td>{{ ucfirst($order->payment_method ?? 'Tidak tersedia') }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn action-btn info-btn" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                    <h5>Belum Ada Pesanan</h5>
                    <p class="text-muted">Pelanggan ini belum melakukan pemesanan</p>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Purchased Products -->
    <div class="card table-card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Produk yang Dibeli</h5>
            </div>
        </div>
        <div class="card-body">
            @php
                $purchasedProducts = collect();
                foreach($orders as $order) {
                    foreach($order->items as $item) {
                        $purchasedProducts->push([
                            'product_id' => $item->product_id ?? 0,
                            'name' => $item->name,
                            'price' => $item->price,
                            'quantity' => $item->quantity,
                            'total' => $item->price * $item->quantity,
                            'order_id' => $order->id,
                            'order_date' => $order->created_at,
                            'image' => $item->product->image ?? null
                        ]);
                    }
                }
            @endphp
            
            @if($purchasedProducts->count() > 0)
                <div class="table-responsive">
                    <table class="table product-table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                                <th>Tanggal Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchasedProducts as $product)
                                <tr class="product-item">
                                    <td>
                                        @if($product['image'])
                                            <img src="{{ asset('storage/' . $product['image']) }}" 
                                                alt="{{ $product['name'] }}" class="img-thumbnail" 
                                                style="max-height: 50px;">
                                        @else
                                            <div class="bg-light text-center p-2 rounded">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $product['name'] }}</td>
                                    <td>Rp{{ number_format($product['price'], 0, ',', '.') }}</td>
                                    <td>{{ $product['quantity'] }}</td>
                                    <td>Rp{{ number_format($product['total'], 0, ',', '.') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($product['order_date'])->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h5>Belum Ada Produk yang Dibeli</h5>
                    <p class="text-muted">Pelanggan ini belum membeli produk apapun</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .text-pink {
        color: #FF87B2 !important;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .back-btn {
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .back-btn:hover {
        transform: translateX(-5px);
    }
    
    .customer-profile-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .customer-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 auto;
    }
    
    .customer-name {
        color: #333;
        font-weight: 600;
        margin-top: 1rem;
    }
    
    .detail-label {
        color: #FF87B2;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .detail-value {
        color: #333;
        margin-bottom: 0.5rem;
        padding-left: 1.8rem;
    }
    
    .stat-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
    }
    
    .chart-card, .table-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1rem 1.5rem;
    }
    
    .card-title {
        color: #555;
        font-weight: 600;
    }
    
    .order-table {
        margin-bottom: 0;
    }
    
    .order-table th {
        font-weight: 600;
        color: #555;
        border-top: none;
        border-bottom: 2px solid #f0f0f0;
        padding: 1rem;
    }
    
    .order-item td {
        vertical-align: middle;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .order-id {
        font-weight: 600;
        color: #555;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .info-btn {
        background-color: rgba(0, 123, 255, 0.1);
        color: #0d6efd;
        border: none;
    }
    
    .info-btn:hover {
        background-color: #0d6efd;
        color: white;
    }
    
    .empty-state {
        padding: 3rem 0;
    }
    
    .empty-state-icon {
        width: 80px;
        height: 80px;
        background-color: rgba(255,135,178,0.1);
        color: #FF87B2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto;
    }
</style>

@if(isset($monthlyStats) && count($monthlyStats) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for chart
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const labels = [];
    const orderData = [];
    const amountData = [];
    
    @foreach($monthlyStats as $stat)
        labels.push(monthNames[{{ $stat->month - 1 }}] + ' {{ $stat->year }}');
        orderData.push({{ $stat->order_count }});
        amountData.push({{ $stat->total_amount }});
    @endforeach
    
    // Create the chart
    const ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Jumlah Pesanan',
                    data: orderData,
                    backgroundColor: '#FF87B2',
                    borderColor: '#FF87B2',
                    borderWidth: 1
                },
                {
                    label: 'Total Belanja (Rp)',
                    data: amountData,
                    backgroundColor: '#D46A9F',
                    borderColor: '#D46A9F',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Pesanan'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total Belanja (Rp)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
});
</script>
@endif
@endsection 