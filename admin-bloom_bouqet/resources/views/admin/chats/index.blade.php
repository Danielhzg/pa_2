@extends('admin.layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Daftar Chat Customer Support</h2>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Pesan Terakhir</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chats as $chat)
                        <tr>
                            <td>{{ $chat->user->name ?? '-' }}</td>
                            <td>{{ $chat->user->email ?? '-' }}</td>
                            <td>{{ $chat->lastMessage->message ?? '-' }}</td>
                            <td>{{ $chat->updated_at->diffForHumans() }}</td>
                            <td>
                                @php
                                    $unread = $chat->messages->where('is_admin', false)->where('read_at', null)->count();
                                @endphp
                                @if($unread > 0)
                                    <span class="badge bg-danger">{{ $unread }} baru</span>
                                @else
                                    <span class="badge bg-success">Semua dibaca</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.chats.show', $chat->id) }}" class="btn btn-sm btn-primary">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Belum ada chat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 