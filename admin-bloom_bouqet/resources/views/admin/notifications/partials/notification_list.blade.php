@if($notifications->isEmpty())
    <div class="no-notifications">
        <i class="fas fa-bell-slash"></i>
        <p>Tidak ada notifikasi</p>
    </div>
@else
    @foreach($notifications as $notification)
        @if($notification->type === 'order')
            @include('admin.components.order_notification', ['notification' => $notification])
        @elseif($notification->type === 'payment')
            @include('admin.components.payment_notification', ['notification' => $notification])
        @elseif($notification->type === 'shopee')
            @include('admin.components.shopee_notification', ['notification' => $notification])
        @elseif($notification->type === 'system')
            @include('admin.components.system_notification', ['notification' => $notification])
        @else
            <div class="notification-item {{ $notification->read_at ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
                <div class="notification-icon-container">
                    @if($notification->type === 'user')
                        <i class="fas fa-user"></i>
                    @else
                        <i class="fas fa-bell"></i>
                    @endif
                </div>
                <div class="notification-content-wrapper">
                    <div class="notification-title">
                        {{ $notification->title }}
                    </div>
                    <div class="notification-content">
                        {{ $notification->message }}
                    </div>
                    <div class="notification-time">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                    @if(isset($notification->data['action_url']))
                    <div class="notification-actions">
                        <a href="{{ $notification->data['action_url'] }}" class="btn btn-sm btn-outline-secondary">
                            {{ $notification->data['action_text'] ?? 'Lihat' }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
@endif 