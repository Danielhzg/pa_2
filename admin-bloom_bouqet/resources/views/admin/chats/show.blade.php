@if(!request()->has('partial'))
    @extends('layouts.admin')
    
    @section('title', 'Chat Pelanggan')

    @section('page-title', 'Chat Pelanggan')
    
    @section('styles')
    @include('admin.chats.styles')
    <style>
        .content-header {
            margin-bottom: 1.5rem;
        }
        
        .page-title {
            color: #D46A9F;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .table-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
        }
        
        .back-btn {
            background-color: white;
            border: 1px solid rgba(255,105,180,0.2);
            color: #D46A9F;
            border-radius: 20px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: rgba(255,135,178,0.05);
            border-color: #FF87B2;
            color: #D46A9F;
        }
    </style>
    @endsection
    
    @section('content')
    <div class="container-fluid">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="page-title">Chat dengan {{ $chat->user->name ?? 'User' }}</h3>
                    <p class="text-muted">Percakapan dengan pelanggan melalui aplikasi</p>
                </div>
                <div>
                    <a href="{{ route('admin.chats.index') }}" class="btn back-btn">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card table-card">
            <div class="card-body p-0">
                @include('admin.chats.partial', ['chat' => $chat])
            </div>
        </div>
    </div>
    @endsection
    
    @push('scripts')
    @include('admin.chats.scripts')
    @endpush
