@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex align-items-center">
            <div>
                <h3 class="page-title">Edit Kategori</h3>
                <p class="text-muted">Perbarui informasi kategori</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 col-md-10">
            <div class="card form-card">
                <div class="card-body">
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
                                <i class="fas fa-save me-2"></i>Perbarui Kategori
                            </button>
                            <a href="{{ route('admin.categories.index') }}" class="btn cancel-btn ms-2">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
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
    
    .form-control {
        border: 1px solid rgba(255,105,180,0.2);
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }
    
    .form-control:focus {
        border-color: var(--pink-primary);
        box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
    }
    
    .form-actions {
        margin-top: 2rem;
        display: flex;
        align-items: center;
    }
    
    .save-btn {
        background: linear-gradient(45deg, var(--pink-primary), var(--pink-dark));
        color: white;
        border-radius: 10px;
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        transition: all 0.3s;
    }
    
    .save-btn:hover {
        background: linear-gradient(45deg, var(--pink-dark), var(--pink-primary));
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
    
    @media (max-width: 768px) {
        .form-actions {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        
        .cancel-btn {
            margin-left: 0 !important;
        }
    }
</style>
@endsection
