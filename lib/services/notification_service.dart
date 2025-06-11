import 'dart:async';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class OrderNotification {
  final String id;
  final String title;
  final String message;
  final DateTime timestamp;
  final String orderId;
  final OrderStatus status;
  final bool isRead;

  OrderNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.timestamp,
    required this.orderId,
    required this.status,
    this.isRead = false,
  });

  factory OrderNotification.fromJson(Map<String, dynamic> json) {
    return OrderNotification(
      id: json['id'] ?? '',
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : DateTime.now(),
      orderId: json['order_id'] ?? '',
      status: OrderStatusExtension.fromString(json['status'] ?? 'unknown'),
      isRead: json['is_read'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'message': message,
      'timestamp': timestamp.toIso8601String(),
      'order_id': orderId,
      'status': status.value,
      'is_read': isRead,
    };
  }

  OrderNotification copyWith({
    String? id,
    String? title,
    String? message,
    DateTime? timestamp,
    String? orderId,
    OrderStatus? status,
    bool? isRead,
  }) {
    return OrderNotification(
      id: id ?? this.id,
      title: title ?? this.title,
      message: message ?? this.message,
      timestamp: timestamp ?? this.timestamp,
      orderId: orderId ?? this.orderId,
      status: status ?? this.status,
      isRead: isRead ?? this.isRead,
    );
  }
}

class NotificationService with ChangeNotifier {
  static const String _storageKey = 'order_notifications';

  List<OrderNotification> _notifications = [];
  int _unreadCount = 0;
  bool _isInitialized = false;

  final FlutterLocalNotificationsPlugin _flutterLocalNotificationsPlugin =
      FlutterLocalNotificationsPlugin();

  // Getters
  List<OrderNotification> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get hasUnread => _unreadCount > 0;

  NotificationService() {
    _init();
  }

  Future<void> _init() async {
    if (_isInitialized) return;

    // Initialize Flutter Local Notifications
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@drawable/ic_notification');

    const InitializationSettings initializationSettings =
        InitializationSettings(
      android: initializationSettingsAndroid,
    );

    await _flutterLocalNotificationsPlugin.initialize(
      initializationSettings,
    );

    // Load saved notifications
    await _loadNotifications();

    _isInitialized = true;
  }

  Future<void> _loadNotifications() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final notificationsJson = prefs.getString(_storageKey);

      if (notificationsJson != null) {
        final List<dynamic> decodedList = json.decode(notificationsJson);
        _notifications = decodedList
            .map((item) => OrderNotification.fromJson(item))
            .toList();

        // Sort notifications by timestamp (newest first)
        _notifications.sort((a, b) => b.timestamp.compareTo(a.timestamp));

        // Count unread notifications
        _unreadCount = _notifications.where((n) => !n.isRead).length;

        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error loading notifications: $e');
    }
  }

  Future<void> _saveNotifications() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final notificationsJson = json.encode(
        _notifications.map((notification) => notification.toJson()).toList(),
      );

      await prefs.setString(_storageKey, notificationsJson);
    } catch (e) {
      debugPrint('Error saving notifications: $e');
    }
  }

  // Add a new notification
  Future<void> addNotification({
    required String title,
    required String message,
    required String orderId,
    required OrderStatus status,
    bool showLocalNotification = true,
  }) async {
    final notification = OrderNotification(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      title: title,
      message: message,
      timestamp: DateTime.now(),
      orderId: orderId,
      status: status,
      isRead: false,
    );

    _notifications.insert(0, notification);
    _unreadCount++;

    await _saveNotifications();
    notifyListeners();

    if (showLocalNotification) {
      await _showLocalNotification(notification);
    }
  }

  // Mark notification as read
  Future<void> markAsRead(String notificationId) async {
    final index = _notifications.indexWhere((n) => n.id == notificationId);

    if (index != -1) {
      final notification = _notifications[index];

      if (!notification.isRead) {
        _notifications[index] = notification.copyWith(isRead: true);
        _unreadCount--;

        await _saveNotifications();
        notifyListeners();
      }
    }
  }

  // Mark all notifications as read
  Future<void> markAllAsRead() async {
    if (_unreadCount == 0) return;

    _notifications = _notifications.map((notification) {
      return notification.copyWith(isRead: true);
    }).toList();

    _unreadCount = 0;

    await _saveNotifications();
    notifyListeners();
  }

  // Remove a notification
  Future<void> removeNotification(String notificationId) async {
    final index = _notifications.indexWhere((n) => n.id == notificationId);

    if (index != -1) {
      final notification = _notifications[index];

      if (!notification.isRead) {
        _unreadCount--;
      }

      _notifications.removeAt(index);

      await _saveNotifications();
      notifyListeners();
    }
  }

  // Clear all notifications
  Future<void> clearAllNotifications() async {
    _notifications = [];
    _unreadCount = 0;

    await _saveNotifications();
    notifyListeners();
  }

  // Create a notification for order status change
  Future<void> notifyOrderStatusChange(Order oldOrder, Order newOrder) async {
    if (oldOrder.status != newOrder.status) {
      const String title = 'Order Status Updated';
      final String message =
          'Your order #${newOrder.id.substring(0, 8)} status has changed to: ${newOrder.status.title}';

      await addNotification(
        title: title,
        message: message,
        orderId: newOrder.id,
        status: newOrder.status,
      );
    }
  }

  // Create a notification for payment completion
  Future<void> notifyPaymentComplete(Order order) async {
    const String title = 'Payment Successful';
    final String message =
        'Your payment for order #${order.id.substring(0, 8)} has been received.';

    await addNotification(
      title: title,
      message: message,
      orderId: order.id,
      status: order.status,
    );

    // Also notify that order is now being processed
    await notifyOrderProcessing(order);
  }

  // Create a notification for order processing after payment
  Future<void> notifyOrderProcessing(Order order) async {
    const String title = 'Order Processing Started';
    final String message =
        'Your order #${order.id.substring(0, 8)} is now being processed. We\'ll update you when it ships!';

    await addNotification(
      title: title,
      message: message,
      orderId: order.id,
      status: OrderStatus.processing,
    );
  }

  // Create a notification for order delivery
  Future<void> notifyOrderDelivered(Order order) async {
    const String title = 'Order Delivered';
    final String message =
        'Your order #${order.id.substring(0, 8)} has been delivered. Enjoy!';

    await addNotification(
      title: title,
      message: message,
      orderId: order.id,
      status: OrderStatus.delivered,
    );
  }

  // Show a local notification
  Future<void> _showLocalNotification(OrderNotification notification) async {
    const AndroidNotificationDetails androidDetails =
        AndroidNotificationDetails(
      'order_status_channel',
      'Order Status Updates',
      channelDescription: 'Notifications for order status changes',
      importance: Importance.high,
      priority: Priority.high,
      color: Color(0xFFFF87B2),
    );

    const NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
    );

    await _flutterLocalNotificationsPlugin.show(
      int.parse(notification.id.substring(0, 8), radix: 16),
      notification.title,
      notification.message,
      platformDetails,
      payload: notification.orderId,
    );
  }

  // Check for new notifications from the server
  Future<void> checkServerNotifications(String token) async {
    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8000/api/notifications'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true && data['data'] != null) {
          final List<dynamic> serverNotifications = data['data'];

          for (var notificationData in serverNotifications) {
            // Convert server notification format to local format
            final notification = OrderNotification(
              id: notificationData['id'].toString(),
              title: notificationData['title'] ?? '',
              message: notificationData['message'] ?? '',
              timestamp: DateTime.parse(notificationData['created_at']),
              orderId: notificationData['order_id'] ?? '',
              status: OrderStatusExtension.fromString(
                  notificationData['type'] ?? 'unknown'),
              isRead: notificationData['is_read'] ?? false,
            );

            // Check if notification already exists
            final exists = _notifications.any((n) => n.id == notification.id);

            if (!exists) {
              _notifications.insert(0, notification);

              if (!notification.isRead) {
                _unreadCount++;
              }

              await _showLocalNotification(notification);
            }
          }

          // Sort notifications by timestamp (newest first)
          _notifications.sort((a, b) => b.timestamp.compareTo(a.timestamp));

          await _saveNotifications();
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Error checking server notifications: $e');
    }
  }

  // Mark notification as read on server
  Future<void> markAsReadOnServer(String notificationId, String token) async {
    try {
      final response = await http.post(
        Uri.parse(
            'http://10.0.2.2:8000/api/notifications/$notificationId/read'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        debugPrint('✓ Notification marked as read on server');
      }
    } catch (e) {
      debugPrint('Warning: Could not mark notification as read on server: $e');
    }
  }

  // Create a notification for new order placed
  Future<void> notifyNewOrderCreated(String orderId, double amount) async {
    const String title = 'Order Placed Successfully';
    final String message =
        'Your order #${orderId.substring(0, 8)} has been placed successfully. Please complete the payment to process your order.';

    await addNotification(
      title: title,
      message: message,
      orderId: orderId,
      status: OrderStatus.waitingForPayment,
      showLocalNotification: true,
    );

    // Also try to notify admin through API
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token != null) {
        final response = await http
            .post(
              Uri.parse('http://10.0.2.2:8000/api/admin/notifications'),
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer $token',
              },
              body: jsonEncode({
                'order_id': orderId,
                'title': 'New Order Received',
                'message':
                    'A new order #${orderId.substring(0, 8)} for Rp${amount.toInt()} has been placed.',
                'type': 'new_order',
                'is_read': false,
              }),
            )
            .timeout(const Duration(seconds: 5));

        if (response.statusCode == 200 || response.statusCode == 201) {
          debugPrint('✓ Admin notification sent for new order');
        }
      }
    } catch (e) {
      debugPrint('Warning: Could not notify admin about new order: $e');
      // Continue anyway, this is not critical
    }
  }
}
