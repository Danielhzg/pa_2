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
                                @if($order->status == 'waiting_for_payment')
                                    Menunggu Pembayaran
                                @elseif($order->status == 'processing')
                                    Pesanan Diproses
                                @elseif($order->status == 'shipping')
                                    Dalam Pengiriman
                                @elseif($order->status == 'delivered')
                                    Pesanan Selesai
                                @elseif($order->status == 'cancelled')
                                    Dibatalkan
                                @endif
                            </span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="waiting_for_payment">
                                    Menunggu Pembayaran
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="processing">
                                    Pesanan Diproses
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="shipping">
                                    Dalam Pengiriman
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item status-option" href="#" data-status="delivered">
                                    Pesanan Selesai
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
                        <h5>{{ $order->user->name ?? 'Guest' }}</h5>
                        @if($order->user)
                            <a href="{{ route('admin.customers.show', $order->user->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt"></i> Lihat Profil
                            </a>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Email</h6>
                        <p>{{ $order->user->email ?? 'Tidak tersedia' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Nomor Telepon</h6>
                        <p>{{ $order->user->phone ?? 'Tidak tersedia' }}</p>
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
                        if (status === 'waiting_for_payment') displayStatus = 'Menunggu Pembayaran';
                        else if (status === 'processing') displayStatus = 'Pesanan Diproses';
                        else if (status === 'shipping') displayStatus = 'Dalam Pengiriman';
                        else if (status === 'delivered') displayStatus = 'Pesanan Selesai';
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