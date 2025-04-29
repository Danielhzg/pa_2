@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Daftar Pelanggan</h1>
        <div>
            <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Cari nama atau email..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if(count($customers) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Total Pesanan</th>
                                <th>Total Belanja</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                <tr>
                                    <td>{{ $customer->id }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->orders_count }}</td>
                                    <td>Rp {{ number_format($customer->orders_sum_total_amount ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $customer->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $customers->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    Tidak ada pelanggan yang ditemukan.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 