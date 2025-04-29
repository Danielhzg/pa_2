@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Laporan Penjualan</h1>
        <div class="d-flex">
            <a href="{{ route('admin.reports.export', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
               class="btn btn-success me-2">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dateFilterModal">
                <i class="fas fa-calendar"></i> Pilih Tanggal
            </button>
        </div>
    </div>

    <!-- Filter Summary -->
    @if(request('start_date') || request('end_date'))
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Rentang Waktu:</strong>
                {{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }} - 
                {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
        </div>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase mb-1">Total Pesanan</h6>
                            <h2 class="mb-0">{{ $orderStats['total_orders'] }}</h2>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase mb-1">Total Pendapatan</h6>
                            <h2 class="mb-0">Rp {{ number_format($orderStats['total_revenue'], 0, ',', '.') }}</h2>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase mb-1">Rata-rata Pesanan</h6>
                            <h2 class="mb-0">Rp {{ number_format($orderStats['average_order'], 0, ',', '.') }}</h2>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase mb-1">Pesanan Aktif</h6>
                            <h2 class="mb-0">{{ $orderStats['active_orders'] }}</h2>
                        </div>
                        <i class="fas fa-spinner fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Daily Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Penjualan Harian</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods Chart -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Metode Pembayaran</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Best Selling Products -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Produk Terlaris</h5>
        </div>
        <div class="card-body">
            @if(count($topProducts) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" 
                                                class="img-thumbnail" style="max-height: 50px;">
                                        @else
                                            <div class="bg-light text-center p-2 rounded">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->quantity_sold }}</td>
                                    <td>Rp {{ number_format($product->total_sales, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    Tidak ada data penjualan produk dalam periode ini.
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
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-range" data-days="7">
                                7 Hari
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-range" data-days="30">
                                30 Hari
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-range" data-days="90">
                                90 Hari
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary this-month">
                                Bulan Ini
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary last-month">
                                Bulan Lalu
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
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
        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        const dailySalesChart = new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailySales->pluck('date')) !!},
                datasets: [{
                    label: 'Penjualan Harian',
                    data: {!! json_encode($dailySales->pluck('total')) !!},
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        
        // Extract data from PHP
        const paymentLabels = {!! json_encode($paymentMethods->pluck('method')) !!};
        const paymentData = {!! json_encode($paymentMethods->pluck('count')) !!};
        
        // Generate colors dynamically
        const backgroundColors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)'
        ];
        
        // Create a dataset with enough colors
        const dataColors = [];
        for (let i = 0; i < paymentData.length; i++) {
            dataColors.push(backgroundColors[i % backgroundColors.length]);
        }
        
        const paymentMethodsChart = new Chart(paymentMethodsCtx, {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentData,
                    backgroundColor: dataColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Quick date range buttons
        document.querySelectorAll('.quick-range').forEach(button => {
            button.addEventListener('click', function() {
                const days = parseInt(this.dataset.days);
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - days);
                
                document.getElementById('start_date').value = formatDate(startDate);
                document.getElementById('end_date').value = formatDate(endDate);
            });
        });
        
        // This month button
        document.querySelector('.this-month').addEventListener('click', function() {
            const now = new Date();
            const startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(now);
        });
        
        // Last month button
        document.querySelector('.last-month').addEventListener('click', function() {
            const now = new Date();
            const startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const endDate = new Date(now.getFullYear(), now.getMonth(), 0);
            
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
        });
        
        // Helper function to format date to YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    });
</script>
@endpush 