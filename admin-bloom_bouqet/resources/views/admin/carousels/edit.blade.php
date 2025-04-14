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
                            <label for="title">Title (Optional)</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                value="{{ old('title', $carousel->title) }}">
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Description (Optional)</label>
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
                        
                        <div class="form-group mb-3">
                            <label for="order">Order</label>
                            <input type="number" name="order" id="order" class="form-control @error('order') is-invalid @enderror" 
                                value="{{ old('order', $carousel->order) }}" min="0">
                            @error('order')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="active" id="active" class="form-check-input" value="1" 
                                {{ $carousel->active ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('admin.carousels.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
