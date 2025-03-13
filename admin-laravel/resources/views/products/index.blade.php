@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Products</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            Add New Product
        </button>
    </div>

    <div class="row g-4">
        @foreach($products as $product)
        <div class="col-md-4">
            <div class="card">
                <img src="{{ $product->image_url }}" class="card-img-top" alt="{{ $product->name }}">
                <div class="card-body">
                    <h5 class="card-title">{{ $product->name }}</h5>
                    <p class="card-text">{{ $product->description }}</p>
                    <h6 class="mb-3">Rp {{ number_format($product->price) }}</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary edit-product" 
                                data-id="{{ $product->id }}" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editProductModal">
                            Edit
                        </button>
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Add Product Modal -->
@include('products.modals.add')

<!-- Edit Product Modal -->
@include('products.modals.edit')
@endsection
