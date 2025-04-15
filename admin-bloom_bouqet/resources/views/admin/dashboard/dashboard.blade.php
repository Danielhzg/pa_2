@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="dashboard-header mb-4">
        <h3 class="welcome-text">Selamat Datang di Bloom Bouqet Dashboard</h3>
        <p class="text-muted">Kelola toko bunga Anda dengan mudah dan efisien</p>
    </div>
    
    <div class="row mt-4 dashboard-stats">
        <!-- Total Products -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container">
                        <i class="fas fa-seedling stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $totalProducts }}</h2>
                        <p class="stat-label mb-0">Produk</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.products.index') }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Categories -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container">
                        <i class="fas fa-tags stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $totalCategories }}</h2>
                        <p class="stat-label mb-0">Kategori</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.categories.index') }}" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Orders -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container">
                        <i class="fas fa-shopping-bag stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $totalOrders }}</h2>
                        <p class="stat-label mb-0">Pesanan</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Customers -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-container">
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="ms-3 stat-details">
                        <h2 class="stat-value">{{ $totalCustomers }}</h2>
                        <p class="stat-label mb-0">Pelanggan</p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#" class="text-decoration-none">
                        <small>Lihat Semua <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div class="row mt-4">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Aktivitas Terbaru</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Hari Ini</a></li>
                            <li><a class="dropdown-item" href="#">Minggu Ini</a></li>
                            <li><a class="dropdown-item" href="#">Bulan Ini</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="activity-item d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="activity-icon-container me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Pesanan Baru #12345</h6>
                            <small class="text-muted">2 jam yang lalu</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success">Baru</span>
                        </div>
                    </div>
                    
                    <div class="activity-item d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="activity-icon-container me-3">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Pelanggan Baru Terdaftar</h6>
                            <small class="text-muted">5 jam yang lalu</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-info">Info</span>
                        </div>
                    </div>
                    
                    <div class="activity-item d-flex align-items-center">
                        <div class="activity-icon-container me-3">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Ulasan Produk Baru</h6>
                            <small class="text-muted">8 jam yang lalu</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-warning">Review</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="#" class="btn btn-sm btn-view-all">Lihat Semua Aktivitas</a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.products.create') }}" class="btn quick-action-btn mb-2">
                            <i class="fas fa-plus me-2"></i> Tambah Produk Baru
                        </a>
                        <a href="{{ route('admin.categories.create') }}" class="btn quick-action-btn mb-2">
                            <i class="fas fa-folder-plus me-2"></i> Tambah Kategori Baru
                        </a>
                        <a href="{{ route('admin.carousels.create') }}" class="btn quick-action-btn mb-2">
                            <i class="fas fa-image me-2"></i> Tambah Carousel Baru
                        </a>
                        <a href="#" class="btn quick-action-btn">
                            <i class="fas fa-cog me-2"></i> Pengaturan Aplikasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-header {
        padding: 1rem 0;
    }
    
    .welcome-text {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .text-gradient {
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-card {
        border-radius: 15px;
        border: none;
        background-color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    
    .stat-icon-container {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon {
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0;
        color: var(--pink-dark);
    }
    
    .stat-label {
        color: #888;
        font-size: 0.9rem;
    }
    
    .card-footer {
        background-color: transparent;
        padding: 0.75rem 1.25rem;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .card-footer a {
        color: var(--pink-dark); /* Ensure the text is visible */
        font-weight: 500;
        transition: color 0.2s;
    }

    .card-footer a:hover {
        color: var(--pink-dark); /* Make the hover color more pink */
    }
    
    .activity-icon-container {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background-color: rgba(255,105,180,0.1);
        color: var(--pink-primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .activity-item {
        transition: transform 0.2s;
    }
    
    .activity-item:hover {
        transform: translateX(5px);
    }
    
    .btn-view-all {
        background-color: var(--pink-light);
        color: var(--pink-dark);
        border: none;
        transition: background-color 0.3s;
    }
    
    .btn-view-all:hover {
        background-color: var(--pink-primary);
        color: white;
    }
    
    .quick-action-btn {
        background-color: white;
        color: var(--pink-dark);
        border: 1px solid rgba(255,105,180,0.3);
        text-align: left;
        transition: all 0.3s;
    }
    
    .quick-action-btn:hover {
        background-color: var(--pink-primary);
        color: white;
        transform: translateX(5px);
    }
    
    @media (max-width: 768px) {
        .stat-value {
            font-size: 1.5rem;
        }
        
        .stat-icon-container {
            width: 50px;
            height: 50px;
        }
        
        .dashboard-stats {
            margin-top: 1rem;
        }
    }
</style>
@endsection
