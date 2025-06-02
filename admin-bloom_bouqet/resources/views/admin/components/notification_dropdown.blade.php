{{-- Notification Dropdown Component --}}
<div class="dropdown notification-dropdown">
    <a class="notification-icon-wrapper position-relative" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <i class="fas fa-bell notification-icon"></i>
        <span class="notification-badge {{ $unreadNotificationCount > 0 ? '' : 'd-none' }}" id="notification-badge">
            {{ $unreadNotificationCount ?? 0 }}
        </span>
    </a>
    
    <div class="dropdown-menu dropdown-menu-end notifications-dropdown shadow" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0">Notifikasi</h6>
            <button class="btn btn-sm btn-pink" id="markAllReadBtn">Tandai Semua Dibaca</button>
        </div>
        
        <div class="notifications-body" id="notificationsContainer">
            <!-- Empty state for no notifications -->
            <div class="text-center py-5" id="emptyNotifications">
                <div class="mb-3">
                    <i class="fas fa-bell-slash text-muted" style="font-size: 48px;"></i>
                </div>
                <p class="text-muted mb-0">Tidak ada notifikasi</p>
            </div>
            
            <!-- Notifications list -->
            <div id="notificationsList" class="list-group list-group-flush">
                <!-- Notifications will be loaded here via AJAX -->
            </div>
            
            <!-- Loading state -->
            <div class="text-center py-4 d-none" id="loadingNotifications">
                <div class="spinner-border text-pink" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted small">Memuat notifikasi...</p>
            </div>
        </div>
        
        <div class="text-center p-2 border-top">
            <a href="{{ route('admin.notifications.index') }}" class="text-decoration-none text-primary notification-page-link">Lihat Semua Notifikasi</a>
        </div>
    </div>
</div>

