@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title"><span class="text-pink">Tambah Produk Baru</span></h3>
                <p class="text-muted">Buat produk baru untuk dijual di toko Anda</p>
            </div>
        </div>
    </div>
    
    <div class="card form-card">
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-4">
                            <label for="name" class="form-label">Nama Produk</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="name"
                                    class="form-control @error('name') is-invalid @enderror" 
                                    value="{{ old('name') }}"
                                    placeholder="Masukkan nama produk"
                                    required
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="category_id" class="form-label">Kategori</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <select 
                                    name="category_id" 
                                    id="category_id" 
                                    class="form-select @error('category_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="" disabled selected>Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Deskripsi Produk</label>
                            <textarea 
                                name="description" 
                                id="description"
                                class="form-control @error('description') is-invalid @enderror" 
                                rows="5"
                                placeholder="Deskripsi detail tentang produk"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="price" class="form-label">Harga (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input 
                                            type="number" 
                                            name="price" 
                                            id="price"
                                            class="form-control @error('price') is-invalid @enderror" 
                                            value="{{ old('price') }}"
                                            min="0"
                                            step="1000"
                                            required
                                        >
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="stock" class="form-label">Stok</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        <input 
                                            type="number" 
                                            name="stock" 
                                            id="stock"
                                            class="form-control @error('stock') is-invalid @enderror" 
                                            value="{{ old('stock') }}"
                                            min="0"
                                            required
                                        >
                                        @error('stock')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="discount" class="form-label">Diskon (%)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                <input 
                                    type="number" 
                                    name="discount" 
                                    id="discount"
                                    class="form-control @error('discount') is-invalid @enderror" 
                                    value="{{ old('discount', 0) }}"
                                    min="0"
                                    max="100"
                                >
                                @error('discount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Persentase diskon dari harga normal (0-100)</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label d-block">Status Produk</label>
                            <div class="status-switches">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_on_sale" name="is_on_sale" {{ old('is_on_sale') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_on_sale">Diskon</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="mb-4">
                            <label for="main_image" class="form-label">Foto Utama Produk</label>
                            <div class="input-group">
                                <input 
                                    type="file" 
                                    name="main_image" 
                                    id="main_image"
                                    class="form-control @error('main_image') is-invalid @enderror" 
                                    accept="image/*"
                                    required
                                >
                                @error('main_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Foto utama produk yang akan ditampilkan</small>
                            <div class="mt-3 text-center">
                                <div class="image-preview-container border rounded p-2" id="primaryImagePreview">
                                    <img src="{{ asset('images/placeholder.jpg') }}" class="img-fluid preview-image" alt="Preview">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions mt-4">
                    <button type="submit" class="btn save-btn">
                        <i class="fas fa-save me-2"></i><span class="text-emphasis">Simpan Produk</span>
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn cancel-btn ms-2">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </form>
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
    
    .form-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .form-label {
        font-weight: 500;
        color: var(--pink-dark);
        margin-bottom: 0.5rem;
    }
    
    .input-group-text {
        background-color: rgba(255,105,180,0.1);
        border: 1px solid rgba(255,105,180,0.2);
        color: var(--pink-primary);
    }
    
    .form-control, .form-select {
        border: 1px solid rgba(255,105,180,0.2);
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--pink-primary);
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .form-check-input:checked {
        background-color: var(--pink-primary);
        border-color: var(--pink-primary);
    }
    
    .form-actions {
        display: flex;
        align-items: center;
    }
    
    .save-btn {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.5rem;
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
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .cancel-btn:hover {
        background-color: #e9ecef;
        color: #495057;
    }
    
    .image-preview-container {
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background-color: #f8f9fa;
    }
    
    .preview-image {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
    
    .preview-item {
        position: relative;
        margin-bottom: 15px;
    }
    
    .preview-item img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 5px;
    }
    
    .delete-btn:hover {
        background-color: var(--red);
        color: white;
    }
    
    /* Status Switches Styling */
    .status-switches {
        display: flex;
        gap: 1.5rem;
    }
    
    .form-switch {
        padding-left: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        margin-left: -2.5rem;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(0, 0, 0, 0.25)'/%3e%3c/svg%3e");
        background-position: left center;
        border-radius: 2em;
        transition: background-position 0.15s ease-in-out;
    }
    
    .form-switch .form-check-input:checked {
        background-position: right center;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
    }
    
    /* Active status */
    #is_active {
        background-color: #ddd;
        border-color: #ccc;
    }
    
    #is_active:checked {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    /* On Sale status */
    #is_on_sale {
        background-color: #ddd;
        border-color: #ccc;
    }
    
    #is_on_sale:checked {
        background-color: #fd7e14;
        border-color: #fd7e14;
    }
    
    .form-check-label {
        font-weight: 500;
        padding-left: 0.5rem;
    }
    
    /* Pink text styling */
    .text-pink {
        color: #FF87B2 !important;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    /* Button text emphasis */
    .text-emphasis {
        color: #ffffff !important;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Primary image preview
        const primaryImageInput = document.getElementById('main_image');
        const primaryImagePreview = document.getElementById('primaryImagePreview').querySelector('img');
        
        primaryImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    primaryImagePreview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
</script>
@endsection
