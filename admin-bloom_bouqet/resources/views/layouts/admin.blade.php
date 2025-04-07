<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florist Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --dark-bg: #f6f9fc;
            --dark-secondary: #ffffff;
            --accent: #7e57c2;
            --gradient-1: linear-gradient(135deg, #7e57c2, #b085f5);
            --gradient-2: linear-gradient(135deg, #b085f5, #7e57c2);
            --text: #2d3436;
            --text-secondary: #636e72;
            --shadow: rgba(126, 87, 194, 0.1);
            --card-hover: rgba(126, 87, 194, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            color: var(--text);
        }

        #sidebar {
            background: var(--gradient-1);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem;
            box-shadow: 4px 0 15px var(--shadow);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin: 0.8rem 0;
            transition: all 0.3s ease;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: white;
            color: var(--accent);
            box-shadow: 0 5px 15px rgba(255,105,180,0.3);
        }

        .main-content {
            margin-left: 280px;
            padding: 2.5rem;
            margin-top: 70px;
        }

        .card {
            background: var(--dark-secondary);
            border: 1px solid rgba(126, 87, 194, 0.1);
            border-radius: 16px;
            box-shadow: 0 8px 16px var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px var(--shadow);
        }

        .stat-card {
            position: relative;
            transition: all 0.3s ease;
            background: white;
            border: 1px solid rgba(126, 87, 194, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(255,105,180,0.2);
        }

        .stat-icon {
            background: var(--gradient-2);
            padding: 1rem;
            border-radius: 14px;
            box-shadow: 0 5px 15px rgba(126, 87, 194, 0.2);
        }

        h4.brand {
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            -webkit-background-clip: initial;
            -webkit-text-fill-color: initial;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
        }

        canvas {
            color: var(--text) !important;
        }

        select.form-select {
            border-color: var(--accent);
            color: var(--text);
        }

        h2, h3, h4, h5, h6 {
            color: var(--text);
        }

        .top-navbar {
            position: fixed;
            right: 0;
            top: 0;
            left: 280px;
            height: 70px;
            background: white;
            box-shadow: 0 2px 10px var(--shadow);
            z-index: 100;
            padding: 0 2rem;
            backdrop-filter: blur(10px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px var(--shadow);
            border-radius: 12px;
            border: 1px solid rgba(126, 87, 194, 0.1);
        }

        .dropdown-item:hover {
            background: var(--card-hover);
            color: var(--accent);
        }

        .nav-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            position: relative;
            transition: all 0.3s;
            color: var(--text);
        }

        .nav-icon:hover {
            background: var(--card-hover);
            color: var(--accent);
        }

        .form-select, .form-control {
            border-color: rgba(126, 87, 194, 0.2);
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(126, 87, 194, 0.25);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <h4 class="brand mb-4">✨ Florist Admin</h4>
        <nav class="nav flex-column">
            <a class="nav-link {{ Request::is('admin') ? 'active' : '' }}" href="{{ route('admin.home') }}">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link " href="{{ route('admin.products.index') }}">
                <i class="fas fa-flower"></i> Produk
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-shopping-bag"></i> Pesanan
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-users"></i> Pelanggan
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <h5 class="mb-0">Dashboard</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Notifications -->
            <div class="dropdown">
                <div class="nav-icon" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <div class="notification-badge">3</div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header">Notifikasi</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item p-2 rounded" href="#">
                            <div class="d-flex gap-3">
                                <i class="fas fa-shopping-bag mt-1"></i>
                                <div>
                                    <p class="mb-0">Pesanan Baru #123</p>
                                    <small class="text-secondary">2 menit yang lalu</small>
                                </div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Messages -->
            <div class="dropdown">
                <div class="nav-icon" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-envelope"></i>
                    <div class="notification-badge">2</div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header">Pesan</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item p-2 rounded" href="#">
                            <div class="d-flex gap-3">
                                <img src="https://via.placeholder.com/32" class="rounded-circle">
                                <div>
                                    <p class="mb-0">John Doe</p>
                                    <small class="text-secondary">Hai, pesanan saya...</small>
                                </div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Profile & Logout -->
            <div class="dropdown">
                <div class="nav-icon" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2">
                    <li><a class="dropdown-item rounded" href="#"><i class="fas fa-user me-2"></i> Profil</a></li>
                    <li><a class="dropdown-item rounded" href="#"><i class="fas fa-cog me-2"></i> Pengaturan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        {{-- <form action="{{ route('logout') }}" method="POST"> --}}
                            @csrf
                            <button type="submit" class="dropdown-item rounded text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