<style>
.notification-dropdown {
    position: relative;
}
.notification-icon-wrapper {
    padding: 8px;
    color: #666;
}
.notification-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: #FF6B6B;
    color: white;
    border-radius: 50%;
    font-size: 0.6rem;
    padding: 2px 5px;
    min-width: 15px;
    text-align: center;
}
.btn-pink {
    background-color: #D46A9F;
    color: white;
    border: none;
    font-size: 0.8rem;
}
.btn-pink:hover {
    background-color: #c55a8e;
    color: white;
}
.notification-action-btn {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 10px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #0d6efd;
    text-decoration: none;
    font-size: 0.8rem;
}
.notification-action-btn:hover {
    background-color: #e9ecef;
}
/* Fix dropdown z-index */
.notifications-dropdown {
    z-index: 10000 !important;
}
/* Fix notification item styling */
.list-group-item.border-0 {
    cursor: pointer;
}
/* Make sure notifications appear above all other elements */
.notifications-dropdown.show {
    position: absolute !important;
    z-index: 10000 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationsList = document.getElementById('notificationsList');
    const emptyNotifications = document.getElementById('emptyNotifications');
    const loadingNotifications = document.getElementById('loadingNotifications');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    
    // Load notifications when dropdown is opened
    document.getElementById('notificationDropdown').addEventListener('show.bs.dropdown', function() {
        loadNotifications();
    });
    
    // Prevent dropdown from closing when clicking inside
    document.querySelector('.notifications-dropdown').addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Mark all as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markAllAsRead();
        });
    }
    
    // Ensure notification page link opens in same window without navbar issues
    document.querySelector('.notification-page-link').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Store in sessionStorage that we're navigating to notifications page
        sessionStorage.setItem('navigatingToNotifications', 'true');
        window.location.href = this.href;
    });
    
    function loadNotifications() {
        // Show loading state
        emptyNotifications.classList.add('d-none');
        notificationsList.classList.add('d-none');
        loadingNotifications.classList.remove('d-none');
        
        // Fetch notifications from server
        fetch('/admin/notifications/latest')
            .then(response => response.json())
            .then(data => {
                // Hide loading state
                loadingNotifications.classList.add('d-none');
                
                if (data.notifications && data.notifications.length > 0) {
                    // Show notifications list
                    notificationsList.classList.remove('d-none');
                    
                    // Clear current list
                    notificationsList.innerHTML = '';
                    
                    // Add notifications to list
                    data.notifications.forEach(notification => {
                        const notificationElement = document.createElement('div');
                        notificationElement.classList.add('list-group-item', 'list-group-item-action', 'border-0', 'py-3');
                        notificationElement.setAttribute('data-notification-id', notification.id);
                        
                        if (notification.status === 'unread') {
                            notificationElement.classList.add('bg-light');
                        }
                        
                        // Add icon based on notification type
                        let iconClass = 'fas fa-bell text-muted';
                        
                        switch (notification.type) {
                            case 'order':
                                iconClass = 'fas fa-shopping-bag text-primary';
                                break;
                            case 'payment':
                                iconClass = 'fas fa-credit-card text-success';
                                break;
                            case 'product':
                                iconClass = 'fas fa-box text-warning';
                                break;
                            case 'user':
                                iconClass = 'fas fa-user text-info';
                                break;
                            case 'system':
                                iconClass = 'fas fa-cog text-secondary';
                                break;
                        }
                        
                        // Generate action button text based on notification type
                        let actionText = 'Lihat Detail';
                        switch (notification.type) {
                            case 'order':
                                actionText = 'Lihat Pesanan';
                                break;
                            case 'payment':
                                actionText = 'Lihat Pembayaran';
                                break;
                            case 'product':
                                actionText = 'Lihat Produk';
                                break;
                            case 'user':
                                actionText = 'Lihat Pelanggan';
                                break;
                        }
                        
                        notificationElement.innerHTML = `
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="${iconClass} me-2"></i>${notification.title}</h6>
                                <small class="text-muted">${notification.time_ago}</small>
                            </div>
                            <p class="mb-0 text-muted small">${notification.message}</p>
                            ${notification.url ? `<div class="mt-2"><a href="${notification.url}" class="notification-action-btn" data-notification-id="${notification.id}">${actionText}</a></div>` : ''}
                        `;
                        
                        notificationsList.appendChild(notificationElement);
                        
                        // Mark as read when notification body is clicked (not when action button is clicked)
                        notificationElement.addEventListener('click', function(e) {
                            // Only proceed if not clicking on the action button
                            if (!e.target.closest('.notification-action-btn')) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                // Mark notification as read
                                markAsRead(notification.id, function() {
                                    // Store in sessionStorage that we're navigating from a notification
                                    if (notification.url) {
                                        sessionStorage.setItem('navigatingFromNotification', 'true');
                                        sessionStorage.setItem('notificationId', notification.id);
                                        window.location.href = notification.url;
                                    } else {
                                        // If no specific URL, go to notifications page
                                        sessionStorage.setItem('navigatingToNotifications', 'true');
                                        window.location.href = "{{ route('admin.notifications.index') }}";
                                    }
                                });
                            }
                        });
                        
                        // Add separate event listener for action button to prevent event bubbling
                        const actionBtn = notificationElement.querySelector('.notification-action-btn');
                        if (actionBtn) {
                            actionBtn.addEventListener('click', function(e) {
                                e.stopPropagation(); // Stop event from bubbling up to parent
                                const notificationId = this.getAttribute('data-notification-id');
                                
                                // Store navigation info in sessionStorage
                                sessionStorage.setItem('navigatingFromNotification', 'true');
                                sessionStorage.setItem('notificationId', notificationId);
                                
                                // Mark as read when action button is clicked
                                markAsRead(notificationId);
                            });
                        }
                    });
                } else {
                    // Show empty state
                    emptyNotifications.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                loadingNotifications.classList.add('d-none');
                emptyNotifications.classList.remove('d-none');
            });
    }
    
    function markAsRead(notificationId, callback) {
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
                updateNotificationBadge();
                
                // Update UI to show notification as read
                const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notification) {
                    notification.classList.remove('bg-light');
                }
                
                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
            // Still execute callback even if there was an error
            if (typeof callback === 'function') {
                callback();
            }
        });
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
                // Update UI to show all notifications as read
                document.querySelectorAll('#notificationsList .bg-light').forEach(item => {
                    item.classList.remove('bg-light');
                });
                
                // Update the badge
                updateNotificationBadge(0);
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    function updateNotificationBadge(count = null) {
        if (count !== null) {
            updateBadge(count);
        } else {
            // Fetch the current count
            fetch('/admin/notifications/unread-count')
                .then(response => response.json())
                .then(data => {
                    updateBadge(data.count);
                })
                .catch(error => console.error('Error updating notification badge:', error));
        }
        
        function updateBadge(count) {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = count;
                if (count > 0) {
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }
        }
    }
});
</script> 