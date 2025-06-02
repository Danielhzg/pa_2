import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/order_service.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import '../services/notification_service.dart';
import '../utils/constants.dart';
import '../utils/image_url_helper.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'order_detail_screen.dart';
import 'dart:async';
import 'package:intl/intl.dart';

class OrderListScreen extends StatefulWidget {
  final int? initialTab;

  const OrderListScreen({Key? key, this.initialTab}) : super(key: key);

  @override
  State<OrderListScreen> createState() => _OrderListScreenState();
}

class _OrderListScreenState extends State<OrderListScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isInit = false;
  bool _isLoading = false;
  Timer? _refreshTimer;
  Timer? _statusCheckTimer;
  bool _manualRefresh = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);

    if (widget.initialTab != null &&
        widget.initialTab! < 5 &&
        widget.initialTab! >= 0) {
      _tabController.animateTo(widget.initialTab!);
    }

    // Set up timer to periodically check for order status updates
    _setupPeriodicRefresh();
  }

  @override
  void didChangeDependencies() {
    if (!_isInit) {
      _loadOrders();
      _isInit = true;
    }
    super.didChangeDependencies();
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _statusCheckTimer?.cancel();
    _tabController.dispose();

    // Cancel any retries when screen is disposed
    final orderService = Provider.of<OrderService>(context, listen: false);
    orderService.cancelRetries();

    super.dispose();
  }

  // Set up periodic refresh of orders
  void _setupPeriodicRefresh() {
    // Cancel any existing timers
    _refreshTimer?.cancel();
    _statusCheckTimer?.cancel();

    // Set up timer to refresh orders every minute (reduced from 2 minutes)
    _refreshTimer = Timer.periodic(const Duration(seconds: 60), (timer) {
      if (mounted && !_manualRefresh) {
        _loadOrders(showLoading: false);
      }
    });

    // Set up timer to check for status changes every 30 seconds
    _statusCheckTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      if (mounted && !_manualRefresh) {
        _checkOrderStatuses();
      }
    });
  }

  // Check for order status changes
  void _checkOrderStatuses() async {
    if (mounted && !_manualRefresh) {
      final orderService = Provider.of<OrderService>(context, listen: false);
      // Use the optimized method that only refreshes orders that need it
      orderService.checkOrderStatuses();
    }
  }

  Future<void> _loadOrders({bool showLoading = true}) async {
    // Set manual refresh flag to prevent timer-based refreshes while this is running
    _manualRefresh = true;

    if (showLoading) {
      setState(() {
        _isLoading = true;
      });
    }

    try {
      final orderService = Provider.of<OrderService>(context, listen: false);

      // Start auto-refresh mode
      orderService.startAutoRefresh();

      // Connect notification service to order service
      final notificationService =
          Provider.of<NotificationService>(context, listen: false);
      notificationService.setOrderService(orderService);

      // Fetch orders
      final result = await orderService.fetchOrders(forceRefresh: true);
      if (!result &&
          orderService.errorMessage != null &&
          !orderService.isRetrying) {
        // Only show error message if not in retry mode
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${orderService.errorMessage}'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
            action: SnackBarAction(
              label: 'RETRY',
              textColor: Colors.white,
              onPressed: () => _loadOrders(),
            ),
          ),
        );
      }
    } catch (e) {
      debugPrint('Error loading orders: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to load orders: $e'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted && showLoading) {
        setState(() {
          _isLoading = false;
        });
      }
      // Reset manual refresh flag
      _manualRefresh = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pesanan Saya'),
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: Theme.of(context).primaryColor,
          unselectedLabelColor: Colors.grey,
          indicatorColor: Theme.of(context).primaryColor,
          tabs: const [
            Tab(text: 'Menunggu Pembayaran'),
            Tab(text: 'Pesanan Diproses'),
            Tab(text: 'Dalam Pengiriman'),
            Tab(text: 'Pesanan Selesai'),
            Tab(text: 'Dibatalkan'),
          ],
        ),
        actions: [
          // Add a refresh button
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _loadOrders(showLoading: true),
            tooltip: 'Refresh pesanan',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await _loadOrders(showLoading: false);
        },
        child: Consumer<OrderService>(
          builder: (ctx, orderService, child) {
            if (_isLoading) {
              return const Center(child: CircularProgressIndicator());
            }

            if (orderService.isLoading) {
              return const Center(child: CircularProgressIndicator());
            }

            if (orderService.isRetrying) {
              // Show retry status with countdown
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const CircularProgressIndicator(),
                    const SizedBox(height: 16),
                    Text(
                      orderService.errorMessage ??
                          'Mencoba menghubungkan kembali...',
                      style: const TextStyle(color: Colors.orange),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () {
                        // Cancel auto-retry and try immediately
                        orderService.cancelRetries();
                        _loadOrders();
                      },
                      child: const Text('Coba Sekarang'),
                    ),
                  ],
                ),
              );
            }

            if (orderService.errorMessage != null) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.error_outline,
                      color: Colors.red,
                      size: 48,
                    ),
                    const SizedBox(height: 16),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 24),
                      child: Text(
                        orderService.errorMessage!,
                        style: const TextStyle(color: Colors.red),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () => _loadOrders(),
                      child: const Text('Coba Lagi'),
                    ),
                  ],
                ),
              );
            }

            // Debug: Log order counts by status
            debugPrint(
                'Waiting for payment: ${orderService.getOrderCountByStatus(OrderStatus.waitingForPayment)}');
            debugPrint(
                'Processing: ${orderService.getOrderCountByStatus(OrderStatus.processing)}');
            debugPrint(
                'Shipping: ${orderService.getOrderCountByStatus(OrderStatus.shipping)}');
            debugPrint(
                'Delivered: ${orderService.getOrderCountByStatus(OrderStatus.delivered)}');
            debugPrint(
                'Cancelled: ${orderService.getOrderCountByStatus(OrderStatus.cancelled)}');

            // If we have no orders at all, show a message
            if (orderService.orders.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.shopping_bag_outlined,
                      size: 64,
                      color: Colors.grey,
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Anda belum memiliki pesanan',
                      style: TextStyle(
                        fontSize: 16,
                        color: Colors.grey,
                      ),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () => _loadOrders(),
                      style: ElevatedButton.styleFrom(
                        foregroundColor: Colors.white,
                        backgroundColor: Theme.of(context).primaryColor,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      child: const Text('Refresh'),
                    ),
                  ],
                ),
              );
            }

            return TabBarView(
              controller: _tabController,
              children: [
                _buildOrderList(OrderStatus.waitingForPayment, orderService),
                _buildOrderList(OrderStatus.processing, orderService),
                _buildOrderList(OrderStatus.shipping, orderService),
                _buildOrderList(OrderStatus.delivered, orderService),
                _buildOrderList(OrderStatus.cancelled, orderService),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildOrderList(OrderStatus status, OrderService orderService) {
    final orders = orderService.getOrdersByStatus(status);

    if (orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              status.icon,
              size: 64,
              color: Colors.grey,
            ),
            const SizedBox(height: 16),
            Text(
              'Tidak ada pesanan ${status.title.toLowerCase()}',
              style: const TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => _loadOrders(),
              style: ElevatedButton.styleFrom(
                foregroundColor: Colors.white,
                backgroundColor: status.color,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: const Text('Refresh'),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: orders.length,
      itemBuilder: (ctx, index) {
        final order = orders[index];
        return _buildEnhancedOrderCard(order, status, orderService);
      },
    );
  }

  Widget _buildEnhancedOrderCard(
      Order order, OrderStatus status, OrderService orderService) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 3,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(
          color: status.color.withOpacity(0.5),
          width: 1,
        ),
      ),
      child: InkWell(
        onTap: () async {
          await Navigator.of(context).push(
            MaterialPageRoute(
              builder: (ctx) => OrderDetailScreen(orderId: order.id),
            ),
          );
          _loadOrders(showLoading: false);
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    order.formattedId,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  Chip(
                    label: Text(
                      status.title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                      ),
                    ),
                    backgroundColor: status.color,
                    padding: EdgeInsets.zero,
                    visualDensity: VisualDensity.compact,
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Order content
              if (order.items.isNotEmpty) ...[
                Row(
                  children: [
                    // Product image if available
                    if (order.items.first.imageUrl != null &&
                        order.items.first.imageUrl!.isNotEmpty)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: SizedBox(
                          width: 60,
                          height: 60,
                          child: CachedNetworkImage(
                            imageUrl: order.items.first.imageUrl!,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => Container(
                              color: Colors.grey.shade200,
                              child: const Center(
                                child: SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                ),
                              ),
                            ),
                            errorWidget: (context, url, error) => Container(
                                    color: Colors.grey.shade200,
                              child: const Icon(Icons.image_not_supported),
                                  ),
                          ),
                        ),
                      ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            order.items.first.name,
                            style: const TextStyle(
                              fontWeight: FontWeight.w500,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            ),
                          const SizedBox(height: 4),
                          Text(
                            order.items.length > 1
                                ? '${order.items.first.quantity}x ${order.formattedTotal} (+ ${order.items.length - 1} item lainnya)'
                                : '${order.items.first.quantity}x ${order.formattedTotal}',
                            style: TextStyle(
                              color: Colors.grey[600],
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],

                const SizedBox(height: 12),
              const Divider(height: 1),
                const SizedBox(height: 12),

              // Customer & Address info
              Row(
                children: [
                  const Icon(Icons.person_outline,
                      size: 16, color: Colors.grey),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      order.deliveryAddress.name,
                      style: const TextStyle(fontSize: 13),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              Row(
                children: [
                  const Icon(Icons.location_on_outlined,
                      size: 16, color: Colors.grey),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _formatAddress(order.deliveryAddress),
                      style: const TextStyle(fontSize: 13),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Helper to format date
  String _formatDate(DateTime date) {
    final formatter = DateFormat('dd MMM yyyy');
    return formatter.format(date);
  }

  // Helper to format date and time
  String _formatDateTime(DateTime date) {
    final formatter = DateFormat('dd MMM yyyy, HH:mm');
    return formatter.format(date);
  }

  // Helper to format currency
  String _formatCurrency(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );
    return formatter.format(amount);
  }

  String _formatAddress(OrderAddress address) {
    List<String> parts = [];

    if (address.address.isNotEmpty) {
      parts.add(address.address);
    }

    if (address.district != null && address.district!.isNotEmpty) {
      parts.add(address.district!);
    }

    if (address.city != null && address.city!.isNotEmpty) {
      parts.add(address.city!);
    }

    if (address.postalCode != null && address.postalCode!.isNotEmpty) {
      parts.add(address.postalCode!);
    }

    return parts.join(', ');
  }
}
