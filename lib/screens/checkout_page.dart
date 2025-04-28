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
// import 'payment_method_screen.dart'; // Commented out as we're integrating this
import 'payment_webview_screen.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

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

  // Payment method - now variable
  String _paymentMethod = 'qris';
  String _paymentMethodName = 'QR Code Payment (QRIS)';
  List<dynamic> _paymentMethods = [];

  final PaymentService _paymentService = PaymentService();
  final OrderService _orderService = OrderService();

  @override
  void initState() {
    super.initState();
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
                      currencyFormatter.format(total),
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
        throw Exception(result['message']);
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
          builder: (context) => AlertDialog(
            title: Row(
              children: [
                const Icon(Icons.account_balance, color: primaryColor),
                const SizedBox(width: 10),
                Flexible(
                  child: Text(
                    'Virtual Account $bankName',
                    style: const TextStyle(fontSize: 18),
                  ),
                ),
              ],
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Silakan lakukan pembayaran dengan rincian berikut:',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                Container(
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
                            style: const TextStyle(fontWeight: FontWeight.bold),
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
                            NumberFormat.currency(
                              locale: 'id',
                              symbol: 'Rp',
                              decimalDigits: 0,
                            ).format(total),
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
                const SizedBox(height: 12),
                Container(
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
                      const Flexible(
                        child: Text(
                          'Salin nomor virtual account untuk melakukan pembayaran',
                          style: TextStyle(fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                const Text(
                  'Pembayaran akan dikonfirmasi secara otomatis oleh sistem.',
                  style: TextStyle(fontSize: 12, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(context); // Tutup dialog
                },
                child: const Text('Cek Status'),
              ),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(context); // Tutup dialog
                  cartProvider.clear(); // Bersihkan keranjang setelah sukses
                  Navigator.pop(context); // Kembali ke layar sebelumnya

                  // Tampilkan pesan sukses
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.',
                      ),
                      backgroundColor: Colors.green,
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                ),
                child: const Text('Selesai'),
              ),
            ],
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

      // Show error message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to place order: $e'),
          backgroundColor: Colors.red,
        ),
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error processing payment: $e'),
          backgroundColor: Colors.red,
        ),
      );
      return false;
    }
  }
}
