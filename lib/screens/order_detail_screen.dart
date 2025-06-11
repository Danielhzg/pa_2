import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'dart:math' as math;
import '../models/order.dart';
import '../models/order_status.dart';
import '../services/order_service.dart';
import 'package:timeline_tile/timeline_tile.dart';

class OrderDetailScreen extends StatefulWidget {
  final String orderId;

  const OrderDetailScreen({Key? key, required this.orderId}) : super(key: key);

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);
  bool _isLoading = true;
  String? _errorMessage;
  Order? _order;

  @override
  void initState() {
    super.initState();
    _loadOrderDetails();
  }

  Future<void> _loadOrderDetails() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final orderService = Provider.of<OrderService>(context, listen: false);
      final order = await orderService.fetchOrderById(widget.orderId);

      setState(() {
        _order = order;
        _isLoading = false;
      });

      if (order == null) {
        setState(() {
          _errorMessage = 'Order not found or couldn\'t be loaded';
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = 'Error loading order details: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Order #${widget.orderId.substring(0, math.min(8, widget.orderId.length))}',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadOrderDetails,
            tooltip: 'Refresh order details',
          ),
        ],
      ),
      body: _buildContent(),
    );
  }

  Widget _buildContent() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 48),
            const SizedBox(height: 16),
            const Text(
              'Error loading order details',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              _errorMessage!,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _loadOrderDetails,
              style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
              child: const Text('Try Again'),
            ),
          ],
        ),
      );
    }

    if (_order == null) {
      return const Center(
        child: Text('Order not found'),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadOrderDetails,
      color: primaryColor,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Order Status Card
            _buildOrderStatusCard(),

            const SizedBox(height: 24),

            // Order Timeline
            _buildOrderTimeline(),

            const SizedBox(height: 24),

            // Order Items Card
            _buildOrderItemsCard(),

            const SizedBox(height: 24),

            // Payment Details Card
            _buildPaymentDetailsCard(),

            const SizedBox(height: 24),

            // Delivery Address Card
            _buildDeliveryAddressCard(),

            const SizedBox(height: 32),

            // Need Help Button
            _buildNeedHelpButton(),

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderStatusCard() {
    final statusColor = _order!.status.color;
    final statusIcon = _order!.status.icon;

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: statusColor.withOpacity(0.3)),
      ),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(statusIcon, color: statusColor, size: 24),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Status Pesanan',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _order!.status.title,
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                        color: statusColor,
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                if (_order!.status == OrderStatus.waitingForPayment)
                  ElevatedButton(
                    onPressed: () {
                      // TODO: Implement pay now functionality
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                            content: Text('Pay Now feature coming soon')),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                    ),
                    child: const Text('Pay Now'),
                  ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Order ID',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _order!.id,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'Order Date',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _order!.formattedDate,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderTimeline() {
    // Determine which steps are completed based on current status
    final isWaitingForPayment = _order!.status == OrderStatus.waitingForPayment;
    final isProcessing = _order!.status == OrderStatus.processing ||
        _order!.status == OrderStatus.shipping ||
        _order!.status == OrderStatus.delivered;
    final isShipping = _order!.status == OrderStatus.shipping ||
        _order!.status == OrderStatus.delivered;
    final isDelivered = _order!.status == OrderStatus.delivered;

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Order Tracking',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 24),

            // Order Placed
            TimelineTile(
              alignment: TimelineAlign.manual,
              lineXY: 0.2,
              isFirst: true,
              endChild: _buildTimelineContent(
                'Order Placed',
                'Your order has been successfully placed',
                true,
                DateFormat('dd MMM yyyy, HH:mm').format(_order!.createdAt),
              ),
              startChild: _buildTimelineIcon(
                Icons.receipt,
                true,
                Colors.green,
              ),
            ),

            // Payment Confirmed
            TimelineTile(
              alignment: TimelineAlign.manual,
              lineXY: 0.2,
              endChild: _buildTimelineContent(
                'Payment Confirmed',
                'We have received your payment',
                !isWaitingForPayment,
                isWaitingForPayment
                    ? null
                    : DateFormat('dd MMM yyyy, HH:mm').format(
                        _order!.createdAt.add(const Duration(minutes: 30))),
              ),
              startChild: _buildTimelineIcon(
                Icons.payments,
                !isWaitingForPayment,
                Colors.blue,
              ),
            ),

            // Processing
            TimelineTile(
              alignment: TimelineAlign.manual,
              lineXY: 0.2,
              endChild: _buildTimelineContent(
                'Processing',
                'Your order is being prepared',
                isProcessing,
                isProcessing
                    ? DateFormat('dd MMM yyyy, HH:mm')
                        .format(_order!.createdAt.add(const Duration(hours: 1)))
                    : null,
              ),
              startChild: _buildTimelineIcon(
                Icons.inventory,
                isProcessing,
                Colors.amber,
              ),
            ),

            // Shipping
            TimelineTile(
              alignment: TimelineAlign.manual,
              lineXY: 0.2,
              endChild: _buildTimelineContent(
                'Shipping',
                'Your order is on the way to you',
                isShipping,
                isShipping
                    ? DateFormat('dd MMM yyyy, HH:mm')
                        .format(_order!.createdAt.add(const Duration(hours: 3)))
                    : null,
              ),
              startChild: _buildTimelineIcon(
                Icons.local_shipping,
                isShipping,
                Colors.purple,
              ),
            ),

            // Delivered
            TimelineTile(
              alignment: TimelineAlign.manual,
              lineXY: 0.2,
              isLast: true,
              endChild: _buildTimelineContent(
                'Delivered',
                'Your order has been delivered',
                isDelivered,
                isDelivered
                    ? DateFormat('dd MMM yyyy, HH:mm')
                        .format(_order!.createdAt.add(const Duration(days: 1)))
                    : null,
              ),
              startChild: _buildTimelineIcon(
                Icons.check_circle,
                isDelivered,
                Colors.green,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTimelineIcon(IconData icon, bool isCompleted, Color color) {
    return Container(
      alignment: Alignment.centerRight,
      padding: const EdgeInsets.only(right: 16),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: isCompleted ? color : Colors.grey[300],
          shape: BoxShape.circle,
        ),
        child: Icon(
          icon,
          color: isCompleted ? Colors.white : Colors.grey[600],
          size: 20,
        ),
      ),
    );
  }

  Widget _buildTimelineContent(
      String title, String description, bool isCompleted, String? date) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: isCompleted ? Colors.black : Colors.grey,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            description,
            style: TextStyle(
              fontSize: 12,
              color: isCompleted ? Colors.grey[700] : Colors.grey[400],
            ),
          ),
          if (date != null) ...[
            const SizedBox(height: 4),
            Text(
              date,
              style: TextStyle(
                fontSize: 11,
                color: isCompleted ? Colors.grey[600] : Colors.grey[400],
                fontStyle: FontStyle.italic,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildOrderItemsCard() {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Order Items',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  '${_order!.items.length} item${_order!.items.length > 1 ? 's' : ''}',
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 12,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(height: 1),

            // List of Items
            ..._order!.items.map((item) => _buildOrderItemRow(item)).toList(),

            const Divider(height: 1),
            const SizedBox(height: 16),

            // Order Summary
            _buildOrderSummaryRow('Subtotal',
                'Rp ${NumberFormat('#,###').format(_order!.subtotal.toInt())}'),
            const SizedBox(height: 8),
            _buildOrderSummaryRow('Shipping',
                'Rp ${NumberFormat('#,###').format(_order!.shippingCost.toInt())}'),
            const SizedBox(height: 8),
            _buildOrderSummaryRow(
              'Total',
              _order!.formattedTotal,
              isTotal: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderItemRow(dynamic item) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Item image
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: item.imageUrl != null
                ? Image.network(
                    item.imageUrl,
                    width: 60,
                    height: 60,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        width: 60,
                        height: 60,
                        color: Colors.grey[300],
                        child: const Icon(Icons.image_not_supported,
                            color: Colors.grey),
                      );
                    },
                  )
                : Container(
                    width: 60,
                    height: 60,
                    color: Colors.grey[300],
                    child: const Icon(Icons.image_not_supported,
                        color: Colors.grey),
                  ),
          ),
          const SizedBox(width: 12),

          // Item details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.name,
                  style: const TextStyle(fontWeight: FontWeight.w500),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  '${item.quantity} x Rp ${NumberFormat('#,###').format(item.price.toInt())}',
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),

          // Item total
          Text(
            'Rp ${NumberFormat('#,###').format((item.price * item.quantity).toInt())}',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOrderSummaryRow(String label, String value,
      {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            fontSize: isTotal ? 15 : 14,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            fontSize: isTotal ? 15 : 14,
            color: isTotal ? primaryColor : null,
          ),
        ),
      ],
    );
  }

  Widget _buildPaymentDetailsCard() {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Payment Details',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),

            // Payment Method
            _buildPaymentDetailRow(
              'Payment Method',
              _getFormattedPaymentMethod(_order!.paymentMethod),
            ),
            const SizedBox(height: 8),

            // Payment Status
            _buildPaymentDetailRow(
              'Payment Status',
              _getFormattedPaymentStatus(_order!.paymentStatus),
              valueColor: _getPaymentStatusColor(_order!.paymentStatus),
            ),

            // QR Code if available
            if (_order!.qrCodeUrl != null &&
                _order!.status == OrderStatus.waitingForPayment) ...[
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 16),
              Center(
                child: Column(
                  children: [
                    const Text(
                      'Scan this QR Code to pay:',
                      style: TextStyle(fontSize: 14),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      width: 200,
                      height: 200,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey[300]!),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.network(
                          _order!.qrCodeUrl!,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.qr_code,
                                      size: 64, color: Colors.grey[400]),
                                  const SizedBox(height: 8),
                                  const Text('QR Code not available',
                                      style: TextStyle(color: Colors.grey)),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentDetailRow(String label, String value,
      {Color? valueColor}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.grey[600],
            fontSize: 14,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: valueColor,
          ),
        ),
      ],
    );
  }

  String _getFormattedPaymentMethod(String method) {
    final formatted = method.replaceAll('_', ' ').toUpperCase();
    switch (method.toLowerCase()) {
      case 'qris':
        return 'QRIS';
      case 'bank_transfer':
        return 'Bank Transfer';
      case 'credit_card':
        return 'Credit Card';
      case 'gopay':
        return 'GoPay';
      case 'shopeepay':
        return 'ShopeePay';
      default:
        return formatted;
    }
  }

  String _getFormattedPaymentStatus(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Pending';
      case 'paid':
        return 'Paid';
      case 'failed':
        return 'Failed';
      case 'expired':
        return 'Expired';
      default:
        return status.toUpperCase();
    }
  }

  Color _getPaymentStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Colors.orange;
      case 'paid':
        return Colors.green;
      case 'failed':
        return Colors.red;
      case 'expired':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Widget _buildDeliveryAddressCard() {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.location_on, color: primaryColor),
                SizedBox(width: 8),
                Text(
                  'Delivery Address',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Recipient Name
            Text(
              _order!.deliveryAddress.name,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 15,
              ),
            ),
            const SizedBox(height: 4),

            // Phone
            Text(
              _order!.deliveryAddress.phone,
              style: TextStyle(
                color: Colors.grey[700],
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 8),

            // Address
            Text(
              _order!.deliveryAddress.address,
              style: TextStyle(
                color: Colors.grey[800],
                fontSize: 14,
              ),
            ),

            // City and Postal Code (if available)
            if (_order!.deliveryAddress.city != null ||
                _order!.deliveryAddress.postalCode != null) ...[
              const SizedBox(height: 4),
              Text(
                [
                  _order!.deliveryAddress.city,
                  _order!.deliveryAddress.postalCode,
                ].where((e) => e != null).join(', '),
                style: TextStyle(
                  color: Colors.grey[800],
                  fontSize: 14,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildNeedHelpButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: () {
          // TODO: Implement chat with admin functionality
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Chat with customer service coming soon')),
          );
        },
        icon: const Icon(Icons.headset_mic),
        label: const Text('Need Help with Your Order?'),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.grey[200],
          foregroundColor: Colors.grey[800],
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: BorderSide(color: Colors.grey[400]!),
          ),
        ),
      ),
    );
  }
}
 