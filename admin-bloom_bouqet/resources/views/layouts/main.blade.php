<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bucket Bunga Admin</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
  <!-- JQVMap -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/jqvmap/jqvmap.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{URL::asset('Template/dist/css/adminlte.min.css')}}">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/daterangepicker/daterangepicker.css')}}">
  <!-- summernote -->
  <link rel="stylesheet" href="{{URL::asset('Template/plugins/summernote/summernote-bs4.min.css')}}">
  <style>
    :root {
      /* Definisi warna pink yang lebih cerah dan konsisten */
      --pink-primary: #ff69b4;
      --pink-secondary: #ff8dc7;
      --pink-dark: #ff1493;
      --pink-light: #ffb6c1;
      --pink-pale: #ffeff5;
      --pink-gradient: linear-gradient(135deg, rgba(255,105,180,0.95) 0%, rgba(255,20,147,0.95) 100%);
      --glass-effect: rgba(255, 255, 255, 0.15);
    }

    /* Global Styles */
    body {
      font-family: 'Source Sans Pro', sans-serif;
      background: var(--pink-pale);
    }

    /* Navbar */
    .main-header {
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    .navbar-light {
      background: var(--glass-effect) !important;
      box-shadow: 0 4px 15px rgba(255,105,180,0.1);
    }

    /* Sidebar */
    .main-sidebar {
      background: var(--pink-gradient) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 0 25px 25px 0;
      box-shadow: 5px 0 20px rgba(255,20,147,0.15);
    }

    .brand-link {
      border: none !important;
      padding: 1.5rem !important;
      text-align: center;
    }

    .brand-link .brand-image {
      float: none;
      margin: 0 auto 0.5rem;
      display: block;
    }

    .brand-text {
      display: block;
      font-weight: 600 !important;
      letter-spacing: 1px;
      font-size: 1.1rem;
      color: white !important;
    }

    .user-panel {
      background: var(--glass-effect);
      margin: 1rem;
      border-radius: 15px;
      padding: 1rem !important;
      text-align: center;
    }

    .user-panel .image {
      display: block;
      float: none;
      padding: 0;
    }

    .user-panel img {
      width: 80px;
      height: 80px;
      border: 3px solid rgba(255,255,255,0.3);
      margin: 0 auto 0.5rem;
    }

    .user-panel .info {
      padding: 0;
      display: block;
    }

    .user-panel .info a {
      font-size: 1.1rem;
      color: white !important;
    }

    /* Simplified Navigation */
    .nav-sidebar .nav-item {
      margin: 5px 15px;
    }

    .nav-sidebar .nav-link {
      border-radius: 12px;
      padding: 0.8rem 1.2rem;
      margin-bottom: 0.3rem;
      color: rgba(255,255,255,0.9) !important;
      transition: all 0.3s ease;
    }

    .nav-sidebar .nav-link:hover {
      background: var(--glass-effect);
      transform: translateX(5px);
    }

    .nav-sidebar .nav-link.active {
      background: rgba(255,255,255,0.2) !important;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .nav-icon {
      margin-right: 0.8rem;
    }

    /* Content Area */
    .content-wrapper {
      background: var(--pink-pale) !important;
      padding: 2rem;
    }

    /* Cards */
    .card {
      border-radius: 15px;
      border: none;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.9);
    }

    .card-header {
      border-radius: 15px 15px 0 0 !important;
      border-bottom: none;
      background: var(--pink-gradient);
      color: white;
      padding: 1rem 1.5rem;
    }

    /* Footer */
    .main-footer {
      background: var(--glass-effect) !important;
      border: none;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      color: var(--pink-dark);
    }

    /* Enhanced Navbar Styles */
    .main-header.navbar {
      height: 70px;
      padding: 0.5rem 2rem;
    }

    .navbar-light .navbar-nav .nav-link {
      color: var(--pink-dark) !important;
      padding: 0.5rem 1rem;
      border-radius: 25px;
      margin: 0 0.2rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .navbar-light .navbar-nav .nav-link:hover {
      background: var(--pink-gradient);
      color: white !important;
      transform: translateY(-1px);
    }

    .navbar-search-block {
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(10px);
      border-radius: 0 0 15px 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .form-control-navbar {
      border: 2px solid var(--pink-light) !important;
      border-radius: 20px !important;
      padding: 1rem 1.5rem;
    }

    .btn-navbar {
      background: var(--pink-gradient) !important;
      border: none !important;
      color: white !important;
      border-radius: 20px !important;
      padding: 0.5rem 1rem;
      margin-left: 0.5rem;
    }

    .navbar-badge {
      background: var(--pink-primary) !important;
      font-size: 0.6rem;
      padding: 0.25rem 0.4rem;
      right: 0;
      top: 0;
    }

    /* Dropdown Menus */
    .dropdown-menu {
      border: none;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 1rem 0;
      backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.95);
    }

    .dropdown-item {
      padding: 0.7rem 1.5rem;
      transition: all 0.3s ease;
    }

    .dropdown-item:hover {
      background: var(--pink-light);
      color: white;
    }

    .dropdown-divider {
      border-color: rgba(255,105,180,0.1);
      margin: 0.5rem 0;
    }

    .dropdown-item-title {
      color: var(--pink-dark);
      font-weight: 600;
    }

    .dropdown-header {
      color: var(--pink-dark);
      font-weight: 600;
      font-size: 0.9rem;
    }

    /* Buttons with Primary Color (Override Bootstrap default blue) */
    .btn-primary {
      background-color: var(--pink-primary) !important;
      border-color: var(--pink-primary) !important;
    }

    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
      background-color: var(--pink-dark) !important;
      border-color: var(--pink-dark) !important;
    }

    /* Badge Colors */
    .badge-primary {
      background-color: var(--pink-primary) !important;
    }

    .badge-warning {
      background-color: #ffbd4a !important;
      color: #212529;
    }

    /* Form Controls */
    .form-control:focus {
      border-color: var(--pink-primary);
      box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }

    /* Table Styling */
    .table thead th {
      border-bottom: 2px solid var(--pink-light);
    }

    .table-bordered td, .table-bordered th {
      border: 1px solid rgba(255,105,180,0.2);
    }

    /* Pagination */
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

    /* Alert Styling */
    .alert-primary {
      background-color: rgba(255,105,180,0.1);
      border-color: rgba(255,105,180,0.2);
      color: var(--pink-dark);
    }

    /* Custom Scroll Bar */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--pink-light);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--pink-primary);
    }

    /* Active State for Sidebar */
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
      background-color: rgba(255,255,255,0.2) !important;
      color: #fff !important;
    }

    /* Login/Register Pages */
    .login-box .card-header, .register-box .card-header {
      background: var(--pink-gradient);
      color: white;
    }

    .login-box .btn-primary, .register-box .btn-primary {
      background: var(--pink-gradient);
      border: none;
      width: 100%;
      padding: 0.75rem;
    }

    /* Progress Bars */
    .progress-bar {
      background-color: var(--pink-primary);
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="{{ asset('images/logo.png') }}" alt="Bloom Bouqet Logo" height="80" width="80">
  </div>

  <!-- Simplified Navbar -->
  <nav class="main-header navbar navbar-expand navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">
          <i class="fas fa-home"></i> Home
        </a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-header">3 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-shopping-cart mr-2" style="color: var(--pink-primary);"></i> New Order
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="fas fa-user-circle"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <i class="fas fa-user mr-2" style="color: var(--pink-primary);"></i> Profile
          </a>
          <div class="dropdown-divider"></div>
          <form action="{{ route('logout') }}" method="GET" class="dropdown-item">
            @csrf
            <button type="submit" class="btn btn-link p-0" style="color: #dc3545; text-decoration: none;">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
          </form>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/admin/dashboard" class="brand-link">
      <img src="{{URL::asset('Template/dist/img/AdminLTELogo.png')}}" alt="Bloom Bouqet Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Bloom Bouqet</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="{{URL::asset('Template/dist/img/user2-160x160.jpg')}}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">Admin</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Simplified Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-home"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-shopping-bag"></i>
              <p>Products</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tags"></i>
              <p>Categories</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.carousels.index') }}" class="nav-link {{ request()->routeIs('admin.carousels.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-images"></i>
              <p>Carousel</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>Orders</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Customers</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>Settings</p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <section class="content">
      @yield('content')
    </section>
  </div>
  <!-- /.content-wrapper -->
  
  <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="#" style="color: var(--pink-primary);">Bloom Bouqet</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{URL::asset('Template/plugins/jquery/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{URL::asset('Template/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{URL::asset('Template/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- ChartJS -->
<script src="{{URL::asset('Template/plugins/chart.js/Chart.min.js')}}"></script>
<!-- Sparkline -->
<script src="{{URL::asset('Template/plugins/sparklines/sparkline.js')}}"></script>
<!-- JQVMap -->
<script src="{{URL::asset('Template/plugins/jqvmap/jquery.vmap.min.js')}}"></script>
<script src="{{URL::asset('Template/plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
<!-- jQuery Knob Chart -->
<script src="{{URL::asset('Template/plugins/jquery-knob/jquery.knob.min.js')}}"></script>
<!-- daterangepicker -->
<script src="{{URL::asset('Template/plugins/moment/moment.min.js')}}"></script>
<script src="{{URL::asset('Template/plugins/daterangepicker/daterangepicker.js')}}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{URL::asset('Template/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
<!-- Summernote -->
<script src="{{URL::asset('Template/plugins/summernote/summernote-bs4.min.js')}}"></script>
<!-- overlayScrollbars -->
<script src="{{URL::asset('Template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{URL::asset('Template/dist/js/adminlte.js')}}"></script>

<script>
  // Override any potential purple colors with pink
  document.addEventListener('DOMContentLoaded', function() {
    // Mengganti warna default tombol primary
    const style = document.createElement('style');
    style.textContent = `
      .btn-primary, .bg-primary, .badge-primary {
        background-color: var(--pink-primary) !important;
        border-color: var(--pink-primary) !important;
      }
      .btn-primary:hover, .btn-primary:active, .btn-primary:focus {
        background-color: var(--pink-dark) !important;
        border-color: var(--pink-dark) !important;
      }
      .text-primary {
        color: var(--pink-primary) !important;
      }
      .border-primary {
        border-color: var(--pink-primary) !important;
      }
      :root {
        --blue: var(--pink-primary);
        --primary: var(--pink-primary);
      }
    `;
    document.head.appendChild(style);
  });
</script>
</body>
</html>
