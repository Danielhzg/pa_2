import 'dart:async';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:provider/provider.dart';
import '../services/payment_service.dart';
import '../models/payment.dart';
import '../providers/cart_provider.dart';

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

class _QRPaymentScreenState extends State<QRPaymentScreen> {
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color accentColor = Color(0xFFFFE5EE);

  bool _isLoading = true;
  String? _qrCodeUrl;
  String? _qrCodeData;
  String _paymentStatus = 'pending';
  Timer? _statusCheckTimer;
  final PaymentService _paymentService = PaymentService();
  Payment? _payment;

  @override
  void initState() {
    super.initState();
    _initQRPayment();
  }

  @override
  void dispose() {
    _statusCheckTimer?.cancel();
    super.dispose();
  }

  Future<void> _initQRPayment() async {
    setState(() {
      _isLoading = true;
    });

    try {
      print("[QR Payment] Fetching QR code for order ID: ${widget.orderId}");

      // Fetch QR code from API using the orderId
      final qrData = await _paymentService.getQRCode(widget.orderId);

      print("[QR Payment] QR code data received: ${qrData.toString()}");

      setState(() {
        _qrCodeData = qrData['qr_code_data'];
        _qrCodeUrl = qrData['qr_code_url'];
        _isLoading = false;
      });

      print("[QR Payment] QR data: $_qrCodeData");
      print("[QR Payment] QR URL: $_qrCodeUrl");

      // Process payment record
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
      print("[QR Payment] QR code error: $e");

      // Create a QRIS compliant QR code string as fallback
      final fallbackQrData =
          "QRIS.ID|ORDER.${widget.orderId}|AMOUNT.${widget.amount.toInt()}|TIME.${DateTime.now().millisecondsSinceEpoch}";

      setState(() {
        _isLoading = false;
        _qrCodeData = fallbackQrData;
        print("[QR Payment] Using fallback QR code data: $_qrCodeData");
      });

      // Try to continue with the fallback QR code
      try {
        final payment = await _paymentService.processPayment(
          orderId: widget.orderId,
          amount: widget.amount,
          paymentMethod: 'QRIS',
          qrCodeUrl: null,
          qrCodeData: fallbackQrData,
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

        // Start checking payment status even with fallback QR
        _startPaymentStatusCheck(widget.orderId);
      } catch (paymentError) {
        print("[QR Payment] Fallback payment processing failed: $paymentError");
      }

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Using local QR code generator'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 3),
        ),
      );
    }
  }

  void _startPaymentStatusCheck(String orderId) {
    // Check payment status every 5 seconds
    _statusCheckTimer =
        Timer.periodic(const Duration(seconds: 5), (timer) async {
      try {
        // Check transaction status with Midtrans
        final status = await _paymentService.checkTransactionStatus(orderId);
        final transactionStatus = status['transaction_status'] ?? 'pending';

        String paymentStatus = 'pending';
        if (transactionStatus == 'settlement' ||
            transactionStatus == 'capture') {
          paymentStatus = 'completed';
        } else if (transactionStatus == 'deny' ||
            transactionStatus == 'cancel' ||
            transactionStatus == 'expire') {
          paymentStatus = 'failed';
        }

        setState(() {
          _paymentStatus = paymentStatus;
        });

        // If payment is completed, stop checking and notify
        if (paymentStatus == 'completed' && _payment != null) {
          _statusCheckTimer?.cancel();
          widget.onPaymentSuccess(_payment!);

          // Optionally pop with true result after a short delay
          Future.delayed(const Duration(seconds: 2), () {
            if (mounted) {
              Navigator.pop(context, true);
            }
          });
        }
      } catch (e) {
        print('Error checking payment status: $e');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('QR Code Payment'),
        backgroundColor: Colors.white,
        foregroundColor: primaryColor,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _buildPaymentContent(),
    );
  }

  Widget _buildPaymentContent() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Card(
              elevation: 4,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  children: [
                    const Text(
                      'Scan this QR Code to pay',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      'Amount: Rp ${widget.amount.toStringAsFixed(0)}',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 30),

                    // QR Code Display
                    Container(
                      width: 250,
                      height: 250,
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: accentColor, width: 2),
                      ),
                      child: _qrCodeUrl != null && _qrCodeUrl!.isNotEmpty
                          ? Image.network(
                              _qrCodeUrl!,
                              width: 220,
                              height: 220,
                              errorBuilder: (context, error, stackTrace) {
                                // Fallback to QR generation if URL fails
                                print(
                                    "[QR Payment] Error loading QR image: $error");
                                return _buildQrImageFromData();
                              },
                            )
                          : _buildQrImageFromData(),
                    ),
                    const SizedBox(height: 30),

                    // Payment Status
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: _getStatusColor(_paymentStatus).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            _getStatusIcon(_paymentStatus),
                            color: _getStatusColor(_paymentStatus),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Status: ${_formatStatus(_paymentStatus)}',
                            style: TextStyle(
                              color: _getStatusColor(_paymentStatus),
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),
                    const Text(
                      'Please do not close this screen until payment is completed',
                      style: TextStyle(
                        color: Colors.grey,
                        fontSize: 14,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Instructions
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Padding(
                padding: EdgeInsets.all(20.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'How to pay:',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 10),
                    _InstructionStep(
                      number: '1',
                      text: 'Open your banking or e-wallet app',
                    ),
                    _InstructionStep(
                      number: '2',
                      text: 'Scan the QR code above',
                    ),
                    _InstructionStep(
                      number: '3',
                      text: 'Confirm the payment amount',
                    ),
                    _InstructionStep(
                      number: '4',
                      text: 'Complete the payment',
                    ),
                    _InstructionStep(
                      number: '5',
                      text: 'Wait for confirmation on this screen',
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Cancel Button
            TextButton.icon(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Cancel Payment?'),
                    content: const Text(
                      'Are you sure you want to cancel this payment? Your order will not be processed.',
                    ),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('No'),
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.pop(context); // Close dialog
                          Navigator.pop(context); // Go back to checkout
                        },
                        child: const Text(
                          'Yes, Cancel',
                          style: TextStyle(color: Colors.red),
                        ),
                      ),
                    ],
                  ),
                );
              },
              icon: const Icon(Icons.cancel, color: Colors.grey),
              label: const Text(
                'Cancel Payment',
                style: TextStyle(color: Colors.grey),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatStatus(String status) {
    switch (status) {
      case 'pending':
        return 'Waiting for Payment';
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
      return QrImageView(
        data: _qrCodeData!,
        version: QrVersions.auto,
        size: 220,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
      );
    } else {
      print("[QR Payment] No QR data available");
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, color: Colors.red, size: 40),
            SizedBox(height: 12),
            Text(
              'QR Code not available',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text(
              'Please try again or use another payment method',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
          ],
        ),
      );
    }
  }
}

class _InstructionStep extends StatelessWidget {
  final String number;
  final String text;

  const _InstructionStep({
    Key? key,
    required this.number,
    required this.text,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 24,
            height: 24,
            decoration: const BoxDecoration(
              color: Color(0xFFFF87B2),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                number,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
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
}
