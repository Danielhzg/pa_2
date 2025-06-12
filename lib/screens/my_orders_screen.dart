import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/order_service.dart';
import '../models/order.dart';
import '../models/order_status.dart';
import 'order_detail_screen.dart';

class MyOrdersScreen extends StatefulWidget {
  final String? highlightOrderId;

  const MyOrdersScreen({Key? key, this.highlightOrderId}) : super(key: key);

  @override
  State<MyOrdersScreen> createState() => _MyOrdersScreenState();
}

class _MyOrdersScreenState extends State<MyOrdersScreen>
    with SingleTickerProviderStateMixin {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);
  late TabController _tabController;
  bool _isLoading = true;
  String? _errorMessage;
  final GlobalKey _highlightedOrderKey = GlobalKey();
  String? _highlightedOrderId;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
    _highlightedOrderId = widget.highlightOrderId;
    _loadOrders();

    // Set up periodic refresh for order status updates
    _setupPeriodicRefresh();
  }

  void _setupPeriodicRefresh() {
    // Refresh orders every 30 seconds to check for status updates
    Timer.periodic(const Duration(seconds: 30), (timer) {
      if (mounted) {
        _refreshOrders();
      } else {
        timer.cancel();
      }
    });
  }

  Future<void> _refreshOrders() async {
    final orderService = Provider.of<OrderService>(context, listen: false);
    await orderService.refreshOrders();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final orderService = Provider.of<OrderService>(context, listen: false);
      final success = await orderService.fetchOrders(forceRefresh: true);

      if (!success && orderService.errorMessage != null) {
        setState(() {
          _errorMessage = orderService.errorMessage;
        });
      }

      // If we have a highlighted order ID, navigate to the appropriate tab
      if (_highlightedOrderId != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _navigateToOrderTab();
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Failed to load orders: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _navigateToOrderTab() {
    if (_highlightedOrderId == null) return;

    final orderService = Provider.of<OrderService>(context, listen: false);
    final targetOrder = orderService.orders.firstWhere(
      (order) => order.id == _highlightedOrderId,
      orElse: () => orderService.orders.first,
    );

    // Determine which tab the order belongs to
    int targetTabIndex = 0; // Default to "All Orders"

    switch (targetOrder.status) {
      case OrderStatus.waitingForPayment:
        targetTabIndex = 1; // "To Pay"
        break;
      case OrderStatus.processing:
        targetTabIndex = 2; // "Processing"
        break;
      case OrderStatus.shipping:
        targetTabIndex = 3; // "Shipping"
        break;
      case OrderStatus.delivered:
        targetTabIndex = 4; // "Completed"
        break;
      default:
        targetTabIndex = 0; // "All Orders"
    }

    // Animate to the appropriate tab
    _tabController.animateTo(targetTabIndex);

    // Show a snackbar to indicate the highlighted order
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content:
            Text('Showing order #${_highlightedOrderId!.substring(0, 8)}...'),
        backgroundColor: primaryColor,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        duration: const Duration(seconds: 3),
      ),
    );

    // Clear the highlighted order ID after navigation
    setState(() {
      _highlightedOrderId = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'My Orders',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: primaryColor,
          unselectedLabelColor: Colors.grey,
          indicatorColor: primaryColor,
          indicatorWeight: 3,
          tabs: const [
            Tab(text: 'All Orders'),
            Tab(text: 'To Pay'),
            Tab(text: 'Processing'),
            Tab(text: 'Shipping'),
            Tab(text: 'Completed'),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadOrders,
        color: primaryColor,
        child: _buildOrderListContent(),
      ),
    );
  }

  Widget _buildOrderListContent() {
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
              'Error loading orders',
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
              onPressed: _loadOrders,
              style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
              child: const Text('Try Again'),
            ),
          ],
        ),
      );
    }

    return Consumer<OrderService>(
      builder: (context, orderService, child) {
        if (orderService.orders.isEmpty) {
          return _buildEmptyOrdersView();
        }

        return TabBarView(
          controller: _tabController,
          children: [
            // All Orders
            _buildOrderList(orderService.orders),

            // To Pay (Waiting for Payment)
            _buildOrderList(
              orderService.getOrdersByStatus(OrderStatus.waitingForPayment),
            ),

            // Processing
            _buildOrderList(
              orderService.getOrdersByStatus(OrderStatus.processing),
            ),

            // Shipping
            _buildOrderList(
              orderService.getOrdersByStatus(OrderStatus.shipping),
            ),

            // Completed (Delivered)
            _buildOrderList(
              orderService.getOrdersByStatus(OrderStatus.delivered),
            ),
          ],
        );
      },
    );
  }

  Widget _buildEmptyOrdersView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 120,
            height: 120,
            decoration: const BoxDecoration(
              color: accentColor,
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.receipt_long,
              size: 64,
              color: primaryColor.withOpacity(0.7),
            ),
          ),
          const SizedBox(height: 24),
          const Text(
            'No Orders Yet',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              'You haven\'t placed any orders yet. Start shopping and your orders will appear here.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: () =>
                Navigator.of(context).pushReplacementNamed('/home'),
            style: ElevatedButton.styleFrom(
              backgroundColor: primaryColor,
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(30),
              ),
            ),
            child: const Text('Start Shopping'),
          ),
        ],
      ),
    );
  }

  Widget _buildOrderList(List<Order> orders) {
    if (orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.assignment_outlined,
              size: 64,
              color: Colors.grey[400],
            ),
            const SizedBox(height: 16),
            Text(
              'No orders in this category',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: orders.length,
      itemBuilder: (context, index) {
        final order = orders[index];
        return _buildOrderCard(order);
      },
    );
  }

  Widget _buildOrderCard(Order order) {
    // Format the first item name to show
    final String firstItemName =
        order.items.isNotEmpty ? order.items.first.name : 'Unknown product';

    // Count additional items
    final int additionalItems = order.items.length - 1;

    // Status color and icon
    final Color statusColor = order.status.color;
    final IconData statusIcon = order.status.icon;

    // Check if this order should be highlighted
    final bool isHighlighted = widget.highlightOrderId == order.id;

    return Card(
      key: isHighlighted ? _highlightedOrderKey : null,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(
          color: isHighlighted ? primaryColor : Colors.grey.shade200,
          width: isHighlighted ? 2 : 1,
        ),
      ),
      elevation: isHighlighted ? 8 : 2,
      color: isHighlighted ? primaryColor.withOpacity(0.05) : null,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => OrderDetailScreen(orderId: order.id),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Order ID and Date Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Order #${order.id}',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 15,
                    ),
                  ),
                  Text(
                    order.formattedDate,
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 12,
                    ),
                  ),
                ],
              ),

              const Divider(height: 24),

              // Order Items Preview
              if (order.items.isNotEmpty)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // First item image
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: order.items.first.imageUrl != null
                          ? Image.network(
                              order.items.first.imageUrl!,
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
                            firstItemName,
                            style: const TextStyle(fontWeight: FontWeight.w500),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 4),
                          if (additionalItems > 0)
                            Text(
                              '+ $additionalItems more item${additionalItems > 1 ? 's' : ''}',
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 12,
                              ),
                            ),
                          const SizedBox(height: 8),
                          Text(
                            'Total: ${order.formattedTotal}',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              color: primaryColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

              const SizedBox(height: 16),

              // Status Indicator
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: statusColor.withOpacity(0.5)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          statusIcon,
                          size: 16,
                          color: statusColor,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          order.status.title,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: statusColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  TextButton(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) =>
                              OrderDetailScreen(orderId: order.id),
                        ),
                      );
                    },
                    style: TextButton.styleFrom(
                      foregroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Text('View Details'),
                        SizedBox(width: 4),
                        Icon(Icons.arrow_forward, size: 16),
                      ],
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
}
