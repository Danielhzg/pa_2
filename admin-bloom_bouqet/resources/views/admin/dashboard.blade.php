@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h3>Welcome to the Admin Dashboard</h3>
    <div class="row mt-4">
        <!-- Total Products -->
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Total Products</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalProducts }}</h5>
                    <p class="card-text">The total number of products in the system.</p>
                </div>
            </div>
        </div>
        <!-- Total Categories -->
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Total Categories</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalCategories }}</h5>
                    <p class="card-text">The total number of categories in the system.</p>
                </div>
            </div>
        </div>
        <!-- Total Orders -->
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Total Orders</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalOrders }}</h5>
                    <p class="card-text">The total number of orders placed by customers.</p>
                </div>
            </div>
        </div>
        <!-- Total Customers -->
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">Total Customers</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalCustomers }}</h5>
                    <p class="card-text">The total number of registered customers.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
