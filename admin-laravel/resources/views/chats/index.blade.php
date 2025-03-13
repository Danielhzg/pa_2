@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Chat List -->
        <div class="col-md-4 border-end">
            <h4 class="mb-4">Customer Chats</h4>
            <div class="list-group">
                @foreach($chats as $chat)
                <a href="#" class="list-group-item list-group-item-action chat-item" 
                   data-user-id="{{ $chat->user_id }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">{{ $chat->user->name }}</h6>
                        @if($chat->unread_count > 0)
                        <span class="badge bg-primary rounded-pill">{{ $chat->unread_count }}</span>
                        @endif
                    </div>
                    <small class="text-muted">{{ $chat->last_message }}</small>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="col-md-8">
            <div id="chat-messages" class="bg-light p-3" style="height: 70vh; overflow-y: auto;">
                <!-- Messages will be loaded here -->
            </div>
            <div class="p-3 border-top">
                <form id="message-form" class="d-flex gap-2">
                    <input type="text" class="form-control" id="message-input" placeholder="Type your message...">
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Add WebSocket connection and chat functionality here
    const ws = new WebSocket('ws://your-websocket-server');
    // ... rest of the chat functionality
</script>
@endpush
@endsection
