import 'dart:async';
import 'package:flutter/material.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import '../services/order_status_service.dart';

class OrderStatusTracker extends StatefulWidget {
  final String orderId;
  final Order? initialOrder;
  final VoidCallback? onStatusChanged;
  final bool showSimulateButton;

  const OrderStatusTracker({
    Key? key,
    required this.orderId,
    this.initialOrder,
    this.onStatusChanged,
    this.showSimulateButton = false,
  }) : super(key: key);

  @override
  State<OrderStatusTracker> createState() => _OrderStatusTrackerState();
}

class _OrderStatusTrackerState extends State<OrderStatusTracker> {
  final OrderStatusService _statusService = OrderStatusService();
  StreamSubscription<Order>? _statusSubscription;
  Order? _currentOrder;
  bool _isSimulating = false;

  @override
  void initState() {
    super.initState();
    _currentOrder = widget.initialOrder;
    _startMonitoring();
  }

  @override
  void dispose() {
    _statusSubscription?.cancel();
    _statusService.stopMonitoringOrder(widget.orderId);
    super.dispose();
  }

  void _startMonitoring() {
    // Start monitoring the order
    _statusService.startMonitoringOrder(widget.orderId);

    // Listen to status updates
    _statusSubscription =
        _statusService.getOrderStatusStream(widget.orderId)?.listen(
      (order) {
        if (mounted) {
          setState(() {
            _currentOrder = order;
          });
          widget.onStatusChanged?.call();
        }
      },
      onError: (error) {
        debugPrint('Error in order status stream: $error');
      },
    );
  }

  Future<void> _simulatePayment() async {
    if (_isSimulating) return;

    setState(() {
      _isSimulating = true;
    });

    try {
      final success =
          await _statusService.simulatePaymentSuccess(widget.orderId);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(success
                ? 'Pembayaran berhasil disimulasikan!'
                : 'Gagal mensimulasikan pembayaran'),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSimulating = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_currentOrder == null) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: Center(
            child: CircularProgressIndicator(),
          ),
        ),
      );
    }

    return Card(
      elevation: 4,
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Status Pesanan',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(_currentOrder!.status),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _getStatusText(_currentOrder!.status),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Order ID
            Text(
              'ID Pesanan: ${widget.orderId}',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w500,
                  ),
            ),

            const SizedBox(height: 8),

            // Payment Status
            Row(
              children: [
                Icon(
                  _getPaymentIcon(_currentOrder!.paymentStatus),
                  color: _getPaymentColor(_currentOrder!.paymentStatus),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(
                  'Pembayaran: ${_getPaymentStatusText(_currentOrder!.paymentStatus)}',
                  style: TextStyle(
                    color: _getPaymentColor(_currentOrder!.paymentStatus),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Status Timeline
            _buildStatusTimeline(),

            // Simulate Payment Button (for testing)
            if (widget.showSimulateButton &&
                _currentOrder!.paymentStatus == 'pending') ...[
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _isSimulating ? null : _simulatePayment,
                  icon: _isSimulating
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.payment),
                  label: Text(
                      _isSimulating ? 'Memproses...' : 'Simulasi Pembayaran'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatusTimeline() {
    final statuses = [
      OrderStatus.waitingForPayment,
      OrderStatus.processing,
      OrderStatus.shipping,
      OrderStatus.delivered,
    ];

    return Column(
      children: statuses.map((status) {
        final isActive = _isStatusActive(status);
        final isCurrent = _currentOrder!.status == status;

        return Row(
          children: [
            // Status Icon
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                color: isActive ? _getStatusColor(status) : Colors.grey[300],
                shape: BoxShape.circle,
              ),
              child: Icon(
                _getStatusIcon(status),
                size: 14,
                color: isActive ? Colors.white : Colors.grey[600],
              ),
            ),

            const SizedBox(width: 12),

            // Status Text
            Expanded(
              child: Text(
                _getStatusText(status),
                style: TextStyle(
                  fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                  color: isActive ? Colors.black87 : Colors.grey[600],
                ),
              ),
            ),

            // Current indicator
            if (isCurrent)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: _getStatusColor(status).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  'Saat ini',
                  style: TextStyle(
                    fontSize: 10,
                    color: _getStatusColor(status),
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
          ],
        );
      }).toList(),
    );
  }

  bool _isStatusActive(OrderStatus status) {
    final currentIndex = _getStatusIndex(_currentOrder!.status);
    final statusIndex = _getStatusIndex(status);
    return statusIndex <= currentIndex;
  }

  int _getStatusIndex(OrderStatus status) {
    switch (status) {
      case OrderStatus.waitingForPayment:
        return 0;
      case OrderStatus.processing:
        return 1;
      case OrderStatus.shipping:
        return 2;
      case OrderStatus.delivered:
        return 3;
      case OrderStatus.cancelled:
        return -1; // Special case
    }
  }

  Color _getStatusColor(OrderStatus status) {
    switch (status) {
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
    }
  }

  IconData _getStatusIcon(OrderStatus status) {
    switch (status) {
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
    }
  }

  String _getStatusText(OrderStatus status) {
    switch (status) {
      case OrderStatus.waitingForPayment:
        return 'Menunggu Pembayaran';
      case OrderStatus.processing:
        return 'Sedang Diproses';
      case OrderStatus.shipping:
        return 'Sedang Diantar';
      case OrderStatus.delivered:
        return 'Selesai';
      case OrderStatus.cancelled:
        return 'Dibatalkan';
    }
  }

  Color _getPaymentColor(String paymentStatus) {
    switch (paymentStatus.toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'failed':
      case 'expired':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getPaymentIcon(String paymentStatus) {
    switch (paymentStatus.toLowerCase()) {
      case 'paid':
        return Icons.check_circle;
      case 'pending':
        return Icons.schedule;
      case 'failed':
        return Icons.error;
      case 'expired':
        return Icons.timer_off;
      default:
        return Icons.help;
    }
  }

  String _getPaymentStatusText(String paymentStatus) {
    switch (paymentStatus.toLowerCase()) {
      case 'paid':
        return 'Lunas';
      case 'pending':
        return 'Menunggu';
      case 'failed':
        return 'Gagal';
      case 'expired':
        return 'Kedaluwarsa';
      default:
        return 'Tidak Diketahui';
    }
  }
}
