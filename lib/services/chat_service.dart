import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/chat.dart';
import '../models/user.dart';
import 'api_service.dart';

class ChatService {
  final ApiService _apiService = ApiService();

  // Get the current user's chat history
  Future<Chat?> getUserChat() async {
    try {
      final response = await _apiService.get('chat', withAuth: true);

      if (response.containsKey('data')) {
        return Chat.fromJson(response['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching chat: $e');
      return null;
    }
  }

  // Send a new message
  Future<ChatMessage?> sendMessage(
    String message, {
    String? productImageUrl,
    String? productName,
  }) async {
    try {
      final Map<String, dynamic> data = {
        'message': message,
        if (productImageUrl != null) 'product_image_url': productImageUrl,
        if (productName != null) 'product_name': productName,
      };

      final response =
          await _apiService.post('chat/message', data, withAuth: true);

      if (response.containsKey('data')) {
        return ChatMessage.fromJson(response['data']);
      }
      return null;
    } catch (e) {
      print('Error sending message: $e');
      return null;
    }
  }

  // Get messages after a certain message ID (for polling new messages)
  Future<List<ChatMessage>> getNewMessages(int lastMessageId) async {
    try {
      final response = await _apiService
          .get('chat/messages?last_message_id=$lastMessageId', withAuth: true);

      if (response.containsKey('data') && response['data'] is List) {
        final List<dynamic> messagesList = response['data'];
        return messagesList.map((m) => ChatMessage.fromJson(m)).toList();
      }
      return [];
    } catch (e) {
      print('Error fetching new messages: $e');
      return [];
    }
  }

  // Mark messages as read
  Future<bool> markMessagesAsRead() async {
    try {
      final response =
          await _apiService.post('chat/mark-read', {}, withAuth: true);
      return response.containsKey('success') && response['success'] == true;
    } catch (e) {
      print('Error marking messages as read: $e');
      return false;
    }
  }

  // Update typing status
  Future<bool> updateTypingStatus(bool isTyping) async {
    try {
      final response = await _apiService.post(
        'chat/typing',
        {'is_typing': isTyping},
        withAuth: true,
      );
      return response.containsKey('success') && response['success'] == true;
    } catch (e) {
      print('Error updating typing status: $e');
      return false;
    }
  }

  // Save chat messages locally
  Future<void> saveChatMessagesLocally(List<ChatMessage> messages) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final messagesJson = messages.map((m) => m.toJson()).toList();
      await prefs.setString('chat_messages', json.encode(messagesJson));
    } catch (e) {
      print('Error saving chat messages: $e');
    }
  }

  // Get locally saved chat messages
  Future<List<ChatMessage>> getLocalChatMessages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final messagesString = prefs.getString('chat_messages');

      if (messagesString != null) {
        final List<dynamic> messagesJson = json.decode(messagesString);
        return messagesJson.map((m) => ChatMessage.fromJson(m)).toList();
      }

      return [];
    } catch (e) {
      print('Error loading chat messages: $e');
      return [];
    }
  }
}
