@extends('admin.layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Chat dengan {{ $chat->user->name ?? 'User' }}</h2>
    <div class="card mb-3">
        <div class="card-body" id="chat-messages" style="height: 400px; overflow-y: auto; background: #f8f9fa;">
            @foreach($chat->messages as $message)
                <div class="d-flex mb-2 {{ $message->is_admin ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="p-2 rounded {{ $message->is_admin ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                        <div class="small mb-1">
                            <strong>{{ $message->is_admin ? 'Admin' : ($chat->user->name ?? 'User') }}</strong>
                            <span class="text-muted float-end">{{ $message->created_at->format('H:i d/m/Y') }}</span>
                        </div>
                        <div>{{ $message->message }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <form id="send-message-form" action="{{ route('admin.chats.send', $chat->id) }}" method="POST" autocomplete="off">
        @csrf
        <div class="input-group">
            <input type="text" name="message" class="form-control" placeholder="Ketik pesan..." required maxlength="1000">
            <button type="submit" class="btn btn-primary">Kirim</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Scroll ke bawah saat halaman dibuka
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // AJAX kirim pesan
    document.getElementById('send-message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const input = form.querySelector('input[name="message"]');
        const message = input.value.trim();
        if (!message) return;
        const url = form.action;
        const token = form.querySelector('input[name="_token"]').value;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Tambahkan pesan ke chat
                const msgDiv = document.createElement('div');
                msgDiv.className = 'd-flex mb-2 justify-content-end';
                msgDiv.innerHTML = `<div class='p-2 rounded bg-primary text-white' style='max-width:70%;'>
                    <div class='small mb-1'><strong>Admin</strong> <span class='text-muted float-end'>Baru saja</span></div>
                    <div>${data.message.message}</div>
                </div>`;
                chatMessages.appendChild(msgDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                input.value = '';
            } else {
                alert(data.message || 'Gagal mengirim pesan.');
            }
        })
        .catch(() => alert('Gagal mengirim pesan.'));
    });
</script>
@endpush 