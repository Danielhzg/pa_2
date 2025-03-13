@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Chat List -->
        <div class="col-md-4 border-end">
            <h4 class="mb-4">Customer Messages</h4>
            <div class="list-group">
                @foreach($chats as $chat)
                <a href="#" class="list-group-item list-group-item-action" data-user-id="{{ $chat->user_id }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">{{ $chat->user->name }}</h6>
                        @if($chat->unread_count > 0)
                        <span class="badge bg-primary">{{ $chat->unread_count }}</span>
                        @endif
                    </div>
                    <small>{{ $chat->latest_message }}</small>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="col-md-8">
            <div id="messages" class="bg-light p-3" style="height: 500px; overflow-y: auto;">
                <!-- Messages will be loaded here -->
            </div>
            <form id="message-form" class="p-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="message-input">
                    <button class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
