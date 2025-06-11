@extends('layouts.admin')

@section('title', 'Notifikasi')

@section('content')
<div class="container-fluid notification-page">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Notifikasi</h3>
                <p class="text-muted">Kelola notifikasi pesanan dan sistem</p>
            </div>
            <button id="markAllRead" class="btn btn-primary rounded-pill">
                <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Daftar Notifikasi</h5>
                </div>
                <div class="col-auto">
                    <button id="refreshNotifications" class="btn btn-sm btn-outline-primary rounded-pill" title="Refresh Notifications">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="notificationsContainer">
            @if($notifications->isEmpty())
                    <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada notifikasi</p>
                </div>
            @else
                    <div class="list-group list-group-flush notification-list">
                    @foreach($notifications as $notification)
                            <div class="list-group-item list-group-item-action notification-item border-0 py-3 px-4 {{ !$notification->is_read ? 'unread' : '' }}"
                             data-notification-id="{{ $notification->id }}">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        @php
                                            $iconClass = 'fas fa-bell';
                                            $bgClass = 'bg-light text-muted';
                                            
                                            if ($notification->type) {
                                                switch($notification->type) {
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
                                            <h5 class="mb-1 fw-semibold">{{ $notification->title }}</h5>
                                <small class="text-muted">
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                                        <p class="mb-2">{{ $notification->message }}</p>
                            <div class="mt-2 d-flex gap-2">
                                @if($notification->getUrl())
                                    <a href="{{ $notification->getUrl() }}" 
                                                class="btn btn-sm btn-outline-primary rounded-pill view-notification-btn"
                                       data-id="{{ $notification->id }}">
                                        <i class="fas fa-external-link-alt me-1"></i> {{ $notification->getActionText() }}
                                    </a>
                                @endif
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill mark-read-btn" data-id="{{ $notification->id }}">
                                    <i class="fas fa-check me-1"></i> Tandai Dibaca
                                </button>
                                        </div>
                                    </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                    <div class="p-4 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .notification-page {
        position: relative;
        z-index: 1;
    }
    
    .notification-list {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .notification-item {
        transition: all 0.3s ease;
    }
    
    .notification-item:hover {
        background-color: rgba(255, 135, 178, 0.05);
    }
    
    .notification-item.unread {
        background-color: rgba(255, 135, 178, 0.05);
        border-left: 4px solid #FF87B2 !important;
    }
    
    .notification-item.unread:hover {
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
    
    /* Pastikan tidak ada elemen yang menutupi halaman notifikasi */
    body.notifications-page .modal-backdrop {
        z-index: 1040 !important;
    }
    
    body.notifications-page .modal {
        z-index: 1050 !important;
    }
    
    /* Hindari konflik dengan dropdown */
    .dropdown-menu.notifications-dropdown {
        display: none !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add class to body to help with targeting styles
    document.body.classList.add('notifications-page');
    
    // Pastikan dropdown notifikasi tidak muncul di halaman ini
    const notificationDropdown = document.querySelector('.notification-dropdown');
    if (notificationDropdown) {
        notificationDropdown.style.display = 'none';
    }
    
    // Mark single notification as read via button
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const notificationId = this.dataset.id;
            markAsRead(notificationId);
        });
    });

    // Handle view notification buttons
    document.querySelectorAll('.view-notification-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const notificationId = this.dataset.id;
            markAsRead(notificationId, false); // Mark as read but don't reload page
        });
    });

    // Mark all notifications as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        markAllAsRead();
    });

    // Refresh notifications
    document.getElementById('refreshNotifications').addEventListener('click', function() {
        refreshNotifications();
    });

    function markAsRead(notificationId, shouldReloadUI = true) {
        fetch(`/admin/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notification) {
                    notification.classList.remove('unread');
                }
                updateUnreadCount();
                
                // Reload UI if needed
                if (shouldReloadUI) {
                    // Add visual feedback
                    notification.style.backgroundColor = 'rgba(46, 125, 50, 0.1)';
                    setTimeout(() => {
                        notification.style.backgroundColor = '';
                    }, 1000);
                }
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
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateUnreadCount();
                
                // Visual feedback
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-success alert-dismissible fade show m-3';
                successMessage.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> Semua notifikasi telah ditandai sebagai dibaca.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('#notificationsContainer').prepend(successMessage);
                
                // Auto dismiss after 3 seconds
                setTimeout(() => {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            alert.remove();
                        }, 300);
                    }
                }, 3000);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateUnreadCount() {
        fetch('/admin/notifications/unread-count', {
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
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

    function refreshNotifications() {
        // Tampilkan loading spinner
        document.getElementById('notificationsContainer').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Memuat notifikasi...</p>
            </div>
        `;
        
        // Reload halaman dengan AJAX
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#notificationsContainer');
                
                if (newContainer) {
                    document.getElementById('notificationsContainer').innerHTML = newContainer.innerHTML;
                    
                    // Reattach event listeners
                    document.querySelectorAll('.mark-read-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const notificationId = this.dataset.id;
                            markAsRead(notificationId);
                        });
                    });
                    
                    document.querySelectorAll('.view-notification-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            const notificationId = this.dataset.id;
                            markAsRead(notificationId, false);
                        });
                    });
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.reload();
            });
    }
});
</script>
@endpush
@endsection 