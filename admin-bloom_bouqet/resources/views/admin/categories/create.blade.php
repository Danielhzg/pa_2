@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title"><span class="text-pink">Tambah Kategori Baru</span></h3>
                <p class="text-muted">Buat kategori baru untuk produk Anda</p>
            </div>
        </div>
    </div>
    
    <div class="card form-card">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <form action="{{ route('admin.categories.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="form-label">Nama Kategori</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="name" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    placeholder="Masukkan nama kategori" 
                                    value="{{ old('name') }}"
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
                                <i class="fas fa-save me-2"></i><span class="text-emphasis">Simpan Kategori</span>
                            </button>
                            <a href="{{ route('admin.categories.index') }}" class="btn cancel-btn ms-2">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                    
                    <!-- Tips Card -->
                    <div class="card tips-card mt-4">
                        <div class="card-body">
                            <h5 class="tips-title"><i class="fas fa-lightbulb me-2"></i>Tips</h5>
                            <ul class="tips-list">
                                <li>Gunakan nama kategori yang singkat dan mendeskripsikan produk dengan jelas</li>
                                <li>Hindari menggunakan karakter spesial pada nama kategori</li>
                                <li>Pastikan kategori yang Anda buat tidak duplikat dengan yang sudah ada</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-12 mt-4 mt-lg-0">
                    <div class="card preview-card">
                        <div class="card-header">
                            <h5 class="mb-0">Pratinjau Kategori</h5>
                        </div>
                        <div class="card-body">
                            <div class="preview-container text-center">
                                <div class="preview-icon-container mb-3">
                                    <i class="fas fa-tag preview-icon"></i>
                                </div>
                                <h5 class="preview-category-name" id="previewName">Nama Kategori</h5>
                                <p class="text-muted mb-0">0 Produk</p>
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
    
    .text-pink {
        color: #D46A9F;
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
    
    .form-check-input:checked {
        background-color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .form-actions {
        margin-top: 2rem;
        display: flex;
        align-items: center;
    }
    
    /* Text emphasis for the "Simpan Kategori" button */
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
    
    .tips-card {
        border-radius: 15px;
        border: none;
        background-color: rgba(255,255,224,0.5);
        border-left: 4px solid #ffc107;
    }
    
    .tips-title {
        color: #856404;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    .tips-list {
        padding-left: 1.5rem;
        margin-bottom: 0;
    }
    
    .tips-list li {
        margin-bottom: 0.5rem;
        color: #6c757d;
    }
    
    .tips-list li:last-child {
        margin-bottom: 0;
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
    
    .preview-container {
        padding: 1.5rem;
    }
    
    .preview-icon-container {
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const previewName = document.getElementById('previewName');
        
        // Update preview when name changes
        nameInput.addEventListener('input', function() {
            if (this.value) {
                previewName.textContent = this.value;
            } else {
                previewName.textContent = 'Nama Kategori';
            }
        });
    });
</script>
@endsection
