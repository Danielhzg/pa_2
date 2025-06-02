<script>
    document.addEventListener('DOMContentLoaded', function() {
        const messagesContainer = document.getElementById('chat-messages');
        
        // Scroll to bottom of messages
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Handle message form submission
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
                                    Baru saja
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
                        showNotification('error', data.message || 'Gagal mengirim pesan.');
                    }
                })
                .catch((error) => {
                    console.error('Error sending message:', error);
                    showNotification('error', 'Gagal mengirim pesan. Silakan coba lagi.');
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
        
        // Clear chat button
        const clearChatBtn = document.getElementById('clearChatBtn');
        if (clearChatBtn) {
            clearChatBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Apakah Anda yakin ingin menghapus semua riwayat chat ini?')) {
                    return;
                }
                
                const chatId = new URL(window.location.href).pathname.split('/').pop();
                
                // Show loading state
                const chatContent = document.getElementById('chat-messages');
                const originalContent = chatContent.innerHTML;
                chatContent.innerHTML = `
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <div class="spinner-border" style="color: #D46A9F;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                fetch(`/admin/chats/${chatId}/clear`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        // Clear all messages except system messages
                        messagesContainer.innerHTML = '';
                        
                        // Add system message
                        const systemMsg = document.createElement('div');
                        systemMsg.className = 'message-row';
                        systemMsg.innerHTML = `
                            <div class='message-bubble system-message'>
                                <div class='message-content'>Riwayat chat telah dihapus oleh admin</div>
                                <div class='message-time'>Baru saja</div>
                            </div>
                        `;
                        messagesContainer.appendChild(systemMsg);
                        showNotification('success', 'Riwayat chat berhasil dihapus');
                    } else {
                        chatContent.innerHTML = originalContent;
                        showNotification('error', data.message || 'Gagal menghapus riwayat chat.');
                    }
                })
                .catch((error) => {
                    console.error('Error clearing chat:', error);
                    chatContent.innerHTML = originalContent;
                    showNotification('error', 'Gagal menghapus riwayat chat. Silakan coba lagi.');
                });
            });
        }
        
        // Refresh chat button
        const refreshChat = document.getElementById('refreshChat');
        if (refreshChat) {
            refreshChat.addEventListener('click', function(e) {
                e.preventDefault();
                const chatId = new URL(window.location.href).pathname.split('/').pop();
                
                // Show loading indicator
                const refreshBtn = this;
                const originalText = refreshBtn.innerHTML;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memuat...';
                refreshBtn.classList.add('disabled');
                
                fetch(`/admin/chats/${chatId}?partial=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Update chat content with new messages
                    document.querySelector('.chat-header').outerHTML = doc.querySelector('.chat-header').outerHTML;
                    document.querySelector('.chat-messages').outerHTML = doc.querySelector('.chat-messages').outerHTML;
                    
                    // Re-initialize scroll
                    const newMessagesContainer = document.getElementById('chat-messages');
                    newMessagesContainer.scrollTop = newMessagesContainer.scrollHeight;
                    
                    showNotification('success', 'Chat berhasil diperbarui');
                })
                .catch(error => {
                    console.error('Error refreshing chat:', error);
                    showNotification('error', 'Gagal memperbarui chat');
                })
                .finally(() => {
                    // Restore button state
                    refreshBtn.innerHTML = originalText;
                    refreshBtn.classList.remove('disabled');
                });
            });
        }
        
        // Poll for new messages
        let lastMessageId = 0;
        const lastMessageEl = document.querySelector('.message-row:last-child');
        if (lastMessageEl) {
            lastMessageId = lastMessageEl.getAttribute('data-message-id') || 0;
        }
        
        function updateTypingStatus(isTyping) {
            const typingIndicator = document.getElementById('typing-indicator');
            const onlineStatus = document.getElementById('online-status');
            const offlineStatus = document.getElementById('offline-status');
            
            if (typingIndicator) {
                typingIndicator.style.display = isTyping ? 'inline-block' : 'none';
                
                if (isTyping) {
                    onlineStatus.style.display = 'none';
                    offlineStatus.style.display = 'none';
                } else {
                    // Determine user status logic here
                    // This is a simplified version
                    onlineStatus.style.display = '';
                    offlineStatus.style.display = 'none';
                }
            }
        }
        
        function pollForNewMessages() {
            const chatId = new URL(window.location.href).pathname.split('/').pop();
            
            fetch(`/admin/chats/${chatId}/new-messages?last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(message => {
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
                                        <i class="fas fa-check${message.read_at ? '-double" title="Read" style="color: #FFE5EE;"' : '" title="Sent"'}</i>
                                    </span>
                                    </div>
                                `);
                                bubbleContent = bubbleContent.split('<div class="message-time">')[0] + '<div class="message-time">' + newTimeSection;
                            }
                            
                            // Set the bubble HTML
                            msgRow.innerHTML = `<div class="message-bubble ${message.is_admin ? 'admin' : 'user'}">${bubbleContent}</div>`;
                            
                            // Add to messages container at the bottom
                            messagesContainer.appendChild(msgRow);
                            
                            // Update last message id
                            lastMessageId = message.id;
                            
                            // Play notification sound for new user messages
                            if (!message.is_admin) {
                                playNotificationSound();
                                showBrowserNotification(message.message);
                                
                                // Mark message as read
                                fetch(`/admin/chats/${chatId}/read/${message.id}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                    }
                                }).catch(error => console.error('Error marking message as read:', error));
                            }
                        });
                        
                        // Always scroll to bottom when new messages arrive
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    
                    // Update typing status
                    if (data.user_typing) {
                        updateTypingStatus(true);
                    } else {
                        updateTypingStatus(false);
                    }
                })
                .catch(error => console.error('Error polling for messages:', error));
        }
        
        // Poll for new messages every 3 seconds
        setInterval(pollForNewMessages, 3000);
        
        // Play notification sound
        function playNotificationSound() {
            const notificationSound = new Audio('/sounds/chat-notification.mp3');
            notificationSound.play().catch(error => {
                console.error('Error playing notification sound:', error);
            });
        }
        
        // Show browser notification
        function showBrowserNotification(message) {
            if (Notification.permission === 'granted') {
                const notification = new Notification('Pesan baru dari pelanggan', {
                    body: message,
                    icon: '/img/logo.png'
                });
                
                // Close notification after 5 seconds
                setTimeout(() => notification.close(), 5000);
            }
            
            // Request permission if not granted
            else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        showBrowserNotification(message);
                    }
                });
            }
        }
        
        // Show toast notifications
        function showNotification(type, message) {
            const notif = document.createElement('div');
            notif.className = 'new-message-notification';
            
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
    });
</script>

<style>
    .new-message-notification {
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
    
    .new-message-indicator {
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(255,105,180,0.3);
        z-index: 999;
        display: flex;
        align-items: center;
        font-size: 14px;
        animation: bounce 2s infinite;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-8px); }
        60% { transform: translateY(-4px); }
    }
</style> 