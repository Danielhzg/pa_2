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
import 'address_selection_screen.dart';
import 'qr_payment_screen.dart';
// import 'payment_method_screen.dart'; // Commented out as we're integrating this
import 'payment_webview_screen.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'chat_page.dart';

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

      // Get user email from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      final userEmail = userData != null
          ? jsonDecode(userData)['email']
          : 'customer@example.com';

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

      // Menambahkan field untuk memastikan pesanan masuk dengan status "waiting_for_payment"
      final Map<String, dynamic> orderData = {
        'id': 'ORDER-${DateTime.now().millisecondsSinceEpoch}',
        'items': itemsForPayment,
        'deliveryAddress': {
          'address': deliveryAddress.fullAddress,
          'phone': deliveryAddress.phone,
          'name': deliveryAddress.name,
        },
        'subtotal': subtotal,
        'shippingCost': shippingCost,
        'total': total,
        'paymentMethod': _paymentMethod,
        'status':
            'waiting_for_payment', // Status eksplisit dengan string literal
        'payment_status': 'pending',
        'payment_deadline': DateTime.now()
            .add(const Duration(minutes: 15))
            .toIso8601String(), // Deadline 15 menit
      };

      debugPrint('Mengirim data order ke server: ${jsonEncode(orderData)}');

      // Membuat pesanan terlebih dahulu sebelum memproses pembayaran
      final createOrderResult = await _paymentService.createOrder(orderData);

      if (!createOrderResult['success']) {
        // Jika gagal membuat pesanan, tampilkan pesan error
        if (Navigator.canPop(context)) {
          Navigator.pop(context);
        }

        _showNetworkErrorDialog(
          context,
          'Order Creation Failed',
          createOrderResult['message'] ??
              'Failed to create order. Please try again.',
        );
        return;
      }

      // Get Midtrans Snap Token
      debugPrint('Memanggil getMidtransSnapToken...');
      final result = await _paymentService.getMidtransSnapToken(
        items: itemsForPayment,
        customerId: customerId,
        shippingCost: shippingCost,
        shippingAddress: deliveryAddress.fullAddress,
        phoneNumber: deliveryAddress.phone,
        email: userEmail,
        selectedBank: selectedBank,
      );

      // Pop loading dialog
      if (Navigator.canPop(context)) {
        Navigator.pop(context);
      }

      if (!result['success']) {
        debugPrint('❌ Midtrans API error: ${result['message']}');
        _showNetworkErrorDialog(
            context,
            'Payment Error',
            result['message'] ??
                'Failed to create payment. Please try again later.');
        return;
      }

      final orderId = result['data']['order_id'];
      final String snapToken = result['data']['token'] ?? '';
      final String redirectUrl = result['data']['redirect_url'] ?? '';
      final dynamic vaNumber = result['data']['va_number'];
      final String? bank = result['data']['bank'];

      debugPrint('============= PAYMENT DETAILS =============');
      debugPrint('Order ID: $orderId');
      debugPrint('Snap Token: $snapToken');
      debugPrint('Redirect URL: $redirectUrl');

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

      // Refresh the OrderService to show the new order in "My Orders"
      try {
        final orderService = Provider.of<OrderService>(context, listen: false);
        await orderService.fetchOrders();
        debugPrint(
            '✓ Orders refreshed - new order should appear in My Orders with waiting_for_payment status');
      } catch (e) {
        debugPrint('Warning: Failed to refresh orders: $e');
      }

      // Clear selected items from cart
      cartProvider.removeSelectedItems();

      // Jika ini pembayaran virtual account, tampilkan dialog dengan informasi VA
      if (vaNumber != null) {
        // Tutup dialog loading jika masih terbuka
        if (Navigator.canPop(context)) {
          Navigator.pop(context);
        }

        // Format bank name untuk tampilan
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

        debugPrint('Menampilkan dialog VA untuk bank: $bankName');
        debugPrint('VA Number: $vaNumber');

        // Pastikan VA number adalah string
        final String vaNumberStr = vaNumber.toString();

        // Tampilkan dialog VA dengan format yang lebih baik
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
                          const Icon(Icons.account_balance,
                              color: primaryColor),
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
                                  style: const TextStyle(
                                      fontWeight: FontWeight.bold),
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
                    const SizedBox(height: 12),

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
                                        final int requested =
                                            item['quantity'] ?? 0;
                                        final int available =
                                            item['available'] ?? 0;

                                        // Cari gambar produk dari cartProvider
                                        final cartProvider =
                                            Provider.of<CartProvider>(context,
                                                listen: false);
                                        final cartItem =
                                            cartProvider.items.firstWhere(
                                          (ci) => ci.name == productName,
                                          orElse: () =>
                                              cartProvider.items.first,
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
                                            builder: (context) =>
                                                const ChatPage(
                                              showBottomNav: true,
                                            ),
                                          ),
                                        );
                                      }
                                    },
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: primaryColor,
                                      padding: const EdgeInsets.symmetric(
                                          vertical: 12),
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
                                      padding: const EdgeInsets.symmetric(
                                          vertical: 12),
                                      side:
                                          const BorderSide(color: primaryColor),
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
                                        final int requested =
                                            item['quantity'] ?? 0;
                                        final int available =
                                            item['available'] ?? 0;

                                        // Cari gambar produk dari cartProvider
                                        final cartProvider =
                                            Provider.of<CartProvider>(context,
                                                listen: false);
                                        final cartItem =
                                            cartProvider.items.firstWhere(
                                          (ci) => ci.name == productName,
                                          orElse: () =>
                                              cartProvider.items.first,
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
                                            builder: (context) =>
                                                const ChatPage(
                                              showBottomNav: true,
                                            ),
                                          ),
                                        );
                                      }
                                    },
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: primaryColor,
                                      padding: EdgeInsets.symmetric(
                                          horizontal: constraints.maxWidth < 350
                                              ? 12
                                              : 20,
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
                                          horizontal: constraints.maxWidth < 350
                                              ? 12
                                              : 20,
                                          vertical: 12),
                                      side:
                                          const BorderSide(color: primaryColor),
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

        return; // Hentikan eksekusi lebih lanjut
      }

      // Process payment based on method
      debugPrint('Proses pembayaran metode non-VA');
      bool paymentResult = false;

      if (_paymentMethod == 'qris') {
        // QR Code payment using QRIS
        debugPrint('Memproses pembayaran QRIS');
        paymentResult = await _handleQRCodePayment(orderId, total, snapToken);
      } else if (_paymentMethod == 'bank_transfer') {
        // Bank transfers - use WebView with redirect URL
        debugPrint(
            'Memproses pembayaran Bank Transfer dengan WebView URL: $redirectUrl');
        paymentResult =
            await _handleMidtransWebPayment(orderId, redirectUrl, snapToken);
      } else {
        // Fallback if somehow an invalid payment method is selected
        debugPrint('Metode pembayaran tidak valid: $_paymentMethod');
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content:
                Text('Silakan pilih metode pembayaran QRIS atau Transfer Bank'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      // If payment was not successful, don't continue
      if (!paymentResult) {
        debugPrint('Pembayaran dibatalkan atau gagal');
        return;
      }

      // On successful payment, clear cart
      debugPrint('Pembayaran berhasil, membersihkan keranjang');
      cartProvider.clear();

      if (!mounted) return;

      // Navigate back to the previous screen
      Navigator.of(context).pop(); // Pop checkout screen

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Payment successful! Your order has been placed.'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      debugPrint('❌ ERROR DALAM PEMROSESAN PEMBAYARAN: $e');

      // Pop loading dialog if still showing
      if (Navigator.canPop(context)) {
        Navigator.pop(context);
      }

      // Show error message with better UI
      _showNetworkErrorDialog(
        context,
        'Payment Error',
        'An error occurred while processing your payment: ${e.toString().replaceAll('Exception: ', '')}',
      );
    }
  }

  // Handle QR Code Payment method with Snap Token
  Future<bool> _handleQRCodePayment(
      String orderId, double total, String snapToken) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => QRPaymentScreen(
          amount: total,
          orderId: orderId,
          snapToken: snapToken,
          onPaymentSuccess: (payment) async {
            try {
              // Payment success handled in callback
            } catch (e) {
              print('Error updating order status: $e');
            }
          },
        ),
      ),
    );

    // Return true if payment was successful
    return result == true;
  }

  // Handle Midtrans WebView Payment with redirect URL
  Future<bool> _handleMidtransWebPayment(
      String orderId, String redirectUrl, String snapToken) async {
    try {
      // Open WebView for payment
      final webViewResult = await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PaymentWebViewScreen(
            redirectUrl: redirectUrl,
            transactionId: snapToken,
            onPaymentComplete: (status) {
              // Payment status callback
            },
          ),
        ),
      );

      // Return true if payment was successful
      return webViewResult == true;
    } catch (e) {
      // Show error message with better UI
      _showNetworkErrorDialog(
        context,
        'Payment Error',
        'Error processing payment: ${e.toString()}',
      );
      return false;
    }
  }

  // Method to validate stock availability
  Future<bool> _validateStock(
      BuildContext context, CartProvider cartProvider) async {
    final selectedItems =
        cartProvider.items.where((item) => item.isSelected).toList();

    // Make API request to check stock
    try {
      const apiUrl = 'http://10.0.2.2:8000/api/v1/products/check-stock';
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
