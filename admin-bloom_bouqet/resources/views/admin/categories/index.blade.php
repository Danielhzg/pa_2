@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Kategori</h3>
                <p class="text-muted">Kelola kategori produk toko Anda</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn add-new-btn">
                <i class="fas fa-plus me-2"></i> Tambah Kategori Baru
            </a>
        </div>
    </div>
    
    @if (session('success'))
        <div class="alert custom-alert alert-success fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
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
                            <th>ID Kategori</th>
                            <th>Nama Kategori</th>
                            <th>Jumlah Produk</th>
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
                                <td>{{ $category->products->count() ?? 0 }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn action-btn delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $category->id }}" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal{{ $category->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Apakah Anda yakin ingin menghapus kategori <strong>{{ $category->name }}</strong>?</p>
                                                    <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan dan mungkin mempengaruhi produk yang terkait.</small></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <form action="{{ route('admin.categories.delete', $category) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
                    <i class="fas fa-plus me-2"></i> Tambah Kategori Baru
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .content-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        color: var(--pink-dark);
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .add-new-btn {
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .add-new-btn:hover {
        background: linear-gradient(45deg, var(--pink-dark), var(--pink-primary));
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
        color: var(--pink-dark);
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
        border-color: var(--pink-primary);
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .category-table {
        margin-bottom: 0;
    }
    
    .category-table thead th {
        background-color: rgba(255,105,180,0.05);
        color: var(--pink-dark);
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
        background-color: rgba(255,105,180,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pink-primary);
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
    
    .empty-state-icon {
        font-size: 3rem;
        color: rgba(255,105,180,0.3);
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .modal-header {
        background-color: rgba(255,105,180,0.05);
        border-bottom: 1px solid rgba(255,105,180,0.1);
    }
    
    .modal-title {
        color: var(--pink-dark);
    }
    
    @media (max-width: 768px) {
        .content-header .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        
        .card-header .row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .action-buttons {
            flex-wrap: nowrap;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const categoryItems = document.querySelectorAll('.category-item');
    
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        
        categoryItems.forEach(item => {
            const categoryName = item.querySelector('td:nth-child(2)').textContent.toLowerCase();
            
            if (categoryName.includes(searchValue)) {
                item.style.display = 'table-row';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Autofade alerts
    const alerts = document.querySelectorAll('.custom-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endsection
