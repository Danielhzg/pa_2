import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/order.dart';
import '../utils/constants.dart';
import '../models/order_status.dart';
import 'auth_service.dart';

class OrderService with ChangeNotifier {
  final AuthService _authService;
  List<Order> _orders = [];
  bool _isLoading = false;
  String? _errorMessage;
  DateTime? _lastRefresh;

  OrderService(this._authService);

  List<Order> get orders => _orders;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

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
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/api/orders'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          _orders = List<Order>.from(
            data['data'].map((order) => Order.fromJson(order)),
          );
          _lastRefresh = DateTime.now();
          _isLoading = false;
          notifyListeners();
          return true;
        } else {
          _errorMessage = data['message'] ?? 'Failed to fetch orders';
          _isLoading = false;
          notifyListeners();
          return false;
        }
      } else {
        _errorMessage = 'Error: ${response.statusCode}';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Error fetching orders: ${e.toString()}';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Get a single order by ID
  Future<Order?> fetchOrderById(String orderId) async {
    if (!_authService.isLoggedIn) {
      _errorMessage = 'Anda belum login';
      notifyListeners();
      return null;
    }

    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final token = _authService.token;
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/api/orders/$orderId'),
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
            _orders[existingOrderIndex] = order;
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
  Future<bool> refreshOrders() {
    return fetchOrders(forceRefresh: true);
  }
}
