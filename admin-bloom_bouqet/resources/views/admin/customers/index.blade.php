@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title"><span class="text-pink">Daftar Pelanggan</span></h3>
                <p class="text-muted">Kelola data pelanggan Bloom Bouquet</p>
            </div>
            <div class="d-flex">
                <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau nomor telepon..." 
                               value="{{ request('search') }}">
                        <button class="btn add-new-btn" type="submit">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Customer Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Total Pelanggan</h6>
                            <h3 class="fw-bold">{{ $statistics['total_customers'] ?? $customers->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Pelanggan Baru Bulan Ini</h6>
                            <h3 class="fw-bold">{{ $statistics['new_customers_this_month'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer List Card -->
    <div class="card table-card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Data Pelanggan</h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(count($customers) > 0)
            <div class="table-responsive">
                <table class="table category-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Total Pesanan</th>
                            <th>Total Belanja</th>
                            <th>Tgl Registrasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            @if(!str_contains($customer->email ?? '', '@guestgmail.com'))
                            <tr class="category-item">
                                <td>{{ $customer->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="category-icon-container me-2 bg-primary">
                                            <span class="text-white">{{ substr($customer->full_name ?? $customer->username ?? 'U', 0, 1) }}</span>
                                        </div>
                                        <span>{{ $customer->full_name ?? $customer->username }}</span>
                                    </div>
                                </td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge product-count-badge">{{ $customer->orders_count ?? 0 }}</span>
                                </td>
                                <td>Rp{{ number_format($customer->orders_sum_total_amount ?? 0, 0, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($customer->created_at)->format('d M Y') }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn action-btn info-btn" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $customers->links() }}
            </div>
            @else
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon mb-3">
                    <i class="fas fa-users"></i>
                </div>
                <h5>Tidak ada pelanggan yang ditemukan</h5>
                <p class="text-muted">Pelanggan akan muncul saat ada yang mendaftar di aplikasi</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Top Customers Sections -->
    @if(isset($statistics) && isset($statistics['top_spending_customers']) && count($statistics['top_spending_customers']) > 0)
    <div class="row mt-4">
        <!-- Top Spending Customers -->
        <div class="col-lg-6 mb-4">
            <div class="card table-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pelanggan dengan Pengeluaran Tertinggi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table category-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Total Belanja</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['top_spending_customers'] as $topCustomer)
                                @if(!str_contains($topCustomer->email ?? '', '@guestgmail.com'))
                                <tr class="category-item">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="category-icon-container me-2 bg-success">
                                                <span class="text-white">{{ substr($topCustomer->full_name ?? $topCustomer->username ?? 'U', 0, 1) }}</span>
                                            </div>
                                            <span>{{ $topCustomer->full_name ?? $topCustomer->username }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $topCustomer->email }}</td>
                                    <td>Rp{{ number_format($topCustomer->orders_sum_total_amount ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        <a href="{{ route('admin.customers.show', $topCustomer->id) }}" class="btn action-btn info-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Active Customers -->
        <div class="col-lg-6 mb-4">
            <div class="card table-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pelanggan Paling Aktif</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table category-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Jumlah Pesanan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['most_active_customers'] as $activeCustomer)
                                @if(!str_contains($activeCustomer->email ?? '', '@guestgmail.com'))
                                <tr class="category-item">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="category-icon-container me-2 bg-info">
                                                <span class="text-white">{{ substr($activeCustomer->full_name ?? $activeCustomer->username ?? 'U', 0, 1) }}</span>
                                            </div>
                                            <span>{{ $activeCustomer->full_name ?? $activeCustomer->username }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $activeCustomer->email }}</td>
                                    <td class="text-center">
                                        <span class="badge product-count-badge">{{ $activeCustomer->orders_count }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.customers.show', $activeCustomer->id) }}" class="btn action-btn info-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .text-pink {
        color: #FF87B2 !important;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .add-new-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .add-new-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
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
    
    .category-icon-container {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
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
    
    .table-card {
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
    
    .category-table {
        margin-bottom: 0;
    }
    
    .category-table th {
        font-weight: 600;
        color: #555;
        border-top: none;
        border-bottom: 2px solid #f0f0f0;
        padding: 1rem;
    }
    
    .category-item td {
        vertical-align: middle;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
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
    
    .product-count-badge {
        background-color: #FF87B2;
        color: white;
        padding: 0.5rem 0.8rem;
        border-radius: 6px;
        font-weight: 500;
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
@endsection 