import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:provider/provider.dart';
import '../services/payment_service.dart';
import '../services/order_service.dart';

class PaymentWebViewScreen extends StatefulWidget {
  final String redirectUrl;
  final String transactionId;
  final Function(bool status) onPaymentComplete;

  const PaymentWebViewScreen({
    super.key,
    required this.redirectUrl,
    required this.transactionId,
    required this.onPaymentComplete,
  });

  @override
  State<PaymentWebViewScreen> createState() => _PaymentWebViewScreenState();
}

class _PaymentWebViewScreenState extends State<PaymentWebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
            debugPrint('Loading URL: $url');
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
            debugPrint('Finished loading: $url');

            // Check for success or failure redirects
            if (url.contains('payment_successful') ||
                url.contains('callback-finish') ||
                url.contains('transaction_status=capture') ||
                url.contains('transaction_status=settlement')) {
              debugPrint('Payment successful detected in WebView');
              _handlePaymentSuccess();
            } else if (url.contains('payment_failed') ||
                url.contains('transaction_status=deny') ||
                url.contains('transaction_status=cancel') ||
                url.contains('transaction_status=expire')) {
              debugPrint('Payment failed detected in WebView');
              widget.onPaymentComplete(false);

              // Pop after short delay to give user feedback
              Future.delayed(const Duration(seconds: 1), () {
                if (mounted) {
                  Navigator.pop(context, false);
                }
              });
            }
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('WebView error: ${error.description}');
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.redirectUrl));
  }

  // Handle payment success and update order status
  Future<void> _handlePaymentSuccess() async {
    try {
      debugPrint(
          'Handling payment success for transaction: ${widget.transactionId}');

      // Show loading indicator
      setState(() {
        _isLoading = true;
      });

      // Get payment service
      final paymentService = PaymentService();

      // First try to simulate payment success via webhook to trigger backend logic
      try {
        final simulateResult =
            await paymentService.simulatePaymentSuccess(widget.transactionId);
        debugPrint('Payment simulation result: $simulateResult');

        // If simulation was successful, the backend should have already updated the order
        if (simulateResult['success'] == true) {
          debugPrint(
              'Payment simulation successful - order should be updated by backend');
        }
      } catch (e) {
        debugPrint('Payment simulation failed: $e');
      }

      // Wait a moment for backend processing
      await Future.delayed(const Duration(milliseconds: 500));

      // Update order status to processing after successful payment (as fallback)
      final updateResult = await paymentService.updateOrderStatus(
          widget.transactionId,
          'processing', // New order status
          'paid' // New payment status
          );

      debugPrint('Order status update result: $updateResult');

      // Get order service and refresh orders
      if (mounted) {
        final orderService = Provider.of<OrderService>(context, listen: false);
        await orderService.refreshOrders();
      }

      // Call the callback
      widget.onPaymentComplete(true);

      // Show success message
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content:
                Text('Pembayaran berhasil! Status pesanan telah diperbarui.'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }

      // Pop after short delay to give user feedback that payment is successful
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) {
          Navigator.pop(context, true);
        }
      });
    } catch (e) {
      debugPrint('Error handling payment success: $e');

      // Still call the callback even if update fails
      widget.onPaymentComplete(true);

      // Show warning message
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
                'Pembayaran berhasil, namun status pesanan akan diperbarui secara otomatis.'),
            backgroundColor: Colors.orange,
            duration: Duration(seconds: 3),
          ),
        );
      }

      // Pop after delay
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) {
          Navigator.pop(context, true);
        }
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment Gateway'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFFFF87B2),
        elevation: 2,
        actions: [
          IconButton(
            icon: const Icon(Icons.info_outline),
            onPressed: () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Secure Payment'),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                          'You are being redirected to Midtrans secure payment gateway.'),
                      const SizedBox(height: 8),
                      const Text(
                          'Complete your payment using your preferred method.'),
                      const SizedBox(height: 8),
                      Text('Your transaction ID: ${widget.transactionId}'),
                    ],
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(),
                      child: const Text('Close'),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_isLoading)
            Container(
              color: Colors.white.withOpacity(0.8),
              child: const Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(
                      color: Color(0xFFFF87B2),
                    ),
                    SizedBox(height: 16),
                    Text(
                      'Loading payment gateway...',
                      style: TextStyle(color: Color(0xFFFF87B2)),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}
