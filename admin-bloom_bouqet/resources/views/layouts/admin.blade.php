<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Florist Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --dark-bg: #FF6B9E;
            --dark-secondary: #ffffff;
            --accent: #7e57c2;
            --gradient-1: linear-gradient(135deg,  #FF87B2,  #FF87B2);
            --gradient-2: linear-gradient(135deg, #FF87B2 ,  #FF87B2);
            --text: #2d3436;
            --text-secondary: #636e72;
            --shadow: rgba(126, 87, 194, 0.1);
            --card-hover: rgba(126, 87, 194, 0.05);
            --pink-primary:#ffffff;
            --pink-dark:rgb(241, 133, 171);
            --pink-light: #FFA8C7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #FFDEE2;
            color: #333;
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
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateX(5px);
            background-color: var(--pink-dark);
            color: white;
        }

        .nav-link.active {
            background: var(--pink-light);
            color: var(--pink-primary);
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.3);
            border-left: 5px solid var(--pink-dark);
            transition: all 0.3s ease;
        }

        .nav-link.active i {
            color: var(--pink-dark);
        }

        .main-content {
            margin-left: 280px;
            padding: 2.5rem;
            margin-top: 70px;
        }

        .card {
            background: var(--dark-secondary);
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
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
            background-color: var(--pink-primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 100;
            padding: 0 2rem;
            backdrop-filter: blur(10px);
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
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

        .btn-primary {
            background-color: var(--pink-primary);
            border-color: var(--pink-primary);
        }

        .btn-primary:hover {
            background-color: var(--pink-dark);
            border-color: var(--pink-dark);
        }

        .alert {
            animation: fadeIn 0.5s ease-in-out, fadeOut 0.5s ease-in-out 3s forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .notification {
            background-color: var(--pink-primary);
            color: #fff;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            background-color: var(--pink-primary);
            color: #fff;
        }

        /* Sidebar styles */
        .main-sidebar {
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .nav-sidebar .nav-item .nav-link {
            color: #666;
        }

        .nav-sidebar .nav-item .nav-link:hover,
        .nav-sidebar .nav-item .nav-link.active {
            background-color: var(--pink-light);
            color: var(--pink-primary);
        }

        .nav-sidebar .nav-item .nav-link.active i {
            color: var(--pink-primary);
        }

       
        .table thead th {
            background-color: var(--pink-primary);
            color: white;
            border: none;
        }

        .table-hover tbody tr:hover {
            background-color: #fff5f8;
        }

        /* Pagination styles */
        .page-item.active .page-link {
            background-color: var(--pink-primary);
            border-color: var(--pink-primary);
        }

        .page-link {
            color: var(--pink-primary);
        }

        .page-link:hover {
            color: var(--pink-dark);
        }

        /* Form styles */
        .form-control:focus {
            border-color: var(--pink-light);
            box-shadow: 0 0 0 0.2rem rgba(255, 135, 178, 0.25);
        }

        /* Select2 customization */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--pink-primary);
        }

        /* Custom checkbox and radio */
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--pink-primary);
            border-color: var(--pink-primary);
        }

        /* Progress bar */
        .progress-bar {
            background-color: var(--pink-primary);
        }

        /* Card header */
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid var(--pink-light);
        }

        /* Footer */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid var(--pink-light);
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <h4 class="brand mb-4"> Bloom Bouqet</h4>
        <nav class="nav flex-column">
            <a class="nav-link {{ Request::is('admin') || Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.home') }}">
                <i class="fas fa-home"></i> Dashboard 
            </a>
            <a class="nav-link {{ Request::is('admin/categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                <i class="fas fa-tags"></i> Kategori
            </a>
            <a class="nav-link {{ Request::is('admin/products*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                <i class="fas fa-seedling"></i> Produk
            </a>
            <a class="nav-link {{ Request::is('admin/carousels*') ? 'active' : '' }}" href="{{ route('admin.carousels.index') }}">
                <i class="fas fa-images"></i> Carousel
            </a>
            <a class="nav-link {{ Request::is('admin/orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                <i class="fas fa-shopping-bag"></i> Pesanan
            </a>
            <a class="nav-link {{ Request::is('admin/customers*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                <i class="fas fa-users"></i> Pelanggan
            </a>
            <a class="nav-link {{ Request::is('admin/reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
            <a class="nav-link {{ Request::is('admin/chats*') ? 'active' : '' }}" href="{{ route('admin.chats.index') }}">
                <i class="fas fa-comments"></i> Chat <span id="unread-chat-badge" class="badge bg-danger ms-2" style="display: none;">0</span>
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
                    <div class="notification-badge" id="header-unread-count" style="display: none;">0</div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header">Pesan</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li id="message-list">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 mt-2 small">Memuat pesan...</p>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-primary text-center" href="{{ route('admin.chats.index') }}">
                            Lihat Semua Pesan <i class="fas fa-arrow-right ms-1"></i>
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
                        <form action="{{ route('logout') }}" method="POST">
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
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Fungsi untuk menampilkan notifikasi pesan yang belum dibaca
        function fetchUnreadCount() {
            fetch('{{ route("admin.chats.unread") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > 0) {
                        const badge = document.getElementById('unread-chat-badge');
                        badge.textContent = data.unread_count;
                        badge.style.display = 'inline-block';
                        
                        // Update juga di header
                        const headerBadge = document.getElementById('header-unread-count');
                        headerBadge.textContent = data.unread_count;
                        headerBadge.style.display = 'flex';
                    }
                })
                .catch(error => console.error('Error fetching unread count:', error));
        }
        
        // Fungsi untuk menampilkan pesan terbaru di dropdown
        function fetchRecentMessages() {
            const messageList = document.getElementById('message-list');
            
            fetch('{{ route("admin.chats.index") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chats && data.chats.length > 0) {
                    let html = '';
                    // Ambil maksimal 3 pesan terbaru
                    const recentChats = data.chats.slice(0, 3);
                    
                    recentChats.forEach(chat => {
                        const unreadClass = chat.unread_count > 0 ? 'fw-bold' : '';
                        html += `
                            <a class="dropdown-item p-2 rounded" href="{{ url('admin/chats') }}/${chat.id}">
                                <div class="d-flex gap-3">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 ${unreadClass}">${chat.user ? chat.user.name : 'Pengguna'}</p>
                                        <small class="text-secondary">${chat.last_message ? chat.last_message.substring(0, 30) + '...' : 'Tidak ada pesan'}</small>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    
                    messageList.innerHTML = html;
                } else {
                    messageList.innerHTML = `
                        <div class="text-center py-3">
                            <i class="fas fa-inbox text-muted mb-2" style="font-size: 1.5rem;"></i>
                            <p class="mb-0 small">Belum ada pesan</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching recent messages:', error);
                messageList.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-circle text-danger mb-2" style="font-size: 1.5rem;"></i>
                        <p class="mb-0 small">Gagal memuat pesan</p>
                    </div>
                `;
            });
        }
        
        // Cek pesan baru setiap 30 detik
        document.addEventListener('DOMContentLoaded', function() {
            fetchUnreadCount();
            fetchRecentMessages();
            
            setInterval(fetchUnreadCount, 30000);
            setInterval(fetchRecentMessages, 60000);
            
            // Tampilkan pesan terbaru saat dropdown dibuka
            const messageDropdown = document.querySelector('.dropdown');
            messageDropdown.addEventListener('show.bs.dropdown', fetchRecentMessages);
        });
    </script>
    
    @stack('scripts')
</body>
</html>
