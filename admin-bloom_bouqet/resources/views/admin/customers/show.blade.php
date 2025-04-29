@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Detail Pelanggan</h1>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Informasi Pelanggan</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-lg mb-3">
                            <i class="fas fa-user-circle fa-5x text-secondary"></i>
                        </div>
                        <h5>{{ $customer->name }}</h5>
                        <p class="text-muted">Member sejak {{ $customer->created_at->format('d M Y') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Email</h6>
                        <p>{{ $customer->email }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Nomor Telepon</h6>
                        <p>{{ $customer->phone ?? 'Tidak tersedia' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Alamat</h6>
                        <p>{{ $customer->address ?? 'Tidak tersedia' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Statistics -->
        <div class="col-md-8 mb-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Pesanan</h6>
                                    <h2 class="mb-0">{{ $stats['total_orders'] }}</h2>
                                </div>
                                <i class="fas fa-shopping-bag fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Belanja</h6>
                                    <h2 class="mb-0">Rp {{ number_format($stats['total_spent'] ?? 0, 0, ',', '.') }}</h2>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Rata-rata Belanja</h6>
                                    <h2 class="mb-0">Rp {{ number_format($stats['avg_order_value'] ?? 0, 0, ',', '.') }}</h2>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Pesanan Terakhir</h6>
                                    <h2 class="mb-0">{{ $stats['last_order'] ? $stats['last_order']->created_at->format('d M Y') : 'Belum ada' }}</h2>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Orders -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Riwayat Pesanan</h5>
        </div>
        <div class="card-body">
            @if(count($orders) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
                                    <td>{{ $order->id }}</td>
                                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        @if($order->status == 'pending')
                                            <span class="badge bg-warning">Menunggu</span>
                                        @elseif($order->status == 'processing')
                                            <span class="badge bg-info">Diproses</span>
                                        @elseif($order->status == 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($order->status == 'cancelled')
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->payment_method }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lihat
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
                    Pelanggan ini belum memiliki pesanan.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 