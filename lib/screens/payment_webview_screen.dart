import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

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
              widget.onPaymentComplete(true);

              // Pop after short delay to give user feedback that payment is successful
              Future.delayed(const Duration(seconds: 1), () {
                if (mounted) {
                  Navigator.pop(context, true);
                }
              });
            } else if (url.contains('payment_failed') ||
                url.contains('transaction_status=deny') ||
                url.contains('transaction_status=cancel') ||
                url.contains('transaction_status=expire')) {
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
