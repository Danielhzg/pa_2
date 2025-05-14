<!-- Chat Header -->
<div class="chat-header">
    <div class="d-flex align-items-center">
        <div class="chat-avatar-lg">
            {{ strtoupper(substr($chat->user->name ?? 'User', 0, 1)) }}
            @if($chat->user && $chat->user->is_online)
                <span class="online-indicator"></span>
            @endif
        </div>
        <div class="chat-user-details">
            <div class="chat-user-name-lg">{{ $chat->user->name ?? 'User #'.$chat->user_id }}</div>
            <div class="chat-user-status">
                @if($chat->user && $chat->user->is_online)
                    <span class="text-success"><i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Online</span>
                @else
                    <span class="text-muted"><i class="fas fa-clock me-1" style="font-size: 8px;"></i> Terakhir dilihat: {{ $chat->user && $chat->user->last_active ? \Carbon\Carbon::parse($chat->user->last_active)->diffForHumans() : 'Tidak diketahui' }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="chat-actions">
        <div class="dropdown">
            <button class="btn action-btn action-btn-outline" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" id="markAllReadBtn"><i class="fas fa-check-double me-2"></i> Tandai Semua Terbaca</a></li>
                <li><a class="dropdown-item" href="{{ route('admin.customers.show', $chat->user_id) }}"><i class="fas fa-user me-2"></i> Lihat Profil Pelanggan</a></li>
                <li><a class="dropdown-item" href="#" id="refreshChatBtn"><i class="fas fa-sync-alt me-2"></i> Refresh Chat</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" id="clearChatBtn"><i class="fas fa-trash me-2"></i> Hapus Riwayat Chat</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Chat Messages -->
<div class="chat-messages" id="chat-messages">
    @php
        $currentDate = null;
    @endphp
    
    @forelse($chat->messages as $message)
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
                                <i class="fas fa-check-double" title="Read" style="color: #FFE5EE;"></i>
                            @else
                                <i class="fas fa-check" title="Sent"></i>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="no-messages">
            <div class="chat-welcome-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h5>Belum ada pesan</h5>
            <p class="text-muted">Mulai kirim pesan untuk memulai percakapan</p>
        </div>
    @endforelse
</div>

<!-- Chat Input -->
<div class="chat-input">
    <form id="send-message-form" action="{{ route('admin.chats.send', $chat->id) }}" method="POST">
        @csrf
        <div class="input-group">
            <button type="button" class="btn" id="attach-button">
                <i class="fas fa-paperclip"></i>
            </button>
            <input type="text" name="message" class="form-control" placeholder="Ketik pesan..." autocomplete="off" autofocus>
            <button type="submit" class="btn send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </form>
</div>

<style>
    .action-btn {
        padding: 8px 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .action-btn-outline {
        background-color: transparent;
        color: #D46A9F;
        border: 1px solid rgba(255,105,180,0.3);
    }
    
    .action-btn-outline:hover {
        background-color: rgba(255,105,180,0.05);
        color: #D46A9F;
        border-color: #FF87B2;
    }
    
    .no-messages {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        padding: 40px 20px;
        text-align: center;
        height: 100%;
    }
    
    .send-btn {
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
    
    .send-btn:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
        color: white;
    }
    
    .new-message-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        padding: 10px 16px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(255,105,180,0.4);
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
        transition: opacity 0.3s ease;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>

<script>
    // Function to scroll to bottom of messages
    function scrollToBottom() {
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Scroll to bottom on load
    document.addEventListener('DOMContentLoaded', scrollToBottom);
    
    // Refresh chat button
    document.getElementById('refreshChatBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border" style="color: #D46A9F" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat pesan...</p>
            </div>
        `;
        
        fetch(`/admin/chats/{{ $chat->id }}/new-messages`)
            .then(response => response.text())
            .then(html => {
                messagesContainer.innerHTML = html;
                scrollToBottom();
            })
            .catch(error => {
                console.error('Error refreshing chat:', error);
                messagesContainer.innerHTML = `
                    <div class="text-center p-4">
                        <div class="text-danger"><i class="fas fa-exclamation-circle fa-3x"></i></div>
                        <p class="mt-3">Gagal memuat pesan. Silakan coba lagi.</p>
                        <button class="btn add-new-btn mt-2" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i> <span class="text-emphasis">Refresh</span>
                        </button>
                    </div>
                `;
            });
    });
    
    // Mark all as read button
    document.getElementById('markAllReadBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/admin/chats/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Successfully marked as read
                const badges = document.querySelectorAll('.chat-badge');
                badges.forEach(badge => badge.remove());
                
                // Remove unread classes
                const unreadItems = document.querySelectorAll('.chat-list-item.unread');
                unreadItems.forEach(item => item.classList.remove('unread'));
                
                // Show success notification
                showSuccessToast('Semua pesan telah ditandai sebagai terbaca.');
            }
        })
        .catch(error => {
            console.error('Error marking messages as read:', error);
        });
    });
    
    // Clear chat button
    document.getElementById('clearChatBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        if (confirm('Anda yakin ingin menghapus semua riwayat chat ini? Tindakan ini tidak dapat dibatalkan.')) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/admin/chats/{{ $chat->id }}/clear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh chat messages
                    document.getElementById('refreshChatBtn').click();
                    
                    // Show success notification
                    showSuccessToast('Riwayat chat telah dihapus.');
                }
            })
            .catch(error => {
                console.error('Error clearing chat:', error);
                showErrorToast('Gagal menghapus riwayat chat.');
            });
        }
    });
    
    // Attachment button (placeholder functionality)
    document.getElementById('attach-button').addEventListener('click', function() {
        alert('Fitur upload file akan segera hadir!');
    });
    
    // Function to show toast notification
    function showSuccessToast(message) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'new-message-notification';
        toast.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <span>${message}</span>
        `;
        
        // Add to body and remove after 3 seconds
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function showErrorToast(message) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'new-message-notification';
        toast.style.background = 'linear-gradient(45deg, #ff5b5b, #ff2121)';
        toast.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            <span>${message}</span>
        `;
        
        // Add to body and remove after 3 seconds
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Setup send message form
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
                    
                    // Refresh messages
                    document.getElementById('refreshChatBtn').click();
                } else {
                    showErrorToast('Gagal mengirim pesan');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showErrorToast('Terjadi kesalahan saat mengirim pesan');
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
</script> 