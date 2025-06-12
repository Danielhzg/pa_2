@extends('layouts.admin')

@section('title', 'Laporan')

@section('page-title', 'Laporan')

@section('styles')
<style>
    /* Custom styles for report page */
    .content-header {
        margin-bottom: 1rem;
    }
    
    .page-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0.25rem;
        font-size: 1.5rem;
    }
    
    .table-card {
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: none;
        overflow: hidden;
    }
    
    .card-header {
        background-color: white !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        padding: 0.75rem 1.25rem !important;
    }
    
    .card-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0;
        font-size: 0.95rem;
    }
    
    .export-btn, .date-filter-btn {
        background-color: white;
        border: 1px solid rgba(255,105,180,0.2);
        color: #D46A9F;
        border-radius: 20px;
        padding: 6px 15px;
        transition: all 0.3s;
        font-size: 0.85rem;
        text-decoration: none;
    }

    .export-btn:hover, .date-filter-btn:hover {
        background-color: #D46A9F;
        color: white;
        border-color: #D46A9F;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(212, 106, 159, 0.3);
    }


    
    .export-btn:hover, .date-filter-btn:hover {
        background-color: rgba(255,135,178,0.05);
        border-color: #FF87B2;
        color: #D46A9F;
    }
    
    .action-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        border: none;
        color: white;
        border-radius: 20px;
        padding: 8px 20px;
        box-shadow: 0 4px 10px rgba(255,105,180,0.2);
        transition: all 0.3s;
    }
    
    .action-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        box-shadow: 0 6px 15px rgba(255,105,180,0.3);
        transform: translateY(-2px);
        color: white;
    }
    
    .stats-card {
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        padding: 12px;
        height: 100%;
        border: none;
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stats-card .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        margin-bottom: 12px;
    }
    
    .stats-card .stats-info h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 3px;
        color: #333;
    }
    
    .stats-card .stats-info p {
        color: #777;
        margin-bottom: 0;
    }
    
    .stats-card .stats-decoration {
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(45deg, rgba(255,135,178,0.05), rgba(212,106,159,0.08));
        border-radius: 0 0 0 100%;
        z-index: 0;
    }
    
    .chart-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        height: 100%;
    }
    
    .chart-card .card-header {
        background: white !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 0.5rem 1rem !important;
    }
    
    .chart-card .card-body {
        padding: 0.5rem;
        height: 200px; /* Further reduced height for chart containers */
    }
    
    .filter-badge {
        background: rgba(255,135,178,0.1);
        color: #D46A9F;
        border: 1px solid rgba(255,105,180,0.2);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        display: inline-block;
    }
    
    .reset-btn {
        color: #D46A9F;
        background: transparent;
        border: none;
        padding: 5px 10px;
        transition: all 0.2s;
    }
    
    .reset-btn:hover {
        background: rgba(255,135,178,0.05);
        border-radius: 20px;
    }
    
    .table th {
        font-weight: 600;
        color: #555;
        border-bottom-width: 1px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .product-img {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        object-fit: cover;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .empty-img {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
    }
    
    .modal-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Laporan Penjualan</h3>
                <p class="text-muted small mb-0">Pantau performa bisnis Anda dengan laporan penjualan</p>
            </div>
            <div class="d-flex gap-2">
                <!-- Export Excel Dropdown -->
                <div class="dropdown">
                    <button class="btn export-btn btn-sm dropdown-toggle" type="button" id="excelExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="excelExportDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.reports.export-excel', ['start_date' => request('start_date'), 'end_date' => request('end_date'), 'type' => 'orders']) }}">
                                <i class="fas fa-shopping-cart me-2"></i> Laporan Pesanan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.reports.export-excel', ['start_date' => request('start_date'), 'end_date' => request('end_date'), 'type' => 'summary']) }}">
                                <i class="fas fa-chart-bar me-2"></i> Ringkasan Laporan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.reports.export-excel', ['start_date' => request('start_date'), 'end_date' => request('end_date'), 'type' => 'products']) }}">
                                <i class="fas fa-box me-2"></i> Laporan Produk
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Date Filter -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form action="{{ route('admin.reports.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label small mb-1">Tanggal Mulai</label>
                    <input type="date" class="form-control form-control-sm" id="start_date" name="start_date"
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label small mb-1">Tanggal Akhir</label>
                    <input type="date" class="form-control form-control-sm" id="end_date" name="end_date"
                        value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn action-btn btn-sm">
                            <i class="fas fa-filter me-1"></i> Terapkan
                        </button>
                        <button type="button" class="btn export-btn btn-sm" id="reset-btn">
                            <i class="fas fa-sync me-1"></i> Reset
                        </button>
                    </div>
                    @if(request('start_date') && request('end_date'))
                    <div class="mt-2">
                        <span class="filter-badge small">
                            <i class="fas fa-filter me-1"></i>
                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-2">
        <div class="col-md-3 mb-2">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $orderStats['total_orders'] }}</h3>
                    <p>Total Pesanan</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stats-info">
                    <h3>Rp {{ number_format($orderStats['total_revenue'], 0, ',', '.') }}</h3>
                    <p>Total Pendapatan</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stats-info">
                    <h3>Rp {{ number_format($orderStats['average_order'], 0, ',', '.') }}</h3>
                    <p>Rata-rata Pesanan</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stats-info">
                    <h3>Rp {{ number_format($orderStats['monthly_revenue'], 0, ',', '.') }}</h3>
                    <p>Pendapatan {{ $orderStats['period_label'] ?? 'Periode Ini' }}</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <!-- Sales Chart -->
        <div class="col-lg-12 mb-2">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-area me-2"></i>Penjualan Harian</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Revenue Chart -->
    <div class="row mb-2">
        <div class="col-lg-12 mb-2">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Pendapatan Bulanan</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Simple Pie Chart -->
    <div class="row mb-2">
        <div class="col-lg-6 mb-2">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Distribusi Pesanan</h5>
                </div>
                <div class="card-body">
                    <canvas id="orderDistributionChart" height="180"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-2">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Tren Penjualan</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesTrendChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Best Selling Products -->
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-crown me-2"></i>Produk Terlaris</h5>
        </div>
        <div class="card-body">
            @if(count($topProducts) > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Gambar</th>
                                <th>Nama Produk</th>
                                <th>Terjual</th>
                                <th>Total Penjualan</th>
                                <th>Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if(isset($product['product']) && $product['product']->getPrimaryImage())
                                            <img src="{{ asset('storage/' . $product['product']->getPrimaryImage()) }}" alt="{{ $product['name'] }}" 
                                                class="product-img">
                                        @else
                                            <div class="empty-img">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $product['name'] }}</td>
                                    <td>{{ $product['quantity_sold'] }}</td>
                                    <td>Rp {{ number_format($product['total_sales'], 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($product['price'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-chart-pie fa-3x text-muted"></i>
                    </div>
                    <h5>Belum ada data penjualan</h5>
                    <p class="text-muted">Tidak ada data penjualan produk dalam periode ini</p>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Latest Customer Orders -->
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-shopping-bag me-2"></i>Pesanan Terbaru</h5>
        </div>
        <div class="card-body">
            @if(isset($latestOrders) && count($latestOrders) > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestOrders as $order)
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
                                        <span class="badge bg-{{ 
                                            $order->status == 'waiting_for_payment' ? 'warning' : 
                                            ($order->status == 'processing' ? 'primary' : 
                                            ($order->status == 'shipping' ? 'info' : 
                                            ($order->status == 'delivered' ? 'success' : 
                                            ($order->status == 'cancelled' ? 'danger' : 'secondary')))) 
                                        }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="payment-info">
                                            <span class="payment-method">{{ ucfirst($order->payment_method) }}</span>
                                            <span class="payment-badge badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                                                {{ $order->payment_status_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="order-date" data-timestamp="{{ $order->created_at }}">
                                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                    </div>
                    <h5>Belum ada pesanan</h5>
                    <p class="text-muted">Tidak ada pesanan dalam periode ini</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Chart.js Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded, initializing charts');
        
        // Directly create simple charts that will always work
        createSimpleCharts();
        
        // Function to create simple charts that will always work
        function createSimpleCharts() {
            // Create sample data
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const salesData = [1500000, 2200000, 1800000, 2500000, 3100000, 2800000, 3500000, 3200000, 2700000, 3000000, 3300000, 3800000];
            const ordersData = [15, 22, 18, 25, 31, 28, 35, 32, 27, 30, 33, 38];
            
            // Format currency helper
            const formatCurrency = (value) => {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value);
            };
            
            // Chart colors
            const colors = {
                pink: '#D46A9F',
                lightPink: '#FF87B2',
                purple: '#935EB7',
                blue: '#4E73DF',
                teal: '#36B9CC',
                green: '#1CC88A',
                yellow: '#F6C23E',
                red: '#E74A3B'
            };
            
            // 1. Daily Sales Chart
            try {
                const salesCtx = document.getElementById('salesChart');
                if (salesCtx) {
                    new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($salesChartData['labels'] ?? []) !!},
                            datasets: {!! json_encode($salesChartData['datasets'] ?? []) !!}
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 5,
                                    right: 10,
                                    bottom: 5,
                                    left: 10
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 10,
                                        padding: 8,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            if (context.dataset.label === 'Total Penjualan') {
                                                return context.dataset.label + ': ' + formatCurrency(context.raw);
                                            }
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return formatCurrency(value);
                                        },
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Sales chart created successfully');
                }
            } catch (error) {
                console.error('Error creating sales chart:', error);
            }
            
            // 2. Monthly Revenue Chart
            try {
                const revenueCtx = document.getElementById('monthlyRevenueChart');
                if (revenueCtx) {
                    new Chart(revenueCtx, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($monthlyRevenueData['chartData']['labels'] ?? []) !!},
                            datasets: {!! json_encode($monthlyRevenueData['chartData']['datasets'] ?? []) !!}
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 5,
                                    right: 10,
                                    bottom: 5,
                                    left: 10
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 10,
                                        padding: 8,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            if (context.dataset.label === 'Pendapatan Bulanan') {
                                                return context.dataset.label + ': ' + formatCurrency(context.raw);
                                            }
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return formatCurrency(value);
                                        },
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Monthly revenue chart created successfully');
                }
            } catch (error) {
                console.error('Error creating monthly revenue chart:', error);
            }
            
            // 3. Order Distribution Pie Chart
            try {
                const pieCtx = document.getElementById('orderDistributionChart');
                if (pieCtx) {
                    new Chart(pieCtx, {
                        type: 'pie',
                        data: {
                            labels: {!! json_encode($orderStatusDistribution['labels'] ?? ['Tidak ada data']) !!},
                            datasets: [{
                                data: {!! json_encode($orderStatusDistribution['data'] ?? [100]) !!},
                                backgroundColor: {!! json_encode($orderStatusDistribution['colors'] ?? ['#cccccc']) !!},
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 5,
                                    right: 10,
                                    bottom: 5,
                                    left: 10
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 10,
                                        padding: 8,
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Order distribution chart created successfully');
                }
            } catch (error) {
                console.error('Error creating order distribution chart:', error);
            }
            
            // 4. Sales Trend Chart
            try {
                const trendCtx = document.getElementById('salesTrendChart');
                if (trendCtx) {
                    new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($salesChartData['labels'] ?? ['Tidak ada data']) !!},
                            datasets: [{
                                label: 'Total Penjualan',
                                data: {!! json_encode($salesChartData['datasets'][0]['data'] ?? [0]) !!},
                                backgroundColor: 'rgba(54, 185, 204, 0.1)',
                                borderColor: colors.teal,
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 5,
                                    right: 10,
                                    bottom: 5,
                                    left: 10
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 10,
                                        padding: 8,
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return formatCurrency(value);
                                        },
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Sales trend chart created successfully');
                }
            } catch (error) {
                console.error('Error creating sales trend chart:', error);
            }
        }

        // Reset button functionality
        document.getElementById('reset-btn').addEventListener('click', function() {
            // Clear date inputs
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';

            // Redirect to reports page without any parameters
            window.location.href = '{{ route("admin.reports.index") }}';
        });
    });
</script>
@endpush 