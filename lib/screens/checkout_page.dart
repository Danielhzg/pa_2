import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/cart_provider.dart';
import '../providers/delivery_provider.dart';
import '../models/delivery_address.dart';
import '../models/payment.dart';
import '../models/order.dart';
import '../services/payment_service.dart';
import '../services/order_service.dart';
import 'address_selection_screen.dart';
import 'qr_payment_screen.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';

class CheckoutPage extends StatefulWidget {
  const CheckoutPage({super.key});

  @override
  State<CheckoutPage> createState() => _CheckoutPageState();
}

class _CheckoutPageState extends State<CheckoutPage> {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);
  bool _isLoading = true;

  // Only allow QR Code payment method
  final String _paymentMethod = 'QR Code';

  final OrderService _orderService = OrderService();

  @override
  void initState() {
    super.initState();
    _initCheckout();
  }

  Future<void> _initCheckout() async {
    setState(() {
      _isLoading = true;
    });

    // Initialize delivery provider
    await Provider.of<DeliveryProvider>(context, listen: false).initAddresses();

    setState(() {
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat.currency(
      locale: 'id',
      symbol: 'Rp',
      decimalDigits: 0,
    );

    return Scaffold(
      appBar: AppBar(
        title: const Text('Checkout'),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : Consumer2<CartProvider, DeliveryProvider>(
              builder: (context, cartProvider, deliveryProvider, child) {
                if (cartProvider.items.isEmpty) {
                  return const Center(
                    child: Text('No items to checkout'),
                  );
                }

                final selectedItems = cartProvider.items
                    .where((item) => item.isSelected)
                    .toList();

                if (selectedItems.isEmpty) {
                  return const Center(
                    child: Text('No items selected for checkout'),
                  );
                }

                final subtotal = cartProvider.totalAmount;
                final shippingCost = deliveryProvider.shippingCost;
                final total = subtotal + shippingCost;
                final selectedAddress = deliveryProvider.selectedAddress;

                return Column(
                  children: [
                    Expanded(
                      child: ListView(
                        padding: const EdgeInsets.all(16.0),
                        children: [
                          // Delivery Address Section
                          _buildSectionHeader('Delivery Address'),

                          if (selectedAddress != null)
                            _buildAddressCard(
                              context,
                              selectedAddress,
                              deliveryProvider,
                            )
                          else
                            _buildNoAddressCard(context),

                          const SizedBox(height: 16),

                          // Order Summary Section
                          _buildSectionHeader('Order Summary'),

                          Card(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 2,
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // List of selected items
                                  ...selectedItems
                                      .map((item) => Padding(
                                            padding: const EdgeInsets.only(
                                                bottom: 12.0),
                                            child: Row(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                // Product image
                                                ClipRRect(
                                                  borderRadius:
                                                      BorderRadius.circular(8),
                                                  child: SizedBox(
                                                    width: 60,
                                                    height: 60,
                                                    child: Image.network(
                                                      item.imageUrl,
                                                      fit: BoxFit.cover,
                                                      errorBuilder: (context,
                                                          error, stackTrace) {
                                                        return Container(
                                                          color:
                                                              Colors.grey[200],
                                                          child: const Icon(Icons
                                                              .image_not_supported),
                                                        );
                                                      },
                                                    ),
                                                  ),
                                                ),
                                                const SizedBox(width: 12),
                                                // Product details
                                                Expanded(
                                                  child: Column(
                                                    crossAxisAlignment:
                                                        CrossAxisAlignment
                                                            .start,
                                                    children: [
                                                      Text(
                                                        item.name,
                                                        style: const TextStyle(
                                                          fontWeight:
                                                              FontWeight.bold,
                                                        ),
                                                        maxLines: 2,
                                                        overflow: TextOverflow
                                                            .ellipsis,
                                                      ),
                                                      const SizedBox(height: 4),
                                                      Text(
                                                        '${item.quantity} x ${currencyFormatter.format(item.price)}',
                                                        style: TextStyle(
                                                          color:
                                                              Colors.grey[600],
                                                          fontSize: 13,
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                                // Item total
                                                Text(
                                                  currencyFormatter.format(
                                                      item.price *
                                                          item.quantity),
                                                  style: const TextStyle(
                                                    fontWeight: FontWeight.bold,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ))
                                      .toList(),

                                  const Divider(),

                                  // Subtotal
                                  _buildPriceRow(
                                    'Subtotal',
                                    currencyFormatter.format(subtotal),
                                  ),
                                  const SizedBox(height: 8),

                                  // Shipping cost
                                  _buildPriceRow(
                                    'Shipping',
                                    currencyFormatter.format(shippingCost),
                                  ),
                                  const SizedBox(height: 8),

                                  const Divider(),

                                  // Total
                                  _buildPriceRow(
                                    'Total',
                                    currencyFormatter.format(total),
                                    isTotal: true,
                                  ),
                                ],
                              ),
                            ),
                          ),

                          const SizedBox(height: 16),

                          // Payment Method Section
                          _buildSectionHeader('Payment Method'),

                          Card(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 2,
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Row(
                                children: [
                                  Container(
                                    width: 40,
                                    height: 40,
                                    decoration: BoxDecoration(
                                      color: accentColor,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: const Icon(
                                      Icons.qr_code,
                                      color: primaryColor,
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  const Text(
                                    'QR Code Payment',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  const Spacer(),
                                  const Icon(
                                    Icons.check_circle,
                                    color: primaryColor,
                                  ),
                                ],
                              ),
                            ),
                          ),

                          const SizedBox(height: 16),

                          // Payment Instructions
                          Card(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 2,
                            child: const Padding(
                              padding: EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Payment Instructions',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  SizedBox(height: 12),
                                  Text(
                                    '1. Click "Place Order" to proceed to payment',
                                    style: TextStyle(fontSize: 14),
                                  ),
                                  SizedBox(height: 6),
                                  Text(
                                    '2. Scan the QR code with your mobile banking or e-wallet app',
                                    style: TextStyle(fontSize: 14),
                                  ),
                                  SizedBox(height: 6),
                                  Text(
                                    '3. Complete the payment to process your order',
                                    style: TextStyle(fontSize: 14),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Bottom Checkout Bar
                    _buildCheckoutBar(
                      context,
                      cartProvider,
                      deliveryProvider,
                      currencyFormatter.format(total),
                    ),
                  ],
                );
              },
            ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _buildPriceRow(String label, String amount, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            fontSize: isTotal ? 16 : 14,
          ),
        ),
        Text(
          amount,
          style: TextStyle(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            fontSize: isTotal ? 16 : 14,
            color: isTotal ? primaryColor : null,
          ),
        ),
      ],
    );
  }

  Widget _buildAddressCard(
    BuildContext context,
    DeliveryAddress address,
    DeliveryProvider deliveryProvider,
  ) {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: accentColor, width: 1.5),
      ),
      elevation: 2,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () async {
          await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const AddressSelectionScreen(),
            ),
          );
          // Recalculate shipping when returning from address selection
          deliveryProvider.calculateShippingCost();
        },
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(
                Icons.location_on,
                color: primaryColor,
                size: 24,
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          address.name,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const Icon(
                          Icons.chevron_right,
                          color: Colors.grey,
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(address.phone),
                    const SizedBox(height: 4),
                    Text(
                      address.fullAddress,
                      style: const TextStyle(
                        color: Colors.black87,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNoAddressCard(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Colors.red, width: 1.5),
      ),
      elevation: 2,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const AddressSelectionScreen(),
            ),
          );
        },
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Row(
            children: [
              Icon(
                Icons.add_location_alt,
                color: Colors.red,
                size: 24,
              ),
              SizedBox(width: 16),
              Expanded(
                child: Text(
                  'Add a delivery address',
                  style: TextStyle(
                    color: Colors.red,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              Icon(
                Icons.chevron_right,
                color: Colors.grey,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCheckoutBar(
    BuildContext context,
    CartProvider cartProvider,
    DeliveryProvider deliveryProvider,
    String total,
  ) {
    final selectedAddress = deliveryProvider.selectedAddress;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.2),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, -1),
          ),
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'Total Payment',
                    style: TextStyle(
                      color: Colors.grey,
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    total,
                    style: const TextStyle(
                      color: primaryColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(
              width: 150,
              child: ElevatedButton(
                onPressed: selectedAddress == null
                    ? null // Disable button if no address selected
                    : () => _placeOrder(context, cartProvider),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  disabledBackgroundColor: Colors.grey,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Place Order',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _placeOrder(
      BuildContext context, CartProvider cartProvider) async {
    // Show loading indicator
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Dialog(
        backgroundColor: Colors.transparent,
        elevation: 0,
        child: Center(
          child: CircularProgressIndicator(color: primaryColor),
        ),
      ),
    );

    try {
      // Generate a unique order ID
      final orderId = const Uuid().v4();

      // Get delivery address
      final deliveryProvider =
          Provider.of<DeliveryProvider>(context, listen: false);
      final deliveryAddress = deliveryProvider.selectedAddress!;

      // Calculate totals
      final subtotal = cartProvider.totalAmount;
      final shippingCost = deliveryProvider.shippingCost;
      final total = subtotal + shippingCost;

      // Get selected items
      final selectedItems =
          cartProvider.items.where((item) => item.isSelected).toList();

      // Create order with pending payment status
      await _orderService.createOrder(
        orderId: orderId,
        items: selectedItems,
        deliveryAddress: deliveryAddress,
        subtotal: subtotal,
        shippingCost: shippingCost,
        total: total,
        paymentMethod: _paymentMethod,
        paymentStatus: 'pending',
      );

      // Pop loading dialog
      Navigator.pop(context);

      // Navigate to QR Payment screen
      final result = await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => QRPaymentScreen(
            amount: total,
            orderId: orderId,
            onPaymentSuccess: (payment) async {
              try {
                // Update order with completed payment status
                await _orderService.updateOrderStatus(
                  orderId: orderId,
                  paymentStatus: 'completed',
                  orderStatus: 'processing',
                );

                // Handle payment success
                _handlePaymentSuccess(context, cartProvider, payment);
              } catch (e) {
                print('Error updating order status: $e');
              }
            },
          ),
        ),
      );

      // If payment was not successful or user returned without payment
      if (result != true) {
        return;
      }
    } catch (e) {
      // Pop loading dialog
      Navigator.pop(context);

      // Show error message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to place order: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _handlePaymentSuccess(
    BuildContext context,
    CartProvider cartProvider,
    Payment payment,
  ) {
    // Process order
    cartProvider.clear();

    if (!mounted) return;

    // Navigate back to the previous screen
    Navigator.of(context).pop(true); // Pop QR screen with success result
    Navigator.of(context).pop(); // Pop checkout screen

    // Show success message
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Payment successful! Your order has been placed.'),
        backgroundColor: Colors.green,
      ),
    );
  }
}
