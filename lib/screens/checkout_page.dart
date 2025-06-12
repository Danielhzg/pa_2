import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/cart_provider.dart';
import '../providers/delivery_provider.dart';
import '../models/delivery_address.dart';
import '../models/payment.dart';
import '../models/order.dart';
import '../models/cart_item.dart';
import '../services/payment_service.dart';
import '../services/order_service.dart';
import '../utils/constants.dart';
import 'address_selection_screen.dart';
import 'qr_payment_screen.dart';
import 'payment_webview_screen.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'chat_page.dart';
import '../services/notification_service.dart';
import '../services/order_status_service.dart';
import '../widgets/order_status_tracker.dart';

class CheckoutPage extends StatefulWidget {
  const CheckoutPage({super.key});

  @override
  State<CheckoutPage> createState() => _CheckoutPageState();
}

class _CheckoutPageState extends State<CheckoutPage> {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);
  bool _isLoading = true;
  bool _isLoadingPaymentMethods = true;

  final _addressFormKey = GlobalKey<FormState>();
  final _orderFormKey = GlobalKey<FormState>();

  List<dynamic> insufficientItems = [];

  // Payment method - now variable
  String _paymentMethod = 'qris';
  String _paymentMethodName = 'QR Code Payment (QRIS)';
  List<dynamic> _paymentMethods = [];

  final PaymentService _paymentService = PaymentService();
  late OrderService _orderService;
  static bool _orderCreationInProgress = false;

  // Custom currency formatter
  final formatCurrency = (double amount) {
    return 'Rp${amount.toInt().toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (match) => '${match[1]}.')}';
  };

  @override
  void initState() {
    super.initState();
    _orderService = Provider.of<OrderService>(context, listen: false);
    _initCheckout();
  }

  Future<void> _initCheckout() async {
    setState(() {
      _isLoading = true;
      _isLoadingPaymentMethods = true;
    });

    // Initialize delivery provider
    await Provider.of<DeliveryProvider>(context, listen: false).initAddresses();

    // Load payment methods
    await _loadPaymentMethods();

    setState(() {
      _isLoading = false;
    });
  }

  Future<void> _loadPaymentMethods() async {
    try {
      // Get payment methods from Midtrans
      final methods = await _paymentService.getMidtransPaymentMethods();
      setState(() {
        _paymentMethods = methods;
        _isLoadingPaymentMethods = false;
      });
    } catch (e) {
      setState(() {
        _isLoadingPaymentMethods = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to load payment methods: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  // Method to handle payment method selection
  void _selectPaymentMethod(String method) {
    setState(() {
      _paymentMethod = method;
      _paymentMethodName = _getPaymentMethodName(method);
    });
  }

  String _getPaymentMethodName(String methodCode) {
    // First check if it's one of the bank VA options
    for (var method in _paymentMethods) {
      if (method['code'] == methodCode) {
        return method['name'];
      }
    }

    // Otherwise check standard options
    switch (methodCode) {
      case 'qris':
        return 'QR Code Payment (QRIS)';
      case 'bank_transfer':
        return 'Transfer Bank Manual';
      default:
        return 'Online Payment';
    }
  }

  IconData _getPaymentMethodIcon(String methodCode) {
    switch (methodCode) {
      case 'qris':
        return Icons.qr_code;
      case 'bank_transfer':
        return Icons.account_balance;
      default:
        return Icons.payment;
    }
  }

  @override
  Widget build(BuildContext context) {
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
                                                        '${item.quantity} x ${formatCurrency(item.price)}',
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
                                                  formatCurrency(item.price *
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
                                    formatCurrency(subtotal),
                                  ),
                                  const SizedBox(height: 8),

                                  // Shipping cost
                                  _buildPriceRow(
                                    'Shipping',
                                    formatCurrency(shippingCost),
                                  ),
                                  const SizedBox(height: 8),

                                  const Divider(),

                                  // Total
                                  _buildPriceRow(
                                    'Total',
                                    formatCurrency(total),
                                    isTotal: true,
                                  ),
                                ],
                              ),
                            ),
                          ),

                          const SizedBox(height: 16),

                          // Payment Method Section
                          _buildSectionHeader('Payment Method'),

                          // Loading indicator for payment methods
                          if (_isLoadingPaymentMethods)
                            const Center(
                              child: Padding(
                                padding: EdgeInsets.symmetric(vertical: 20.0),
                                child: CircularProgressIndicator(
                                    color: primaryColor),
                              ),
                            )
                          else
                            _buildPaymentMethods(),

                          const SizedBox(height: 16),

                          // Payment Instructions
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
                                  const Text(
                                    'Payment Instructions',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  if (_paymentMethod == 'qris') ...[
                                    _buildInstructionStep(1,
                                        'Click "Place Order" to proceed to payment'),
                                    _buildInstructionStep(2,
                                        'Scan the QR code with your mobile banking or e-wallet app'),
                                    _buildInstructionStep(3,
                                        'Complete the payment to process your order'),
                                  ] else if (_paymentMethod ==
                                      'bank_transfer') ...[
                                    _buildInstructionStep(1,
                                        'Click "Place Order" to receive bank transfer details'),
                                    _buildInstructionStep(2,
                                        'Transfer the exact amount to the provided account number'),
                                    _buildInstructionStep(3,
                                        'Your order will be processed after payment confirmation'),
                                  ],
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
                      formatCurrency(total),
                    ),
                  ],
                );
              },
            ),
    );
  }

  Widget _buildPaymentMethods() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // QR Code Payment
        _buildPaymentMethodItem(
          code: 'qris',
          name: 'QR Code Payment (QRIS)',
          icon: Icons.qr_code,
          description: 'Bayar dengan aplikasi e-wallet dan mobile banking',
        ),

        // Bank Transfer (Generic)
        _buildPaymentMethodItem(
          code: 'bank_transfer',
          name: 'Transfer Bank Manual',
          icon: Icons.account_balance,
          description: 'Transfer manual via bank Anda',
        ),

        // Dynamic methods dari API (jika ada)
        ..._paymentMethods.map((method) {
          if ([
            'qris',
            'bank_transfer',
            // List semua kode metode yang difilter
            'bca',
            'bni',
            'bri',
            'mandiri',
            'permata',
            'credit_card',
            'cod'
          ].contains(method['code'])) {
            return const SizedBox
                .shrink(); // Skip jika sudah ditampilkan di atas atau diblokir
          }

          return const SizedBox.shrink(); // Tidak menampilkan metode lain
        }).toList(),
      ],
    );
  }

  Widget _buildPaymentMethodItem({
    required String code,
    required String name,
    required dynamic icon,
    required String description,
  }) {
    final isSelected = _paymentMethod == code;

    // Convert string icon name to IconData
    IconData iconData = Icons.payment;
    if (icon is String) {
      switch (icon) {
        case 'qr_code':
          iconData = Icons.qr_code;
          break;
        case 'account_balance':
          iconData = Icons.account_balance;
          break;
        case 'credit_card':
          iconData = Icons.credit_card;
          break;
        case 'account_balance_wallet':
          iconData = Icons.account_balance_wallet;
          break;
        case 'payments_outlined':
          iconData = Icons.payments_outlined;
          break;
        default:
          iconData = Icons.payment;
      }
    } else if (icon is IconData) {
      iconData = icon;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: isSelected ? 3 : 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: isSelected
            ? const BorderSide(color: primaryColor, width: 2)
            : BorderSide.none,
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          _selectPaymentMethod(code);
        },
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              // Payment Method Icon
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: isSelected ? primaryColor : accentColor,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  iconData,
                  color: isSelected ? Colors.white : primaryColor,
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),

              // Payment Method Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      description,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[700],
                      ),
                    ),
                  ],
                ),
              ),

              // Selection Indicator
              Icon(
                isSelected ? Icons.check_circle : Icons.circle_outlined,
                color: isSelected ? primaryColor : Colors.grey,
                size: 24,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInstructionStep(int number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '$number. ',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
          ),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 14),
            ),
          ),
        ],
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
    // Calculate the total (we already have the formatted string, but for consistency we'll reformat it)
    final subtotal = cartProvider.totalAmount;
    final shippingCost = deliveryProvider.shippingCost;
    final totalAmount = subtotal + shippingCost;

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
                    formatCurrency(totalAmount),
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
    // Check stock availability first
    final stockValidationResult = await _validateStock(context, cartProvider);
    if (!stockValidationResult) {
      // Stock validation failed, exit the method
      return;
    }

    // Check internet connection first
    try {
      bool hasConnection = await _checkInternetConnection();
      if (!hasConnection) {
        _showNetworkErrorDialog(context, 'No Internet Connection',
            'Please check your internet connection and try again.');
        return;
      }
    } catch (e) {
      debugPrint('Error checking internet connection: $e');
    }

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
      debugPrint('======== MEMULAI PROSES CHECKOUT ========');

      // Get delivery address
      final deliveryProvider =
          Provider.of<DeliveryProvider>(context, listen: false);
      final deliveryAddress = deliveryProvider.selectedAddress!;

      // Calculate totals
      final subtotal = cartProvider.totalAmount;
      final shippingCost = deliveryProvider.shippingCost;
      final total = subtotal + shippingCost;

      debugPrint('Delivery address: ${deliveryAddress.fullAddress}');
      debugPrint('Phone: ${deliveryAddress.phone}');
      debugPrint(
          'Total amount: $total (subtotal: $subtotal, shipping: $shippingCost)');

      // Get selected items
      final selectedItems =
          cartProvider.items.where((item) => item.isSelected).toList();

      debugPrint('Selected items count: ${selectedItems.length}');

      // Convert items to the format expected by the payment service
      final itemsForPayment = selectedItems
          .map((item) => {
                'id': item.productId,
                'name': item.name,
                'price': item.price,
                'quantity': item.quantity,
              })
          .toList();

      // Generate a unique customer ID
      final customerId = 'user_${DateTime.now().millisecondsSinceEpoch}';

      // Get user data from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      final authToken = prefs.getString('auth_token');

      String userEmail = 'guest@bloombouquet.com';
      String userName = 'Guest Customer';
      int? userId;

      if (userData != null && authToken != null) {
        final user = jsonDecode(userData);
        userEmail = user['email'] ?? 'guest@bloombouquet.com';
        userName = user['name'] ??
            user['full_name'] ??
            user['username'] ??
            'Guest Customer';
        userId = user['id'];
        debugPrint(
            'Authenticated user found: $userName ($userEmail) - ID: $userId');
      } else {
        debugPrint('No authenticated user, creating guest order');
        // For guest orders, use delivery address name and a guest email
        userName = deliveryAddress.name;
        userEmail =
            'guest.${DateTime.now().millisecondsSinceEpoch}@bloombouquet.com';
      }

      debugPrint('Customer ID: $customerId');
      debugPrint('Email: $userEmail');

      // Cek apakah memilih metode VA
      String? selectedBank;
      if (_paymentMethod == 'bca' ||
          _paymentMethod == 'bni' ||
          _paymentMethod == 'bri' ||
          _paymentMethod == 'mandiri' ||
          _paymentMethod == 'permata') {
        selectedBank = _paymentMethod;
        debugPrint('Selected bank for VA payment: $selectedBank');
      } else {
        debugPrint('Payment method: $_paymentMethod (bukan VA)');
      }

      // Generate unique order ID
      final String orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 4)}';
      debugPrint('Generated Order ID: $orderId');

      // Get current timestamp for real-time order creation
      final now = DateTime.now();
      final orderTimestamp = now.toIso8601String();
      final paymentDeadline =
          now.add(const Duration(hours: 24)).toIso8601String();

      // Menambahkan field untuk memastikan pesanan masuk dengan status "waiting_for_payment"
      final Map<String, dynamic> orderData = {
        'id': orderId,
        'order_id': orderId, // Duplicate for safety
        'user_id': userId, // Include authenticated user ID
        'items': itemsForPayment,
        'deliveryAddress': {
          'address': deliveryAddress.fullAddress,
          'phone': deliveryAddress.phone,
          'name': deliveryAddress.name,
          'email': userEmail,
        },
        'subtotal': subtotal,
        'shippingCost': shippingCost,
        'total': total,
        'paymentMethod': _paymentMethod,
        'status': 'waiting_for_payment',
        'payment_status': 'pending',
        'is_read': false,
        'payment_deadline': paymentDeadline,
        'customer_name': userName, // Use authenticated user name
        'customer_email': userEmail, // Include customer email
        'notify_admin': true,
        'created_at': orderTimestamp, // Real-time timestamp
        'order_timestamp': orderTimestamp, // Additional timestamp field
        'timezone': 'Asia/Jakarta', // Specify timezone
        'request_id':
            'order_${now.millisecondsSinceEpoch}_${orderId.substring(orderId.length - 8)}', // Unique request ID
      };

      debugPrint('Mengirim data order ke server: ${jsonEncode(orderData)}');

      // Single order creation attempt with duplicate prevention
      Map<String, dynamic>? createOrderResult;

      try {
        if (_orderCreationInProgress) {
          debugPrint(
              'Order creation already in progress, skipping duplicate call');
          return;
        }

        _orderCreationInProgress = true;
        debugPrint('Creating order (single attempt to prevent duplicates)');

        // Membuat pesanan terlebih dahulu sebelum memproses pembayaran
        createOrderResult = await _paymentService.createOrder(orderData);

        if (!createOrderResult['success']) {
          debugPrint('Order creation failed: ${createOrderResult['message']}');
          // Don't retry - show error to user instead
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                    'Gagal membuat pesanan: ${createOrderResult['message']}'),
                backgroundColor: Colors.red,
              ),
            );
          }
          return;
        } else {
          debugPrint('Order created successfully');

          // Order created successfully, refresh the OrderService to show the new order in "My Orders"
          try {
            final orderService =
                Provider.of<OrderService>(context, listen: false);

            // Force refresh orders to ensure it appears in My Orders
            await orderService.fetchOrders(forceRefresh: true);

            debugPrint(
                '✓ Orders refreshed - new order should appear in My Orders with waiting_for_payment status');

            // Also create a local notification for the customer using NotificationService
            try {
              final notificationService = NotificationService();
              await notificationService.notifyNewOrderCreated(orderId, total);
              debugPrint('✓ Customer notification created for new order');
            } catch (e) {
              debugPrint('Warning: Failed to create customer notification: $e');
              // Continue anyway as this is not critical
            }
          } catch (e) {
            debugPrint('Warning: Failed to refresh orders: $e');
          }
        }
      } catch (e) {
        debugPrint('Exception in order creation: $e');
        _orderCreationInProgress = false; // Reset flag on error
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Gagal membuat pesanan. Silakan coba lagi.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      } finally {
        _orderCreationInProgress = false; // Always reset flag
      }

      // Retry logic for getting Midtrans token
      Map<String, dynamic>? result;
      int paymentAttempts = 0;
      const maxPaymentAttempts = 2;

      debugPrint('Memanggil getMidtransSnapToken...');

      while (result == null ||
          (!result['success'] && paymentAttempts < maxPaymentAttempts)) {
        try {
          paymentAttempts++;
          debugPrint(
              'Getting Midtrans token attempt $paymentAttempts of $maxPaymentAttempts');

          // Get Midtrans Snap Token
          result = await _paymentService.getMidtransSnapToken(
            items: itemsForPayment,
            customerId: customerId,
            shippingCost: shippingCost,
            shippingAddress: deliveryAddress.fullAddress,
            phoneNumber: deliveryAddress.phone,
            email: userEmail,
            selectedBank: selectedBank,
          );

          if (!result['success']) {
            debugPrint(
                'Midtrans token failed, attempt $paymentAttempts: ${result['message']}');

            if (paymentAttempts >= maxPaymentAttempts) {
              // Last attempt failed
              debugPrint('All Midtrans token attempts failed');
              break;
            }

            // Wait before retry
            await Future.delayed(const Duration(seconds: 1));
          }
        } catch (e) {
          debugPrint(
              'Exception in Midtrans token attempt $paymentAttempts: $e');
          if (paymentAttempts >= maxPaymentAttempts) {
            // All attempts failed with exception
            debugPrint('All Midtrans token attempts threw exceptions');
            result = {
              'success': false,
              'message': 'Payment processing failed: $e'
            };
            break;
          }
          await Future.delayed(const Duration(seconds: 1));
        }
      }

      // Pop loading dialog
      if (Navigator.canPop(context)) {
        Navigator.pop(context);
      }

      // Handle result based on payment method
      if (!result['success']) {
        debugPrint('❌ Midtrans API error: ${result['message']}');

        // Show simulation payment option if payment processing failed
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Payment Service Unavailable'),
            content: const Text(
                'We\'re having trouble connecting to our payment service. Would you like to continue with simulation mode for testing?'),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(context);
                },
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);

                  // Simulate successful payment by showing QR code with simulation
                  _processQRPayment(
                      context,
                      orderId,
                      "SIMULATION-TOKEN-${DateTime.now().millisecondsSinceEpoch}",
                      total,
                      cartProvider);
                },
                style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
                child: const Text('Continue with Simulation'),
              ),
            ],
          ),
        );
        return;
      }

      final responseOrderId = result['data']['order_id'];
      final String snapToken = result['data']['token'] ?? '';
      final String redirectUrl = result['data']['redirect_url'] ?? '';
      final dynamic vaNumber = result['data']['va_number'];
      final String? bank = result['data']['bank'];

      // Check if we're in simulation mode
      final bool isSimulation = result['simulation'] == true;
      if (isSimulation) {
        debugPrint('⚠️ USING SIMULATION MODE FOR PAYMENT');
      }

      debugPrint('============= PAYMENT DETAILS =============');
      debugPrint('Order ID: $responseOrderId');
      debugPrint('Snap Token: $snapToken');
      debugPrint('Redirect URL: $redirectUrl');
      debugPrint('Simulation Mode: $isSimulation');

      if (vaNumber != null) {
        debugPrint('VA NUMBER FOUND: $vaNumber');
        if (bank != null) {
          debugPrint('BANK: $bank');
        }
      } else {
        debugPrint('No VA number in response');
      }

      debugPrint('Payment Method: $_paymentMethod');
      debugPrint('==========================================');

      // Process payment based on selected method
      if (_paymentMethod == 'qris') {
        // Process QR Payment
        _processQRPayment(
            context, responseOrderId, snapToken, total, cartProvider);
      } else if (vaNumber != null) {
        // Process VA Payment
        _processVAPayment(
            context, responseOrderId, vaNumber, bank, total, cartProvider);
      } else if (redirectUrl.isNotEmpty) {
        // Process Web Redirect Payment
        _processWebPayment(
            context, responseOrderId, redirectUrl, total, cartProvider);
      } else {
        // Fallback to QR if nothing else works
        _processQRPayment(
            context, responseOrderId, snapToken, total, cartProvider);
      }
    } catch (e) {
      // Pop loading dialog if still showing
      if (Navigator.canPop(context)) {
        Navigator.pop(context);
      }

      debugPrint('Error during checkout: $e');
      _showNetworkErrorDialog(
        context,
        'Checkout Error',
        'An error occurred during checkout: $e\n\nPlease try again.',
      );
    }
  }

  // Process QR Code Payment
  void _processQRPayment(BuildContext context, String orderId, String snapToken,
      double total, CartProvider cartProvider) async {
    debugPrint('Processing QR payment for order: $orderId');

    try {
      // Navigate to QR Payment Screen
      final result = await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => QRPaymentScreen(
            orderId: orderId,
            amount: total,
            snapToken: snapToken,
            onPaymentSuccess: (payment) {
              // Handle payment success
            },
          ),
        ),
      );

      // Check the result
      if (result == 'success') {
        debugPrint('QR Payment successful');
        // Clear cart items
        cartProvider.removeSelectedItems();

        // Navigate to order tracking screen
        if (mounted) {
          Navigator.pushReplacementNamed(
            context,
            '/order-tracking',
            arguments: {
              'orderId': orderId,
            },
          );
        }
      } else {
        debugPrint('QR Payment was not successful or was cancelled');

        // Navigate to order tracking screen even if payment was cancelled
        // so user can see order status and try payment again
        if (mounted) {
          Navigator.pushReplacementNamed(
            context,
            '/order-tracking',
            arguments: {
              'orderId': orderId,
            },
          );
        }
      }
    } catch (e) {
      debugPrint('Error during QR payment: $e');
      // Show error if needed
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  // Process Virtual Account Payment
  void _processVAPayment(BuildContext context, String orderId, dynamic vaNumber,
      String? bank, double total, CartProvider cartProvider) async {
    debugPrint('Processing VA payment for order: $orderId');

    // Format bank name for display
    String bankName = '';
    switch (bank) {
      case 'bca':
        bankName = 'BCA';
        break;
      case 'bni':
        bankName = 'BNI';
        break;
      case 'bri':
        bankName = 'BRI';
        break;
      case 'mandiri':
        bankName = 'Mandiri';
        break;
      case 'permata':
        bankName = 'Permata';
        break;
      default:
        bankName = bank?.toUpperCase() ?? 'Bank';
    }

    debugPrint('VA payment for bank: $bankName');
    debugPrint('VA Number: $vaNumber');

    // Ensure VA number is a string
    final String vaNumberStr = vaNumber.toString();

    // Show VA payment information dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        child: TweenAnimationBuilder(
          duration: const Duration(milliseconds: 400),
          tween: Tween<double>(begin: 0.8, end: 1.0),
          builder: (context, value, child) {
            return Transform.scale(
              scale: value,
              child: child,
            );
          },
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Title with animation
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 400),
                  delay: const Duration(milliseconds: 100),
                  offset: const Offset(0, 20),
                  child: Row(
                    children: [
                      const Icon(Icons.account_balance, color: primaryColor),
                      const SizedBox(width: 10),
                      Flexible(
                        child: Text(
                          'Virtual Account $bankName',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 15),

                const _FadeInTranslate(
                  duration: Duration(milliseconds: 400),
                  delay: Duration(milliseconds: 200),
                  offset: Offset(0, 20),
                  child: Text(
                    'Silakan lakukan pembayaran dengan rincian berikut:',
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(height: 16),

                // Payment details with animation
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 600),
                  delay: const Duration(milliseconds: 300),
                  offset: const Offset(0, 20),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Bank'),
                            Text(
                              bankName,
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                        const Divider(),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Nomor VA'),
                            Flexible(
                              child: Text(
                                vaNumberStr,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                                textAlign: TextAlign.right,
                              ),
                            ),
                          ],
                        ),
                        const Divider(),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Total'),
                            Text(
                              formatCurrency(total),
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                color: primaryColor,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),

                // Info box with animation
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 600),
                  delay: const Duration(milliseconds: 400),
                  offset: const Offset(0, 20),
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.yellow.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.yellow.shade700),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline,
                            size: 20, color: Colors.yellow.shade800),
                        const SizedBox(width: 8),
                        const Expanded(
                          child: Text(
                            'Salin nomor virtual account untuk melakukan pembayaran',
                            style: TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Close button
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 800),
                  delay: const Duration(milliseconds: 500),
                  offset: const Offset(0, 20),
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop();

                      // Clear selected items from cart
                      cartProvider.removeSelectedItems();

                      // Navigate to order tracking screen
                      Navigator.pushReplacementNamed(
                        context,
                        '/order-tracking',
                        arguments: {
                          'orderId': orderId,
                        },
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(30),
                      ),
                    ),
                    child: const Text(
                      'Close',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Process Web Redirect Payment
  void _processWebPayment(BuildContext context, String orderId,
      String redirectUrl, double total, CartProvider cartProvider) async {
    debugPrint('Processing web payment for order: $orderId');
    debugPrint('Redirect URL: $redirectUrl');

    try {
      // Navigate to WebView Payment Screen
      final result = await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PaymentWebViewScreen(
            redirectUrl: redirectUrl,
            transactionId:
                orderId, // This matches the parameter in PaymentWebViewScreen
            onPaymentComplete: (status) async {
              // Handle payment status callback
              debugPrint('Payment status callback: $status');

              if (status) {
                // Payment successful - refresh orders to get updated status
                try {
                  final orderService =
                      Provider.of<OrderService>(context, listen: false);
                  await orderService.refreshOrders();
                  debugPrint('Orders refreshed after successful payment');
                } catch (e) {
                  debugPrint('Error refreshing orders after payment: $e');
                }
              }
            },
          ),
        ),
      );

      // Check the result
      if (result == true) {
        debugPrint('Web Payment successful');
        // Clear cart items
        cartProvider.removeSelectedItems();

        // Navigate to order tracking screen
        if (mounted) {
          Navigator.pushReplacementNamed(
            context,
            '/order-tracking',
            arguments: {
              'orderId': orderId,
            },
          );
        }
      } else {
        debugPrint('Web Payment was not successful or was cancelled');

        // Navigate to order tracking screen even if payment was cancelled
        // so user can see order status and try payment again
        if (mounted) {
          Navigator.pushReplacementNamed(
            context,
            '/order-tracking',
            arguments: {
              'orderId': orderId,
            },
          );
        }
      }
    } catch (e) {
      debugPrint('Error during web payment: $e');
      // Show error if needed
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  // Method to validate stock availability
  Future<bool> _validateStock(
      BuildContext context, CartProvider cartProvider) async {
    final selectedItems =
        cartProvider.items.where((item) => item.isSelected).toList();

    // Make API request to check stock
    try {
      final apiUrl = '${ApiConstants.getBaseUrl()}/api/v1/products/check-stock';
      final http.Response response = await http.post(
        Uri.parse(apiUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'items': selectedItems
              .map((item) => {
                    'product_id': item.productId,
                    'quantity': item.quantity,
                  })
              .toList(),
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true) {
          // All products have sufficient stock
          return true;
        } else {
          // Some products have insufficient stock
          final List<dynamic> insufficientItems =
              data['insufficient_items'] ?? [];

          if (insufficientItems.isNotEmpty) {
            // Show beautiful notification dialog for insufficient stock
            _showInsufficientStockDialog(context, insufficientItems);
            return false;
          }
        }
      } else {
        // API request failed, show error message
        _showNetworkErrorDialog(
          context,
          'Stock Check Failed',
          'Failed to check stock availability. Please try again.',
        );
        return false;
      }
    } catch (e) {
      // Exception occurred, let's do client-side stock check as fallback
      return _clientSideStockCheck(context, selectedItems);
    }

    return true;
  }

  // Fallback method to check stock locally
  bool _clientSideStockCheck(BuildContext context, List<CartItem> items) {
    // This is a fallback method that would check stock locally
    // In a real app, you would pull product data with latest stock info

    // For now, we'll simulate stock check with a simple dialog
    _showInsufficientStockDialog(context, [
      {
        'name': items.first.name,
        'quantity': items.first.quantity,
        'available': items.first.quantity - 2, // Simulate 2 less than requested
      }
    ]);

    return false;
  }

  // Method to show beautiful insufficient stock dialog
  void _showInsufficientStockDialog(
      BuildContext context, List<dynamic> insufficientItems) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        elevation: 0,
        backgroundColor: Colors.transparent,
        child: TweenAnimationBuilder(
          duration: const Duration(milliseconds: 400),
          tween: Tween<double>(begin: 0.0, end: 1.0),
          builder: (context, value, child) {
            return Transform.scale(
              scale: value,
              child: child,
            );
          },
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.rectangle,
              borderRadius: BorderRadius.circular(20),
              boxShadow: const [
                BoxShadow(
                  color: Colors.black26,
                  blurRadius: 10.0,
                  offset: Offset(0.0, 10.0),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icon and title with animation
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 800),
                  delay: const Duration(milliseconds: 200),
                  offset: const Offset(0, 20),
                  child: Container(
                    padding: const EdgeInsets.all(15),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.red.shade100, width: 2),
                    ),
                    child: Icon(
                      Icons.inventory_2,
                      color: Colors.red.shade300,
                      size: 50,
                    ),
                  ),
                ),
                const SizedBox(height: 15),
                Text(
                  'Stok Tidak Mencukupi',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w600,
                    color: Colors.red.shade700,
                  ),
                ),
                const SizedBox(height: 15),

                // Description
                const Text(
                  'Mohon maaf, beberapa produk di keranjang Anda memiliki stok yang tidak mencukupi:',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 15),
                ),
                const SizedBox(height: 15),

                // List of insufficient items with animation
                Container(
                  constraints: BoxConstraints(
                    maxHeight: MediaQuery.of(context).size.height * 0.25,
                  ),
                  child: SingleChildScrollView(
                    child: Container(
                      padding: const EdgeInsets.all(15),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        children:
                            List.generate(insufficientItems.length, (index) {
                          final item = insufficientItems[index];
                          final String name =
                              item['name'] ?? 'Produk tidak diketahui';
                          final int requested = item['quantity'] ?? 0;
                          final int available = item['available'] ?? 0;

                          return _FadeInTranslate(
                            duration: const Duration(milliseconds: 500),
                            delay: Duration(milliseconds: 300 + (index * 100)),
                            offset: const Offset(30, 0),
                            child: Padding(
                              padding: const EdgeInsets.only(bottom: 10.0),
                              child: Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(8),
                                  border:
                                      Border.all(color: Colors.red.shade200),
                                ),
                                child: Row(
                                  children: [
                                    Stack(
                                      alignment: Alignment.center,
                                      children: [
                                        Icon(Icons.circle,
                                            color: Colors.red.shade100,
                                            size: 36),
                                        Icon(Icons.warning_amber_rounded,
                                            color: Colors.red.shade700,
                                            size: 20),
                                      ],
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            name,
                                            style: const TextStyle(
                                                fontWeight: FontWeight.bold),
                                          ),
                                          const SizedBox(height: 4),
                                          Row(
                                            children: [
                                              _buildStockInfoTag(
                                                  'Diminta',
                                                  requested.toString(),
                                                  Colors.blue.shade700),
                                              const SizedBox(width: 8),
                                              _buildStockInfoTag(
                                                  'Tersedia',
                                                  available.toString(),
                                                  Colors.red.shade700),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // Animated info box
                _FadeInTranslate(
                  duration: const Duration(milliseconds: 800),
                  delay: const Duration(milliseconds: 600),
                  offset: const Offset(0, 20),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.yellow.shade50,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.yellow.shade700),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.lightbulb_outline,
                            color: Colors.amber.shade800),
                        const SizedBox(width: 10),
                        const Expanded(
                          child: Text(
                            'Silakan hubungi admin untuk bantuan pemesanan produk ini atau pilih jumlah yang sesuai dengan stok yang tersedia.',
                            style: TextStyle(fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // Action buttons with animation
                Padding(
                  padding: const EdgeInsets.only(top: 8.0),
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      // For narrow screens, stack the buttons vertically
                      if (constraints.maxWidth < 280) {
                        return Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            // Contact admin button
                            _FadeInTranslate(
                              duration: const Duration(milliseconds: 800),
                              delay: const Duration(milliseconds: 800),
                              offset: const Offset(0, 20),
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  // Close the dialog
                                  Navigator.of(context).pop();

                                  // Ambil data produk pertama yang stoknya tidak cukup
                                  final item = insufficientItems.isNotEmpty
                                      ? insufficientItems[0]
                                      : null;

                                  if (item != null) {
                                    final String productName =
                                        item['name'] ?? '';
                                    final int requested = item['quantity'] ?? 0;
                                    final int available =
                                        item['available'] ?? 0;

                                    // Cari gambar produk dari cartProvider
                                    final cartProvider =
                                        Provider.of<CartProvider>(context,
                                            listen: false);
                                    final cartItem =
                                        cartProvider.items.firstWhere(
                                      (ci) => ci.name == productName,
                                      orElse: () => cartProvider.items.first,
                                    );
                                    final String productImageUrl =
                                        cartItem.imageUrl;

                                    // Buat pesan otomatis
                                    final String autoMessage =
                                        'Halo Admin, saya ingin menanyakan ketersediaan produk "$productName" yang ingin saya beli sebanyak $requested buah, namun stok hanya tersedia $available. Mohon informasinya, terima kasih.';

                                    // Navigate to chat page with product info
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => ChatPage(
                                          showBottomNav: true,
                                          initialMessage: autoMessage,
                                          productName: productName,
                                          productImageUrl: productImageUrl,
                                          productStock: available,
                                          requestedQuantity: requested,
                                        ),
                                      ),
                                    );
                                  } else {
                                    // Fallback if no insufficientItems
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => const ChatPage(
                                          showBottomNav: true,
                                        ),
                                      ),
                                    );
                                  }
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: primaryColor,
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 12),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(30),
                                  ),
                                ),
                                icon: const Icon(Icons.message,
                                    color: Colors.white, size: 18),
                                label: const Text(
                                  'Hubungi Admin',
                                  style: TextStyle(
                                      color: Colors.white, fontSize: 13),
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),

                            // Continue shopping button
                            _FadeInTranslate(
                              duration: const Duration(milliseconds: 800),
                              delay: const Duration(milliseconds: 900),
                              offset: const Offset(0, 20),
                              child: OutlinedButton.icon(
                                onPressed: () {
                                  Navigator.of(context).pop();
                                },
                                style: OutlinedButton.styleFrom(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 12),
                                  side: const BorderSide(color: primaryColor),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(30),
                                  ),
                                ),
                                icon: const Icon(Icons.arrow_back,
                                    color: primaryColor, size: 16),
                                label: const Text(
                                  'Kembali',
                                  style: TextStyle(
                                      color: primaryColor, fontSize: 13),
                                ),
                              ),
                            ),
                          ],
                        );
                      }

                      // For wider screens, use a row layout
                      return Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          // Contact admin button
                          Flexible(
                            child: _FadeInTranslate(
                              duration: const Duration(milliseconds: 800),
                              delay: const Duration(milliseconds: 800),
                              offset: const Offset(-30, 0),
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  // Close the dialog
                                  Navigator.of(context).pop();

                                  // Ambil data produk pertama yang stoknya tidak cukup
                                  final item = insufficientItems.isNotEmpty
                                      ? insufficientItems[0]
                                      : null;

                                  if (item != null) {
                                    final String productName =
                                        item['name'] ?? '';
                                    final int requested = item['quantity'] ?? 0;
                                    final int available =
                                        item['available'] ?? 0;

                                    // Cari gambar produk dari cartProvider
                                    final cartProvider =
                                        Provider.of<CartProvider>(context,
                                            listen: false);
                                    final cartItem =
                                        cartProvider.items.firstWhere(
                                      (ci) => ci.name == productName,
                                      orElse: () => cartProvider.items.first,
                                    );
                                    final String productImageUrl =
                                        cartItem.imageUrl;

                                    // Buat pesan otomatis
                                    final String autoMessage =
                                        'Halo Admin, saya ingin menanyakan ketersediaan produk "$productName" yang ingin saya beli sebanyak $requested buah, namun stok hanya tersedia $available. Mohon informasinya, terima kasih.';

                                    // Navigate to chat page with product info
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => ChatPage(
                                          showBottomNav: true,
                                          initialMessage: autoMessage,
                                          productName: productName,
                                          productImageUrl: productImageUrl,
                                          productStock: available,
                                          requestedQuantity: requested,
                                        ),
                                      ),
                                    );
                                  } else {
                                    // Fallback if no insufficientItems
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => const ChatPage(
                                          showBottomNav: true,
                                        ),
                                      ),
                                    );
                                  }
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: primaryColor,
                                  padding: EdgeInsets.symmetric(
                                      horizontal:
                                          constraints.maxWidth < 350 ? 12 : 20,
                                      vertical: 12),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(30),
                                  ),
                                ),
                                icon: const Icon(Icons.message,
                                    color: Colors.white, size: 20),
                                label: const FittedBox(
                                  fit: BoxFit.scaleDown,
                                  child: Text(
                                    'Hubungi Admin',
                                    style: TextStyle(
                                        color: Colors.white, fontSize: 14),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),

                          // Continue shopping button
                          Flexible(
                            child: _FadeInTranslate(
                              duration: const Duration(milliseconds: 800),
                              delay: const Duration(milliseconds: 900),
                              offset: const Offset(30, 0),
                              child: OutlinedButton.icon(
                                onPressed: () {
                                  Navigator.of(context).pop();
                                },
                                style: OutlinedButton.styleFrom(
                                  padding: EdgeInsets.symmetric(
                                      horizontal:
                                          constraints.maxWidth < 350 ? 12 : 20,
                                      vertical: 12),
                                  side: const BorderSide(color: primaryColor),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(30),
                                  ),
                                ),
                                icon: const Icon(Icons.arrow_back,
                                    color: primaryColor, size: 16),
                                label: const FittedBox(
                                  fit: BoxFit.scaleDown,
                                  child: Text(
                                    'Kembali',
                                    style: TextStyle(
                                        color: primaryColor, fontSize: 14),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Helper method for stock info tag
  Widget _buildStockInfoTag(String label, String value, Color textColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: textColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label: ',
            style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.8)),
          ),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 12,
              color: textColor,
            ),
          ),
        ],
      ),
    );
  }

  // Check internet connectivity
  Future<bool> _checkInternetConnection() async {
    try {
      final result = await InternetAddress.lookup('google.com');
      return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
    } on SocketException catch (_) {
      return false;
    }
  }

  // Show network error dialog with retry option
  void _showNetworkErrorDialog(
      BuildContext context, String title, String message) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.signal_wifi_off,
                color: Colors.red[400],
                size: 50,
              ),
              const SizedBox(height: 16),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                message,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.grey[700],
                ),
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 24,
                        vertical: 12,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(30),
                      ),
                    ),
                    onPressed: () {
                      Navigator.of(context).pop();
                    },
                    child: const Text('OK'),
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

// Custom animated widget with delay capability
class _FadeInTranslate extends StatefulWidget {
  final Widget child;
  final Duration duration;
  final Duration delay;
  final Offset offset;

  const _FadeInTranslate({
    required this.child,
    required this.duration,
    required this.delay,
    required this.offset,
  });

  @override
  State<_FadeInTranslate> createState() => _FadeInTranslateState();
}

class _FadeInTranslateState extends State<_FadeInTranslate>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _opacity;
  late Animation<Offset> _position;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: widget.duration,
    );

    _opacity = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOut,
    ));

    _position = Tween<Offset>(
      begin: widget.offset,
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOut,
    ));

    // Add delay
    Future.delayed(widget.delay, () {
      if (mounted) {
        _controller.forward();
      }
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Opacity(
          opacity: _opacity.value,
          child: Transform.translate(
            offset: _position.value,
            child: child,
          ),
        );
      },
      child: widget.child,
    );
  }
}
