import 'package:flutter/material.dart';

enum OrderStatus {
  waitingForPayment,
  processing,
  shipping,
  delivered,
  cancelled,
}

extension OrderStatusExtension on OrderStatus {
  String get value {
    switch (this) {
      case OrderStatus.waitingForPayment:
        return 'waiting_for_payment';
      case OrderStatus.processing:
        return 'processing';
      case OrderStatus.shipping:
        return 'shipping';
      case OrderStatus.delivered:
        return 'delivered';
      case OrderStatus.cancelled:
        return 'cancelled';
      default:
        return 'unknown';
    }
  }

  String get title {
    switch (this) {
      case OrderStatus.waitingForPayment:
        return 'Menunggu Pembayaran';
      case OrderStatus.processing:
        return 'Pesanan Sedang Diproses';
      case OrderStatus.shipping:
        return 'Pesanan Sedang Diantar';
      case OrderStatus.delivered:
        return 'Pesanan Selesai';
      case OrderStatus.cancelled:
        return 'Pesanan Dibatalkan';
      default:
        return 'Status Tidak Diketahui';
    }
  }

  IconData get icon {
    switch (this) {
      case OrderStatus.waitingForPayment:
        return Icons.payment;
      case OrderStatus.processing:
        return Icons.inventory;
      case OrderStatus.shipping:
        return Icons.local_shipping;
      case OrderStatus.delivered:
        return Icons.check_circle;
      case OrderStatus.cancelled:
        return Icons.cancel;
      default:
        return Icons.error;
    }
  }

  Color get color {
    switch (this) {
      case OrderStatus.waitingForPayment:
        return Colors.orange;
      case OrderStatus.processing:
        return Colors.blue;
      case OrderStatus.shipping:
        return Colors.purple;
      case OrderStatus.delivered:
        return Colors.green;
      case OrderStatus.cancelled:
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  static OrderStatus fromString(String status) {
    switch (status) {
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
}
