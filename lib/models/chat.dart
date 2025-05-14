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
  });

  // Convert ChatMessage to JSON for API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'message': message,
      'is_user': isFromUser,
      'timestamp': timestamp.toIso8601String(),
      'is_read': isRead,
      'is_delivered': isDelivered,
      'attachment_url': attachmentUrl,
      'product_image_url': productImageUrl,
      'product_name': productName,
    };
  }

  // Create ChatMessage from JSON
  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'],
      message: json['message'],
      isFromUser: json['is_user'] ?? true,
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : DateTime.now(),
      isRead: json['is_read'] ?? false,
      isDelivered: json['is_delivered'] ?? false,
      attachmentUrl: json['attachment_url'],
      productImageUrl: json['product_image_url'],
      productName: json['product_name'],
    );
  }
}

class Chat {
  final int? id;
  final int userId;
  final int? adminId;
  final List<ChatMessage> messages;
  final DateTime lastUpdated;

  Chat({
    this.id,
    required this.userId,
    this.adminId,
    required this.messages,
    required this.lastUpdated,
  });

  // Convert Chat to JSON for API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'admin_id': adminId,
      'messages': messages.map((message) => message.toJson()).toList(),
      'last_updated': lastUpdated.toIso8601String(),
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
    );
  }
}
