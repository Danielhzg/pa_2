@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Products</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            Add Product
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td><img src="{{ $product->image_url }}" width="50"></td>
                            <td>{{ $product->name }}</td>
                            <td>Rp {{ number_format($product->price) }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>{{ $product->category }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editProduct({{ $product->id }})">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct({{ $product->id }})">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('products.modals.add')
@include('products.modals.edit')
@endsection
