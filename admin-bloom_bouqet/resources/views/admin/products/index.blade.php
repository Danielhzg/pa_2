@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Produk</h3>
                <p class="text-muted">Kelola produk toko Anda</p>
            </div>
            <div>
                <a href="{{ route('admin.products.create') }}" class="btn add-new-btn">
                    <i class="fas fa-plus me-2"></i> <span class="text-emphasis">Tambah Produk Baru</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="card table-card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Daftar Produk</h5>
                </div>
                <div class="col-auto">
                    <div class="search-box">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari produk...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table category-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr class="category-item">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <img src="{{ $product->main_image ? asset('storage/' . $product->main_image) : asset('images/default-product.png') }}" 
                                         alt="{{ $product->name }}" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>{{ Str::limit($product->description, 50) }}</td>
                                <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                                <td>Rp{{ number_format($product->price, 0, ',', '.') }}</td>
                                <td>{{ $product->stock }}</td>
                                <td>
                                    <div class="status-indicators">
                                        <span class="status-badge {{ $product->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        @if($product->is_on_sale)
                                            <span class="status-badge status-sale">Diskon {{ $product->discount }}%</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn action-btn delete-btn" 
                                               onclick="openDeleteModal('{{ $product->id }}', '{{ $product->name }}', 'produk', 'admin/products')"
                                               title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon mb-3">
                    <i class="fas fa-box"></i>
                </div>
                <h5>Tidak ada produk yang tersedia</h5>
                <p class="text-muted">Mulai dengan menambahkan produk baru untuk toko Anda</p>
                <a href="{{ route('admin.products.create') }}" class="btn add-new-btn mt-3">
                    <i class="fas fa-plus me-2"></i> <span class="text-emphasis">Tambah Produk Baru</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Include Delete Confirmation Modal -->
@include('partials.delete-confirmation-modal')

<style>
    .content-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    /* Text emphasis for the "Tambah Produk" button */
    .text-emphasis {
        color: #ffffff;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        position: relative;
        transition: all 0.3s ease;
    }
    
    /* Add a subtle underline animation on hover */
    .text-emphasis::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: #FFE5EE;
        transition: width 0.3s ease;
    }
    
    .add-new-btn:hover .text-emphasis::after {
        width: 100%;
    }
    
    /* Make button text larger and more visible */
    .add-new-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
        font-size: 1.05rem;
    }
    
    .add-new-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
    }
    
    .custom-alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        padding: 1rem;
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .table-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1rem 1.5rem;
    }
    
    .card-title {
        color: #D46A9F;
        font-weight: 600;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }
    
    .search-box input {
        padding-right: 30px;
        border-radius: 20px;
        border: 1px solid rgba(255,105,180,0.2);
    }
    
    .search-box input:focus {
        border-color: #FF87B2;
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .category-table {
        margin-bottom: 0;
    }
    
    .category-table thead th {
        background-color: rgba(255,135,178,0.05);
        color: #D46A9F;
        font-weight: 600;
        border: none;
        padding: 1rem 1.5rem;
    }
    
    .category-item {
        transition: all 0.2s;
    }
    
    .category-item:hover {
        background-color: rgba(255,105,180,0.03);
    }
    
    .category-icon-container {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background-color: rgba(255,135,178,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF87B2;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s;
    }
    
    .edit-btn {
        background-color: rgba(255,193,7,0.1);
        color: #ffc107;
        border: none;
    }
    
    .edit-btn:hover {
        background-color: #ffc107;
        color: white;
    }
    
    .delete-btn {
        background-color: rgba(220,53,69,0.1);
        color: #dc3545;
        border: none;
    }
    
    .delete-btn:hover {
        background-color: #dc3545;
        color: white;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-inactive {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    .status-sale {
        background-color: rgba(255, 193, 7, 0.1);
        color: #fd7e14;
        margin-left: 5px;
    }
    
    .status-indicators {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: rgba(255,105,180,0.3);
    }

    /* Fix for modal display */
    .modal {
        z-index: 9999 !important;
    }
    .modal-dialog {
        z-index: 10000 !important;
        pointer-events: auto !important;
    }
    .modal-backdrop {
        z-index: 9990 !important;
    }
    body.modal-open {
        overflow: hidden;
        padding-right: 0px !important;
    }

    /* Tooltip fix */
    .tooltip {
        z-index: 10050 !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const productItems = document.querySelectorAll('.category-item');
    
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        
        productItems.forEach(item => {
            const productName = item.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const categoryName = item.querySelector('td:nth-child(5)').textContent.toLowerCase();
            
            if (productName.includes(searchValue) || categoryName.includes(searchValue)) {
                item.style.display = 'table-row';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            container: 'body',
            trigger: 'hover'
        });
    });
});
</script>

@section('scripts')
<script>
    // Additional modal handling for product deletion
    document.addEventListener('DOMContentLoaded', function() {
        // Force modals to be top-level by moving them to the body
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            // Move modal to body to prevent stacking context issues
            document.body.appendChild(modal);
            
            // Ensure high z-index
            modal.style.zIndex = '9999';
            
            // Fix pointer events
            const modalDialog = modal.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.zIndex = '10000';
                modalDialog.style.pointerEvents = 'auto';
            }
        });
        
        // Ensure the body can't be scrolled when modal is open
        document.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
        });
        
        document.addEventListener('hidden.bs.modal', function() {
            if (!document.querySelector('.modal.show')) {
                document.body.style.overflow = '';
            }
        });
    });
</script>
@endsection
@endsection