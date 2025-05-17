import 'dart:async';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:provider/provider.dart';
import '../services/payment_service.dart';
import '../services/midtrans_service.dart';
import '../models/payment.dart';
import '../providers/cart_provider.dart';
import 'dart:math' as math;
import 'dart:convert';
import 'package:intl/intl.dart';

class QRPaymentScreen extends StatefulWidget {
  final double amount;
  final String orderId;
  final String snapToken;
  final Function(Payment) onPaymentSuccess;

  const QRPaymentScreen({
    Key? key,
    required this.amount,
    required this.orderId,
    required this.snapToken,
    required this.onPaymentSuccess,
  }) : super(key: key);

  @override
  State<QRPaymentScreen> createState() => _QRPaymentScreenState();
}

class _QRPaymentScreenState extends State<QRPaymentScreen>
    with SingleTickerProviderStateMixin {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);
  static const List<Color> gradientColors = [
    Color(0xFFFF87B2),
    Color(0xFFFF5A8A),
  ];

  bool _isLoading = true;
  String? _qrCodeUrl;
  String? _qrCodeData;
  final String _paymentStatus = 'pending';
  Timer? _statusCheckTimer;
  final PaymentService _paymentService = PaymentService();
  Payment? _payment;
  late AnimationController _animationController;
  late Animation<double> _pulseAnimation;
  bool _isQrVisible = false;
  bool _isExpired = false;
  Duration _remainingTime = const Duration(minutes: 15);
  DateTime? _expiryTime;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(seconds: 2),
      vsync: this,
    )..repeat(reverse: true);

    _pulseAnimation = Tween(begin: 1.0, end: 1.05).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));

    _initQRPayment();
  }

  @override
  void dispose() {
    _statusCheckTimer?.cancel();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _initQRPayment() async {
    setState(() {
      _isLoading = true;
      _isQrVisible = false;
    });

    try {
      print(
          "[QR Payment] Fetching QR code for order ID: ${widget.orderId} with amount: ${widget.amount}");

      // Fetch QR code from API using the orderId and amount
      final qrData = await _paymentService.getQRCode(widget.orderId,
          amount: widget.amount);

      print("[QR Payment] QR code data received: ${qrData.toString()}");

      setState(() {
        _qrCodeData = qrData['qr_code_data'];
        _qrCodeUrl = qrData['qr_code_url'];
        _isLoading = false;

        // Get expiry time
        if (qrData['expiry_time'] != null) {
          try {
            _expiryTime = DateTime.parse(qrData['expiry_time']);
            final now = DateTime.now();
            if (_expiryTime!.isAfter(now)) {
              _remainingTime = _expiryTime!.difference(now);
              _startCountdownTimer();
            } else {
              _remainingTime = Duration.zero;
              _isExpired = true;
            }
          } catch (e) {
            print("[QR Payment] Error parsing expiry time: $e");
            // Default to 15 minutes if there's an error
            _remainingTime = const Duration(minutes: 15);
            _startCountdownTimer();
          }
        } else {
          // Default to 15 minutes if no expiry time is provided
          _remainingTime = const Duration(minutes: 15);
          _startCountdownTimer();
        }
      });

      // Animate the QR code appearance
      Future.delayed(const Duration(milliseconds: 300), () {
        if (mounted) {
          setState(() {
            _isQrVisible = true;
          });
        }
      });

      print("[QR Payment] QR data: $_qrCodeData");
      print("[QR Payment] QR URL: $_qrCodeUrl");

      // Process payment record specifically with the amount
      final payment = await _paymentService.processPayment(
        orderId: widget.orderId,
        amount: widget.amount,
        paymentMethod: 'QRIS',
        qrCodeUrl: _qrCodeUrl,
        qrCodeData: _qrCodeData,
      );

      setState(() {
        _payment = Payment(
          id: payment['payment_id'] ?? '',
          orderId: widget.orderId,
          amount: widget.amount,
          status: payment['status'] ?? 'pending',
          paymentMethod: 'QRIS',
          qrCodeUrl: payment['qr_url'],
          qrCodeData: payment['qr_data'],
          createdAt: DateTime.now(),
        );
      });

      // Start checking payment status periodically
      _startPaymentStatusCheck(widget.orderId);
    } catch (e) {
      print("[QR Payment] Error generating QR code: $e");
      setState(() {
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error generating QR code: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _startCountdownTimer() {
    _statusCheckTimer?.cancel();
    _statusCheckTimer =
        Timer.periodic(const Duration(seconds: 5), (timer) async {
      try {
        final statusData =
            await _paymentService.checkTransactionStatus(widget.orderId);
        final status = statusData['transaction_status'] ?? 'pending';

        if (status == 'settlement' || status == 'capture' || status == 'paid') {
          // Payment successful
          timer.cancel();
          _statusCheckTimer?.cancel();

          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Payment successful!'),
                backgroundColor: Colors.green,
              ),
            );
            Navigator.pop(context, 'success');
          }
        } else if (status == 'expire' || status == 'expired') {
          // Payment expired
          timer.cancel();
          _statusCheckTimer?.cancel();

          if (mounted) {
            setState(() {
              _isExpired = true;
            });
          }
        }
      } catch (e) {
        print("[QR Payment] Error checking payment status: $e");
      }
    });
  }

  void _startPaymentStatusCheck(String orderId) {
    _statusCheckTimer?.cancel();
    _statusCheckTimer =
        Timer.periodic(const Duration(seconds: 5), (timer) async {
      try {
        final statusData =
            await _paymentService.checkTransactionStatus(orderId);
        final status = statusData['transaction_status'] ?? 'pending';

        if (status == 'settlement' || status == 'capture' || status == 'paid') {
          // Payment successful
          timer.cancel();
          _statusCheckTimer?.cancel();

          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Payment successful!'),
                backgroundColor: Colors.green,
              ),
            );
            Navigator.pop(context, 'success');
          }
        } else if (status == 'expire' || status == 'expired') {
          // Payment expired
          timer.cancel();
          _statusCheckTimer?.cancel();

          if (mounted) {
            setState(() {
              _isExpired = true;
            });
          }
        }
      } catch (e) {
        print("[QR Payment] Error checking payment status: $e");
      }
    });
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    String minutes = twoDigits(duration.inMinutes.remainder(60));
    String seconds = twoDigits(duration.inSeconds.remainder(60));
    return '$minutes:$seconds';
  }

  void _showSuccessAnimation() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.check_circle_outline,
                  color: Colors.green,
                  size: 80,
                ),
                const SizedBox(height: 16),
                const Text(
                  'Payment Successful!',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Rp ${_formatCurrency(widget.amount)} has been paid',
                  style: const TextStyle(
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () {
                    Navigator.pop(context); // Close dialog
                    Navigator.pop(context, true); // Return to previous screen
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 24, vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: const Text('Continue'),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showPaymentFailedDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          title: const Text('Payment Failed'),
          content: const Text(
              'The payment transaction has failed or been cancelled. Would you like to try again?'),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.of(context)
                    .pop(); // Go back to payment method selection
              },
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                _initQRPayment(); // Try again with a fresh QR
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(30)),
              ),
              child: const Text('Try Again'),
            ),
          ],
        );
      },
    );
  }

  // Helper method to format currency with dot separator
  String _formatCurrency(double amount) {
    String amountStr = amount.toInt().toString();
    String result = '';
    int count = 0;

    // Process from right to left
    for (int i = amountStr.length - 1; i >= 0; i--) {
      result = amountStr[i] + result;
      count++;
      // Add dot after every 3 digits except for the last group
      if (count % 3 == 0 && i > 0) {
        result = '.$result';
      }
    }

    return result;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('QR Code Payment'),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
        centerTitle: true,
        elevation: 0,
      ),
      body: _isLoading
          ? _buildLoadingState()
          : _isExpired
              ? _buildExpiredView()
              : _buildPaymentContent(),
    );
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 80,
            height: 80,
            child: CircularProgressIndicator(
              color: primaryColor,
              strokeWidth: 3,
              backgroundColor: accentColor.withOpacity(0.3),
            ),
          ),
          const SizedBox(height: 24),
          const Text(
            'Generating QR Code',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Please wait while we prepare your payment',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildExpiredView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.timer_off,
            size: 72,
            color: Colors.red,
          ),
          const SizedBox(height: 24),
          const Text(
            'Payment Time Expired',
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          const Text(
            'The QR code payment has expired. Please try again with a new order.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 16),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
            },
            child: const Text('Back to Orders'),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentContent() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            _buildHeaderInfo(),
            const SizedBox(height: 24),
            _buildQRCodeCard(),
            const SizedBox(height: 16),
            _buildStatusIndicator(),
            const SizedBox(height: 24),
            _buildInstructionsCard(),
            const SizedBox(height: 20),
            _buildSupportedApps(),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderInfo() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: gradientColors,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          const Text(
            'Total Payment',
            style: TextStyle(
              fontSize: 16,
              color: Colors.white,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Rp ${_formatCurrency(widget.amount)}',
            style: const TextStyle(
              fontSize: 28,
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.3),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Text(
              'Order ID: ${widget.orderId}',
              style: const TextStyle(
                fontSize: 12,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQRCodeCard() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            const Text(
              'Scan QR Code to Pay',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Using your mobile banking or e-wallet app',
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 24),

            // Animated QR Code Display
            AnimatedOpacity(
              opacity: _isQrVisible ? 1.0 : 0.0,
              duration: const Duration(milliseconds: 500),
              child: AnimatedScale(
                scale: _isQrVisible ? 1.0 : 0.8,
                duration: const Duration(milliseconds: 500),
                curve: Curves.easeOutBack,
                child: AnimatedBuilder(
                  animation: _pulseAnimation,
                  builder: (context, child) {
                    return Transform.scale(
                      scale: _paymentStatus == 'pending'
                          ? _pulseAnimation.value
                          : 1.0,
                      child: Container(
                        width: 250,
                        height: 250,
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: _getStatusColor(_paymentStatus)
                                .withOpacity(0.5),
                            width: 2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.05),
                              blurRadius: 10,
                              spreadRadius: 1,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: _qrCodeUrl != null && _qrCodeUrl!.isNotEmpty
                            ? Stack(
                                alignment: Alignment.center,
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.network(
                                      _qrCodeUrl!,
                                      width: 220,
                                      height: 220,
                                      fit: BoxFit.contain,
                                      errorBuilder:
                                          (context, error, stackTrace) {
                                        print(
                                            "[QR Payment] Error loading QR image: $error");
                                        return _buildQrImageFromData();
                                      },
                                    ),
                                  ),
                                  if (_paymentStatus == 'completed')
                                    Container(
                                      width: 220,
                                      height: 220,
                                      decoration: BoxDecoration(
                                        color: Colors.green.withOpacity(0.7),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: const Center(
                                        child: Icon(
                                          Icons.check_circle_outline,
                                          color: Colors.white,
                                          size: 80,
                                        ),
                                      ),
                                    ),
                                  if (_paymentStatus == 'failed')
                                    Container(
                                      width: 220,
                                      height: 220,
                                      decoration: BoxDecoration(
                                        color: Colors.red.withOpacity(0.7),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: const Center(
                                        child: Icon(
                                          Icons.error_outline,
                                          color: Colors.white,
                                          size: 80,
                                        ),
                                      ),
                                    ),
                                ],
                              )
                            : _buildQrImageFromData(),
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusIndicator() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: _getStatusColor(_paymentStatus).withOpacity(0.1),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(
          color: _getStatusColor(_paymentStatus).withOpacity(0.5),
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            _getStatusIcon(_paymentStatus),
            color: _getStatusColor(_paymentStatus),
            size: 20,
          ),
          const SizedBox(width: 8),
          Text(
            'Status: ${_formatStatus(_paymentStatus)}',
            style: TextStyle(
              color: _getStatusColor(_paymentStatus),
              fontWeight: FontWeight.bold,
            ),
          ),
          if (_paymentStatus == 'pending')
            Container(
              margin: const EdgeInsets.only(left: 8),
              width: 12,
              height: 12,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(
                    _getStatusColor(_paymentStatus)),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildInstructionsCard() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.info_outline, color: primaryColor),
                SizedBox(width: 8),
                Text(
                  'How to Pay:',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildInstructionStep(
              number: '1',
              text: 'Open your banking or e-wallet app',
              icon: Icons.phone_android,
            ),
            _buildInstructionStep(
              number: '2',
              text: 'Scan the QR code above or take a screenshot',
              icon: Icons.qr_code_scanner,
            ),
            _buildInstructionStep(
              number: '3',
              text:
                  'Confirm the payment amount of Rp ${_formatCurrency(widget.amount)}',
              icon: Icons.attach_money,
            ),
            _buildInstructionStep(
              number: '4',
              text: 'Complete the payment process in your app',
              icon: Icons.check_circle,
            ),
            _buildInstructionStep(
              number: '5',
              text: 'Wait for confirmation on this screen',
              icon: Icons.hourglass_empty,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInstructionStep({
    required String number,
    required String text,
    required IconData icon,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: gradientColors,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                number,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
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
                  text,
                  style: const TextStyle(fontSize: 14),
                ),
              ],
            ),
          ),
          Icon(
            icon,
            size: 20,
            color: Colors.grey[400],
          ),
        ],
      ),
    );
  }

  Widget _buildSupportedApps() {
    // Updated app data with proper logos and identifiers
    final apps = [
      {
        'name': 'DANA',
        'icon': Icons.account_balance_wallet,
        'color': const Color(0xFF1A9DE4)
      },
      {
        'name': 'OVO',
        'icon': Icons.account_balance_wallet,
        'color': const Color(0xFF4C3494)
      },
      {
        'name': 'GoPay',
        'icon': Icons.account_balance_wallet,
        'color': const Color(0xFF00ADD0)
      },
      {
        'name': 'LinkAja',
        'icon': Icons.account_balance_wallet,
        'color': const Color(0xFFED1D25)
      },
      {
        'name': 'ShopeePay',
        'icon': Icons.account_balance_wallet,
        'color': const Color(0xFFEE4D2D)
      },
      {
        'name': 'BCA Mobile',
        'icon': Icons.account_balance,
        'color': const Color(0xFF005FAF)
      },
      {
        'name': 'BRI Mobile',
        'icon': Icons.account_balance,
        'color': const Color(0xFF0D4E98)
      },
      {
        'name': 'BNI Mobile',
        'icon': Icons.account_balance,
        'color': const Color(0xFF003D79)
      },
      {
        'name': 'Mandiri Livin',
        'icon': Icons.account_balance,
        'color': const Color(0xFF003C7D)
      },
      {
        'name': 'CIMB Niaga',
        'icon': Icons.account_balance,
        'color': const Color(0xFF790F13)
      },
    ];

    return Column(
      children: [
        Text(
          'Supported Payment Apps',
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.bold,
            color: Colors.grey[800],
          ),
        ),
        const SizedBox(height: 16),
        Container(
          decoration: BoxDecoration(
            color: Colors.grey[100],
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey[300]!),
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              const Row(
                children: [
                  Icon(Icons.phone_android, color: primaryColor, size: 18),
                  SizedBox(width: 8),
                  Text(
                    'E-Wallet Options',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: primaryColor,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                alignment: WrapAlignment.center,
                spacing: 10,
                runSpacing: 10,
                children: apps.sublist(0, 5).map((app) {
                  return Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: app['color'] as Color,
                          spreadRadius: 0,
                          blurRadius: 3,
                          offset: const Offset(0, 1),
                        ),
                      ],
                      border: Border.all(
                          color: (app['color'] as Color).withOpacity(0.5)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(app['icon'] as IconData,
                            size: 16, color: app['color'] as Color),
                        const SizedBox(width: 6),
                        Text(
                          app['name'] as String,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: Colors.black87,
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 20),
              const Row(
                children: [
                  Icon(Icons.account_balance, color: primaryColor, size: 18),
                  SizedBox(width: 8),
                  Text(
                    'Mobile Banking',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: primaryColor,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                alignment: WrapAlignment.center,
                spacing: 10,
                runSpacing: 10,
                children: apps.sublist(5).map((app) {
                  return Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: app['color'] as Color,
                          spreadRadius: 0,
                          blurRadius: 3,
                          offset: const Offset(0, 1),
                        ),
                      ],
                      border: Border.all(
                          color: (app['color'] as Color).withOpacity(0.5)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(app['icon'] as IconData,
                            size: 16, color: app['color'] as Color),
                        const SizedBox(width: 6),
                        Text(
                          app['name'] as String,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: Colors.black87,
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ],
          ),
        ),
      ],
    );
  }

  String _formatStatus(String status) {
    switch (status) {
      case 'pending':
        return 'Pending Payment';
      case 'completed':
        return 'Payment Completed';
      case 'failed':
        return 'Payment Failed';
      default:
        return 'Unknown Status';
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status) {
      case 'pending':
        return Icons.access_time;
      case 'completed':
        return Icons.check_circle;
      case 'failed':
        return Icons.error;
      default:
        return Icons.help;
    }
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'completed':
        return Colors.green;
      case 'failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  // Helper method to build QR image from data
  Widget _buildQrImageFromData() {
    if (_qrCodeData != null && _qrCodeData!.isNotEmpty) {
      print("[QR Payment] Building QR from data: $_qrCodeData");
      return Stack(
        alignment: Alignment.center,
        children: [
          QrImageView(
            data: _qrCodeData!,
            version: QrVersions.auto,
            size: 220,
            backgroundColor: Colors.white,
            foregroundColor: Colors.black,
            errorCorrectionLevel: QrErrorCorrectLevel.H,
            embeddedImage: const AssetImage('assets/images/logo.png'),
            embeddedImageStyle: const QrEmbeddedImageStyle(
              size: Size(40, 40),
            ),
          ),
          if (_paymentStatus == 'completed')
            Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                color: Colors.green.withOpacity(0.7),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Center(
                child: Icon(
                  Icons.check_circle_outline,
                  color: Colors.white,
                  size: 80,
                ),
              ),
            ),
          if (_paymentStatus == 'failed')
            Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                color: Colors.red.withOpacity(0.7),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Center(
                child: Icon(
                  Icons.error_outline,
                  color: Colors.white,
                  size: 80,
                ),
              ),
            ),
        ],
      );
    } else {
      print("[QR Payment] No QR data available");
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, color: Colors.red[400], size: 50),
            const SizedBox(height: 16),
            const Text(
              'QR Code not available',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Please try again or use another payment method',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _initQRPayment,
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              ),
              child: const Text('Try Again'),
            ),
          ],
        ),
      );
    }
  }
}
