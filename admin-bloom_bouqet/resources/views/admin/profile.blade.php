@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Profil Saya</h3>
                <p class="text-muted">Kelola informasi akun dan preferensi Anda</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" role="alert">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Informasi Profil</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-3 d-inline-flex justify-content-center align-items-center rounded-circle bg-primary" 
                             style="width: 100px; height: 100px;">
                            <span class="display-4 text-white">
                                {{ substr($user->full_name ?? $user->username ?? 'A', 0, 1) }}
                            </span>
                        </div>
                        <h5 class="font-weight-bold">{{ $user->full_name ?? $user->username }}</h5>
                        <p class="text-muted">
                            <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Member sejak {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Edit Profil</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.profile.update') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $user->username) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}">
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3 font-weight-bold">Ubah Password</h6>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-6">
                                <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-label {
        font-weight: 500;
    }
    
    .card {
        border-radius: 10px;
        border: none;
    }
    
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
    }
    
    .form-control {
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }
    
    .btn-primary {
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
    }
</style>
@endsection 