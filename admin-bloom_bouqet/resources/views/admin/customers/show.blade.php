@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Pelanggan</h1>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
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
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Informasi Pelanggan</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-3 d-inline-flex justify-content-center align-items-center bg-primary rounded-circle" 
                             style="width: 100px; height: 100px;">
                            <span class="display-4 text-white">
                                {{ substr($customer['full_name'] ?? $customer['username'] ?? 'U', 0, 1) }}
                            </span>
                        </div>
                        <h5 class="font-weight-bold">{{ $customer['full_name'] ?? $customer['username'] }}</h5>
                        <p class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Member sejak {{ \Carbon\Carbon::parse($customer['created_at'])->format('d M Y') }}
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold text-primary">
                            <i class="fas fa-envelope me-1"></i> Email
                        </h6>
                        <p>{{ $customer['email'] }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold text-primary">
                            <i class="fas fa-phone me-1"></i> Nomor Telepon
                        </h6>
                        <p>{{ $customer['phone'] ?? 'Tidak tersedia' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold text-primary">
                            <i class="fas fa-map-marker-alt me-1"></i> Alamat
                        </h6>
                        <p>{{ $customer['address'] ?? 'Tidak tersedia' }}</p>
                    </div>

                    <div class="mb-3">
                        <h6 class="font-weight-bold text-primary">
                            <i class="fas fa-birthday-cake me-1"></i> Tanggal Lahir
                        </h6>
                        <p>{{ isset($customer['birth_date']) ? \Carbon\Carbon::parse($customer['birth_date'])->format('d M Y') : 'Tidak tersedia' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Statistics -->
        <div class="col-md-8 mb-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Pesanan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_orders'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-bag fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Belanja</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp{{ number_format($stats['total_spent'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Rata-rata Belanja</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp{{ number_format($stats['avg_order_value'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Pesanan Terakhir</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ isset($stats['last_order_date']) ? \Carbon\Carbon::parse($stats['last_order_date'])->format('d M Y') : 'Belum ada' }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Activity Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Pelanggan</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        @if(isset($monthlyStats) && count($monthlyStats) > 0)
                            <canvas id="monthlyOrdersChart"></canvas>
                        @else
                            <p class="text-center text-muted">Pelanggan belum memiliki catatan aktivitas pembelian.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Orders -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Pesanan</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="orderDropdown" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" 
                     aria-labelledby="orderDropdown">
                    <div class="dropdown-header">Opsi Pesanan:</div>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                        Export Data Pesanan
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(count($orders) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="bg-light">
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Metode Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>{{ $order['id'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d M Y H:i') }}</td>
                                    <td>Rp{{ number_format($order['total_amount'], 0, ',', '.') }}</td>
                                    <td>
                                        @if($order['status'] == 'pending')
                                            <span class="badge bg-warning">Menunggu</span>
                                        @elseif($order['status'] == 'processing')
                                            <span class="badge bg-info">Diproses</span>
                                        @elseif($order['status'] == 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($order['status'] == 'cancelled')
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order['status'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $order['payment_method'] ?? 'Tidak tersedia' }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order['id']) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    Pelanggan ini belum memiliki pesanan.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable if needed
        if ($.fn.dataTable) {
            $('#dataTable').DataTable({
                paging: false,
                searching: false
            });
        }

        // Initialize monthly orders chart if data exists
        @if(isset($monthlyStats) && count($monthlyStats) > 0)
        var ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
        var labels = [];
        var orderCounts = [];
        var orderAmounts = [];
        var monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        @foreach($monthlyStats as $stat)
            labels.push(monthNames[{{ $stat['month'] - 1 }}] + ' {{ $stat['year'] }}');
            orderCounts.push({{ $stat['orders_count'] }});
            orderAmounts.push({{ $stat['monthly_spent'] }});
        @endforeach
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: orderCounts,
                    backgroundColor: 'rgba(78, 115, 223, 0.8)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }, {
                    label: 'Total Belanja (Rp)',
                    data: orderAmounts,
                    type: 'line',
                    backgroundColor: 'rgba(255, 135, 178, 0.3)',
                    borderColor: 'rgba(255, 135, 178, 1)',
                    pointBorderWidth: 2,
                    pointBackgroundColor: 'rgba(255, 135, 178, 1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
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
        @endif
    });
</script>
@endsection 