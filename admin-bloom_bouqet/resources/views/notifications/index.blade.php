@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold text-primary">Notifikasi</h5>
                    <button id="markAllRead" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
                    </button>
                </div>

                <div class="card-body p-0">
                    @if($notifications->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada notifikasi</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                <div class="list-group-item list-group-item-action border-0 py-3 px-4 {{ $notification->isUnread() ? 'unread' : '' }}"
                                     data-notification-id="{{ $notification->id }}">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            @php
                                                $iconClass = 'fas fa-bell';
                                                $bgClass = 'bg-light text-muted';
                                                
                                                if (isset($notification->data['type'])) {
                                                    switch($notification->data['type']) {
                                                        case 'order':
                                                            $iconClass = 'fas fa-shopping-bag';
                                                            $bgClass = 'bg-primary text-white';
                                                            break;
                                                        case 'payment':
                                                            $iconClass = 'fas fa-credit-card';
                                                            $bgClass = 'bg-success text-white';
                                                            break;
                                                        case 'product':
                                                            $iconClass = 'fas fa-box';
                                                            $bgClass = 'bg-warning text-white';
                                                            break;
                                                        case 'user':
                                                            $iconClass = 'fas fa-user';
                                                            $bgClass = 'bg-info text-white';
                                                            break;
                                                        case 'system':
                                                            $iconClass = 'fas fa-cog';
                                                            $bgClass = 'bg-secondary text-white';
                                                            break;
                                                    }
                                                }
                                            @endphp
                                            <div class="rounded-circle {{ $bgClass }}" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                                <i class="{{ $iconClass }}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1 fw-semibold">{{ $notification->data['title'] ?? 'Notifikasi' }}</h5>
                                                <small class="text-muted">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <p class="mb-2">{{ $notification->data['message'] ?? $notification->data['body'] ?? '' }}</p>
                                            @if(isset($notification->data['order_id']))
                                                <a href="{{ route('admin.orders.show', $notification->data['order_id']) }}" 
                                                   class="btn btn-sm btn-outline-primary rounded-pill mt-2">
                                                    <i class="fas fa-eye me-1"></i> Lihat Detail Pesanan
                                                </a>
                                            @elseif(isset($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" 
                                                   class="btn btn-sm btn-outline-primary rounded-pill mt-2">
                                                    <i class="fas fa-external-link-alt me-1"></i> Lihat Detail
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="px-4 py-3">
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
        background-color: rgba(0, 123, 255, 0.05);
        border-left: 4px solid #FF87B2 !important;
    }
    
    .list-group-item {
        transition: all 0.3s ease;
    }
    
    .list-group-item:hover {
        background-color: rgba(255, 135, 178, 0.05);
    }
    
    .list-group-item.unread:hover {
        background-color: rgba(255, 135, 178, 0.1);
    }
    
    .btn-primary {
        background-color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .btn-primary:hover {
        background-color: #D46A9F;
        border-color: #D46A9F;
    }
    
    .btn-outline-primary {
        color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .btn-outline-primary:hover {
        background-color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .text-primary {
        color: #FF87B2 !important;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .pagination .page-link {
        color: #FF87B2;
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
                document.querySelectorAll('.list-group-item.unread').forEach(item => {
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
                    badge.textContent = data.count || data.unread_count || 0;
                    if ((data.count || data.unread_count || 0) === 0) {
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