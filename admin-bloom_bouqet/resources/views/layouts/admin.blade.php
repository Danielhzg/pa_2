<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - Bloom Bouquet</title>
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #FF87B2;
            --primary-light: #FFB6C1;
            --primary-dark: #D46A9F;
            --secondary-color: #FFC0D9;
            --accent-color: #FFE5EE;
            --dark-text: #333333;
            --light-text: #717171;
            --sidebar-width: 260px;
            --white: #FFFFFF;
            --light-bg: #F9F9F9;
            --card-shadow: 0 5px 15px rgba(255, 135, 178, 0.1);
            --hover-shadow: 0 8px 25px rgba(255, 135, 178, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        
        .sidebar-header .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .menu-heading {
            padding: 10px 20px;
            font-size: 12px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .nav-item {
            margin: 4px 10px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 10px 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .nav-link.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 20px 30px;
            min-height: 100vh;
            transition: all 0.3s;
            background: var(--light-bg);
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 20px;
            border-radius: 15px;
            background: var(--white);
            box-shadow: var(--card-shadow);
        }
        
        .page-title {
            color: var(--primary-dark);
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-dropdown button {
            background: var(--primary-light);
            border: none;
            display: flex;
            align-items: center;
            color: white;
            font-weight: 500;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .user-dropdown button:hover {
            background: var(--primary-dark);
        }
        
        .user-dropdown img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid white;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            font-size: 14px;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-light), var(--primary-color));
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .card-body {
            padding: 20px;
            background-color: var(--white);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 106, 159, 0.3);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 135, 178, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
        }
        
        .alert-danger {
            background-color: #FFEBEE;
            color: #C62828;
        }
        
        /* Stats Cards */
        .stats-card {
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            background-color: var(--white);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 15px;
            font-size: 24px;
            color: white;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
        }
        
        .stats-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .stats-info p {
            color: var(--light-text);
            margin: 0;
            font-size: 14px;
        }
        
        .stats-decoration {
            position: absolute;
            right: -15px;
            bottom: -15px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(45deg, transparent, rgba(255, 182, 193, 0.1));
            z-index: 0;
        }
        
        /* Forms */
        .form-control, .form-select {
            border: 1px solid #E0E0E0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 135, 178, 0.2);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        /* Tables */
        .table {
            width: 100%;
            box-shadow: var(--card-shadow);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
            border: none;
            padding: 15px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: rgba(255, 230, 238, 0.2);
        }
        
        .table tbody td {
            border-color: #F0F0F0;
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        /* Badges */
        .badge {
            padding: 6px 10px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: white;
        }
        
        .badge-success {
            background-color: #66BB6A;
            color: white;
        }
        
        .badge-warning {
            background-color: #FFA726;
            color: white;
        }
        
        .badge-danger {
            background-color: #EF5350;
            color: white;
        }
        
        /* Hamburger menu for mobile */
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-color);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0;
            }
            
            .hamburger {
                display: block;
            }
        }
        
        /* Custom Animation Effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animated-fade {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .page-item:not(:first-child) .page-link {
            margin-left: 5px;
        }
        
        .page-link {
            border: none;
            color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 15px;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-link:hover {
            background-color: var(--accent-color);
            color: var(--primary-dark);
        }
        
        /* Text color utilities */
        .text-pink {
            color: var(--primary-color) !important;
            font-weight: 600;
        }
        
        .text-pink:hover {
            color: var(--primary-dark) !important;
            transition: color 0.3s;
        }
        
        /* Notification Styles */
        .notification-icon-wrapper {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 15px;
        }
        
        .notification-icon-wrapper:hover {
            background-color: rgba(255, 135, 178, 0.2);
            transform: translateY(-2px);
        }
        
        .notification-icon {
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #FF3366;
            color: white;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }
        
        .notifications-dropdown {
            width: 380px !important;
            padding: 0 !important;
            border-radius: 12px !important;
            border: none !important;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
            max-height: 80vh;
            overflow: hidden;
        }
        
        .notifications-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            background-color: white;
        }
        
        .notifications-header h6 {
            font-weight: 600;
            color: #333;
            letter-spacing: 0.5px;
        }
        
        .notifications-header a {
            font-size: 14px;
            text-decoration: none;
        }
        
        .notifications-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 0;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            position: relative;
            background-color: white;
        }
        
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        
        .notification-item.unread {
            background-color: #f9f9f9;
        }
        
        .notification-icon-container {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .notification-content-wrapper {
            flex-grow: 1;
        }
        
        .notification-title {
            font-size: 15px;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        
        .notification-content {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }
        
        /* Order notification specific styles */
        .notification-item.order-notification .notification-icon-container {
            background-color: #f8e5ff;
        }
        
        .notification-item.order-notification .notification-icon-container i {
            color: #9c27b0;
        }
        
        /* Payment notification specific styles */
        .notification-item.payment-notification .notification-icon-container {
            background-color: #e3f2fd;
        }
        
        .notification-item.payment-notification .notification-icon-container i {
            color: #2196f3;
        }
        
        /* Shopee notification specific styles */
        .notification-item.shopee-notification .notification-icon-container {
            background-color: #fff8e1;
        }
        
        .notification-item.shopee-notification .notification-icon-container i {
            color: #ff5722;
        }
        
        /* System notification specific styles */
        .notification-item.system-notification .notification-icon-container {
            background-color: #e8f5e9;
        }
        
        .notification-item.system-notification .notification-icon-container i {
            color: #4caf50;
        }
        
        .no-notifications {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #999;
        }
        
        .no-notifications i {
            font-size: 32px;
            margin-bottom: 10px;
            color: #ddd;
        }
        
        .no-notifications p {
            font-size: 14px;
            margin: 0;
        }
        
        /* Toast notifications */
        .toast {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            width: 320px;
        }

        .toast.bg-success {
            border-left: 4px solid #2ecc71;
        }

        .toast.bg-info {
            border-left: 4px solid #3498db;
        }

        .toast.bg-warning {
            border-left: 4px solid #f1c40f;
        }

        .toast.bg-danger {
            border-left: 4px solid #e74c3c;
        }

        .toast .toast-body {
            padding: 12px 15px;
            display: flex;
            align-items: flex-start;
        }

        .toast .btn-close {
            background-size: 0.65em;
            opacity: 0.5;
        }

        .toast .toast-body i {
            margin-right: 10px;
            font-size: 18px;
        }

        .toast .toast-body strong {
            display: block;
            margin-bottom: 3px;
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Bloom Bouquet</h3>
            <div class="subtitle">Admin Dashboard</div>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-heading">Main Menu</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin') || Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/products*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                        <i class="fas fa-spa"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/carousels*') ? 'active' : '' }}" href="{{ route('admin.carousels.index') }}">
                        <i class="fas fa-images"></i> Carousels
                    </a>
                </li>
            </ul>
            
            <div class="menu-heading">Orders & Customers</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/customers*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/chats*') ? 'active' : '' }}" href="{{ route('admin.chats.index') }}">
                        <i class="fas fa-comments"></i> Chat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
            </ul>
            
            <div class="menu-heading">Settings</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/profile*') ? 'active' : '' }}" href="{{ route('admin.profile') }}">
                        <i class="fas fa-user-cog"></i> My Profile
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-light btn-sm w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="content-wrapper">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="hamburger me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="page-title">@yield('page-title', 'Dashboard')</h4>
            </div>
            
            <div class="user-dropdown d-flex align-items-center">
                <!-- Notification icon -->
                <div class="ms-3">
                    @include('admin.components.notification_dropdown', ['unreadNotificationCount' => $unreadNotificationCount ?? 0])
                </div>
                
                <!-- User dropdown -->
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=FF87B2&color=fff" alt="Admin">
                    <span>Admin</span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="fas fa-user-cog"></i> My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animated-fade" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animated-fade" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <div class="animated-fade">
            @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap and jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Make sure Bootstrap is globally available -->
    <script>
        // Ensure Bootstrap is available globally
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded properly. Adding fallback.');
            // Create a fallback for Bootstrap modal if needed
            window.bootstrap = {
                Modal: function(element) {
                    return {
                        show: function() {
                            if (element) {
                                element.classList.add('show');
                                element.style.display = 'block';
                                document.body.classList.add('modal-open');
                                
                                // Create backdrop
                                let backdrop = document.createElement('div');
                                backdrop.className = 'modal-backdrop fade show';
                                document.body.appendChild(backdrop);
                            }
                        },
                        hide: function() {
                            if (element) {
                                element.classList.remove('show');
                                element.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                
                                // Remove backdrop
                                let backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) {
                                    backdrop.remove();
                                }
                            }
                        }
                    };
                }
            };
        }
        
        // Test Bootstrap availability
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof bootstrap !== 'undefined') {
                console.log('Bootstrap is loaded correctly.');
            } else {
                console.error('Bootstrap is still not available after fallback.');
            }
        });
    </script>
    
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Notification system
        $(document).ready(function() {
            // Track already displayed notifications to prevent duplicates
            const displayedNotifications = new Set();
            
            // Create notification sound
            const notificationSound = new Audio('{{ asset('sounds/notification.mp3') }}');
            
            // Create notification container if it doesn't exist
            if ($('#notification-container').length === 0) {
                $('body').append(`
                    <div id="notification-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999;"></div>
                `);
            }
            
            // Load notifications
            function loadNotifications() {
                fetch('{{ route('admin.notifications.unread-count') }}')
                    .then(response => response.json())
                    .then(data => {
                        // Update badge count
                        if (data.count > 0) {
                            $('#notification-badge').removeClass('d-none').text(data.count);
                        } else {
                            $('#notification-badge').addClass('d-none');
                        }
                        
                        // Get notifications content
                        return fetch('{{ route('admin.notifications.index') }}?ajax=true');
                    })
                    .then(response => response.text())
                    .then(html => {
                        $('#notificationsContainer').html(html);
                    })
                    .catch(error => {
                        console.error('Error loading notifications:', error);
                        $('#notificationsContainer').html(`
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>Gagal memuat notifikasi</p>
                            </div>
                        `);
                    });
            }
            
            // Show notifications when dropdown is opened
            $('#notificationDropdown').on('click', function() {
                loadNotifications();
            });
            
            // Mark all notifications as read
            $('#markAllAsRead').on('click', function(e) {
                e.preventDefault();
                markAllNotificationsAsRead();
            });
            
            function markAllNotificationsAsRead() {
                fetch('{{ route('admin.notifications.mark-all-as-read') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update badge
                    $('#notification-badge').addClass('d-none').text('0');
                    
                    // Remove unread styling from all notifications
                    $('.notification-item').removeClass('unread');
                })
                .catch(error => {
                    console.error('Error marking notifications as read:', error);
                });
            }
            
            // Mark single notification as read when clicked
            $(document).on('click', '.notification-item', function(e) {
                // Don't trigger if clicking on a link inside the notification
                if ($(e.target).closest('a').length > 0) {
                    return;
                }
                
                const notificationId = $(this).data('notification-id');
                
                fetch(`{{ route('admin.notifications.mark-as-read', '') }}/${notificationId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update badge count
                    const currentCount = parseInt($('#notification-badge').text() || '0');
                    if (currentCount > 0) {
                        const newCount = currentCount - 1;
                        if (newCount > 0) {
                            $('#notification-badge').text(newCount);
                        } else {
                            $('#notification-badge').addClass('d-none');
                        }
                    }
                    
                    // Remove unread styling
                    $(this).removeClass('unread');
                    
                    // If it's an order notification, redirect to the order detail page
                    if (data.type === 'order' && data.order_id) {
                        window.location.href = `{{ route('admin.orders.show', '') }}/${data.order_id}`;
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            });
            
            // Auto-refresh notifications
            const CHECK_INTERVAL = 30000; // 30 seconds
            let lastNotificationCount = 0;
            
            // Check for new orders/notifications with throttling
            let lastCheck = 0;
            function throttledCheckNotifications() {
                const now = Date.now();
                if (now - lastCheck > 60000) { // Max once per minute
                    lastCheck = now;
                    checkForNotifications();
                }
            }
            
            // Set interval to check for notifications
            setInterval(throttledCheckNotifications, CHECK_INTERVAL);
            
            // Initial check
            throttledCheckNotifications();
            
            // Function to check for new notifications
            function checkForNotifications() {
                $.ajax({
                    url: '{{ route('admin.notifications.unread-count') }}',
                    method: 'GET',
                    success: function(response) {
                        // Update badge without spam notifications
                        if (response.count > 0) {
                            $('#notification-badge').removeClass('d-none').text(response.count);
                            
                            // Only play sound if there are new unread notifications since last check
                            if (response.count > lastNotificationCount) {
                                // Only play sound once per session for new notifications
                                notificationSound.play().catch(e => console.log('Error playing sound:', e));
                                
                                // Check for new orders
                                checkForNewOrders();
                            }
                            
                            lastNotificationCount = response.count;
                        } else {
                            $('#notification-badge').addClass('d-none');
                            lastNotificationCount = 0;
                        }
                    },
                    error: function(error) {
                        console.error('Error checking for notifications:', error);
                    }
                });
            }
            
            // Function to check for new orders
            function checkForNewOrders() {
                fetch('{{ route('admin.orders.check-new') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_orders_count > 0) {
                            // Show toast notification for new orders
                            showToast('Pesanan Baru', `Ada ${data.new_orders_count} pesanan baru yang perlu ditinjau`, 'success');
                            
                            // Auto-reload notifications in dropdown if it's open
                            if ($('.notifications-dropdown').hasClass('show')) {
                                loadNotifications();
                            }
                        }
                        
                        if (data.payment_status_changed_count > 0) {
                            // Show toast notification for payment status changes
                            showToast('Pembayaran Diterima', `${data.payment_status_changed_count} pesanan telah dibayar dan perlu diproses`, 'info');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for new orders:', error);
                    });
            }
            
            // Function to show toast notification
            function showToast(title, message, type = 'info') {
                const toastId = 'toast-' + Date.now();
                
                // Determine icon based on type
                let icon = 'fa-info-circle text-info';
                if (type === 'success') icon = 'fa-check-circle text-success';
                if (type === 'warning') icon = 'fa-exclamation-triangle text-warning';
                if (type === 'danger') icon = 'fa-exclamation-circle text-danger';
                
                const toast = `
                    <div id="${toastId}" class="toast align-items-center border-0 mb-3 bg-${type}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                        <div class="d-flex">
                            <div class="toast-body d-flex">
                                <div class="me-3">
                                    <i class="fas ${icon} fa-lg"></i>
                                </div>
                                <div>
                                    <strong>${title}</strong>
                                    <div>${message}</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                $('#notification-container').append(toast);
                
                // Initialize and show the toast
                const toastElement = new bootstrap.Toast(document.getElementById(toastId), {
                    autohide: true,
                    delay: 5000
                });
                toastElement.show();
                
                // Remove toast from DOM after it's hidden
                $(`#${toastId}`).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
        });
    </script>
    
    <!-- Add this script just before the closing </body> tag -->
    <script>
        // Enhanced modal fix to ensure proper display and interaction
        document.addEventListener('DOMContentLoaded', function() {
            // Fix any existing modals
            const allModals = document.querySelectorAll('.modal');
            allModals.forEach(modal => {
                // Apply higher z-index
                modal.style.zIndex = '9999';
                
                // Ensure dialog is clickable
                const modalDialog = modal.querySelector('.modal-dialog');
                if(modalDialog) {
                    modalDialog.style.zIndex = '10000';
                    modalDialog.style.pointerEvents = 'auto';
                }
            });
            
            // Listen for modal show events
            document.addEventListener('show.bs.modal', function(event) {
                // Make sure body has modal-open class
                document.body.classList.add('modal-open');
                
                // Create backdrop if it doesn't exist
                if(!document.querySelector('.modal-backdrop')) {
                    let modalBackdrop = document.createElement('div');
                    modalBackdrop.classList.add('modal-backdrop', 'fade', 'show');
                    modalBackdrop.style.zIndex = '9998';
                    document.body.appendChild(modalBackdrop);
                }
                
                // Force the current modal to appear on top with higher z-index
                const modal = event.target;
                modal.style.zIndex = '9999';
                modal.style.display = 'block';
                
                // Ensure the modal dialog is clickable
                const modalDialog = modal.querySelector('.modal-dialog');
                if(modalDialog) {
                    modalDialog.style.zIndex = '10000';
                    modalDialog.style.pointerEvents = 'auto';
                }
                
                // Make sure content wrapper doesn't interfere
                const contentWrapper = document.querySelector('.content-wrapper');
                if(contentWrapper) {
                    contentWrapper.style.position = 'relative';
                    contentWrapper.style.zIndex = '1';
                }
            });
            
            // Remove artifacts when modal is hidden
            document.addEventListener('hidden.bs.modal', function(event) {
                // Remove any stray backdrops when all modals are closed
                if(document.querySelectorAll('.modal.show').length === 0) {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                    document.body.classList.remove('modal-open');
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>
