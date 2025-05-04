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
                        <i class="fas fa-comments"></i> Chats
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
            
            <div class="user-dropdown">
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
    </script>
    
    @yield('scripts')
</body>
</html>
