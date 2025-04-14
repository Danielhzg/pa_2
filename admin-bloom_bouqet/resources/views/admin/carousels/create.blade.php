@extends('layouts.admin')

@section('content')
<h1>Create Carousel</h1>
<form action="{{ route('admin.carousels.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" class="form-control">
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea name="description" id="description" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <label for="image">Image</label>
        <input type="file" name="image" id="image" class="form-control">
    </div>
    <div class="form-group">
        <label for="order">Order</label>
        <input type="number" name="order" id="order" class="form-control">
    </div>
    <div class="form-group">
        <label for="active">Active</label>
        <input type="checkbox" name="active" id="active">
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection
