import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import '../utils/constants.dart';

class OrderStatusService extends ChangeNotifier {
  static String get _baseUrl => '${ApiConstants.getBaseUrl()}/api/v1';

  Timer? _statusCheckTimer;
  final Map<String, Order> _orders = {};
  final Map<String, StreamController<Order>> _orderStreams = {};

  // Singleton pattern
  static final OrderStatusService _instance = OrderStatusService._internal();
  factory OrderStatusService() => _instance;
  OrderStatusService._internal();

  /// Start monitoring order status for a specific order
  void startMonitoringOrder(String orderId) {
    debugPrint('Starting to monitor order: $orderId');

    // Create stream controller if not exists
    if (!_orderStreams.containsKey(orderId)) {
      _orderStreams[orderId] = StreamController<Order>.broadcast();
    }

    // Start periodic status check
    _startPeriodicStatusCheck(orderId);
  }

  /// Stop monitoring order status
  void stopMonitoringOrder(String orderId) {
    debugPrint('Stopping monitoring for order: $orderId');

    _orderStreams[orderId]?.close();
    _orderStreams.remove(orderId);
    _orders.remove(orderId);
  }

  /// Get stream for order status updates
  Stream<Order>? getOrderStatusStream(String orderId) {
    return _orderStreams[orderId]?.stream;
  }

  /// Get current order status
  Order? getCurrentOrderStatus(String orderId) {
    return _orders[orderId];
  }

  /// Start periodic status check for an order
  void _startPeriodicStatusCheck(String orderId) {
    // Check status every 10 seconds
    Timer.periodic(const Duration(seconds: 10), (timer) async {
      if (!_orderStreams.containsKey(orderId)) {
        timer.cancel();
        return;
      }

      try {
        final order = await _fetchOrderStatus(orderId);
        if (order != null) {
          final previousOrder = _orders[orderId];
          _orders[orderId] = order;

          // Check if status changed
          if (previousOrder == null ||
              previousOrder.status != order.status ||
              previousOrder.paymentStatus != order.paymentStatus) {
            debugPrint(
                'Order status changed for $orderId: ${order.status}, Payment: ${order.paymentStatus}');

            // Notify listeners
            _orderStreams[orderId]?.add(order);
            notifyListeners();

            // If order is completed or cancelled, stop monitoring
            if (order.status == OrderStatus.delivered ||
                order.status == OrderStatus.cancelled) {
              timer.cancel();
              stopMonitoringOrder(orderId);
            }
          }
        }
      } catch (e) {
        debugPrint('Error checking order status for $orderId: $e');
      }
    });
  }

  /// Fetch order status from API
  Future<Order?> _fetchOrderStatus(String orderId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await http.get(
        Uri.parse('$_baseUrl/payment/status/$orderId'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return _parseOrderFromPaymentStatus(data['data']);
        }
      }

      return null;
    } catch (e) {
      debugPrint('Error fetching order status: $e');
      return null;
    }
  }

  /// Parse order from payment status response
  Order _parseOrderFromPaymentStatus(Map<String, dynamic> data) {
    return Order(
      id: data['order_id'] ?? '',
      items: [], // We don't need items for status updates
      deliveryAddress: OrderAddress(
        name: 'Customer',
        address: 'Address',
        phone: 'Phone',
      ),
      subtotal: (data['total_amount'] ?? 0).toDouble(),
      shippingCost: 0.0,
      total: (data['total_amount'] ?? 0).toDouble(),
      paymentMethod: data['payment_method'] ?? 'unknown',
      paymentStatus: data['payment_status'] ?? 'pending',
      status: _parseOrderStatus(data['order_status'] ?? 'waiting_for_payment'),
      createdAt: DateTime.now(),
    );
  }

  /// Parse order status from string
  OrderStatus _parseOrderStatus(String status) {
    switch (status.toLowerCase()) {
      case 'waiting_for_payment':
        return OrderStatus.waitingForPayment;
      case 'processing':
        return OrderStatus.processing;
      case 'shipping':
        return OrderStatus.shipping;
      case 'delivered':
        return OrderStatus.delivered;
      case 'cancelled':
        return OrderStatus.cancelled;
      default:
        return OrderStatus.waitingForPayment;
    }
  }

  /// Simulate payment success (for testing)
  Future<bool> simulatePaymentSuccess(String orderId) async {
    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/payment/simulate-success'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'order_id': orderId,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          debugPrint('Payment simulation successful for order: $orderId');

          // Immediately check status
          final order = await _fetchOrderStatus(orderId);
          if (order != null) {
            _orders[orderId] = order;
            _orderStreams[orderId]?.add(order);
            notifyListeners();
          }

          return true;
        }
      }

      return false;
    } catch (e) {
      debugPrint('Error simulating payment: $e');
      return false;
    }
  }

  /// Update payment status manually
  Future<bool> updatePaymentStatus(String orderId, String paymentStatus) async {
    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/payment/update-status'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'order_id': orderId,
              'payment_status': paymentStatus,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          debugPrint(
              'Payment status updated for order: $orderId to $paymentStatus');

          // Immediately check status
          final order = await _fetchOrderStatus(orderId);
          if (order != null) {
            _orders[orderId] = order;
            _orderStreams[orderId]?.add(order);
            notifyListeners();
          }

          return true;
        }
      }

      return false;
    } catch (e) {
      debugPrint('Error updating payment status: $e');
      return false;
    }
  }

  /// Get all monitored orders
  Map<String, Order> get monitoredOrders => Map.unmodifiable(_orders);

  /// Check if order is being monitored
  bool isMonitoring(String orderId) {
    return _orderStreams.containsKey(orderId);
  }

  /// Dispose all resources
  @override
  void dispose() {
    _statusCheckTimer?.cancel();
    for (var controller in _orderStreams.values) {
      controller.close();
    }
    _orderStreams.clear();
    _orders.clear();
    super.dispose();
  }
}
