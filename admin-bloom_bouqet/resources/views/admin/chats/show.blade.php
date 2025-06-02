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
        
        .customer-highlight {
            background-color: #FFE5EE;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
            color: #D46A9F;
            border: 1px solid rgba(212, 106, 159, 0.2);
        }
        
        .table-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
            max-height: calc(100vh - 180px);
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
                    <h3 class="page-title">Chat dengan <span class="customer-highlight">{{ $chat->user->name ?? 'User' }}</span></h3>
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
                <div class="chat-user-name-lg">
                    <span class="customer-name-lg">{{ $chat->user->name ?? 'User' }}</span>
                    <span class="customer-badge">Customer</span>
                </div>
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
            // Sort messages by created_at in ascending order to display oldest first
            $sortedMessages = $chat->messages->sortBy('created_at');
        @endphp
        
        @foreach($sortedMessages as $message)
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
            padding: 12px;
            background-color: #fff;
            border-bottom: 1px solid rgba(255,105,180,0.1);
        }
        
        .chat-avatar-lg {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(45deg, #FF87B2, #D46A9F);
            margin-right: 12px;
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
            display: flex;
            align-items: center;
        }
        
        .customer-name-lg {
            font-weight: 700;
            font-size: 18px;
            color: #D46A9F;
        }
        
        .customer-badge {
            background-color: #FFE5EE;
            color: #D46A9F;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            font-weight: 500;
            border: 1px solid rgba(212, 106, 159, 0.2);
        }
        
        .chat-user-status {
            font-size: 12px;
            color: #6c757d;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f8f9fa;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23FFE5EE' fill-opacity='0.4'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 260px);
            max-height: calc(100vh - 260px);
        }
        
        .message-row {
            display: flex;
            margin-bottom: 10px;
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
            padding: 8px 12px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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
            margin-bottom: 4px;
            word-wrap: break-word;
            line-height: 1.3;
            font-size: 0.9rem;
        }
        
        .message-time {
            font-size: 10px;
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
            padding: 10px;
            background-color: #fff;
            border-top: 1px solid rgba(255,105,180,0.1);
        }
        
        .chat-input .form-control {
            border-radius: 20px;
            padding: 8px 12px;
            height: auto;
            border: 1px solid rgba(255,105,180,0.2);
        }
        
        .chat-input .btn.send-btn {
            border-radius: 10px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #FF87B2, #D46A9F);
            border: none;
            color: white;
            box-shadow: 0 3px 6px rgba(255,105,180,0.3);
            transition: all 0.3s;
        }
        
        #attach-button {
            border-radius: 10px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            background-color: rgba(255,105,180,0.1);
            color: #D46A9F;
            border: none;
            transition: all 0.3s;
        }
        
        .day-divider {
            text-align: center;
            margin: 15px 0;
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
            padding: 0 12px;
            font-size: 11px;
            color: #D46A9F;
            position: relative;
            z-index: 2;
        }
        
        .online-indicator {
            width: 10px;
            height: 10px;
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
            // Scroll to bottom on page load
            const messagesContainer = document.getElementById('chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                // Mark messages as read on page load
                markMessagesAsRead();
            }
            
            // Add refresh button handler
            const refreshBtn = document.getElementById('refreshChat');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
            }
            
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
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    submitBtn.disabled = true;
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message })
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
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
                            
                            // Add to chat and scroll to bottom
                            messagesContainer.appendChild(msgRow);
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            
                            // Clear input
                            input.value = '';
                            
                            // Update last message ID for polling
                            lastMessageId = data.message.id;
                        } else {
                            showNotification('error', data.message || 'Failed to send message');
                        }
                    })
                    .catch((error) => {
                        console.error('Error sending message:', error);
                        showNotification('error', 'Failed to send message. Please try again.');
                    })
                    .finally(() => {
                        // Re-enable input and button
                        input.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                        input.focus();
                    });
                });
            }
            
            // Poll for new messages every 3 seconds
            let lastMessageId = 0;
            const lastMessageEl = document.querySelector('.message-row:last-child');
            if (lastMessageEl) {
                lastMessageId = lastMessageEl.getAttribute('data-message-id') || 0;
                console.log('Last message ID:', lastMessageId);
            }
            
            function pollForNewMessages() {
                        const chatId = window.location.pathname.split('/').pop();
                        
                fetch(`/admin/chats/${chatId}/new-messages?last_message_id=${lastMessageId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                        .then(data => {
                        console.log('Polling response:', data);
                        
                        if (data.success && data.messages && data.messages.length > 0) {
                            console.log(`Found ${data.messages.length} new message(s)`);
                            
                            data.messages.forEach(message => {
                                console.log('New message:', message);
                                
                                // Skip if message already exists in DOM
                                if (document.querySelector(`.message-row[data-message-id="${message.id}"]`)) {
                                    console.log('Message already exists in DOM, skipping:', message.id);
                                    return;
                                }
                                
                                // Create new message element
                                    const msgRow = document.createElement('div');
                                    msgRow.className = `message-row ${message.is_admin ? 'message-admin' : 'message-user'}`;
                                    msgRow.setAttribute('data-message-id', message.id);
                                    
                                // Create message bubble HTML
                                let bubbleContent = `
                                    <div class="message-content">${message.message}</div>
                                    <div class="message-time">
                                        ${new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                    </div>
                                `;
                                
                                // Add attachment if exists
                                    if (message.attachment_url) {
                                    const isImage = /\.(jpg|jpeg|png|gif)$/i.test(message.attachment_url);
                                    if (isImage) {
                                        bubbleContent = `
                                                <div class="message-attachment">
                                                    <img src="${message.attachment_url}" alt="Attachment" class="img-fluid rounded mb-2">
                                                </div>
                                            ${bubbleContent}
                                            `;
                                        } else {
                                        bubbleContent = `
                                                <div class="message-attachment">
                                                    <a href="${message.attachment_url}" target="_blank" class="attachment-link">
                                                        <i class="fas fa-file me-2"></i> Attachment
                                                    </a>
                                                </div>
                                            ${bubbleContent}
                                            `;
                                        }
                                    }
                                    
                                // Add read receipt if admin message
                                if (message.is_admin) {
                                    const timeSection = bubbleContent.split('<div class="message-time">')[1];
                                    const newTimeSection = timeSection.replace('</div>', `
                                                <span class="ms-1">
                                            <i class="fas fa-check${message.read_at ? '-double" title="Read" style="color: #4fc3f7;"' : '" title="Sent"'}</i>
                                                </span>
                                        </div>
                                    `);
                                    bubbleContent = bubbleContent.split('<div class="message-time">')[0] + '<div class="message-time">' + newTimeSection;
                                }
                                
                                // Set the bubble HTML
                                msgRow.innerHTML = `<div class="message-bubble ${message.is_admin ? 'admin' : 'user'}">${bubbleContent}</div>`;
                                    
                                // Add to messages container
                                    messagesContainer.appendChild(msgRow);
                                
                                // Update last message id
                                if (message.id > lastMessageId) {
                                    lastMessageId = message.id;
                                    console.log('Updated last message ID:', lastMessageId);
                                }
                                
                                // Play notification sound for new user messages
                                if (!message.is_admin) {
                                    // Play notification sound
                                    const audio = new Audio('/sounds/notification.mp3');
                                    audio.play().catch(e => console.log('Error playing sound:', e));
                                    
                                    // Show browser notification
                                    if (Notification.permission === 'granted') {
                                        const notification = new Notification('New Message', {
                                            body: message.message,
                                            icon: '/favicon.ico'
                                        });
                                        setTimeout(() => notification.close(), 5000);
                                    }
                                }
                            });
                            
                            // Always scroll to bottom when new messages arrive
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            
                            // Mark new messages as read
                            markMessagesAsRead();
                        }
                    })
                    .catch(error => console.error('Error polling for messages:', error));
            }
            
            // Poll for new messages every 3 seconds
            setInterval(pollForNewMessages, 3000);
            
            // Initial poll for new messages
            pollForNewMessages();
            
            // Show toast notifications
            function showNotification(type, message) {
                const notif = document.createElement('div');
                notif.className = 'notification-toast';
                
                if (type === 'error') {
                    notif.style.background = 'linear-gradient(45deg, #ff5b5b, #ff2121)';
                    notif.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i> ${message}`;
                } else {
                    notif.style.background = 'linear-gradient(45deg, #FF87B2, #D46A9F)';
                    notif.innerHTML = `<i class="fas fa-check-circle me-2"></i> ${message}`;
                }
                
                document.body.appendChild(notif);
                
                setTimeout(() => {
                    notif.style.opacity = '0';
                    setTimeout(() => notif.remove(), 300);
                }, 3000);
            }
            
            // After messages are loaded or received, mark them as read
            function markMessagesAsRead() {
                const chatId = window.location.pathname.split('/').pop();
                const unreadUserMessages = document.querySelectorAll('.message-row.message-user');
                
                if (unreadUserMessages.length > 0) {
                    console.log('Marking messages as read');
                    
                    fetch(`/admin/chats/${chatId}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log(`Marked ${data.count} messages as read`);
                            }
                        })
                    .catch(error => {
                        console.error('Error marking messages as read:', error);
                    });
                }
            }
        });
    </script>
    
    <style>
        .notification-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 16px;
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 12px rgba(255,105,180,0.4);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
            transition: opacity 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
@endif 