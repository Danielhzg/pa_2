@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Carousel</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.carousels.update', $carousel->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group mb-3">
                            <label for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                value="{{ old('title', $carousel->title) }}" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                rows="3">{{ old('description', $carousel->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="image">Current Image</label>
                            <div>
                                <img src="{{ asset('storage/' . $carousel->image) }}" alt="Carousel Image" style="max-width: 300px; margin-bottom: 10px;">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="image">Change Image (Optional)</label>
                            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                            @error('image')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" 
                                {{ $carousel->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary update-btn">Update Carousel</button>
                            <a href="{{ route('admin.carousels.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .update-btn {
        background: linear-gradient(45deg, #007bff, #0056b3);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }
    
    .update-btn:hover {
        background: linear-gradient(45deg, #0056b3, #003d80);
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }
</style>
@endsection
