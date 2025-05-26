{{-- Payment Notification Component --}}
<div class="notification-item payment-notification {{ $notification->read_at ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
    <div class="notification-icon-container">
        <i class="fas fa-credit-card"></i>
    </div>
    <div class="notification-content-wrapper">
        <div class="notification-title">
            Orderan baru dari Payment Gateway
        </div>
        <div class="notification-content">
            {{ $notification->data['customer_name'] ?? 'Pelanggan' }}
            <div>
                telah melakukan pembayaran order #{{ $notification->data['order_id'] ?? 'N/A' }} dengan nominal
                Rp{{ number_format($notification->data['total'] ?? 0, 0, ',', '.') }}, segera proses pesanan
            </div>
        </div>
        <div class="notification-time">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
</div> 