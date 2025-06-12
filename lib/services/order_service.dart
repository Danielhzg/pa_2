import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/order.dart';
import '../utils/constants.dart';
import '../models/order_status.dart';
import 'auth_service.dart';
import '../providers/cart_provider.dart';
import '../models/cart_item.dart';
import '../models/product.dart';
import 'notification_service.dart';
import 'dart:async';

class OrderService with ChangeNotifier {
  final AuthService _authService;
  List<Order> _orders = [];
  bool _isLoading = false;
  String? _errorMessage;
  DateTime? _lastRefresh;
  Timer? _expirationCheckTimer;

  // Reference to cart provider for returning items
  CartProvider? _cartProvider;

  OrderService(this._authService);

  List<Order> get orders => _orders;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Set cart provider reference
  void setCartProvider(CartProvider cartProvider) {
    _cartProvider = cartProvider;
  }

  // Initialize expiration checker
  void initExpirationChecker() {
    // Cancel any existing timer
    _expirationCheckTimer?.cancel();

    // Check for expired orders every 5 minutes
    _expirationCheckTimer = Timer.periodic(const Duration(minutes: 5), (_) {
      checkForExpiredOrders();
    });

    // Run an initial check
    checkForExpiredOrders();
  }

  @override
  void dispose() {
    _expirationCheckTimer?.cancel();
    super.dispose();
  }

  // Get all orders for the current user with auto-refresh capability
  Future<bool> fetchOrders({bool forceRefresh = false}) async {
    // Check if we need to refresh (only refresh if it's been more than 1 minute or forced)
    if (!forceRefresh &&
        _lastRefresh != null &&
        DateTime.now().difference(_lastRefresh!).inMinutes < 1 &&
        _orders.isNotEmpty) {
      return true; // Use cached data if it's recent
    }

    if (!_authService.isLoggedIn) {
      _errorMessage = 'Anda belum login';
      notifyListeners();
      return false;
    }

    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final token = _authService.token;
      debugPrint('Fetching orders with token: ${token?.substring(0, 20)}...');

      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.orders}'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 15));

      debugPrint('Orders API response status: ${response.statusCode}');
      debugPrint('Orders API response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          _orders = List<Order>.from(
            data['data'].map((order) {
              final orderData = Order.fromJson(order);
              debugPrint(
                  'Loaded order: ${orderData.id} - Status: ${orderData.status.value} - Payment: ${orderData.paymentStatus}');
              return orderData;
            }),
          );
          _lastRefresh = DateTime.now();
          _isLoading = false;

          debugPrint('Successfully loaded ${_orders.length} orders');

          // Check for expired orders after loading
          checkForExpiredOrders();