@else
    <div class="chat-header">
        <div class="d-flex align-items-center">
            <div class="chat-avatar-lg">
                {{ strtoupper(substr($chat->user->name ?? 'U', 0, 1)) }}
                @if($chat->user && $chat->user->last_active && \Carbon\Carbon::parse($chat->user->last_active)->diffInMinutes() < 5)
                    <div class="online-indicator"></div>
                @endif
            </div>
            <div class="chat-user-details">
                <div class="chat-user-name-lg">{{ $chat->user->name ?? 'User' }}</div>
                <div class="chat-user-status">
                    <span id="typing-indicator" style="display: none; color: #D46A9F;">
                        <i class="fas fa-keyboard me-1"></i> Sedang mengetik...
                    </span>
                    <span id="online-status" style="{{ ($chat->user && $chat->user->last_active && \Carbon\Carbon::parse($chat->user->last_active)->diffInMinutes() < 5) ? '' : 'display: none;' }}">
                        <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Online
                    </span>
                    <span id="offline-status" style="{{ (!$chat->user || !$chat->user->last_active || \Carbon\Carbon::parse($chat->user->last_active)->diffInMinutes() >= 5) ? '' : 'display: none;' }}">
                        @if($chat->user && $chat->user->last_active)
                            <i class="fas fa-clock me-1" style="font-size: 8px;"></i> Terakhir aktif {{ \Carbon\Carbon::parse($chat->user->last_active)->diffForHumans() }}
                        @else
                            <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> Offline
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-light btn-sm" type="button" id="chatOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chatOptionsDropdown">
                    <li><a class="dropdown-item" href="#" id="refreshChat"><i class="fas fa-sync-alt me-2"></i> Refresh</a></li>
                    @if($chat->user)
                        <li><a class="dropdown-item" href="{{ route('admin.customers.show', $chat->user->id) }}"><i class="fas fa-user me-2"></i> Lihat Profil</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" id="clearChatBtn"><i class="fas fa-trash me-2"></i> Hapus Riwayat Chat</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="chat-messages" id="chat-messages">
        @php
            $currentDate = null;
        @endphp
        
        @foreach($chat->messages as $message)
            @php
                $messageDate = \Carbon\Carbon::parse($message->created_at)->format('Y-m-d');
                $showDateDivider = $currentDate !== $messageDate;
                $currentDate = $messageDate;
            @endphp
            
            @if($showDateDivider)
                <div class="day-divider">
                    <span>{{ \Carbon\Carbon::parse($message->created_at)->format('d F Y') }}</span>
                </div>
            @endif
            
            <div class="message-row {{ $message->is_admin ? 'message-admin' : 'message-user' }}" data-message-id="{{ $message->id }}">
                <div class="message-bubble {{ $message->is_admin ? 'admin' : 'user' }} {{ $message->is_system ? 'system-message' : '' }}">
                    @if($message->attachment_url)
                        <div class="message-attachment">
                            @if(Str::endsWith(strtolower($message->attachment_url), ['.jpg', '.jpeg', '.png', '.gif']))
                                <img src="{{ asset($message->attachment_url) }}" alt="Attachment" class="img-fluid rounded mb-2">
                            @else
                                <a href="{{ asset($message->attachment_url) }}" target="_blank" class="attachment-link">
                                    <i class="fas fa-file me-2"></i> Attachment
                                </a>
                            @endif
                        </div>
                    @endif
                    
                    <div class="message-content">{{ $message->message }}</div>
                    <div class="message-time">
                        {{ $message->created_at->format('H:i') }}
                        @if($message->is_admin)
                            <span class="ms-1">
                                @if($message->read_at)
                                    <i class="fas fa-check-double" title="Read" style="color: #4fc3f7;"></i>
                                @else
                                    <i class="fas fa-check" title="Sent"></i>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="chat-input">
        <form id="send-message-form" action="{{ route('admin.chats.send', $chat->id) }}" method="POST" autocomplete="off">
            @csrf
            <div class="input-group">
                <button type="button" class="btn btn-light border" id="attach-button">
                    <i class="fas fa-paperclip"></i>
                </button>
                <input type="text" name="message" class="form-control border" placeholder="Ketik pesan..." required maxlength="1000">
                <button type="submit" class="btn send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
    
    <style>
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background-color: #fff;
            border-bottom: 1px solid rgba(255,105,180,0.1);
        }
        
        .chat-avatar-lg {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, #FF87B2, #D46A9F);
            margin-right: 15px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .chat-user-details {
            flex: 1;
        }
        
        .chat-user-name-lg {
            font-weight: 600;
            font-size: 16px;
            color: #D46A9F;
        }
        
        .chat-user-status {
            font-size: 12px;
            color: #6c757d;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8f9fa;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23FFE5EE' fill-opacity='0.4'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 290px);
        }
        
        .message-row {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-start;
            position: relative;
        }
        
        .message-admin {
            justify-content: flex-end;
        }
        
        .message-user {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 75%;
            padding: 10px 14px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .message-bubble.admin {
            background: linear-gradient(45deg, #FF87B2, #D46A9F);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 8px rgba(255,105,180,0.3);
        }
        
        .message-bubble.user {
            background-color: white;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .message-bubble.system-message {
            background-color: #fff3cd;
            border-radius: 18px;
            color: #856404;
            font-style: italic;
            margin: 15px auto;
            text-align: center;
            max-width: 80%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .message-content {
            margin-bottom: 6px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.8;
            text-align: right;
        }
        
        .message-bubble.admin .message-time {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .message-bubble.user .message-time {
            color: #adb5bd;
        }
        
        .chat-input {
            padding: 15px;
            background-color: #fff;
            border-top: 1px solid rgba(255,105,180,0.1);
        }
        
        .chat-input .form-control {
            border-radius: 20px;
            padding: 10px 15px;
            height: auto;
            border: 1px solid rgba(255,105,180,0.2);
        }
        
        .chat-input .form-control:focus {
            border-color: #FF87B2;
            box-shadow: 0 0 0 0.25rem rgba(255,135,178,0.25);
        }
        
        .chat-input .btn.send-btn {
            border-radius: 10px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #FF87B2, #D46A9F);
            border: none;
            color: white;
            box-shadow: 0 4px 8px rgba(255,105,180,0.3);
            transition: all 0.3s;
        }
        
        .chat-input .btn.send-btn:hover {
            background: linear-gradient(45deg, #D46A9F, #FF87B2);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255,105,180,0.4);
        }
        
        .chat-input .input-group {
            align-items: center;
        }
        
        #attach-button {
            border-radius: 10px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            background-color: rgba(255,105,180,0.1);
            color: #D46A9F;
            border: none;
            transition: all 0.3s;
        }
        
        #attach-button:hover {
            background-color: rgba(255,105,180,0.2);
        }
        
        .day-divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .day-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background-color: rgba(255,105,180,0.2);
            z-index: 1;
        }
        
        .day-divider span {
            background-color: #f8f9fa;
            padding: 0 15px;
            font-size: 12px;
            color: #D46A9F;
            position: relative;
            z-index: 2;
        }
        
        .online-indicator {
            width: 12px;
            height: 12px;
            background-color: #10b981;
            border-radius: 50%;
            position: absolute;
            bottom: 0;
            right: 0;
            border: 2px solid white;
        }
        
        /* Attachments */
        .attachment-link {
            display: inline-block;
            padding: 6px 12px;
            background-color: rgba(255,135,178,0.08);
            border-radius: 10px;
            margin-bottom: 8px;
            text-decoration: none;
            color: #D46A9F;
            transition: all 0.2s;
        }
        
        .attachment-link:hover {
            background-color: rgba(255,135,178,0.15);
            color: #D46A9F;
        }
        
        .message-attachment img {
            max-width: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .chat-messages {
                min-height: calc(100vh - 320px);
            }
            
            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to bottom
            const messagesContainer = document.getElementById('chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Simulate typing indicator (for demo purposes)
            let typingTimeout;
            function simulateTypingIndicator() {
                // Show typing indicator randomly
                if (Math.random() > 0.7) {
                    document.getElementById('typing-indicator').style.display = 'inline';
                    document.getElementById('online-status').style.display = 'none';
                    document.getElementById('offline-status').style.display = 'none';
                    
                    // Hide after random time
                    typingTimeout = setTimeout(() => {
                        document.getElementById('typing-indicator').style.display = 'none';
                        
                        // Show online status again
                        if (document.getElementById('online-status').textContent.trim() !== '') {
                            document.getElementById('online-status').style.display = 'inline';
                        } else {
                            document.getElementById('offline-status').style.display = 'inline';
                        }
                    }, 2000 + Math.random() * 3000);
                }
            }
            
            // Simulate typing every 10-20 seconds (for demo only)
            setInterval(simulateTypingIndicator, 10000 + Math.random() * 10000);
            
            // Handle form submission
            const form = document.getElementById('send-message-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const input = this.querySelector('input[name="message"]');
                    const message = input.value.trim();
                    if (!message) return;
                    
                    const url = this.action;
                    const token = this.querySelector('input[name="_token"]').value;
                    
                    // Disable input while sending
                    input.disabled = true;
                    
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
                            // Add the message to chat
                            const msgRow = document.createElement('div');
                            msgRow.className = 'message-row message-admin';
                            msgRow.setAttribute('data-message-id', data.message.id || 0);
                            msgRow.innerHTML = `
                                <div class='message-bubble admin'>
                                    <div class='message-content'>${data.message.message}</div>
                                    <div class='message-time'>
                                        ${new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}
                                        <span class="ms-1">
                                            <i class="fas fa-check" title="Sent"></i>
                                        </span>
                                    </div>
                                </div>
                            `;
                            messagesContainer.appendChild(msgRow);
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            
                            // Clear input
                            input.value = '';
                        } else {
                            alert(data.message || 'Gagal mengirim pesan.');
                        }
                    })
                    .catch(() => alert('Gagal mengirim pesan.'))
                    .finally(() => {
                        // Re-enable input
                        input.disabled = false;
                        input.focus();
                    });
                });
            }
            
            // Refresh button
            const refreshBtn = document.getElementById('refreshChat');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.reload();
                });
            }
            
            // Clear chat button
            const clearChatBtn = document.getElementById('clearChatBtn');
            if (clearChatBtn) {
                clearChatBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Apakah Anda yakin ingin menghapus seluruh riwayat chat?')) {
                        const chatId = window.location.pathname.split('/').pop();
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        
                        fetch(`/admin/chats/${chatId}/clear`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Clear chat area and add system message
                                messagesContainer.innerHTML = `
                                    <div class="message-row">
                                        <div class="message-bubble system-message">
                                            <div class="message-content">Riwayat chat telah dihapus oleh admin</div>
                                            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}</div>
                                        </div>
                                    </div>
                                `;
                            } else {
                                alert(data.message || 'Gagal menghapus riwayat chat.');
                            }
                        })
                        .catch(error => {
                            console.error('Error clearing chat:', error);
                            alert('Gagal menghapus riwayat chat. Silakan coba lagi.');
                        });
                    }
                });
            }
            
            // Attachment button
            const attachButton = document.getElementById('attach-button');
            if (attachButton) {
                attachButton.addEventListener('click', function() {
                    alert('Fitur attachment akan segera tersedia.');
                });
            }
            
            // Poll for new messages
            setInterval(function() {
                if (!document.hidden) {
                    // Get chat ID from URL
                    const chatId = window.location.pathname.split('/').pop();
                    // Get last message ID
                    const messages = document.querySelectorAll('.message-row');
                    let lastMessageId = 0;
                    
                    if (messages.length > 0) {
                        const lastMessage = messages[messages.length - 1];
                        lastMessageId = lastMessage.getAttribute('data-message-id') || 0;
                    }
                    
                    fetch(`/admin/chats/${chatId}/new-messages?last_message_id=${lastMessageId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.messages && data.messages.length > 0) {
                                data.messages.forEach(message => {
                                    const msgRow = document.createElement('div');
                                    msgRow.className = `message-row ${message.is_admin ? 'message-admin' : 'message-user'}`;
                                    msgRow.setAttribute('data-message-id', message.id);
                                    
                                    let attachmentHtml = '';
                                    if (message.attachment_url) {
                                        if (message.attachment_url.match(/\.(jpg|jpeg|png|gif)$/i)) {
                                            attachmentHtml = `
                                                <div class="message-attachment">
                                                    <img src="${message.attachment_url}" alt="Attachment" class="img-fluid rounded mb-2">
                                                </div>
                                            `;
                                        } else {
                                            attachmentHtml = `
                                                <div class="message-attachment">
                                                    <a href="${message.attachment_url}" target="_blank" class="attachment-link">
                                                        <i class="fas fa-file me-2"></i> Attachment
                                                    </a>
                                                </div>
                                            `;
                                        }
                                    }
                                    
                                    // Add system message class if needed
                                    const systemClass = message.is_system ? ' system-message' : '';
                                    
                                    msgRow.innerHTML = `
                                        <div class="message-bubble ${message.is_admin ? 'admin' : 'user'}${systemClass}">
                                            ${attachmentHtml}
                                            <div class="message-content">${message.message}</div>
                                            <div class="message-time">
                                                ${new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}
                                                ${message.is_admin ? `
                                                <span class="ms-1">
                                                    ${message.read_at ? '<i class="fas fa-check-double" title="Read" style="color: #4fc3f7;"></i>' : '<i class="fas fa-check" title="Sent"></i>'}
                                                </span>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                    
                                    messagesContainer.appendChild(msgRow);
                                });
                                
                                // Scroll to bottom if we're already near the bottom
                                const isAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 100;
                                if (isAtBottom) {
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                } else {
                                    // Show new message indicator if not at bottom
                                    const indicator = document.createElement('div');
                                    indicator.className = 'new-message-indicator';
                                    indicator.innerHTML = '<i class="fas fa-arrow-down"></i> Pesan baru';
                                    indicator.addEventListener('click', () => {
                                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                        indicator.remove();
                                    });
                                    messagesContainer.parentNode.appendChild(indicator);
                                }
                            }
                        })
                        .catch(error => console.error('Error fetching new messages:', error));
                }
            }, 5000); // Check every 5 seconds
        });
    </script>
@endif 