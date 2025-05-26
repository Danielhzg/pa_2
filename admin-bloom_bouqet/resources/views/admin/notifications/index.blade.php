@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Notifikasi</h3>
                <p class="text-muted">Kelola notifikasi pesanan dan sistem</p>
            </div>
            <button id="markAllRead" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Daftar Notifikasi</h5>
                </div>
                <div class="col-auto">
                    <button id="refreshNotifications" class="btn btn-sm btn-outline-primary" title="Refresh Notifications">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($notifications->isEmpty())
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada notifikasi</p>
                </div>
            @else
                <div class="list-group notification-list">
                    @foreach($notifications as $notification)
                        <div class="list-group-item list-group-item-action notification-item {{ $notification->status === 'unread' ? 'unread' : '' }}"
                             data-notification-id="{{ $notification->id }}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $notification->title }}</h6>
                                <small class="text-muted">
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <p class="mb-1">{{ $notification->message }}</p>
                            @if($notification->order_id)
                                <div class="mt-2">
                                    <a href="{{ route('admin.orders.show', $notification->order_id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> Lihat Pesanan
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .notification-list {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .notification-item {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .notification-item:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    .notification-item.unread {
        background-color: rgba(0, 123, 255, 0.1);
        border-left: 3px solid #007bff;
    }
    
    .notification-item.unread:hover {
        background-color: rgba(0, 123, 255, 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.closest('a')) {
                const notificationId = this.dataset.notificationId;
                markAsRead(notificationId);
            }
        });
    });

    // Mark all notifications as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        markAllAsRead();
    });

    // Refresh notifications
    document.getElementById('refreshNotifications').addEventListener('click', function() {
        window.location.reload();
    });

    function markAsRead(notificationId) {
        fetch(`/admin/notifications/${notificationId}/mark-as-read`, {
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
        fetch('/admin/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateUnreadCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateUnreadCount() {
        fetch('/admin/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('#notification-badge');
                if (badge) {
                    badge.textContent = data.count;
                    if (data.count === 0) {
                        badge.classList.add('d-none');
                    } else {
                        badge.classList.remove('d-none');
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