          notifyListeners();
          return true;
        } else {
          _errorMessage = data['message'] ?? 'Failed to fetch orders';
          _isLoading = false;
          debugPrint('API returned error: $_errorMessage');
          notifyListeners();
          return false;
        }
      } else if (response.statusCode == 401) {
        _errorMessage = 'Session expired. Please login again.';
        _isLoading = false;
        debugPrint('Authentication error: ${response.statusCode}');
        notifyListeners();
        return false;
      } else {
        _errorMessage = 'Error: ${response.statusCode}';
        _isLoading = false;
        debugPrint('HTTP error: ${response.statusCode} - ${response.body}');
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Error fetching orders: ${e.toString()}';
      _isLoading = false;
      debugPrint('Exception fetching orders: $e');
      notifyListeners();
      return false;
    }
  }

  // Check for orders that have been in "waiting_for_payment" status for more than 24 hours
  Future<void> checkForExpiredOrders() async {
    if (_orders.isEmpty) {
      return;
    }

    final now = DateTime.now();
    final expiredOrders = _orders.where((order) {
      return order.status == OrderStatus.waitingForPayment &&
          now.difference(order.createdAt).inHours >= 24;
    }).toList();

    if (expiredOrders.isNotEmpty) {
      debugPrint('Found ${expiredOrders.length} expired orders');

      for (final order in expiredOrders) {
        await cancelExpiredOrder(order);
      }
    }
  }

  // Cancel an expired order and return items to cart
  Future<void> cancelExpiredOrder(Order order) async {
    try {
      // 1. Call API to cancel the order
      final token = _authService.token;
      final response = await http
          .post(
            Uri.parse(
                '${ApiConstants.baseUrl}${ApiConstants.orders}/${order.id}/cancel'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: json.encode({
              'reason': 'Payment timeout after 24 hours',
            }),
          )
          .timeout(const Duration(seconds: 10));

      // 2. Return items to cart
      if (_cartProvider != null) {
        for (var item in order.items) {
          _cartProvider!.addToCart(
            Product(
              id: int.parse(item.id.toString()),
              name: item.name,
              price: item.price,
              imageUrl: item.imageUrl ?? '',
              description: '',
              categoryName: 'Returned Item',
              categoryId: 0,
              rating: 0.0,
              isFeatured: false,
              isOnSale: false,
              discount: 0,
            ),
            item.quantity,
          );
        }
      }

      // 3. Remove the order from local list
      _orders.removeWhere((o) => o.id == order.id);
      notifyListeners();

      // 4. Add notification about expired order
      final notificationService = NotificationService();
      await notificationService.addNotification(
        title: 'Order Expired',
        message:
            'Your order #${order.id.substring(0, 8)} has been cancelled due to payment timeout. Items have been returned to your cart.',
        orderId: order.id,
        status: OrderStatus.cancelled,
      );
    } catch (e) {
      debugPrint('Error cancelling expired order: $e');
      // Even if API call fails, still return items to cart
      if (_cartProvider != null) {
        for (var item in order.items) {
          _cartProvider!.addToCart(
            Product(
              id: int.parse(item.id.toString()),
              name: item.name,
              price: item.price,
              imageUrl: item.imageUrl ?? '',
              description: '',
              categoryName: 'Returned Item',
              categoryId: 0,
              rating: 0.0,
              isFeatured: false,
              isOnSale: false,
              discount: 0,
            ),
            item.quantity,
          );
        }
      }
    }
  }

  // Track order by order ID (for both authenticated users and guests)
  Future<Order?> trackOrderById(String orderId) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      Map<String, String> headers = {
        'Content-Type': 'application/json',
      };

      // Add auth token if user is logged in
      if (_authService.isLoggedIn) {
        headers['Authorization'] = 'Bearer ${_authService.token}';
      }

      final response = await http
          .get(
            Uri.parse(
                '${ApiConstants.baseUrl}${ApiConstants.ordersTrack}/$orderId'),
            headers: headers,
          )
          .timeout(const Duration(seconds: 10));

      _isLoading = false;

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          final order = Order.fromJson(data['data']);
          notifyListeners();
          return order;
        } else {
          _errorMessage = data['message'] ?? 'Order not found';
          notifyListeners();
          return null;
        }
      } else {
        _errorMessage = 'Error: ${response.statusCode}';
        notifyListeners();
        return null;
      }
    } catch (e) {
      _errorMessage = 'Error tracking order: ${e.toString()}';
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  // Get a single order by ID (authenticated users only)
  Future<Order?> fetchOrderById(String orderId) async {
    if (!_authService.isLoggedIn) {
      _errorMessage = 'Anda belum login';
      notifyListeners();
      return null;
    }

    try {
      final isRefresh = _orders.any((o) => o.id == orderId);

      if (isRefresh) {
        // Don't show loading indicator for refreshes
        _errorMessage = null;
      } else {
        _isLoading = true;
        _errorMessage = null;
        notifyListeners();
      }

      final token = _authService.token;
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.orders}/$orderId'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          final order = Order.fromJson(data['data']);

          // Update local cache if this order exists in it
          final existingOrderIndex = _orders.indexWhere((o) => o.id == orderId);
          if (existingOrderIndex >= 0) {
            final oldOrder = _orders[existingOrderIndex];

            // Check if status changed
            final statusChanged = oldOrder.status != order.status ||
                oldOrder.paymentStatus != order.paymentStatus;

            _orders[existingOrderIndex] = order;

            if (statusChanged) {
              debugPrint(
                  'Order status changed: ${oldOrder.status.value} -> ${order.status.value}');
            }

            notifyListeners();
          }

          _isLoading = false;
          return order;
        } else {
          _errorMessage = data['message'] ?? 'Failed to fetch order';
          _isLoading = false;
          notifyListeners();
          return null;
        }
      } else {
        _errorMessage = 'Error: ${response.statusCode}';
        _isLoading = false;
        notifyListeners();
        return null;
      }
    } catch (e) {
      _errorMessage = 'Error fetching order: ${e.toString()}';
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  // Filter orders by status
  List<Order> getOrdersByStatus(OrderStatus status) {
    return _orders.where((order) => order.status == status).toList();
  }

  // Get count of orders by status
  int getOrderCountByStatus(OrderStatus status) {
    return _orders.where((order) => order.status == status).length;
  }

  // Clear any error messages
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Force a refresh of orders data
  Future<bool> refreshOrders() async {
    debugPrint('Force refreshing orders data from API...');

    // Clear any cached data to ensure we get fresh data
    _lastRefresh = null;

    // Make sure to fetch all orders, including those that might have been created recently
    final result = await fetchOrders(forceRefresh: true);

    if (result) {
      debugPrint(
          'Successfully refreshed orders. Found ${_orders.length} orders.');

      // Emit notification to update UI
      notifyListeners();
    } else {
      debugPrint('Failed to refresh orders: $_errorMessage');
    }

    return result;
  }

  // Refresh a specific order by ID
  Future<bool> refreshOrderById(String orderId) async {
    debugPrint('Refreshing specific order: $orderId');

    try {
      final order = await fetchOrderById(orderId);
      if (order != null) {
        // Update the order in the local list
        final index = _orders.indexWhere((o) => o.id == orderId);
        if (index >= 0) {
          _orders[index] = order;
          notifyListeners();
          debugPrint('Order $orderId refreshed successfully');
          return true;
        }
      }
      return false;
    } catch (e) {
      debugPrint('Error refreshing order $orderId: $e');
      return false;
    }
  }

  // Utility method to check if payment is successful
  bool isPaymentSuccessful(String status) {
    return status.toLowerCase() == 'paid';
  }

  // Show a notification status
  void showOrderStatusNotification(
      BuildContext context, Order oldOrder, Order newOrder) {
    if (oldOrder.status != newOrder.status) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Status pesanan #${newOrder.id} berubah menjadi: ${newOrder.status.title}',
          ),
          backgroundColor: newOrder.status.color,
          behavior: SnackBarBehavior.floating,
          action: SnackBarAction(
            label: 'Lihat',
            textColor: Colors.white,
            onPressed: () {
              // Navigate to order detail
              Navigator.pushNamed(
                context,
                '/order-detail',
                arguments: newOrder.id,
              );
            },
          ),
        ),
      );
    }
  }
}
