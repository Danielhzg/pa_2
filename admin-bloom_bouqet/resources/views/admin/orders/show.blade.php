@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Detail Pesanan #{{ $order->id }}</h1>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Informasi Pesanan</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Status: 
                            <span class="fw-bold status-text">
                                @if($order->status == 'pending')
                                    Menunggu
                                @elseif($order->status == 'processing')
                                    Diproses
                                @elseif($order->status == 'completed')
                                    Selesai
                                @elseif($order->status == 'cancelled')
                                    Dibatalkan
                                @endif
                            </span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="pending">
                                    Menunggu
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="processing">
                                    Diproses
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="completed">
                                    Selesai
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="cancelled">
                                    Dibatalkan
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tanggal Pesanan:</strong></p>
                            <p>{{ $order->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Metode Pembayaran:</strong></p>
                            <p>{{ ucfirst($order->payment_method) }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status Pembayaran:</strong></p>
                            <span class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}">
                                {{ $order->payment_status == 'paid' ? 'Lunas' : 'Belum Lunas' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>ID Transaksi:</strong></p>
                            <p>{{ $order->transaction_id ?? 'Tidak ada' }}</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Gambar</th>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            @if($item->product && $item->product->getPrimaryImage())
                                                <img src="{{ asset('storage/' . $item->product->getPrimaryImage()) }}" 
                                                    alt="{{ $item->product->name }}" class="img-thumbnail" style="max-height: 50px;">
                                            @else
                                                <div class="bg-light text-center p-2 rounded">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $item->product ? $item->product->name : 'Produk tidak tersedia' }}</td>
                                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Informasi Pelanggan</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-lg mb-3">
                            <i class="fas fa-user-circle fa-4x text-secondary"></i>
                        </div>
                        @if($order->user && !str_contains($order->user->email ?? '', '@guestgmail.com'))
                            <h5>{{ $order->user->full_name ?? $order->user->username ?? 'Pelanggan' }}</h5>
                            @if($order->user)
                                <a href="{{ route('admin.customers.show', $order->user->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i> Lihat Profil
                                </a>
                            @endif
                        @else
                            <h5>Pelanggan</h5>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Email</h6>
                        @if($order->user && !str_contains($order->user->email ?? '', '@guestgmail.com'))
                            <p>{{ $order->user->email }}</p>
                        @else
                            <p class="text-muted">-</p>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Nomor Telepon</h6>
                        <p>{{ $order->phone_number ?? ($order->user->phone ?? 'Tidak tersedia') }}</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Alamat Pengiriman</h6>
                        <p>{{ $order->shipping_address ?? $order->user->address ?? 'Tidak tersedia' }}</p>
                    </div>
                    
                    <div class="d-grid">
                        @if($order->user)
                            <a href="mailto:{{ $order->user->email }}" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope"></i> Kirim Email
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information Card -->
    <div class="card order-info-card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informasi Pembayaran</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Metode Pembayaran</div>
                <div class="col-md-8">{{ ucfirst($order->payment_method) }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Status Pembayaran</div>
                <div class="col-md-8">
                    <span class="payment-status-badge badge 
                        @if($order->payment_status == 'paid') bg-success
                        @elseif($order->payment_status == 'pending') bg-warning
                        @elseif($order->payment_status == 'expired') bg-secondary
                        @elseif($order->payment_status == 'failed') bg-danger
                        @elseif($order->payment_status == 'refunded') bg-info
                        @endif">
                        {{ $order->payment_status_label }}
                    </span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Total Pembayaran</div>
                <div class="col-md-8">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
            </div>
            
            @if($order->payment_deadline)
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Batas Waktu Pembayaran</div>
                <div class="col-md-8">
                    {{ $order->payment_deadline->format('d M Y H:i') }}
                    @if($order->payment_status == 'pending' && $order->payment_deadline->isPast())
                        <span class="badge bg-danger">Kadaluarsa</span>
                    @elseif($order->payment_status == 'pending')
                        <span class="badge bg-warning">
                            {{ $order->payment_deadline->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            @endif
            
            @if($order->paid_at)
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Tanggal Pembayaran</div>
                <div class="col-md-8">{{ $order->paid_at->format('d M Y H:i') }}</div>
            </div>
            @endif
            
            @if($order->qr_code_url)
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">QR Code Pembayaran</div>
                <div class="col-md-8">
                    <img src="{{ $order->qr_code_url }}" alt="QR Payment" class="img-thumbnail" style="max-width: 200px;">
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Order status update
        const statusOptions = document.querySelectorAll('.status-option');
        const statusText = document.querySelector('.status-text');
        
        statusOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const status = this.dataset.status;
                
                // Get CSRF token from meta tag
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Send AJAX request to update status
                fetch(`/admin/orders/{{ $order->id }}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status text
                        let displayStatus = '';
                        if (status === 'pending') displayStatus = 'Menunggu';
                        else if (status === 'processing') displayStatus = 'Diproses';
                        else if (status === 'completed') displayStatus = 'Selesai';
                        else if (status === 'cancelled') displayStatus = 'Dibatalkan';
                        
                        statusText.textContent = displayStatus;
                        
                        // Show success notification
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            Status pesanan berhasil diperbarui menjadi ${displayStatus}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.container-fluid').prepend(alertDiv);
                        
                        // Auto dismiss after 3 seconds
                        setTimeout(() => {
                            const bsAlert = new bootstrap.Alert(alertDiv);
                            bsAlert.close();
                        }, 3000);
                    } else {
                        // Show error notification
                        alert('Gagal memperbarui status pesanan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memperbarui status pesanan');
                });
            });
        });
    });
</script>
@endpush 