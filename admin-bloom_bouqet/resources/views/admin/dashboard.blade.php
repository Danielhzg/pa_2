@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('styles')
<style>
    /* Unread order highlighting */
    tr.unread-order {
        background-color: rgba(255, 135, 178, 0.1) !important;
        font-weight: 500;
        position: relative;
    }
    
    tr.unread-order::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: var(--primary-color);
    }
    
    /* Animation for new orders */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .new-order-indicator {
        animation: pulse 1.5s infinite;
    }
    
    .quick-action-card {
        transition: all 0.3s;
        border: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(255, 135, 178, 0.2);
    }
    
    .product-item {
        transition: all 0.3s;
        border-radius: 10px;
    }
    
    .product-item:hover {
        background-color: rgba(255, 230, 238, 0.3);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 182, 193, 0.1);
    }
    
    .text-pink {
        color: #FF87B2 !important;
        font-weight: 600;
    }
    
    /* Add animation to text-pink */
    .text-pink:hover {
        color: #D46A9F !important;
        transition: color 0.3s;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <!-- Stats Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $totalOrders ?? 0 }}</h3>
                    <p>Total Orders</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-spa"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $totalProducts ?? 0 }}</h3>
                    <p>Total Products</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $totalCustomers ?? 0 }}</h3>
                    <p>Total Customers</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ isset($totalRevenue) ? 'Rp '.number_format($totalRevenue, 0, ',', '.') : 'Rp 0' }}</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stats-decoration"></div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i> Recent Orders</span>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light"><i class="fas fa-eye"></i> View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>
                                            @if($order->user)
                                                    <div>
                                                        <span class="fw-medium">{{ $order->user->full_name }}</span>
                                                        <small class="text-muted d-block">{{ $order->user->email }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
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
                                            <span class="badge bg-{{ $statusClass }}">{{ $order->status_label }}</span>
                                        </td>
                                        <td>{{ $order->created_at->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">No recent orders</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Popular Products -->
        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-fire me-2"></i> Popular Products
                </div>
                <div class="card-body">
                    @forelse($popularProducts ?? [] as $product)
                        <div class="product-item d-flex align-items-center mb-3 p-2 border-bottom">
                            <div class="product-image me-3">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="no-image rounded bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="product-details">
                                <h6 class="mb-0">{{ $product->name }}</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                    <span class="text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i> No popular products found.</p>
                        </div>
                    @endforelse
                    
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary w-100 mt-3">
                        <i class="fas fa-plus me-1"></i> Manage Products
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Customers -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-friends me-2"></i> Recent Customers</span>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-light"><i class="fas fa-eye"></i> View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCustomers ?? [] as $customer)
                                <tr>
                                    <td>{{ $customer->username }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i> No recent customers found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="{{ route('admin.products.create') }}" class="card quick-action-card h-100 text-decoration-none">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="quick-action-icon mb-3" style="background: linear-gradient(135deg, #FF87B2, #FF5A87); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-plus text-white fa-2x"></i>
                                    </div>
                                    <h5 class="text-pink">Add Product</h5>
                                    <p class="text-muted text-center mb-0 small">Create a new product listing</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-6">
                            <a href="{{ route('admin.categories.index') }}" class="card quick-action-card h-100 text-decoration-none">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="quick-action-icon mb-3" style="background: linear-gradient(135deg, #FFC0D9, #FF87B2); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tags text-white fa-2x"></i>
                                    </div>
                                    <h5 class="text-pink">Categories</h5>
                                    <p class="text-muted text-center mb-0 small">Manage product categories</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-6">
                            <a href="{{ route('admin.carousels.index') }}" class="card quick-action-card h-100 text-decoration-none">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="quick-action-icon mb-3" style="background: linear-gradient(135deg, #D46A9F, #FF87B2); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-images text-white fa-2x"></i>
                                    </div>
                                    <h5 class="text-pink">Carousels</h5>
                                    <p class="text-muted text-center mb-0 small">Manage homepage carousels</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-6">
                            <a href="{{ route('admin.orders.index') }}" class="card quick-action-card h-100 text-decoration-none">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="quick-action-icon mb-3" style="background: linear-gradient(135deg, #FF87B2, #FFC0D9); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-shopping-cart text-white fa-2x"></i>
                                    </div>
                                    <h5 class="text-pink">Orders</h5>
                                    <p class="text-muted text-center mb-0 small">View and manage orders</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-6">
                            <a href="{{ route('admin.customers.index') }}" class="card quick-action-card h-100 text-decoration-none">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="quick-action-icon mb-3" style="background: linear-gradient(135deg, #FF87B2, #FFC0D9); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-users text-white fa-2x"></i>
                                    </div>
                                    <h5 class="text-pink">Customers</h5>
                                    <p class="text-muted text-center mb-0 small">Manage customer data</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
