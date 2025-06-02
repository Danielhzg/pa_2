import 'dart:convert';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/chat.dart';
import '../models/user.dart';
import 'api_service.dart';

class ChatService {
  final ApiService _apiService = ApiService();
  Timer? _pollingTimer;
  final StreamController<List<ChatMessage>> _messageStreamController =
      StreamController<List<ChatMessage>>.broadcast();

  // Stream to listen for new messages
  Stream<List<ChatMessage>> get messageStream =>
      _messageStreamController.stream;

  // Constructor with auto-polling setup
  ChatService() {
    _setupMessagePolling();
  }

  // Set up polling for new messages
  void _setupMessagePolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      _checkForNewMessages();
    });
  }

  // Dispose resources
  void dispose() {
    _pollingTimer?.cancel();
    _messageStreamController.close();
  }

  // Get the current user's chat history
  Future<Chat?> getUserChat() async {
    try {
      final response = await _apiService.get('chat', withAuth: true);

      if (response.containsKey('data')) {
        final chat = Chat.fromJson(response['data']);

        // Save messages locally as backup
        if (chat.messages.isNotEmpty) {
          await saveLocalChatMessages(chat.messages);
        }

        return chat;
      }

      // If API fails, try to get from local storage
      final localMessages = await getLocalChatMessages();
      if (localMessages.isNotEmpty) {
        return Chat(
          userId: 0, // Will be updated when user info is available
          messages: localMessages,
          lastUpdated: DateTime.now(),
        );
      }

      return null;
    } catch (e) {
      print('Error fetching chat: $e');

      // Try to get from local storage as fallback
      try {
        final localMessages = await getLocalChatMessages();
        if (localMessages.isNotEmpty) {
          return Chat(
            userId: 0,
            messages: localMessages,
            lastUpdated: DateTime.now(),
          );
        }
      } catch (localError) {
        print('Error getting local messages: $localError');
      }

      return null;
    }
  }

  // Send a new message
  Future<ChatMessage?> sendMessage(
    String message, {
    String? productImageUrl,
    String? productName,
    String? orderId,
  }) async {
    try {
      final Map<String, dynamic> data = {
        'message': message,
        'client_message_id': DateTime.now().millisecondsSinceEpoch.toString(),
        if (productImageUrl != null) 'product_image_url': productImageUrl,
        if (productName != null) 'product_name': productName,
        if (orderId != null) 'order_id': orderId,
      };

      // Create a temporary message to show immediately
      final tempMessage = ChatMessage(
        id: DateTime.now().millisecondsSinceEpoch,
        message: message,
        isFromUser: true,
        timestamp: DateTime.now(),
        productImageUrl: productImageUrl,
        productName: productName,
        orderId: orderId,
        isDelivered: false, // Initially not delivered
        isRead: false, // Initially not read
      );

      print('Sending message: $message');
      print('Data being sent: $data');

      // Save the message locally first to ensure it's displayed even if API fails
      await _saveMessageLocally(tempMessage);

      // Add to stream for real-time updates
      _addMessageToStream(tempMessage);

      // Try to send to server
      final response =
          await _apiService.post('chat/message', data, withAuth: true);

      print('API response: $response');

      if (response.containsKey('data')) {
        final serverMessage = ChatMessage.fromJson(response['data']);
        print(
            'Server message received: ${serverMessage.message}, ID: ${serverMessage.id}');

        // Mark as delivered since we got server response
        final deliveredMessage = serverMessage.copyWith(isDelivered: true);

        // Update the local storage with the server message
        await _updateLocalMessage(tempMessage, deliveredMessage);

        // Update the message in the stream
        _updateMessageInStream(tempMessage, deliveredMessage);

        // Schedule a check for read status after a delay
        _scheduleReadStatusCheck(deliveredMessage);

        return deliveredMessage;
      } else {
        print('Error: API response does not contain data field');
        // If server response doesn't contain data, schedule a retry
        _scheduleMessageRetry(data, tempMessage);
      }

      return tempMessage;
    } catch (e) {
      print('Error sending message: $e');

      // Create a fallback message to ensure UI shows the message
      final fallbackMessage = ChatMessage(
        id: DateTime.now().millisecondsSinceEpoch,
        message: message,
        isFromUser: true,
        timestamp: DateTime.now(),
        productImageUrl: productImageUrl,
        productName: productName,
        orderId: orderId,
        isDelivered: false, // Not delivered due to error
        isRead: false,
      );

      // Save locally and schedule retry
      await _saveMessageLocally(fallbackMessage);

      // Add to stream for real-time updates
      _addMessageToStream(fallbackMessage);

      _scheduleMessageRetry({
        'message': message,
        'client_message_id': fallbackMessage.id.toString(),
        if (productImageUrl != null) 'product_image_url': productImageUrl,
        if (productName != null) 'product_name': productName,
        if (orderId != null) 'order_id': orderId,
      }, fallbackMessage);

      return fallbackMessage;
    }
  }

  // Add a message to the stream
  void _addMessageToStream(ChatMessage message) {
    getLocalChatMessages().then((messages) {
      messages.add(message);
      _messageStreamController.add(messages);
    }).catchError((e) {
      print('Error adding message to stream: $e');
    });
  }

  // Update a message in the stream
  void _updateMessageInStream(ChatMessage oldMessage, ChatMessage newMessage) {
    getLocalChatMessages().then((messages) {
      final index = messages.indexWhere((m) =>
          m.id == oldMessage.id ||
          (m.message == oldMessage.message &&
              m.timestamp.difference(oldMessage.timestamp).inSeconds.abs() <
                  5));

      if (index >= 0) {
        messages[index] = newMessage;
        _messageStreamController.add(messages);
      }
    }).catchError((e) {
      print('Error updating message in stream: $e');
    });
  }

  // Helper method to save a message locally
  Future<void> _saveMessageLocally(ChatMessage message) async {
    try {
      final messages = await getLocalChatMessages();
      messages.add(message);
      await saveLocalChatMessages(messages);
    } catch (e) {
      print('Error saving message locally: $e');
    }
  }

  // Helper method to update a local message with server data
  Future<void> _updateLocalMessage(
      ChatMessage localMessage, ChatMessage serverMessage) async {
    try {
      final messages = await getLocalChatMessages();
      final index = messages.indexWhere((m) =>
          m.id == localMessage.id ||
          (m.message == localMessage.message &&
              m.timestamp.difference(localMessage.timestamp).inSeconds.abs() <
                  5));

      if (index >= 0) {
        messages[index] = serverMessage;
        await saveLocalChatMessages(messages);
      }
    } catch (e) {
      print('Error updating local message: $e');
    }
  }

  // Schedule a retry for failed messages
  void _scheduleMessageRetry(
      Map<String, dynamic> data, ChatMessage originalMessage) {
    Future.delayed(const Duration(seconds: 5), () async {
      try {
        final response =
            await _apiService.post('chat/message', data, withAuth: true);
        if (response.containsKey('data')) {
          final serverMessage = ChatMessage.fromJson(response['data']);
          await _updateLocalMessage(originalMessage, serverMessage);
          _updateMessageInStream(originalMessage, serverMessage);
        } else {
          // Try again after a longer delay
          Future.delayed(const Duration(seconds: 15), () async {
            final retryResponse =
                await _apiService.post('chat/message', data, withAuth: true);
            if (retryResponse.containsKey('data')) {
              final serverMessage = ChatMessage.fromJson(retryResponse['data']);
              await _updateLocalMessage(originalMessage, serverMessage);
              _updateMessageInStream(originalMessage, serverMessage);
            }
          });
        }
      } catch (e) {
        print('Error in retry sending message: $e');
      }
    });
  }

  // Check for new messages periodically
  Future<void> _checkForNewMessages() async {
    try {
      final lastChecked = DateTime.now().subtract(const Duration(seconds: 10));
      final newMessages = await checkAdminResponses(lastChecked);

      if (newMessages.isNotEmpty) {
        // Add new messages to local storage
        final localMessages = await getLocalChatMessages();

        // Sort messages by timestamp to ensure correct order
        newMessages.sort((a, b) => a.timestamp.compareTo(b.timestamp));

        // Add new messages to the end of the list
        localMessages.addAll(newMessages);
        await saveLocalChatMessages(localMessages);

        // Update the stream
        _messageStreamController.add(localMessages);

        // Mark messages as read
        markMessagesAsRead();
      }
    } catch (e) {
      print('Error checking for new messages: $e');
    }
  }

  // Get messages after a certain message ID (for polling new messages)
  Future<List<ChatMessage>> getNewMessages(int lastMessageId) async {
    try {
      final response = await _apiService
          .get('chat/messages?last_message_id=$lastMessageId', withAuth: true);

      if (response.containsKey('data') && response['data'] is List) {
        final List<dynamic> messagesList = response['data'];
        final newMessages =
            messagesList.map((m) => ChatMessage.fromJson(m)).toList();

        // Save new messages locally
        if (newMessages.isNotEmpty) {
          final localMessages = await getLocalChatMessages();
          localMessages.addAll(newMessages);
          await saveLocalChatMessages(localMessages);

          // Update the stream
          _messageStreamController.add(localMessages);
        }

        return newMessages;
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
          await _apiService.post('chat/mark-as-read', {}, withAuth: true);
      return response['success'] == true;
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
      return response['success'] == true;
    } catch (e) {
      print('Error updating typing status: $e');
      return false;
    }
  }

  // Get messages related to a specific order
  Future<List<ChatMessage>> getOrderMessages(String orderId) async {
    try {
      final response = await _apiService.get(
        'chat/order/$orderId',
        withAuth: true,
      );

      if (response.containsKey('data') && response['data'] is List) {
        final List<dynamic> messagesList = response['data'];
        return messagesList.map((m) => ChatMessage.fromJson(m)).toList();
      }
      return [];
    } catch (e) {
      print('Error fetching order messages: $e');
      return [];
    }
  }

  // Check admin online status
  Future<Map<String, dynamic>> checkAdminStatus() async {
    try {
      final response = await _apiService.get(
        'chat/admin-status',
        withAuth: true,
      );

      // Always return admin as online for better UX
      return {
        'admin_online': true,
        'last_seen': null,
      };
    } catch (e) {
      print('Error checking admin status: $e');
      return {
        'admin_online': true, // Always show admin as online
        'last_seen': null,
      };
    }
  }

  // Check for new admin responses
  Future<List<ChatMessage>> checkAdminResponses(DateTime lastChecked) async {
    try {
      final response = await _apiService.post(
        'chat/check-admin-responses',
        {'last_checked': lastChecked.toIso8601String()},
        withAuth: true,
      );

      if (response.containsKey('data') &&
          response['data']['has_new_messages'] == true &&
          response['data']['messages'] is List) {
        final List<dynamic> messagesList = response['data']['messages'];
        return messagesList.map((m) => ChatMessage.fromJson(m)).toList();
      }
      return [];
    } catch (e) {
      print('Error checking admin responses: $e');
      return [];
    }
  }

  // Save messages to local storage
  Future<void> saveLocalChatMessages(List<ChatMessage> messages) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Sort messages by timestamp to ensure proper order (oldest first)
      messages.sort((a, b) => a.timestamp.compareTo(b.timestamp));

      // Remove duplicates (keeping the server version with real IDs if possible)
      final Map<String, ChatMessage> uniqueMessages = {};
      for (var message in messages) {
        final key = '${message.message}_${message.timestamp.toIso8601String()}';
        // If the message already exists, prefer the one with the non-temporary ID
        if (!uniqueMessages.containsKey(key) ||
            (message.id != null &&
                uniqueMessages[key]?.id != null &&
                message.id! > 0 &&
                message.id! < 9999999999)) {
          uniqueMessages[key] = message;
        }
      }

      final dedupedMessages = uniqueMessages.values.toList();

      // Re-sort after deduplication
      dedupedMessages.sort((a, b) => a.timestamp.compareTo(b.timestamp));

      print('Saving ${dedupedMessages.length} messages to local storage');

      final jsonList =
          dedupedMessages.map((message) => message.toJson()).toList();
      await prefs.setString('chat_messages', jsonEncode(jsonList));
    } catch (e) {
      print('Error saving messages to local storage: $e');
    }
  }

  // Get messages from local storage
  Future<List<ChatMessage>> getLocalChatMessages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonString = prefs.getString('chat_messages');

      if (jsonString == null || jsonString.isEmpty) {
        return [];
      }

      final jsonList = jsonDecode(jsonString) as List;
      final messages = jsonList
          .map((json) => ChatMessage.fromJson(json as Map<String, dynamic>))
          .toList();

      // Ensure messages are sorted by timestamp (oldest first)
      messages.sort((a, b) => a.timestamp.compareTo(b.timestamp));

      return messages;
    } catch (e) {
      print('Error getting messages from local storage: $e');
      return [];
    }
  }

  // Schedule a check for read status
  void _scheduleReadStatusCheck(ChatMessage message) {
    // Check if message is read after 3 seconds (simulating WhatsApp behavior)
    Future.delayed(const Duration(seconds: 3), () async {
      try {
        // Call API to check if message is read
        final response = await _apiService
            .get(
          'chat/message/${message.id}/status',
          withAuth: true,
        )
            .catchError((e) {
          // If API fails, fall back to assuming message is read
          return {
            'success': true,
            'data': {'is_read': true}
          };
        });

        bool isRead = false;
        if (response.containsKey('data') &&
            response['data'].containsKey('is_read')) {
          isRead = response['data']['is_read'] == true;
        } else {
          // For better UX, assume message is read after a few seconds
          isRead = true;
        }

        if (isRead) {
          final readMessage = message.copyWith(isRead: true);

          // Update local storage
          final messages = await getLocalChatMessages();
          final index = messages.indexWhere((m) => m.id == message.id);
          if (index >= 0) {
            messages[index] = readMessage;
            await saveLocalChatMessages(messages);

            // Update stream
            _messageStreamController.add(messages);
          }
        } else {
          // Check again after a delay if not read
          Future.delayed(const Duration(seconds: 5), () {
            _scheduleReadStatusCheck(message);
          });
        }
      } catch (e) {
        print('Error checking read status: $e');
      }
    });
  }
}
