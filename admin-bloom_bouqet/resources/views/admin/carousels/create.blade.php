@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create Carousel</h4>
                </div>
                <div class="card-body">
<form action="{{ route('admin.carousels.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
                        <div class="form-group mb-3">
        <label for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                value="{{ old('title') }}" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
    </div>
                        
                        <div class="form-group mb-3">
        <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
    </div>
                        
                        <div class="form-group mb-3">
        <label for="image">Image</label>
                            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" required>
                            @error('image')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
    </div>
                        
    <div class="form-group">
                            <button type="submit" class="btn btn-primary save-btn">Save Carousel</button>
                            <a href="{{ route('admin.carousels.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

<style>
    .save-btn {
        background: linear-gradient(45deg, #28a745, #218838);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }
    
    .save-btn:hover {
        background: linear-gradient(45deg, #218838, #1e7e34);
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }
</style>
@endsection
