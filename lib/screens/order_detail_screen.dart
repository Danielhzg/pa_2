import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../services/order_service.dart';
import '../models/order.dart';
import '../models/order_status.dart';

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

  @override
  void initState() {
    super.initState();
    _loadOrderDetails();
  }

  Future<void> _loadOrderDetails() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final orderService = Provider.of<OrderService>(context, listen: false);
      final order = await orderService.fetchOrderById(widget.orderId);

      setState(() {
        _isLoading = false;
        _order = order;
        if (order == null) {
          _error = 'Tidak dapat menemukan pesanan';
        }
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Terjadi kesalahan: ${e.toString()}';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Detail Pesanan #${widget.orderId}'),
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

    return RefreshIndicator(
      onRefresh: _loadOrderDetails,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Shopee-style order status tracking
            _buildOrderTracker(),

            const SizedBox(height: 20),

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
                    _buildInfoRow('ID Pesanan', '#${_order!.id}'),
                    _buildInfoRow('Tanggal Pesanan', _order!.formattedDate),
                    _buildInfoRow('Metode Pembayaran', _order!.paymentMethod),
                    _buildInfoRow('Status Pembayaran',
                        _getPaymentStatusText(_order!.paymentStatus)),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Address Card
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
                          Icons.location_on,
                          color: Colors.pink,
                          size: 20,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Alamat Pengiriman',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Text(
                      _order!.deliveryAddress.name,
                      style: const TextStyle(fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(height: 4),
                    Text(_order!.deliveryAddress.phone),
                    const SizedBox(height: 4),
                    Text(_order!.deliveryAddress.address),
                    if (_order!.deliveryAddress.city != null) ...[
                      const SizedBox(height: 4),
                      Text('${_order!.deliveryAddress.city}'),
                    ],
                    if (_order!.deliveryAddress.postalCode != null) ...[
                      const SizedBox(height: 4),
                      Text('${_order!.deliveryAddress.postalCode}'),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Items Card
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
                          'Item Pesanan',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    ...List.generate(
                      _order!.items.length,
                      (index) => _buildOrderItem(_order!.items[index]),
                    ),
                    const Divider(thickness: 1),
                    _buildTotalRow(
                      'Subtotal',
                      currencyFormatter.format(_order!.subtotal),
                      isBold: false,
                    ),
                    _buildTotalRow(
                      'Biaya Pengiriman',
                      currencyFormatter.format(_order!.shippingCost),
                      isBold: false,
                    ),
                    const Divider(thickness: 1),
                    _buildTotalRow(
                      'Total',
                      currencyFormatter.format(_order!.total),
                      isBold: true,
                      textColor: Colors.pink,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Build timeline for order status tracking
  Widget _buildOrderTracker() {
    if (_order == null) return const SizedBox.shrink();

    final status = _order!.status;
    final statusIndex = _getStatusIndex(status);
    final dateFormatter = DateFormat('dd MMM yyyy, HH:mm');

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 4.0, vertical: 8.0),
              child: Text(
                'Lacak Pesanan',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 8),
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              child: Column(
                children: [
                  _buildTimelineTile(
                    title: 'Pesanan dibuat',
                    subtitle: dateFormatter.format(_order!.createdAt),
                    icon: Icons.add_shopping_cart,
                    isActive: true,
                    isFirst: true,
                    isLast: statusIndex < 1,
                  ),
                  _buildTimelineTile(
                    title: 'Pembayaran diterima',
                    subtitle: statusIndex >= 1
                        ? 'Pembayaran berhasil'
                        : 'Menunggu pembayaran',
                    icon: Icons.payment,
                    isActive: statusIndex >= 1,
                    isFirst: false,
                    isLast: statusIndex < 2,
                  ),
                  _buildTimelineTile(
                    title: 'Pesanan diproses',
                    subtitle: statusIndex >= 2
                        ? 'Pesanan sedang disiapkan'
                        : 'Menunggu proses',
                    icon: Icons.inventory,
                    isActive: statusIndex >= 2,
                    isFirst: false,
                    isLast: statusIndex < 3,
                  ),
                  _buildTimelineTile(
                    title: 'Pesanan dikirim',
                    subtitle: statusIndex >= 3
                        ? 'Dalam pengiriman'
                        : 'Menunggu pengiriman',
                    icon: Icons.local_shipping,
                    isActive: statusIndex >= 3,
                    isFirst: false,
                    isLast: statusIndex < 4,
                  ),
                  _buildTimelineTile(
                    title: 'Pesanan selesai',
                    subtitle: statusIndex >= 4
                        ? 'Pesanan telah diterima'
                        : 'Menunggu konfirmasi',
                    icon: Icons.check_circle,
                    isActive: statusIndex >= 4,
                    isFirst: false,
                    isLast: true,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Timeline tile for order tracking
  Widget _buildTimelineTile({
    required String title,
    required String subtitle,
    required IconData icon,
    required bool isActive,
    required bool isFirst,
    required bool isLast,
  }) {
    const activeColor = Colors.pink;
    final inactiveColor = Colors.grey.shade300;

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8.0),
      child: IntrinsicHeight(
        child: Row(
          children: [
            // Timeline elements (dot and line)
            SizedBox(
              width: 30,
              child: Column(
                children: [
                  // Top line
                  if (!isFirst)
                    Expanded(
                      flex: 1,
                      child: Container(
                        width: 2,
                        color: isActive ? activeColor : inactiveColor,
                      ),
                    ),

                  // Dot indicator
                  Container(
                    margin: const EdgeInsets.symmetric(vertical: 8.0),
                    width: 24,
                    height: 24,
                    decoration: BoxDecoration(
                      color: isActive ? activeColor : Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isActive ? activeColor : inactiveColor,
                        width: 2,
                      ),
                    ),
                    child: Icon(
                      icon,
                      color: isActive ? Colors.white : Colors.grey,
                      size: 14,
                    ),
                  ),

                  // Bottom line
                  if (!isLast)
                    Expanded(
                      flex: 1,
                      child: Container(
                        width: 2,
                        color:
                            isLast || !isActive ? inactiveColor : activeColor,
                      ),
                    ),
                ],
              ),
            ),

            const SizedBox(width: 12),

            // Content
            Expanded(
              child: Container(
                margin: const EdgeInsets.only(bottom: 8.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                        color: isActive ? Colors.black87 : Colors.grey,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(
                        fontSize: 12,
                        color: isActive ? Colors.black54 : Colors.grey.shade400,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Get status index for timeline
  int _getStatusIndex(OrderStatus status) {
    switch (status) {
      case OrderStatus.waitingForPayment:
        return 0;
      case OrderStatus.processing:
        return 2;
      case OrderStatus.shipping:
        return 3;
      case OrderStatus.delivered:
        return 4;
      case OrderStatus.cancelled:
        return 0;
      default:
        return 0;
    }
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

  Widget _buildOrderItem(OrderItem item) {
    final formatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Product image
          Container(
            width: 70,
            height: 70,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8),
              image: item.imageUrl != null
                  ? DecorationImage(
                      image: NetworkImage(item.imageUrl!),
                      fit: BoxFit.cover,
                    )
                  : null,
              color: Colors.grey.shade200,
            ),
            child: item.imageUrl == null
                ? const Icon(Icons.image_not_supported, color: Colors.grey)
                : null,
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
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  '${item.quantity} x ${formatter.format(item.price)}',
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
          // Item subtotal
          Text(
            formatter.format(item.price * item.quantity),
            style: const TextStyle(
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  // Helper for showing order info rows
  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
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
            style: const TextStyle(
              fontWeight: FontWeight.w500,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }

  // Helper for building price total rows
  Widget _buildTotalRow(String label, String value,
      {bool isBold = false, Color? textColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              fontSize: isBold ? 16 : 14,
              color: textColor ?? Colors.black,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              fontSize: isBold ? 16 : 14,
              color: textColor ?? Colors.black,
            ),
          ),
        ],
      ),
    );
  }
}
