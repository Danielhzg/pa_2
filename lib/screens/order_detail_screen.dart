import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../services/order_service.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import 'dart:async';
import '../services/notification_service.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'chat_page.dart'; // Import chat page

class OrderDetailScreen extends StatefulWidget {
  final String orderId;

  const OrderDetailScreen({Key? key, required this.orderId}) : super(key: key);

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  bool _isLoading = true;
  Order? _order;
  String? _error;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    // Start monitoring this order in real-time
    final orderService = Provider.of<OrderService>(context, listen: false);
    orderService.startActiveOrderMonitoring(widget.orderId);

    // Also start notification polling specific to this order
    final notificationService =
        Provider.of<NotificationService>(context, listen: false);
    notificationService.startOrderSpecificPolling(widget.orderId);

    _loadOrderDetails();
  }

  @override
  void dispose() {
    // Stop monitoring when leaving the screen
    final orderService = Provider.of<OrderService>(context, listen: false);
    orderService.stopActiveOrderMonitoring();

    // Also stop notification polling
    final notificationService =
        Provider.of<NotificationService>(context, listen: false);
    notificationService.stopOrderSpecificPolling();

    super.dispose();
  }

  Future<void> _loadOrderDetails({bool showLoading = true}) async {
    if (showLoading) {
      setState(() {
        _isLoading = true;
        _error = null;
      });
    }

    try {
      final orderService = Provider.of<OrderService>(context, listen: false);
      debugPrint('Loading order details for order ID: ${widget.orderId}');

      // First try to get order with customer details
      Order? order;
      try {
        debugPrint('Attempting to fetch order with full customer details');
        final orderData =
            await orderService.fetchOrderWithCustomerDetails(widget.orderId);
        if (orderData != null) {
          debugPrint('Successfully fetched order data with customer details');
          order = Order.fromJson(orderData);
          debugPrint(
              'Order parsed successfully with ${order.items.length} items');
          if (order.customer != null) {
            debugPrint('Customer info available: ${order.customer!.name}');
          } else {
            debugPrint('No customer info available in the order data');
          }
        } else {
          debugPrint(
              'No order data returned from fetchOrderWithCustomerDetails');
        }
      } catch (detailsError) {
        // If customer details fetch fails, try the regular method
        debugPrint('Error fetching order with customer details: $detailsError');
      }

      // If the first method didn't work, try the regular method
      if (order == null) {
        debugPrint('Falling back to regular fetchOrderById method');
        order = await orderService.fetchOrderById(widget.orderId);
        if (order != null) {
          debugPrint('Successfully fetched basic order data');
        } else {
          debugPrint('Failed to fetch order with regular method too');
        }
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
          _order = order;
          if (order == null) {
            _error = 'Tidak dapat menemukan pesanan. Silakan coba lagi nanti.';
          }
        });
      }
    } catch (e) {
      debugPrint('Error in _loadOrderDetails: $e');
      if (mounted) {
        setState(() {
          _isLoading = false;
          _error = 'Terjadi kesalahan: ${e.toString()}';
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    // Format the order ID to show as ORDER-X
    final formattedOrderId = 'ORDER-${widget.orderId}';

    return Scaffold(
      appBar: AppBar(
        title: Text('Detail Pesanan $formattedOrderId'),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        _error!,
                        style: const TextStyle(color: Colors.red),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadOrderDetails,
                        child: const Text('Coba Lagi'),
                      ),
                    ],
                  ),
                )
              : _buildOrderDetails(),
      floatingActionButton: _order != null
          ? FloatingActionButton(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => ChatPage(
                      initialMessage:
                          "Saya ingin bertanya tentang pesanan ${_order!.formattedId}",
                      orderId: widget.orderId,
                    ),
                  ),
                );
              },
              backgroundColor: Theme.of(context).primaryColor,
              tooltip: 'Diskusikan pesanan ini dengan admin',
              child: const Icon(Icons.chat),
            )
          : null,
    );
  }

  Widget _buildOrderDetails() {
    if (_order == null) return const SizedBox.shrink();

    final status = _order!.status;
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    // Use the formattedId getter from the Order class
    final formattedOrderId = _order!.formattedId;

    return RefreshIndicator(
      onRefresh: _loadOrderDetails,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Enhanced order status tracker
            _buildEnhancedOrderTracker(),

            const SizedBox(height: 20),

            // Customer Information Card
            if (_order!.customer != null)
              Card(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 2,
                margin: const EdgeInsets.only(bottom: 16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Informasi Pelanggan',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          // Add chat button
                          ElevatedButton.icon(
                            icon: const Icon(Icons.chat, size: 16),
                            label: const Text('Chat Admin'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFFFF87B2),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(20),
                              ),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 8),
                            ),
                            onPressed: () {
                              // Navigate to chat page with order info
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (context) => ChatPage(
                                    initialMessage:
                                        "Saya ingin bertanya tentang pesanan ${_order!.formattedId}",
                                    showBottomNav: false,
                                    orderId: widget.orderId,
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _buildInfoRow(
                        'Nama',
                        _order!.customer!.name ?? 'Tidak tersedia',
                      ),
                      if (_order!.customer!.email != null &&
                          _order!.customer!.email!.isNotEmpty)
                        _buildInfoRow(
                          'Email',
                          _order!.customer!.email!,
                        ),
                      if (_order!.customer!.phone != null &&
                          _order!.customer!.phone!.isNotEmpty)
                        _buildInfoRow(
                          'Telepon',
                          _order!.customer!.phone!,
                        ),
                    ],
                  ),
                ),
              ),

            // Delivery Address Card
            Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              elevation: 2,
              margin: const EdgeInsets.only(bottom: 16),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Alamat Pengiriman',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Divider(height: 24),
                    _buildInfoRow(
                      'Penerima',
                      _order!.deliveryAddress.name,
                    ),
                    _buildInfoRow(
                      'Telepon',
                      _order!.deliveryAddress.phone,
                    ),
                    _buildInfoRow(
                      'Alamat',
                      _order!.deliveryAddress.address,
                    ),
                    if (_order!.deliveryAddress.city != null &&
                        _order!.deliveryAddress.city!.isNotEmpty)
                      _buildInfoRow(
                        'Kota',
                        _order!.deliveryAddress.city!,
                      ),
                    if (_order!.deliveryAddress.district != null &&
                        _order!.deliveryAddress.district!.isNotEmpty)
                      _buildInfoRow(
                        'Kecamatan',
                        _order!.deliveryAddress.district!,
                      ),
                    if (_order!.deliveryAddress.postalCode != null &&
                        _order!.deliveryAddress.postalCode!.isNotEmpty)
                      _buildInfoRow(
                        'Kode Pos',
                        _order!.deliveryAddress.postalCode!,
                      ),
                  ],
                ),
              ),
            ),

            // Order Info Card
            Card(
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
                          'Informasi Pesanan',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: status.color.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            status.title,
                            style: TextStyle(
                              color: status.color,
                              fontWeight: FontWeight.w500,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _buildInfoRow('ID Pesanan', formattedOrderId),
                    _buildInfoRow('Tanggal Pesanan', _order!.formattedDate),
                    _buildInfoRow('Metode Pembayaran', _order!.paymentMethod),
                    _buildInfoRow('Status Pembayaran',
                        _getPaymentStatusText(_order!.paymentStatus)),
                    if (_order!.courierName != null &&
                        _order!.courierName!.isNotEmpty)
                      _buildInfoRow('Kurir', _order!.courierName!),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Customer Information Card - Improved
            Card(
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
                        Icon(
                          Icons.person,
                          color: Colors.pink,
                          size: 20,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Informasi Pelanggan',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Customer profile section - enhanced
                    if (_order!.customer != null)
                      _buildEnhancedCustomerProfile(_order!.customer!)
                    else
                      _buildBasicCustomerInfo(),

                    const Divider(height: 24),

                    // Delivery address section - enhanced with icon
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(
                              Icons.location_on,
                              color: Colors.pink,
                              size: 20,
                            ),
                            SizedBox(width: 8),
                            Text(
                              'Alamat Pengiriman',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _order!.deliveryAddress.name,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(_order!.deliveryAddress.phone),
                              const SizedBox(height: 4),
                              Text(_order!.deliveryAddress.address),
                              if (_order!.deliveryAddress.district != null)
                                Text('${_order!.deliveryAddress.district}'),
                              if (_order!.deliveryAddress.city != null ||
                                  _order!.deliveryAddress.postalCode != null)
                                Text(
                                  '${_order!.deliveryAddress.city ?? ''} ${_order!.deliveryAddress.postalCode ?? ''}',
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),

                    if (_order!.notes != null && _order!.notes!.isNotEmpty) ...[
                      const Divider(height: 24),
                      _buildNotesSection(_order!.notes!),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Products Card - Enhanced with images and better layout
            Card(
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
                        Icon(
                          Icons.shopping_bag,
                          color: Colors.pink,
                          size: 20,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Produk yang Dipesan',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    // List of products
                    ListView.separated(
                      physics: const NeverScrollableScrollPhysics(),
                      shrinkWrap: true,
                      itemCount: _order!.items.length,
                      separatorBuilder: (context, index) =>
                          const Divider(height: 24),
                      itemBuilder: (context, index) {
                        final item = _order!.items[index];
                        return _buildEnhancedProductItem(item);
                      },
                    ),
                    const Divider(height: 24),
                    // Order summary - enhanced
                    _buildEnhancedOrderSummary(),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildEnhancedOrderTracker() {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Status Pesanan',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 20),
            _buildTrackingTimeline(),
          ],
        ),
      ),
    );
  }

  Widget _buildTrackingTimeline() {
    final currentStatus = _order!.status;
    final bool isWaitingForPayment =
        currentStatus == OrderStatus.waitingForPayment;
    final bool isProcessing = currentStatus == OrderStatus.processing ||
        currentStatus.value.compareTo(OrderStatus.processing.value) > 0;
    final bool isShipping = currentStatus == OrderStatus.shipping ||
        currentStatus.value.compareTo(OrderStatus.shipping.value) > 0;
    final bool isDelivered = currentStatus == OrderStatus.delivered;
    final bool isCancelled = currentStatus == OrderStatus.cancelled;

    // Don't show tracking for cancelled orders
    if (isCancelled) {
      return Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.red.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.cancel,
                  color: Colors.red,
                  size: 24,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Pesanan Dibatalkan',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.red,
                      ),
                    ),
                    Text(
                      'Dibatalkan pada ${DateFormat('dd MMM yyyy, HH:mm').format(_order!.updatedAt)}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      );
    }

    return Column(
      children: [
        // Payment Step
        _buildTrackingStep(
          icon: Icons.payment,
          title: 'Menunggu Pembayaran',
          subtitle: isWaitingForPayment
              ? 'Pesanan Anda sedang menunggu pembayaran'
              : 'Pembayaran berhasil',
          isActive: true,
          isCompleted: !isWaitingForPayment,
          showLine: true,
        ),

        // Processing Step
        _buildTrackingStep(
          icon: Icons.inventory,
          title: 'Pesanan Diproses',
          subtitle: isProcessing && !isShipping && !isDelivered
              ? 'Pesanan Anda sedang diproses'
              : isProcessing
                  ? 'Pesanan telah diproses'
                  : 'Menunggu pembayaran',
          isActive: isProcessing,
          isCompleted: isShipping || isDelivered,
          showLine: true,
        ),

        // Shipping Step
        _buildTrackingStep(
          icon: Icons.local_shipping,
          title: 'Dalam Pengiriman',
          subtitle: isShipping && !isDelivered
              ? 'Pesanan Anda sedang dikirim'
              : isDelivered
                  ? 'Pesanan telah dikirim'
                  : 'Menunggu proses',
          isActive: isShipping,
          isCompleted: isDelivered,
          showLine: true,
        ),

        // Delivery Step
        _buildTrackingStep(
          icon: Icons.check_circle,
          title: 'Pesanan Selesai',
          subtitle: isDelivered
              ? 'Pesanan Anda telah diterima'
              : 'Menunggu pengiriman',
          isActive: isDelivered,
          isCompleted: isDelivered,
          showLine: false,
        ),
      ],
    );
  }

  Widget _buildTrackingStep({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool isActive,
    required bool isCompleted,
    required bool showLine,
  }) {
    final Color activeColor = Theme.of(context).primaryColor;
    const Color completedColor = Colors.green;
    final Color inactiveColor = Colors.grey.shade400;

    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Icon container with animation
            TweenAnimationBuilder<double>(
              tween:
                  Tween<double>(begin: 0, end: isActive || isCompleted ? 1 : 0),
              duration: const Duration(milliseconds: 500),
              builder: (context, value, child) {
                return Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: isCompleted
                        ? Color.lerp(inactiveColor, completedColor, value)
                        : isActive
                            ? Color.lerp(inactiveColor, activeColor, value)
                            : inactiveColor.withOpacity(0.2),
                    shape: BoxShape.circle,
                    boxShadow: isActive || isCompleted
                        ? [
                            BoxShadow(
                              color:
                                  (isCompleted ? completedColor : activeColor)
                                      .withOpacity(0.4 * value),
                              blurRadius: 12 * value,
                              spreadRadius: 2 * value,
                            )
                          ]
                        : null,
                  ),
                  child: Center(
                    child: Icon(
                      isCompleted ? Icons.check : icon,
                      color: Colors.white,
                      size: isCompleted || isActive ? 24 : 20,
                    ),
                  ),
                );
              },
            ),
            const SizedBox(width: 16),
            // Text content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: isActive || isCompleted
                          ? FontWeight.bold
                          : FontWeight.w500,
                      fontSize: 16,
                      color: isActive || isCompleted
                          ? Colors.black87
                          : Colors.grey.shade600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color: isActive
                          ? Colors.black87
                          : isCompleted
                              ? Colors.grey.shade600
                              : Colors.grey.shade500,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        // Connecting line
        if (showLine)
          Container(
            margin: const EdgeInsets.only(left: 25),
            height: 40,
            width: 2,
            color: isCompleted ? completedColor : Colors.grey.shade300,
          ),
      ],
    );
  }

  Widget _buildProductItem(OrderItem item) {
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Product image
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: item.imageUrl != null && item.imageUrl!.isNotEmpty
              ? Image.network(
                  item.imageUrl!,
                  width: 70,
                  height: 70,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) => Container(
                    width: 70,
                    height: 70,
                    color: Colors.grey[300],
                    child: const Icon(Icons.image_not_supported,
                        color: Colors.white),
                  ),
                )
              : Container(
                  width: 70,
                  height: 70,
                  color: Colors.grey[300],
                  child: const Icon(Icons.image, color: Colors.white70),
                ),
        ),
        const SizedBox(width: 12),
        // Product details
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item.name,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 4),
              Text(
                currencyFormatter.format(item.price),
                style: const TextStyle(
                  color: Colors.pink,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Jumlah: ${item.quantity}',
                style: TextStyle(
                  color: Colors.grey[600],
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ),
        // Item subtotal
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              currencyFormatter.format(item.price * item.quantity),
              style: const TextStyle(
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildOrderSummary() {
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Subtotal'),
            Text(currencyFormatter.format(_order!.subtotal)),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Ongkos Kirim'),
            Text(currencyFormatter.format(_order!.shippingCost)),
          ],
        ),
        const SizedBox(height: 8),
        const Divider(),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Total',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            Text(
              currencyFormatter.format(_order!.total),
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: Colors.pink,
              ),
            ),
          ],
        ),
      ],
    );
  }

  // Helper method to build info rows consistently
  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                color: Colors.grey,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  // Format payment status into readable text
  String _getPaymentStatusText(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Menunggu Pembayaran';
      case 'paid':
        return 'Pembayaran Diterima';
      case 'failed':
        return 'Pembayaran Gagal';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status;
    }
  }

  // New method to build customer profile
  Widget _buildCustomerProfile(CustomerInfo customer) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            // Customer avatar
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.grey.shade200,
                image: customer.profileImage != null &&
                        customer.profileImage!.isNotEmpty
                    ? DecorationImage(
                        image: NetworkImage(customer.profileImage!),
                        fit: BoxFit.cover,
                        onError: (exception, stackTrace) => const AssetImage(
                            'assets/images/user_placeholder.png'),
                      )
                    : null,
              ),
              child: customer.profileImage == null ||
                      customer.profileImage!.isEmpty
                  ? const Icon(Icons.person, color: Colors.grey, size: 30)
                  : null,
            ),
            const SizedBox(width: 16),

            // Customer info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    customer.name ?? 'Pelanggan',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  if (customer.email != null && customer.email!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      customer.email!,
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontSize: 14,
                      ),
                    ),
                  ],
                  if (customer.phone != null && customer.phone!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      customer.phone!,
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }

  // Fallback method for basic customer info
  Widget _buildBasicCustomerInfo() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildInfoRow('Nama', _order!.deliveryAddress.name),
        _buildInfoRow('Telepon', _order!.deliveryAddress.phone),
        if (_order!.deliveryAddress.email != null &&
            _order!.deliveryAddress.email!.isNotEmpty)
          _buildInfoRow('Email', _order!.deliveryAddress.email!),
      ],
    );
  }

  // Method to build notes section
  Widget _buildNotesSection(String notes) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Catatan Pelanggan:',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Text(
            notes,
            style: const TextStyle(fontSize: 14),
          ),
        ),
      ],
    );
  }

  // New enhanced method to build customer profile with better styling
  Widget _buildEnhancedCustomerProfile(CustomerInfo customer) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Customer avatar with better styling
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.grey.shade200,
              border: Border.all(color: Colors.grey.shade300, width: 1),
              image: customer.profileImage != null &&
                      customer.profileImage!.isNotEmpty
                  ? DecorationImage(
                      image: NetworkImage(customer.profileImage!),
                      fit: BoxFit.cover,
                    )
                  : null,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child:
                customer.profileImage == null || customer.profileImage!.isEmpty
                    ? const Icon(Icons.person, color: Colors.grey, size: 30)
                    : null,
          ),
          const SizedBox(width: 16),

          // Customer info with better layout
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  customer.name ?? 'Pelanggan',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                if (customer.email != null && customer.email!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.email_outlined,
                          size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          customer.email!,
                          style: TextStyle(
                            color: Colors.grey.shade700,
                            fontSize: 14,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
                if (customer.phone != null && customer.phone!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.phone_outlined,
                          size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(
                        customer.phone!,
                        style: TextStyle(
                          color: Colors.grey.shade700,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ],
                if (customer.id != null) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.badge_outlined,
                          size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(
                        'ID: ${customer.id}',
                        style: TextStyle(
                          color: Colors.grey.shade700,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Enhanced product item with better styling and image handling
  Widget _buildEnhancedProductItem(OrderItem item) {
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Container(
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Product Image with Placeholder
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: item.imageUrl != null && item.imageUrl!.isNotEmpty
                ? CachedNetworkImage(
                    imageUrl: item.imageUrl!,
                    width: 80,
                    height: 80,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(
                      width: 80,
                      height: 80,
                      color: Colors.grey.shade200,
                      child: const Center(
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                        ),
                      ),
                    ),
                    errorWidget: (context, url, error) => Container(
                      width: 80,
                      height: 80,
                      color: Colors.grey.shade200,
                      child: const Icon(
                        Icons.image_not_supported,
                        color: Colors.grey,
                      ),
                    ),
                  )
                : Container(
                    width: 80,
                    height: 80,
                    color: Colors.grey.shade200,
                    child: const Icon(
                      Icons.image,
                      color: Colors.grey,
                    ),
                  ),
          ),
          const SizedBox(width: 12),
          // Product Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.name,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  currencyFormatter.format(item.price),
                  style: TextStyle(
                    color: Theme.of(context).primaryColor,
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Jumlah: ${item.quantity}',
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontSize: 14,
                      ),
                    ),
                    Text(
                      currencyFormatter.format(item.price * item.quantity),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Enhanced order summary with better styling
  Widget _buildEnhancedOrderSummary() {
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        children: [
          _buildSummaryRow(
              'Subtotal', currencyFormatter.format(_order!.subtotal)),
          const SizedBox(height: 8),
          _buildSummaryRow('Biaya Pengiriman',
              currencyFormatter.format(_order!.shippingCost)),
          const Divider(height: 24),
          _buildSummaryRow(
            'Total',
            currencyFormatter.format(_order!.total),
            isTotal: true,
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isTotal ? 16 : 14,
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            color: isTotal ? Colors.black87 : Colors.grey.shade700,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isTotal ? 18 : 15,
            fontWeight: isTotal ? FontWeight.bold : FontWeight.w600,
            color: isTotal ? Theme.of(context).primaryColor : Colors.black87,
          ),
        ),
      ],
    );
  }
}
