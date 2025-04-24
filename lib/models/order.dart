import 'cart_item.dart';
import 'delivery_address.dart';

class Order {
  final String id;
  final List<CartItem> items;
  final DeliveryAddress deliveryAddress;
  final double subtotal;
  final double shippingCost;
  final double total;
  final String paymentMethod;
  final String paymentStatus;
  final String orderStatus;
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
    required this.orderStatus,
    required this.createdAt,
    this.qrCodeUrl,
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'items': items.map((item) => item.toJson()).toList(),
      'deliveryAddress': deliveryAddress.toJson(),
      'subtotal': subtotal,
      'shippingCost': shippingCost,
      'total': total,
      'paymentMethod': paymentMethod,
      'paymentStatus': paymentStatus,
      'orderStatus': orderStatus,
      'createdAt': createdAt.toIso8601String(),
      'qrCodeUrl': qrCodeUrl,
    };
  }

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'],
      items: (json['items'] as List)
          .map((item) => CartItem.fromJson(item))
          .toList(),
      deliveryAddress: DeliveryAddress.fromJson(json['deliveryAddress']),
      subtotal: json['subtotal'].toDouble(),
      shippingCost: json['shippingCost'].toDouble(),
      total: json['total'].toDouble(),
      paymentMethod: json['paymentMethod'],
      paymentStatus: json['paymentStatus'],
      orderStatus: json['orderStatus'],
      createdAt: DateTime.parse(json['createdAt']),
      qrCodeUrl: json['qrCodeUrl'],
    );
  }
}
