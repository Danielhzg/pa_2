<style>
    /* Common chat styles */
    .chat-container {
        display: flex;
        height: calc(100vh - 220px);
        border-radius: 0 0 15px 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(255,105,180,0.05);
        border: 1px solid rgba(255,105,180,0.05);
        border-top: none;
    }
    
    .chat-sidebar {
        width: 350px;
        background-color: #fff;
        border-right: 1px solid rgba(255,105,180,0.1);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    .chat-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #f8f9fa;
        position: relative;
    }
    
    /* Chat list styles */
    .chat-list {
        overflow-y: auto;
        flex: 1;
    }
    
    .chat-list-item {
        display: flex;
        padding: 15px;
        border-bottom: 1px solid rgba(255,105,180,0.1);
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .chat-list-item:hover {
        background-color: rgba(255,135,178,0.03);
    }
    
    .chat-list-item.active {
        background-color: rgba(212,106,159,0.08);
        border-left: 3px solid #D46A9F;
    }
    
    .chat-list-item.unread {
        background-color: rgba(255,135,178,0.05);
    }
    
    .chat-avatar {
        width: 50px;
        height: 50px;
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
    
    .chat-user-info {
        flex: 1;
        min-width: 0;
        padding-right: 5px;
    }
    
    .chat-user-name {
        font-weight: 600;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #D46A9F;
    }
    
    .chat-last-message {
        font-size: 13px;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0;
    }
    
    .chat-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        min-width: 70px;
    }
    
    .chat-time {
        font-size: 12px;
        color: #adb5bd;
        margin-bottom: 8px;
    }
    
    .chat-badge {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border-radius: 50%;
        min-width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
    }
    
    /* Chat welcome screen */
    .chat-welcome {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        padding: 40px 20px;
        text-align: center;
        background-color: #fff;
        border-radius: 15px;
        margin: 30px;
        box-shadow: 0 4px 20px rgba(255,105,180,0.05);
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chat-welcome-icon {
        font-size: 70px;
        color: #D46A9F;
        margin-bottom: 25px;
        background-color: rgba(255,135,178,0.1);
        height: 130px;
        width: 130px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .chat-welcome h3 {
        margin-bottom: 15px;
        color: #D46A9F;
        font-weight: 600;
    }
    
    .chat-welcome p {
        max-width: 500px;
        margin-bottom: 25px;
        line-height: 1.6;
    }
    
    /* Chat message bubbles */
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
    
    /* Chat header */
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
    
    /* Chat input */
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
    
    .chat-input .btn {
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
    
    .chat-input .btn:hover {
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
    
    /* Other elements */
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
    
    .empty-state {
        padding: 30px;
        text-align: center;
        color: #6c757d;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: rgba(255,135,178,0.3);
        margin-bottom: 1rem;
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
    
    /* Day dividers */
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
    
    /* Action buttons */
    .action-btn {
        padding: 8px 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .action-btn-primary {
        background: linear-gradient(45deg, #FF87B2, #D46A9F);
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(255,105,180,0.3);
    }
    
    .action-btn-primary:hover {
        background: linear-gradient(45deg, #D46A9F, #FF87B2);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255,105,180,0.4);
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
    
    /* Notification for new message */
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
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .chat-container {
            flex-direction: column;
            height: auto;
        }
        
        .chat-sidebar {
            width: 100%;
            max-height: 300px;
        }
    }
</style> 