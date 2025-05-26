@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Kategori</h3>
                <p class="text-muted">Kelola kategori produk toko Anda</p>
            </div>
            <div>
                <a href="{{ route('admin.categories.create') }}" class="btn add-new-btn">
                    <i class="fas fa-plus me-2"></i> <span class="text-emphasis">Tambah Kategori Baru</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="card table-card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">Daftar Kategori</h5>
                </div>
                <div class="col-auto">
                    <div class="search-box">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari kategori...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table category-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kategori</th>
                            <th>Jumlah Produk</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr class="category-item">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="category-icon-container me-2">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <span>{{ $category->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($category->products->count() > 0)
                                        <span class="badge product-count-badge">{{ $category->products->count() }}</span>
                                    @else
                                        <span>{{ $category->products->count() }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="status-indicators">
                                        @if($category->products->count() > 0)
                                            <span class="status-badge status-active">Digunakan</span>
                                        @else
                                            <span class="status-badge status-inactive">Tidak Digunakan</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if($category->products->count() > 0)
                                            <button type="button" class="btn action-btn delete-btn-disabled" 
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="top"
                                                   title="Kategori dengan produk tidak dapat dihapus langsung"
                                                   onclick="openDeleteWithProductsModal('{{ $category->id }}', '{{ $category->name }}', {{ $category->products->count() }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn action-btn delete-btn" 
                                                   onclick="openDeleteModal('{{ $category->id }}', '{{ $category->name }}', 'kategori', 'admin/categories')"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="Hapus Kategori">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
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
                    <i class="fas fa-tags"></i>
                </div>
                <h5>Tidak ada kategori yang tersedia</h5>
                <p class="text-muted">Mulai dengan menambahkan kategori baru untuk produk Anda</p>
                <a href="{{ route('admin.categories.create') }}" class="btn add-new-btn mt-3">
                    <i class="fas fa-plus me-2"></i> <span class="text-emphasis">Tambah Kategori Baru</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Include Delete Confirmation Modal -->
@include('partials.delete-confirmation-modal')

<!-- Delete with Products Modal -->
<div class="modal fade delete-with-products-modal" id="deleteWithProductsModal" tabindex="-1" aria-labelledby="deleteWithProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteWithProductsModalLabel">Konfirmasi Hapus Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-circle me-2"></i> Kategori ini memiliki <span id="productCount"></span> produk terkait</h5>
                    <p>Anda tidak dapat menghapus kategori ini tanpa memindahkan produk terlebih dahulu. Silakan pilih kategori lain untuk memindahkan produk-produk ini.</p>
                </div>
                
                <div class="category-info mb-4">
                    <h6>Detail Kategori:</h6>
                    <ul>
                        <li><strong>Nama:</strong> <span id="categoryNameDetail"></span></li>
                        <li><strong>Jumlah Produk:</strong> <span id="productCountDetail"></span></li>
                    </ul>
                </div>
                
                <form id="deleteWithProductsForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="mb-4">
                        <label for="target_category_id" class="form-label">Pindahkan produk ke kategori:</label>
                        <select name="target_category_id" id="target_category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $targetCategory)
                                <option value="{{ $targetCategory->id }}">{{ $targetCategory->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Silakan pilih kategori tujuan untuk memindahkan produk.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteWithProducts">
                    <i class="fas fa-trash me-2"></i> Hapus Kategori dan Pindahkan Produk
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .content-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        color: #D46A9F;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    /* Text emphasis for the "Tambah Kategori" button */
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
        gap: 0.5rem;
    }
    
    .action-btn {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        color: white;
        padding: 0;
    }
    
    .edit-btn {
        background-color: #ffc107;
        box-shadow: 0 2px 6px rgba(255, 193, 7, 0.3);
    }
    
    .edit-btn:hover {
        background-color: #e0a800;
        box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
        color: white;
    }
    
    .delete-btn {
        background-color: #FF5757;
        box-shadow: 0 2px 6px rgba(255, 87, 87, 0.3);
    }
    
    .delete-btn:hover {
        background-color: #EE4646;
        box-shadow: 0 4px 8px rgba(255, 87, 87, 0.4);
        color: white;
    }
    
    .delete-btn-disabled {
        background-color: #F5F5F5;
        color: #AAAAAA;
        cursor: not-allowed;
        box-shadow: none;
    }
    
    .status-indicators {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
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
        background-color: rgba(255, 87, 178, 0.1);
        color: #FF57B2;
    }
    
    .product-count-badge {
        background-color: rgba(255, 87, 178, 0.1);
        color: #FF57B2;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .empty-state {
        padding: 3rem 0;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: rgba(255, 105, 180, 0.3);
    }
    
    /* Modal styles improved */
    .modal-content {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    
    .modal-header {
        background-color: #D46A9F;
        color: white;
        border-bottom: none;
    }
    
    .modal-title {
        font-weight: 600;
    }
    
    .modal-footer {
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .btn-outline-secondary {
        border-color: #D9D9D9;
        color: #777;
    }
    
    .btn-outline-secondary:hover {
        background-color: #F5F5F5;
        color: #555;
    }
    
    .btn-danger {
        background-color: #FF5757;
        border: none;
    }
    
    .btn-danger:hover {
        background-color: #EE4646;
    }
    
    .alert-warning {
        background-color: rgba(255, 193, 7, 0.1);
        border-left: 4px solid #ffc107;
        border-radius: 5px;
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
    
    /* Form elements style improvements */
    .form-select {
        border-radius: 10px;
        padding: 0.6rem 1rem;
        border: 1px solid rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .form-select:focus {
        border-color: #FF87B2;
        box-shadow: 0 0 0 0.25rem rgba(255,135,178,0.25);
    }
    
    .delete-with-products-modal {
        z-index: 9999 !important;
    }
    
    /* Ensure the modal appears above all other elements */
    #deleteWithProductsModal {
        z-index: 9999 !important; 
    }
    
    #deleteWithProductsModal .modal-dialog {
        z-index: 10000 !important;
    }
</style>

<script>
    // Additional modal handling for category deletion
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
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                container: 'body',
                trigger: 'hover'
            });
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

function openDeleteWithProductsModal(categoryId, categoryName, productCount) {
    // Set up modal details
    document.getElementById('categoryNameDetail').textContent = categoryName;
    document.getElementById('productCount').textContent = productCount;
    document.getElementById('productCountDetail').textContent = productCount;
    
    // Set up form action
    const form = document.getElementById('deleteWithProductsForm');
    form.action = `/admin/categories/${categoryId}`;
    
    // Remove the current category from the target selection
    const targetSelect = document.getElementById('target_category_id');
    Array.from(targetSelect.options).forEach(option => {
        if (option.value === categoryId) {
            option.disabled = true;
        } else {
            option.disabled = false;
        }
    });
    
    // Reset selection
    targetSelect.value = '';
    
    // Fix any existing modal backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.style.zIndex = '9990';
    });
    
    // Show modal
    const modalElement = document.getElementById('deleteWithProductsModal');
    modalElement.style.zIndex = '9999';
    const modalDialog = modalElement.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.style.zIndex = '10000';
        modalDialog.style.pointerEvents = 'auto';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('deleteWithProductsModal'));
    modal.show();
}

// Handle form submission
document.getElementById('confirmDeleteWithProducts').addEventListener('click', function() {
    const form = document.getElementById('deleteWithProductsForm');
    const targetCategory = document.getElementById('target_category_id');
    
    if (!targetCategory.value) {
        targetCategory.classList.add('is-invalid');
        return;
    }
    
    form.submit();
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('.category-item');
    
    rows.forEach(row => {
        const categoryName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        if (categoryName.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
@endsection
