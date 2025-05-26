@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Edit Kategori</h3>
                <p class="text-muted">Perbarui informasi kategori</p>
            </div>
        </div>
    </div>
    
    <div class="card form-card">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="name" class="form-label">Nama Kategori</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="name" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    value="{{ $category->name }}"
                                    required
                                    autofocus
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Nama kategori harus unik dan mudah diingat</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn save-btn">
                                <i class="fas fa-save me-2"></i><span class="text-emphasis">Update Kategori</span>
                            </button>
                            <a href="{{ route('admin.categories.index') }}" class="btn cancel-btn ms-2">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4 col-md-12 mt-4 mt-lg-0">
                    <div class="card preview-card">
                        <div class="card-header">
                            <h5 class="mb-0">Informasi Kategori</h5>
                        </div>
                        <div class="card-body">
                            <div class="category-details text-center">
                                <div class="category-icon-container mb-3">
                                    <i class="fas fa-tag preview-icon"></i>
                                </div>
                                <h5 class="preview-category-name">{{ $category->name }}</h5>
                                <div class="product-count mt-3">
                                    <span class="badge product-count-badge">{{ $category->products->count() }} Produk</span>
                                </div>
                                <div class="category-status mt-3">
                                    @if($category->products->count() > 0)
                                        <span class="status-badge status-active">Digunakan</span>
                                    @else
                                        <span class="status-badge status-inactive">Tidak Digunakan</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    
    .form-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .form-label {
        font-weight: 500;
        color: #D46A9F;
        margin-bottom: 0.5rem;
    }
    
    .input-group-text {
        background-color: rgba(255,105,180,0.1);
        border: 1px solid rgba(255,105,180,0.2);
        color: #FF87B2;
    }
    
    .form-control {
        border: 1px solid rgba(255,105,180,0.2);
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }
    
    .form-control:focus {
        border-color: #FF87B2;
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .form-actions {
        margin-top: 2rem;
        display: flex;
        align-items: center;
    }
    
    /* Text emphasis for the "Perbarui Kategori" button */
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
    
    .save-btn:hover .text-emphasis::after {
        width: 100%;
    }
    
    .save-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .save-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
    }
    
    .cancel-btn {
        background-color: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .cancel-btn:hover {
        background-color: #e9ecef;
        color: #495057;
    }

    .preview-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .preview-card .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1rem 1.5rem;
        color: #D46A9F;
        font-weight: 600;
    }
    
    .category-icon-container {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        background-color: rgba(255,135,178,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    
    .preview-icon {
        font-size: 1.8rem;
        color: #FF87B2;
    }
    
    .preview-category-name {
        font-weight: 600;
        color: #333;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .product-count-badge {
        background-color: rgba(255, 87, 178, 0.1);
        color: #FF57B2;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
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
</style>
@endsection
