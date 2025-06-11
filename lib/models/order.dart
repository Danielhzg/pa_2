import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'dart:convert';
import 'order_status.dart';

class OrderItem {
  final int id;
  final String name;
  final double price;
  final int quantity;
  final String? imageUrl;

  OrderItem({
    required this.id,
    required this.name,
    required this.price,
    required this.quantity,
    this.imageUrl,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['product_id'] ?? json['id'] ?? 0,
      name: json['name'] ?? 'Product',
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      quantity: json['quantity'] ?? 1,
      imageUrl: json['imageUrl'] ?? json['image_url'] ?? json['product_image'],
    );
  }
}

class OrderAddress {
  final String name;
  final String phone;
  final String address;
  final String? city;
  final String? postalCode;

  OrderAddress({
    required this.name,
    required this.phone,
    required this.address,
    this.city,
    this.postalCode,
  });

  factory OrderAddress.fromJson(Map<String, dynamic> json) {
    return OrderAddress(
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      address: json['address'] ?? '',
      city: json['city'],
      postalCode: json['postal_code'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'phone': phone,
      'address': address,
      if (city != null) 'city': city,
      if (postalCode != null) 'postal_code': postalCode,
    };
  }
}

class Order {
  final String id;
  final List<OrderItem> items;
  final OrderAddress deliveryAddress;
  final double subtotal;
  final double shippingCost;
  final double total;
  final String paymentMethod;
  final String paymentStatus;
  final OrderStatus status;
  final DateTime createdAt;
  final String? qrCodeUrl;

  Order({
    required this.id,
    required this.items,
    required this.deliveryAddress,
    required this.subtotal,
    required this.shippingCost,
    required this.total,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.status,
    required this.createdAt,
    this.qrCodeUrl,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    // Parse items
    List<OrderItem> orderItems = [];
    if (json['items'] != null) {
      if (json['items'] is List) {
        orderItems = List<OrderItem>.from(
          json['items'].map((item) => OrderItem.fromJson(item)),
        );
      } else if (json['items'] is Map) {
        // Handle case where items might be a map instead of a list
        json['items'].forEach((key, value) {
          if (value is Map<String, dynamic>) {
            orderItems.add(OrderItem.fromJson(value));
          }
        });
      }
    }

    // Parse delivery address
    OrderAddress address;
    if (json['deliveryAddress'] is Map<String, dynamic>) {
      address = OrderAddress.fromJson(json['deliveryAddress']);
    } else if (json['shipping_address'] is String) {
      // Try parsing the JSON string
      try {
        final addressMap = jsonDecode(json['shipping_address']);
        address = OrderAddress.fromJson(addressMap);
      } catch (_) {
        // Fallback if parsing fails
        address = OrderAddress(
          name: 'Unknown',
          phone: json['phone_number'] ?? '',
          address: json['shipping_address'] ?? '',
        );
      }
    } else {
      address = OrderAddress(
        name: 'Unknown',
        phone: json['phone_number'] ?? '',
        address: 'Unknown address',
      );
    }

    // Parse order status
    String statusStr =
        json['orderStatus'] ?? json['status'] ?? 'waiting_for_payment';
    OrderStatus orderStatus = OrderStatusExtension.fromString(statusStr);

    // Parse created date
    DateTime createdDate;
    try {
      if (json['createdAt'] != null) {
        createdDate = DateTime.parse(json['createdAt']);
      } else if (json['created_at'] != null) {
        createdDate = DateTime.parse(json['created_at']);
      } else {
        createdDate = DateTime.now();
      }
    } catch (_) {
      createdDate = DateTime.now();
    }

    return Order(
      id: json['order_id'] ?? json['id'] ?? '',
      items: orderItems,
      deliveryAddress: address,
      subtotal: double.tryParse(json['subtotal'].toString()) ?? 0.0,
      shippingCost: double.tryParse(json['shippingCost']?.toString() ??
              json['shipping_cost']?.toString() ??
              '0') ??
          0.0,
      total: double.tryParse(json['total']?.toString() ??
              json['total_amount']?.toString() ??
              '0') ??
          0.0,
      paymentMethod:
          json['paymentMethod'] ?? json['payment_method'] ?? 'Unknown',
      paymentStatus:
          json['paymentStatus'] ?? json['payment_status'] ?? 'pending',
      status: orderStatus,
      createdAt: createdDate,
      qrCodeUrl: json['qrCodeUrl'] ?? json['qr_code_url'],
    );
  }

  String get formattedDate {
    return DateFormat('dd MMM yyyy, HH:mm').format(createdAt);
  }

  String get formattedTotal {
    final formatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );
    return formatter.format(total);
  }
}
