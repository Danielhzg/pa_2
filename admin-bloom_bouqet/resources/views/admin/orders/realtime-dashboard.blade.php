@extends('layouts.admin')

@section('title', 'Real-time Order Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Real-time Order Management</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshOrders()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-primary" onclick="toggleAutoRefresh()">
                <i class="fas fa-play" id="autoRefreshIcon"></i>
                <span id="autoRefreshText">Start Auto Refresh</span>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Waiting for Payment
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="waitingPaymentCount">
                                {{ $stats['waiting_payment'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Processing
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="processingCount">
                                {{ $stats['processing'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Shipping
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="shippingCount">
                                {{ $stats['shipping'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Delivered
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="deliveredCount">
                                {{ $stats['delivered'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearNotifications()">
                        Clear All
                    </button>
                </div>
                <div class="card-body">
                    <div id="notificationsContainer" style="max-height: 200px; overflow-y: auto;">
                        @if(isset($notifications) && count($notifications) > 0)
                            @foreach($notifications as $notification)
                                <div class="notification-item border-bottom py-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-sm">{{ $notification['message'] ?? 'New notification' }}</span>
                                        <small class="text-muted">{{ $notification['created_at'] ?? 'Just now' }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted mb-0">No new notifications</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
            <div class="d-flex gap-2">
                <select class="form-control form-control-sm" id="statusFilter" onchange="filterOrders()">
                    <option value="all">All Status</option>
                    <option value="waiting_for_payment">Waiting for Payment</option>
                    <option value="processing">Processing</option>
                    <option value="shipping">Shipping</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        @if(isset($orders))
                            @foreach($orders as $order)
                                <tr data-order-id="{{ $order->id }}" class="{{ !$order->is_read ? 'table-warning' : '' }}">
                                    <td>{{ $order->order_id }}</td>
                                    <td>
                                        @if($order->user)
                                            {{ $order->user->name }}
                                        @else
                                            @php
                                                $address = is_string($order->shipping_address) ? json_decode($order->shipping_address, true) : $order->shipping_address;
                                            @endphp
                                            {{ $address['name'] ?? 'Guest Customer' }}
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm status-select" 
                                                data-order-id="{{ $order->id }}" 
                                                onchange="updateOrderStatus({{ $order->id }}, this.value)">
                                            <option value="waiting_for_payment" {{ $order->status === 'waiting_for_payment' ? 'selected' : '' }}>Waiting for Payment</option>
                                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                                            <option value="shipping" {{ $order->status === 'shipping' ? 'selected' : '' }}>Shipping</option>
                                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-info" onclick="viewOrder({{ $order->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($order->payment_status === 'pending')
                                                <button class="btn btn-sm btn-success" onclick="markAsPaid({{ $order->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let autoRefreshInterval = null;
let isAutoRefreshActive = false;

// Auto refresh functionality
function toggleAutoRefresh() {
    if (isAutoRefreshActive) {
        clearInterval(autoRefreshInterval);
        isAutoRefreshActive = false;
        document.getElementById('autoRefreshIcon').className = 'fas fa-play';
        document.getElementById('autoRefreshText').textContent = 'Start Auto Refresh';
    } else {
        autoRefreshInterval = setInterval(refreshOrders, 10000); // Refresh every 10 seconds
        isAutoRefreshActive = true;
        document.getElementById('autoRefreshIcon').className = 'fas fa-pause';
        document.getElementById('autoRefreshText').textContent = 'Stop Auto Refresh';
    }
}

// Refresh orders and statistics
function refreshOrders() {
    fetch('{{ route("admin.order-management.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatistics(data.data);
            }
        })
        .catch(error => console.error('Error refreshing stats:', error));
    
    // Refresh notifications
    fetch('{{ route("admin.order-management.notifications") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotifications(data.data.notifications);
            }
        })
        .catch(error => console.error('Error refreshing notifications:', error));
    
    // Reload the page to refresh orders table
    location.reload();
}

// Update statistics
function updateStatistics(stats) {
    document.getElementById('waitingPaymentCount').textContent = stats.pending_orders || 0;
    document.getElementById('processingCount').textContent = stats.processing_orders || 0;
    document.getElementById('shippingCount').textContent = stats.shipping_orders || 0;
    document.getElementById('deliveredCount').textContent = stats.completed_orders || 0;
}

// Update notifications
function updateNotifications(notifications) {
    const container = document.getElementById('notificationsContainer');
    if (notifications.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No new notifications</p>';
        return;
    }
    
    container.innerHTML = notifications.map(notification => `
        <div class="notification-item border-bottom py-2">
            <div class="d-flex justify-content-between">
                <span class="text-sm">${notification.message}</span>
                <small class="text-muted">${new Date(notification.timestamp * 1000).toLocaleString()}</small>
            </div>
        </div>
    `).join('');
}

// Update order status
function updateOrderStatus(orderId, newStatus) {
    fetch(`{{ url('admin/order-management') }}/${orderId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Order status updated successfully', 'success');
            // Refresh the row
            setTimeout(refreshOrders, 1000);
        } else {
            showAlert('Failed to update order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating order status:', error);
        showAlert('Error updating order status', 'error');
    });
}

// Mark order as paid
function markAsPaid(orderId) {
    if (confirm('Mark this order as paid?')) {
        fetch(`{{ url('admin/order-management') }}/${orderId}/payment-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ payment_status: 'paid' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Payment status updated successfully', 'success');
                setTimeout(refreshOrders, 1000);
            } else {
                showAlert('Failed to update payment status', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            showAlert('Error updating payment status', 'error');
        });
    }
}

// View order details
function viewOrder(orderId) {
    fetch(`{{ url('admin/order-management') }}/${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderModalBody').innerHTML = html;
            $('#orderModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            showAlert('Error loading order details', 'error');
        });
}

// Clear notifications
function clearNotifications() {
    fetch('{{ route("admin.order-management.notifications.read") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('notificationsContainer').innerHTML = '<p class="text-muted mb-0">No new notifications</p>';
            showAlert('Notifications cleared', 'success');
        }
    })
    .catch(error => console.error('Error clearing notifications:', error));
}

// Filter orders
function filterOrders() {
    const status = document.getElementById('statusFilter').value;
    const url = new URL(window.location);
    if (status === 'all') {
        url.searchParams.delete('status');
    } else {
        url.searchParams.set('status', status);
    }
    window.location = url;
}

// Show alert
function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Start auto refresh by default
    toggleAutoRefresh();
});
</script>
@endsection
