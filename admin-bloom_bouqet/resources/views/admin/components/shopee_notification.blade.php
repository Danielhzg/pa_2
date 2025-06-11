{{-- Shopee Notification Component --}}
<div class="notification-item shopee-notification {{ $notification->is_read ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
    <div class="notification-icon-container">
        <i class="fas fa-shopping-cart"></i>
    </div>
    <div class="notification-content-wrapper">
        <div class="notification-title">
            Orderan baru dari Shopee
        </div>
        <div class="notification-content">
            {{ $notification->data['customer_name'] ?? 'Pelanggan Shopee' }}
            <div>
                telah melakukan pembayaran order {{ $notification->data['order_id'] ?? 'N/A' }} dengan nominal
                Rp{{ number_format($notification->data['total'] ?? 0, 0, ',', '.') }}, segera proses pesanan
            </div>
        </div>
        <div class="notification-time">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
</div> 