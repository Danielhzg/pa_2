@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Konfirmasi Hapus Kategori</h3>
                <p class="text-muted">Menghapus kategori "{{ $category->name }}"</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i> 
                Perhatian
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-circle me-2"></i> Kategori ini memiliki {{ $productCount }} produk terkait</h5>
                <p>Anda tidak dapat menghapus kategori ini tanpa memindahkan produk terlebih dahulu. Silakan pilih kategori lain untuk memindahkan produk-produk ini.</p>
            </div>
            
            <div class="category-info mb-4">
                <h6>Detail Kategori:</h6>
                <ul>
                    <li><strong>Nama:</strong> {{ $category->name }}</li>
                    <li><strong>Slug:</strong> {{ $category->slug }}</li>
                    <li><strong>Jumlah Produk:</strong> {{ $productCount }}</li>
                </ul>
            </div>
            
            @if($categories->count() > 0)
                <form action="{{ route('admin.categories.delete-with-products', $category) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="mb-4">
                        <label for="target_category_id" class="form-label">Pindahkan produk ke kategori:</label>
                        <select name="target_category_id" id="target_category_id" class="form-select @error('target_category_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $targetCategory)
                                <option value="{{ $targetCategory->id }}">{{ $targetCategory->name }}</option>
                            @endforeach
                        </select>
                        @error('target_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini dan memindahkan {{ $productCount }} produk ke kategori yang dipilih?')">
                            <i class="fas fa-trash me-2"></i> Hapus Kategori dan Pindahkan Produk
                        </button>
                    </div>
                </form>
            @else
                <div class="alert alert-danger">
                    <h5><i class="fas fa-times-circle me-2"></i> Tidak dapat menghapus kategori</h5>
                    <p>Tidak ada kategori lain yang tersedia untuk memindahkan produk. Silakan buat kategori baru terlebih dahulu.</p>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali
                    </a>
                    <a href="{{ route('admin.categories.create') }}" class="btn add-new-btn">
                        <i class="fas fa-plus me-2"></i> Tambah Kategori Baru
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .category-info {
        background-color: rgba(255,105,180,0.05);
        padding: 1rem;
        border-radius: 10px;
        border-left: 4px solid var(--pink-dark);
    }
    
    .category-info ul {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    
    .category-info li {
        padding: 0.5rem 0;
        border-bottom: 1px dashed rgba(255,105,180,0.2);
    }
    
    .category-info li:last-child {
        border-bottom: none;
    }
</style>
@endsection 