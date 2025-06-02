import 'dart:convert';
import 'package:flutter/material.dart';

class ChatMessage {
  final int? id;
  final String message;
  final bool isFromUser;
  final DateTime timestamp;
  final bool isRead;
  final bool isDelivered;
  final String? attachmentUrl;
  final String? productImageUrl;
  final String? productName;
  final String? orderId;
  final bool isTyping;

  ChatMessage({
    this.id,
    required this.message,
    required this.isFromUser,
    required this.timestamp,
    this.isRead = false,
    this.isDelivered = false,
    this.attachmentUrl,
    this.productImageUrl,
    this.productName,
    this.orderId,
    this.isTyping = false,
  });

  // Convert ChatMessage to JSON for API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'message': message,
      'is_from_user': isFromUser,
      'is_user': isFromUser, // For backward compatibility
      'timestamp': timestamp.toIso8601String(),
      'is_read': isRead,
      'is_delivered': isDelivered,
      'attachment_url': attachmentUrl,
      'product_image_url': productImageUrl,
      'product_name': productName,
      'order_id': orderId,
      'is_typing': isTyping,
    };
  }

  // Create ChatMessage from JSON
  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'],
      message: json['message'],
      isFromUser: json['is_from_user'] ?? json['is_user'] ?? true,
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : DateTime.now(),
      isRead: json['is_read'] ?? false,
      isDelivered: json['is_delivered'] ?? false,
      attachmentUrl: json['attachment_url'],
      productImageUrl: json['product_image_url'],
      productName: json['product_name'],
      orderId: json['order_id']?.toString(),
      isTyping: json['is_typing'] ?? false,
    );
  }

  // Create a copy of this message with updated fields
  ChatMessage copyWith({
    int? id,
    String? message,
    bool? isFromUser,
    DateTime? timestamp,
    bool? isRead,
    bool? isDelivered,
    String? attachmentUrl,
    String? productImageUrl,
    String? productName,
    String? orderId,
    bool? isTyping,
  }) {
    return ChatMessage(
      id: id ?? this.id,
      message: message ?? this.message,
      isFromUser: isFromUser ?? this.isFromUser,
      timestamp: timestamp ?? this.timestamp,
      isRead: isRead ?? this.isRead,
      isDelivered: isDelivered ?? this.isDelivered,
      attachmentUrl: attachmentUrl ?? this.attachmentUrl,
      productImageUrl: productImageUrl ?? this.productImageUrl,
      productName: productName ?? this.productName,
      orderId: orderId ?? this.orderId,
      isTyping: isTyping ?? this.isTyping,
    );
  }
}

class Chat {
  final int? id;
  final int userId;
  final int? adminId;
  final List<ChatMessage> messages;
  final DateTime lastUpdated;
  final bool adminOnline;

  Chat({
    this.id,
    required this.userId,
    this.adminId,
    required this.messages,
    required this.lastUpdated,
    this.adminOnline = true, // Admin is always shown as online
  });

  // Convert Chat to JSON for API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'admin_id': adminId,
      'messages': messages.map((message) => message.toJson()).toList(),
      'last_updated': lastUpdated.toIso8601String(),
      'admin_online': adminOnline,
    };
  }

  // Create Chat from JSON
  factory Chat.fromJson(Map<String, dynamic> json) {
    List<ChatMessage> messages = [];
    if (json['messages'] != null) {
      messages = List<ChatMessage>.from(
        json['messages'].map((m) => ChatMessage.fromJson(m)),
      );
    }

    return Chat(
      id: json['id'],
      userId: json['user_id'],
      adminId: json['admin_id'],
      messages: messages,
      lastUpdated: json['last_updated'] != null
          ? DateTime.parse(json['last_updated'])
          : DateTime.now(),
      adminOnline: true, // Always show admin as online
    );
  }

  // Create a copy of this chat with updated fields
  Chat copyWith({
    int? id,
    int? userId,
    int? adminId,
    List<ChatMessage>? messages,
    DateTime? lastUpdated,
    bool? adminOnline,
  }) {
    return Chat(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      adminId: adminId ?? this.adminId,
      messages: messages ?? this.messages,
      lastUpdated: lastUpdated ?? this.lastUpdated,
      adminOnline: adminOnline ?? this.adminOnline,
    );
  }

  // Add a new message to the chat
  Chat addMessage(ChatMessage message) {
    final updatedMessages = [...messages, message];
    return copyWith(
      messages: updatedMessages,
      lastUpdated: DateTime.now(),
    );
  }

  // Mark all messages as read
  Chat markAllAsRead() {
    final updatedMessages = messages
        .map((m) => m.isFromUser ? m : m.copyWith(isRead: true))
        .toList();

    return copyWith(
      messages: updatedMessages,
    );
  }
}
