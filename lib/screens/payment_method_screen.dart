import 'package:flutter/material.dart';
import '../services/payment_service.dart';

class PaymentMethod {
  final String id;
  final String name;
  final String icon;
  final String description;

  PaymentMethod({
    required this.id,
    required this.name,
    required this.icon,
    required this.description,
  });
}

class PaymentMethodScreen extends StatefulWidget {
  final Function(String) onSelectPaymentMethod;
  final String? initialSelected;

  const PaymentMethodScreen({
    Key? key,
    required this.onSelectPaymentMethod,
    this.initialSelected,
  }) : super(key: key);

  @override
  State<PaymentMethodScreen> createState() => _PaymentMethodScreenState();
}

class _PaymentMethodScreenState extends State<PaymentMethodScreen> {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);

  final PaymentService _paymentService = PaymentService();
  bool _isLoading = true;
  String? _selectedPaymentMethod;
  List<dynamic> _paymentMethods = [];

  @override
  void initState() {
    super.initState();
    _selectedPaymentMethod = widget.initialSelected ?? 'qr_code';
    _loadPaymentMethods();
  }

  Future<void> _loadPaymentMethods() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await _paymentService.getPaymentMethods();
      setState(() {
        // Extract the data list from the response map
        _paymentMethods = response['data'] ?? [];
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Payment Method'),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _buildPaymentMethodsList(),
      bottomNavigationBar: _buildBottomBar(),
    );
  }

  Widget _buildPaymentMethodsList() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'Choose your payment method',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 16),

        // QR Code Payment
        _buildPaymentMethodItem(
          code: 'qris',
          name: 'QR Code Payment (QRIS)',
          icon: Icons.qr_code,
          description: 'Pay using any mobile banking app or e-wallet',
        ),

        // Virtual Account Section
        const SizedBox(height: 16),
        const Text(
          'Bank Virtual Account',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Colors.grey,
          ),
        ),
        const SizedBox(height: 8),

        // BCA Virtual Account
        _buildPaymentMethodItem(
          code: 'bca_va',
          name: 'BCA Virtual Account',
          icon: Icons.account_balance,
          description: 'Pay via BCA Virtual Account',
        ),

        // BNI Virtual Account
        _buildPaymentMethodItem(
          code: 'bni_va',
          name: 'BNI Virtual Account',
          icon: Icons.account_balance,
          description: 'Pay via BNI Virtual Account',
        ),

        // BRI Virtual Account
        _buildPaymentMethodItem(
          code: 'bri_va',
          name: 'BRI Virtual Account',
          icon: Icons.account_balance,
          description: 'Pay via BRI Virtual Account',
        ),

        // Mandiri Bill Payment
        _buildPaymentMethodItem(
          code: 'echannel',
          name: 'Mandiri Bill Payment',
          icon: Icons.account_balance,
          description: 'Pay via Mandiri Bill Payment',
        ),

        // Permata Virtual Account
        _buildPaymentMethodItem(
          code: 'permata_va',
          name: 'Permata Virtual Account',
          icon: Icons.account_balance,
          description: 'Pay via Permata Virtual Account',
        ),

        // Other payment methods
        const SizedBox(height: 16),
        const Text(
          'Other Payment Methods',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Colors.grey,
          ),
        ),
        const SizedBox(height: 8),

        // Credit Card
        _buildPaymentMethodItem(
          code: 'credit_card',
          name: 'Credit Card',
          icon: Icons.credit_card,
          description: 'Pay with Visa, Mastercard, JCB, or Amex',
        ),

        // GoPay
        _buildPaymentMethodItem(
          code: 'gopay',
          name: 'GoPay',
          icon: Icons.account_balance_wallet,
          description: 'Pay with GoPay',
        ),

        // ShopeePay
        _buildPaymentMethodItem(
          code: 'shopeepay',
          name: 'ShopeePay',
          icon: Icons.account_balance_wallet,
          description: 'Pay with ShopeePay',
        ),

        // Dynamic methods from API (if any)
        ..._paymentMethods.map((method) {
          if ([
            'qris',
            'bca_va',
            'bni_va',
            'bri_va',
            'echannel',
            'permata_va',
            'credit_card',
            'gopay',
            'shopeepay',
          ].contains(method['code'])) {
            return const SizedBox
                .shrink(); // Skip if we already have it hardcoded
          }

          return _buildPaymentMethodItem(
            code: method['code'],
            name: method['name'],
            icon: Icons.payment,
            description: method['description'] ?? 'Pay with ${method['name']}',
          );
        }).toList(),
      ],
    );
  }

  Widget _buildPaymentMethodItem({
    required String code,
    required String name,
    required IconData icon,
    required String description,
  }) {
    final isSelected = _selectedPaymentMethod == code;

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
          setState(() {
            _selectedPaymentMethod = code;
          });
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
                  icon,
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

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(16),
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
        child: ElevatedButton(
          onPressed: () {
            if (_selectedPaymentMethod != null) {
              widget.onSelectPaymentMethod(_selectedPaymentMethod!);
              Navigator.pop(context);
            }
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: primaryColor,
            padding: const EdgeInsets.symmetric(vertical: 15),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: const Text(
            'Confirm Payment Method',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ),
    );
  }

  // Helper method to convert between display names and API codes
  String convertPaymentMethodCode(String code, {bool toDisplayName = false}) {
    final Map<String, String> codeToName = {
      'qr_code': 'QR Code Payment',
      'bank_transfer': 'Bank Transfer',
      'bca_va': 'BCA Virtual Account',
      'bni_va': 'BNI Virtual Account',
      'bri_va': 'BRI Virtual Account',
      'mandiri_va': 'Mandiri Virtual Account',
      'credit_card': 'Credit Card',
      'e_wallet': 'E-Wallet',
      'gopay': 'GoPay',
      'shopeepay': 'ShopeePay',
      'cod': 'Cash on Delivery',
    };

    if (toDisplayName) {
      return codeToName[code] ?? 'Online Payment';
    } else {
      // Convert display name to code
      return codeToName.entries
          .firstWhere((entry) => entry.value == code,
              orElse: () => const MapEntry('credit_card', 'Credit Card'))
          .key;
    }
  }
}
