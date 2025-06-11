{{-- Order Notification Component --}}
<div class="notification-item order-notification {{ $notification->is_read ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
    <div class="notification-icon-container">
        <i class="fas fa-shopping-bag"></i>
    </div>
    <div class="notification-content-wrapper">
        <div class="notification-title">
            Orderan baru dari Storefront
        </div>
        <div class="notification-content">
            {{ $notification->data['customer_name'] ?? 'Pelanggan' }}
            <div>
                memesan {{ $notification->data['items_count'] ?? 1 }} produk, 
                sudah konfirmasi bayar Rp{{ number_format($notification->data['total'] ?? 0, 0, ',', '.') }}, mohon
                segera konfirmasi pemesanan
            </div>
        </div>
        <div class="notification-time">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
</div> 