@extends('layouts.admin')

@section('content')
<h1>Manage Carousels</h1>
<a href="{{ route('admin.carousels.create') }}" class="btn btn-primary mb-3">Add New Carousel</a>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Image</th>
            <th>Order</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($carousels as $carousel)
        <tr>
            <td>{{ $carousel->title }}</td>
            <td>{{ $carousel->description }}</td>
            <td><img src="{{ asset('storage/' . $carousel->image) }}" alt="Image" width="100"></td>
            <td>{{ $carousel->order }}</td>
            <td>{{ $carousel->active ? 'Yes' : 'No' }}</td>
            <td>
                <div class="action-buttons">
                    <a href="{{ route('admin.carousels.edit', $carousel) }}" class="btn action-btn edit-btn" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.carousels.destroy', $carousel) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn action-btn delete-btn" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
