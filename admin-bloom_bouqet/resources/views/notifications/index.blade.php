@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifikasi</h5>
                    <button id="markAllRead" class="btn btn-sm btn-outline-primary">
                        Tandai Semua Dibaca
                    </button>
                </div>

                <div class="card-body">
                    @if($notifications->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada notifikasi</p>
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($notifications as $notification)
                                <div class="list-group-item list-group-item-action {{ $notification->isUnread() ? 'unread' : '' }}"
                                     data-notification-id="{{ $notification->id }}">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $notification->title }}</h6>
                                        <small class="text-muted">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <p class="mb-1">{{ $notification->message }}</p>
                                    @if($notification->order_id)
                                        <a href="{{ route('orders.show', $notification->order_id) }}" 
                                           class="btn btn-sm btn-outline-primary mt-2">
                                            Lihat Detail Pesanan
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .unread {
        background-color: rgba(0, 123, 255, 0.1);
        border-left: 4px solid #007bff;
    }
    
    .list-group-item {
        transition: all 0.3s ease;
    }
    
    .list-group-item:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    .list-group-item.unread:hover {
        background-color: rgba(0, 123, 255, 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.classList.contains('btn')) {
                const notificationId = this.dataset.notificationId;
                markAsRead(notificationId);
            }
        });
    });

    // Mark all notifications as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        markAllAsRead();
    });

    function markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
                notification.classList.remove('unread');
                updateUnreadCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function markAllAsRead() {
        fetch('/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.list-group-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateUnreadCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateUnreadCount() {
        fetch('/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('#notification-badge');
                if (badge) {
                    badge.textContent = data.unread_count;
                    if (data.unread_count === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Check for new notifications every minute
    setInterval(updateUnreadCount, 60000);
});
</script>
@endpush
@endsection 