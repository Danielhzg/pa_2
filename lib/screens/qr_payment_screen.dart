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
      // Use Snap Token to generate QRIS Code
      // For demo, we'll generate a QR code with the Snap Token as the content
      // In a real implementation, this data would be used to display the actual QRIS
      final qrData =
          "MIDTRANS|${widget.snapToken}|${widget.orderId}|${widget.amount}";

      setState(() {
        _qrCodeData = qrData;
        _isLoading = false;
      });

      // Process payment record
      final payment = await _paymentService.processPayment(
        orderId: widget.orderId,
        amount: widget.amount,
        paymentMethod: 'QRIS',
        qrCodeUrl: null, // With Midtrans, the QR is generated in the backend
      );

      setState(() {
        _payment = payment;
      });

      // Start checking payment status periodically
      _startPaymentStatusCheck(widget.orderId);
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to initialize QR payment: $e'),
          backgroundColor: Colors.red,
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
                      child: _qrCodeData != null
                          ? QrImageView(
                              data: _qrCodeData!,
                              version: QrVersions.auto,
                              size: 220,
                              backgroundColor: Colors.white,
                              foregroundColor: Colors.black,
                            )
                          : const Center(
                              child: Text('QR Code not available'),
                            ),
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
