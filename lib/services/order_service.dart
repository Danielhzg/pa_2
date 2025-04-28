import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/order.dart';
import '../models/cart_item.dart';
import '../models/delivery_address.dart';
import 'auth_service.dart';

class OrderService {
  final String baseUrl = 'http://10.0.2.2:8000/api';
  final AuthService _authService = AuthService();

  // Get auth headers
  Future<Map<String, String>> _getHeaders() async {
    final token = await _authService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // Create a new order
  Future<Order> createOrder({
    required String orderId,
    required List<CartItem> items,
    required DeliveryAddress deliveryAddress,
    required double subtotal,
    required double shippingCost,
    required double total,
    required String paymentMethod,
    required String paymentStatus,
    String? qrCodeUrl,
  }) async {
    try {
      final headers = await _getHeaders();

      final response = await http.post(
        Uri.parse('$baseUrl/v1/orders'),
        headers: headers,
        body: json.encode({
          'id': orderId,
          'items': items.map((item) => item.toJson()).toList(),
          'deliveryAddress': deliveryAddress.toJson(),
          'subtotal': subtotal,
          'shippingCost': shippingCost,
          'total': total,
          'paymentMethod': paymentMethod,
          'paymentStatus': paymentStatus,
          'orderStatus': 'pending',
          'qrCodeUrl': qrCodeUrl,
        }),
      );

      if (response.statusCode == 201) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Order.fromJson(data['data']);
        } else {
          throw Exception('Failed to create order: ${data['message']}');
        }
      } else if (response.statusCode == 401) {
        throw Exception('Unauthorized: Please login again');
      } else {
        final error = json.decode(response.body);
        throw Exception(
            'Failed to create order: ${error['message'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Error creating order: $e');
      throw Exception('Failed to create order: $e');
    }
  }

  // Update order status after payment
  Future<Order> updateOrderStatus({
    required String orderId,
    required String paymentStatus,
    String? orderStatus,
  }) async {
    try {
      final headers = await _getHeaders();

      final response = await http.put(
        Uri.parse('$baseUrl/v1/orders/$orderId/status'),
        headers: headers,
        body: json.encode({
          'payment_status': paymentStatus,
          'status': orderStatus,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Order.fromJson(data['data']);
        } else {
          throw Exception('Failed to update order: ${data['message']}');
        }
      } else if (response.statusCode == 401) {
        throw Exception('Unauthorized: Please login again');
      } else {
        final error = json.decode(response.body);
        throw Exception(
            'Failed to update order: ${error['message'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Error updating order: $e');
      throw Exception('Failed to update order: $e');
    }
  }

  // Get order by ID
  Future<Order> getOrderById(String orderId) async {
    try {
      final headers = await _getHeaders();

      final response = await http.get(
        Uri.parse('$baseUrl/v1/orders/$orderId'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Order.fromJson(data['data']);
        } else {
          throw Exception('Failed to get order: ${data['message']}');
        }
      } else if (response.statusCode == 401) {
        throw Exception('Unauthorized: Please login again');
      } else {
        final error = json.decode(response.body);
        throw Exception(
            'Failed to get order: ${error['message'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Error getting order: $e');
      throw Exception('Failed to get order: $e');
    }
  }

  // Get user orders
  Future<List<Order>> getUserOrders() async {
    try {
      final headers = await _getHeaders();

      final response = await http.get(
        Uri.parse('$baseUrl/v1/orders'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return (data['data'] as List)
              .map((order) => Order.fromJson(order))
              .toList();
        } else {
          throw Exception('Failed to get orders: ${data['message']}');
        }
      } else if (response.statusCode == 401) {
        throw Exception('Unauthorized: Please login again');
      } else {
        final error = json.decode(response.body);
        throw Exception(
            'Failed to get orders: ${error['message'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Error getting orders: $e');
      throw Exception('Failed to get orders: $e');
    }
  }
}
