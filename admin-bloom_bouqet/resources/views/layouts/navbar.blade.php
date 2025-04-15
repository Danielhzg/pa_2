<nav class="navbar">
    <ul class="navbar-nav">
        <li class="nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
        </li>
        <li class="nav-item {{ Request::is('admin/carousels*') ? 'active' : '' }}">
            <a href="{{ route('admin.carousels.index') }}" class="nav-link">Carousels</a>
        </li>
        <li class="nav-item {{ Request::is('admin/products*') ? 'active' : '' }}">
            <a href="{{ route('admin.products.index') }}" class="nav-link">Products</a>
        </li>
        <!-- ...existing code for other menu items... -->
    </ul>
</nav>
