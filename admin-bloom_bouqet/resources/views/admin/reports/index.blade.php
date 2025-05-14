@extends('layouts.admin')

@section('title', 'Laporan')

@section('page-title', 'Laporan')

@section('styles')
<style>
    /* Custom styles for report page */
    .content-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0.25rem;
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
        padding: 1rem 1.5rem !important;
    }
    
    .card-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0;
    }
    
    .export-btn, .date-filter-btn {
        background-color: white;
        border: 1px solid rgba(255,105,180,0.2);
        color: #D46A9F;
        border-radius: 20px;
        padding: 8px 20px;
        transition: all 0.3s;
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
        padding: 20px;
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
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        margin-bottom: 15px;
    }
    
    .stats-card .stats-info h3 {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 5px;
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
    }
    
    .filter-badge {
        background: rgba(255,135,178,0.1);
        color: #D46A9F;
        border: 1px solid rgba(255,105,180,0.2);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
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
    
    .filter-pill {
        padding: 5px 12px;
        border-radius: 15px;
        background: rgba(255,135,178,0.1);
        color: #D46A9F;
        cursor: pointer;
        border: 1px solid rgba(255,105,180,0.1);
        transition: all 0.2s;
    }
    
    .filter-pill:hover, .filter-pill.active {
        background: rgba(255,135,178,0.2);
        border-color: rgba(255,105,180,0.2);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Laporan Penjualan</h3>
                <p class="text-muted">Pantau performa bisnis Anda dengan laporan penjualan terperinci</p>
            </div>
            <div>
                <a href="{{ route('admin.reports.export', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
                   class="btn export-btn me-2">
                    <i class="fas fa-file-csv me-2"></i> <span class="text-emphasis">Export CSV</span>
                </a>
                <button type="button" class="btn action-btn" data-bs-toggle="modal" data-bs-target="#dateFilterModal">
                    <i class="fas fa-calendar me-2"></i> <span class="text-emphasis">Pilih Tanggal</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Summary -->
    @if(request('start_date') || request('end_date'))
    <div class="card table-card mb-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="filter-badge">
                    <i class="fas fa-filter me-2"></i>
                    <strong>Rentang Waktu:</strong>
                    {{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }} - 
                    {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}
                </div>
                <a href="{{ route('admin.reports.index') }}" class="reset-btn">
                    <i class="fas fa-times me-1"></i> Reset Filter
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
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
        
        <div class="col-md-3 mb-4">
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
        
        <div class="col-md-3 mb-4">
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
        
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $orderStats['active_orders'] }}</h3>
                    <p>Pesanan Aktif</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Daily Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-area me-2"></i>Penjualan Harian</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods Chart -->
        <div class="col-lg-4 mb-4">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-credit-card me-2"></i>Metode Pembayaran</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="paymentMethodsChart"></canvas>
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
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->user->name ?? 'Tamu' }}</td>
                                    <td>{{ $order->total_items }} item</td>
                                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        @if($order->status == 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($order->status == 'processing')
                                            <span class="badge bg-primary">Diproses</span>
                                        @elseif($order->status == 'pending')
                                            <span class="badge bg-warning">Tertunda</span>
                                        @elseif($order->status == 'cancelled')
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</td>
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

<!-- Date Filter Modal -->
<div class="modal fade" id="dateFilterModal" tabindex="-1" aria-labelledby="dateFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateFilterModalLabel">Filter Berdasarkan Tanggal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.reports.index') }}" method="GET">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                        </div>
                        <div class="col">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                value="{{ request('end_date', now()->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rentang Cepat</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="filter-pill quick-range" data-days="7">
                                7 Hari
                            </button>
                            <button type="button" class="filter-pill quick-range" data-days="30">
                                30 Hari
                            </button>
                            <button type="button" class="filter-pill quick-range" data-days="90">
                                90 Hari
                            </button>
                            <button type="button" class="filter-pill this-month">
                                Bulan Ini
                            </button>
                            <button type="button" class="filter-pill last-month">
                                Bulan Lalu
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn export-btn" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn action-btn">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Daily Sales Chart with pink theme
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        const dailySalesChart = new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailySales->pluck('date')) !!},
                datasets: [{
                    label: 'Penjualan Harian',
                    data: {!! json_encode($dailySales->pluck('total')) !!},
                    backgroundColor: 'rgba(255, 135, 178, 0.1)',
                    borderColor: '#D46A9F',
                    pointBackgroundColor: '#D46A9F',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#D46A9F',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#D46A9F',
                        bodyColor: '#555',
                        bodyFont: {
                            size: 13
                        },
                        titleFont: {
                            size: 15,
                            weight: 'bold'
                        },
                        padding: 15,
                        displayColors: false,
                        borderColor: 'rgba(255, 135, 178, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Payment Methods Chart with pink theme
        const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        const paymentMethodsChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($paymentStats->pluck('method')) !!},
                datasets: [{
                    data: {!! json_encode($paymentStats->pluck('count')) !!},
                    backgroundColor: [
                        '#FF87B2',
                        '#D46A9F',
                        '#B05DA9',
                        '#935EB7',
                        '#7373FF'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#D46A9F',
                        bodyColor: '#555',
                        bodyFont: {
                            size: 13
                        },
                        titleFont: {
                            size: 15,
                            weight: 'bold'
                        },
                        padding: 15,
                        displayColors: true,
                        borderColor: 'rgba(255, 135, 178, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 10
                    }
                }
            }
        });
        
        // Quick date range buttons
        document.querySelectorAll('.quick-range').forEach(button => {
            button.addEventListener('click', function() {
                const days = this.getAttribute('data-days');
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - days);
                
                document.getElementById('start_date').value = formatDate(startDate);
                document.getElementById('end_date').value = formatDate(endDate);
            });
        });
        
        // This month button
        document.querySelector('.this-month').addEventListener('click', function() {
            const now = new Date();
            const startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            const endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
        });
        
        // Last month button
        document.querySelector('.last-month').addEventListener('click', function() {
            const now = new Date();
            const startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const endDate = new Date(now.getFullYear(), now.getMonth(), 0);
            
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
        });
        
        // Format date helper
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return `${year}-${month}-${day}`;
        }
        
        // Highlight active filter pill
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
</script>
@endpush 