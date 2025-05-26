{{-- Notification Dropdown Component --}}
<div class="dropdown">
    <a class="notification-icon-wrapper" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell notification-icon"></i>
        <span class="notification-badge {{ $unreadNotificationCount > 0 ? '' : 'd-none' }}" id="notification-badge">
            {{ $unreadNotificationCount ?? 0 }}
        </span>
    </a>
    
    <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationDropdown">
        <div class="notifications-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">ACTIVITY</h6>
            <a href="{{ route('admin.notifications.index') }}" class="text-primary">Lihat semua</a>
        </div>
        <div class="notifications-body" id="notificationsContainer">
            <!-- Notifications will be loaded here via AJAX -->
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted small">Memuat notifikasi...</p>
            </div>
        </div>
    </div>
</div> 