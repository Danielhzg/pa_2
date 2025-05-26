@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Edit Carousel</h3>
                <p class="text-muted">Perbarui informasi carousel</p>
            </div>
        </div>
    </div>
    
    <div class="card form-card">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <form action="{{ route('admin.carousels.update', $carousel->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="title" class="form-label">Title</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                    value="{{ old('title', $carousel->title) }}" required>
                                @error('title')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                    rows="3">{{ old('description', $carousel->description) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="image_url" class="form-label">Change Image (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                <input type="file" name="image_url" id="image_url" class="form-control @error('image_url') is-invalid @enderror">
                                @error('image_url')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" 
                                    {{ $carousel->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn save-btn">
                                <i class="fas fa-save me-2"></i><span class="text-emphasis">Update Carousel</span>
                            </button>
                            <a href="{{ route('admin.carousels.index') }}" class="btn cancel-btn ms-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4 col-md-12 mt-4 mt-lg-0">
                    <div class="card preview-card">
                        <div class="card-header">
                            <h5 class="mb-0">Current Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <img src="{{ asset('storage/' . $carousel->image_url) }}" alt="Carousel Image" class="img-fluid rounded">
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
    
    /* Text emphasis for the "Update Carousel" button */
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
    
    /* Form switch styling */
    .form-check-input:checked {
        background-color: #FF87B2;
        border-color: #FF87B2;
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        margin-left: -2.5rem;
    }
    
    .form-check-label {
        font-weight: 500;
        padding-left: 0.5rem;
    }
</style>
@endsection
