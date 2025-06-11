{{-- System Notification Component --}}
<div class="notification-item system-notification {{ $notification->is_read ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
    <div class="notification-icon-container">
        <i class="fas fa-cog"></i>
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
                <i class="fas fa-external-link-alt"></i> {{ $notification->data['action_text'] ?? 'Lihat' }}
            </a>
        </div>
        @endif
    </div>
</div> 