@extends('layouts.admin')

@section('title', 'Chat Pelanggan')

@section('page-title', 'Chat Pelanggan')

@section('styles')
<style>
    /* WhatsApp-like styling */
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
    
    .card-header {
        background-color: #075E54 !important;
        color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        padding: 1rem 1.5rem !important;
    }
    
    .card-title {
        color: white;
        font-weight: 600;
        margin-bottom: 0;
    }
    
    .search-box {
        position: relative;
        max-width: 300px;
    }
    
    .search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }
    
    .search-box input {
        padding-right: 35px;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.2);
        width: 100%;
        background-color: #128C7E;
        color: white;
    }
    
    .search-box input::placeholder {
        color: rgba(255,255,255,0.8);
    }
    
    .search-box input:focus {
        border-color: white;
        box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        background-color: #128C7E;
        color: white;
    }
    
    .add-new-btn {
        background-color: #128C7E;
        border: none;
        color: white;
        border-radius: 20px;
        padding: 8px 20px;
        box-shadow: 0 4px 10px rgba(18,140,126,0.2);
        transition: all 0.3s;
    }
    
    .add-new-btn:hover {
        background-color: #075E54;
        box-shadow: 0 6px 15px rgba(18,140,126,0.3);
        transform: translateY(-2px);
        color: white;
    }
    
    .text-emphasis {
        font-weight: 500;
    }
    
    .empty-state {
        padding: 40px 20px;
    }
    
    .empty-state-icon {
        font-size: 3.5rem;
        color: rgba(18,140,126,0.3);
        background-color: rgba(18,140,126,0.05);
        height: 100px;
        width: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }
    
    /* Chat container */
    .chat-container {
        display: flex;
        height: calc(100vh - 300px);
        min-height: 450px;
        max-height: 650px;
        background-color: #f0f0f0;
    }
    
    /* Chat sidebar */
    .chat-sidebar {
        width: 30%;
        min-width: 280px;
        max-width: 350px;
        border-right: 1px solid #E5E5E5;
        background-color: white;
        overflow-y: auto;
    }
    
    .chat-list {
        display: flex;
        flex-direction: column;
    }
    
    .chat-list-item {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s;
    }
    
    .chat-list-item:hover {
        background-color: #f5f5f5;
    }
    
    .chat-list-item.active {
        background-color: #EBEBEB;
    }
    
    .chat-list-item.unread {
        background-color: #DCF8C6;
    }
    
    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #128C7E;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
        margin-right: 10px;
        position: relative;
    }
    
    .online-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        background-color: #25D366;
        border-radius: 50%;
        border: 1.5px solid white;
    }
    
    .chat-user-info {
        flex: 1;
        min-width: 0;
        padding-right: 5px;
    }
    
    .chat-user-name {
        font-weight: 700;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #075E54;
        display: flex;
        align-items: center;
    }
    
    .username-badge {
        background-color: #DCF8C6;
        color: #075E54;
        font-size: 0.65rem;
        padding: 1px 5px;
        border-radius: 10px;
        margin-left: 5px;
        font-weight: 500;
    }
    
    .customer-name {
        font-size: 0.95rem;
        color: #D46A9F;
        font-weight: 700;
        letter-spacing: 0.01em;
    }
    
    .chat-last-message {
        font-size: 0.8rem;
        color: #777;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }
    
    .chat-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        min-width: 40px;
    }
    
    .chat-time {
        font-size: 0.7rem;
        color: #128C7E;
        margin-bottom: 3px;
    }
    
    .chat-badge {
        background-color: #25D366;
        color: white;
        font-size: 0.65rem;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Chat content */
    .chat-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #E4DDD6;
        background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgb3BhY2l0eT0iMC4yIj48cGF0aCBkPSJNMTAgMTBjMCAwIDEwIDUgMTAgMTBzLTUgMTAgLTEwIDEwIC0xMC01IC0xMC0xMCAxMC0xMCAxMC0xMHoiIGZpbGw9IndoaXRlIi8+PC9zdmc+');
    }
    
    .chat-welcome {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        padding: 25px 20px;
        margin: 20px auto;
        max-width: 450px;
        text-align: center;
    }
    
    .chat-welcome-icon {
        background-color: #25D366;
        height: 90px;
        width: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 2.5rem;
    }
    
    .chat-header {
        display: flex;
        align-items: center;
        padding: 8px 15px;
        background-color: #075E54;
        color: white;
    }
    
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }
    
    .message-bubble {
        display: inline-block;
        max-width: 70%;
        padding: 6px 10px;
        border-radius: 8px;
        margin-bottom: 8px;
        position: relative;
        color: #303030;
    }
    
    .message-incoming {
        background-color: white;
        border-top-left-radius: 0;
        float: left;
        clear: both;
    }
    
    .message-outgoing {
        background-color: #DCF8C6;
        border-top-right-radius: 0;
        float: right;
        clear: both;
    }
    
    .message-time {
        font-size: 0.65rem;
        color: #777;
        text-align: right;
        margin-top: 1px;
    }
    
    .chat-input {
        display: flex;
        align-items: center;
        padding: 8px;
        background-color: #f0f0f0;
        border-top: 1px solid #E5E5E5;
    }
    
    .chat-input input {
        flex: 1;
        border: none;
        border-radius: 20px;
        padding: 8px 12px;
        margin: 0 8px;
        background-color: white;
    }
    
    .chat-input button {
        background-color: #128C7E;
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .chat-input button:hover {
        background-color: #075E54;
    }
    
    /* Enhanced mobile styling */
    @media (max-width: 768px) {
        .content-header .d-flex {
            flex-direction: column;
            gap: 0.8rem;
            align-items: flex-start !important;
        }
        
        .card-header .row {
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .chat-container {
            flex-direction: column;
            height: calc(100vh - 160px);
            min-height: 400px;
        }
        
        .chat-sidebar {
            width: 100%;
            height: 35%;
            min-height: 200px;
            max-width: none;
        }
        
        .chat-content {
            height: 65%;
        }
        
        .chat-welcome {
            margin: 10px;
            padding: 15px;
        }
        
        .chat-welcome-icon {
            height: 70px;
            width: 70px;
            margin-bottom: 15px;
            font-size: 2rem;
        }
        
        .search-box {
            max-width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title">Chat Pelanggan</h3>
                <p class="text-muted">Kelola percakapan dengan pelanggan melalui aplikasi</p>
            </div>
            <div>
                <button class="btn add-new-btn" id="refreshList">
                    <i class="fas fa-sync-alt me-2"></i> <span class="text-emphasis">Refresh Chat</span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="card table-card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title">Daftar Percakapan</h5>
                </div>
                <div class="col-auto">
                    <div class="search-box">
                        <input type="text" id="searchChat" class="form-control" placeholder="Cari percakapan...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="chat-container">
                <!-- Chat Sidebar -->
                <div class="chat-sidebar">
                    <div class="chat-list">
                        @if(isset($chats) && $chats->count() > 0)
                            @foreach($chats as $chatItem)
                                <div class="chat-list-item {{ $chatItem->unread_count > 0 ? 'unread' : '' }}" data-chat-id="{{ $chatItem->id }}">
                                <div class="chat-avatar">
                                        {{ strtoupper(substr($chatItem->user->name ?? 'User', 0, 1)) }}
                                        @if($chatItem->user && $chatItem->user->is_online)
                                        <span class="online-indicator"></span>
                                    @endif
                                </div>
                                <div class="chat-user-info">
                                    <div class="chat-user-name">
                                            <span class="customer-name">{{ $chatItem->user->name ?? 'User #'.$chatItem->user_id }}</span>
                                            <span class="username-badge">Customer</span>
                                    </div>
                                    <div class="chat-last-message">
                                            @if($chatItem->last_message)
                                                @if($chatItem->last_message->is_admin)
                                                <span class="text-muted me-1">Anda:</span>
                                            @endif
                                                {{ Str::limit($chatItem->last_message->message, 30) }}
                                        @else
                                            <span class="text-muted">Belum ada pesan</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="chat-meta">
                                    <div class="chat-time">
                                            @if($chatItem->last_message)
                                                {{ \Carbon\Carbon::parse($chatItem->last_message->created_at)->format('H:i') }}
                                            @endif
                                        </div>
                                        @if($chatItem->unread_count > 0)
                                            <div class="chat-badge">{{ $chatItem->unread_count }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state text-center">
                                <div class="empty-state-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h5>Belum ada percakapan</h5>
                                <p class="text-muted">Percakapan baru dari pelanggan akan muncul di sini</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Chat Content -->
                <div class="chat-content" id="chat-content">
                    <div class="chat-welcome">
                        <div class="chat-welcome-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Selamat Datang di Chat Admin</h3>
                        <p>Pilih percakapan dari daftar di sebelah kiri untuk mulai membalas pesan dari pelanggan. Anda akan menerima notifikasi ketika ada pesan baru masuk.</p>
                        <button class="btn add-new-btn mt-3" id="refreshListBtn">
                            <i class="fas fa-sync-alt me-2"></i> <span class="text-emphasis">Refresh Daftar Chat</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle chat selection
        const chatItems = document.querySelectorAll('.chat-list-item');
        chatItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                chatItems.forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Remove unread styling
                this.classList.remove('unread');
                
                // Get chat ID
                const chatId = this.getAttribute('data-chat-id');
                
                // Show loading state
                document.getElementById('chat-content').innerHTML = `
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border" style="color: #D46A9F;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                // Load chat content
                fetch(`/admin/chats/${chatId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('chat-content').innerHTML = html;
                        
                        // Scroll to bottom of messages
                        const messagesContainer = document.getElementById('chat-messages');
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                        
                        // Setup event listeners for the chat
                        setupChatEvents();
                    })
                    .catch(error => {
                        console.error('Error loading chat:', error);
                        document.getElementById('chat-content').innerHTML = `
                            <div class="chat-welcome">
                                <div class="chat-welcome-icon text-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <h3>Error Loading Chat</h3>
                                <p>There was a problem loading this conversation. Please try again.</p>
                                <button class="btn add-new-btn mt-3" onclick="window.location.reload()">
                                    <i class="fas fa-sync-alt me-2"></i> <span class="text-emphasis">Refresh</span>
                                </button>
                            </div>
                        `;
                    });
                
                // Update URL without reloading the page
                window.history.pushState({}, '', `/admin/chats/${chatId}`);
            });
        });
        
        // Setup search functionality
        const searchInput = document.getElementById('searchChat');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                chatItems.forEach(item => {
                    const username = item.querySelector('.chat-user-name').textContent.toLowerCase();
                    const lastMessage = item.querySelector('.chat-last-message').textContent.toLowerCase();
                    
                    if (username.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Setup refresh button
        const refreshBtn = document.getElementById('refreshList');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        const refreshListBtn = document.getElementById('refreshListBtn');
        if (refreshListBtn) {
            refreshListBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Function to setup chat events
        function setupChatEvents() {
            const form = document.getElementById('send-message-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const messageInput = this.querySelector('input[name="message"]');
                    const message = messageInput.value.trim();
                    if (!message) return;
                    
                    // Disable form elements during submission
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    submitBtn.disabled = true;
                    messageInput.disabled = true;
                    
                    // Get form data
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Send message
                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            message: message
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear input
                            messageInput.value = '';
                            
                            // Reload messages
                            const chatId = window.location.pathname.split('/').pop();
                            fetch(`/admin/chats/${chatId}/new-messages`)
                                .then(response => response.text())
                                .then(html => {
                                    document.getElementById('chat-messages').innerHTML = html;
                                    
                                    // Scroll to bottom
                                    const messagesContainer = document.getElementById('chat-messages');
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                });
                        } else {
                            alert('Failed to send message: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        alert('An error occurred while sending your message.');
                    })
                    .finally(() => {
                        // Re-enable form elements
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                        messageInput.disabled = false;
                        messageInput.focus();
                    });
                });
            }
        }
        
        // Autofade alerts
        const alerts = document.querySelectorAll('.custom-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endsection 