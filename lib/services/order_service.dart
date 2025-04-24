import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/order.dart';
import '../models/cart_item.dart';
import '../models/delivery_address.dart';

class OrderService {
  // Use the same base URL as in ApiService
  final String baseUrl = 'http://10.0.2.2:8000/api';

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
      final response = await http.post(
        Uri.parse('$baseUrl/v1/orders'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
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
      } else {
        throw Exception('Failed to create order: ${response.statusCode}');
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
      final response = await http.patch(
        Uri.parse('$baseUrl/v1/orders/$orderId'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'paymentStatus': paymentStatus,
          'orderStatus': orderStatus,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Order.fromJson(data['data']);
        } else {
          throw Exception('Failed to update order: ${data['message']}');
        }
      } else {
        throw Exception('Failed to update order: ${response.statusCode}');
      }
    } catch (e) {
      print('Error updating order: $e');
      throw Exception('Failed to update order: $e');
    }
  }

  // Get order by ID
  Future<Order> getOrderById(String orderId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/v1/orders/$orderId'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Order.fromJson(data['data']);
        } else {
          throw Exception('Failed to get order: ${data['message']}');
        }
      } else {
        throw Exception('Failed to get order: ${response.statusCode}');
      }
    } catch (e) {
      print('Error getting order: $e');
      throw Exception('Failed to get order: $e');
    }
  }
}
