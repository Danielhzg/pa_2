import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'dart:async';
import 'package:url_launcher/url_launcher.dart';

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
  bool _hasError = false;
  String _errorMessage = '';
  Timer? _timeoutTimer;
  bool _paymentProcessed = false;

  @override
  void initState() {
    super.initState();
    _initWebView();

    // Set a timeout in case the payment gateway doesn't load
    _timeoutTimer = Timer(const Duration(seconds: 30), () {
      if (_isLoading && mounted) {
        setState(() {
          _hasError = true;
          _isLoading = false;
          _errorMessage =
              'Payment gateway is taking too long to respond. Please try again.';
        });
      }
    });
  }

  @override
  void dispose() {
    _timeoutTimer?.cancel();
    super.dispose();
  }

  void _initWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0x00000000))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _hasError = false;
            });
            debugPrint('Loading URL: $url');
            _checkPaymentStatus(url);
          },
          onPageFinished: (String url) {
            _timeoutTimer?.cancel();
            setState(() {
              _isLoading = false;
            });
            debugPrint('Finished loading: $url');
            _checkPaymentStatus(url);
          },
          onWebResourceError: (WebResourceError error) {
            _timeoutTimer?.cancel();
            debugPrint('WebView error: ${error.description}');
            setState(() {
              _hasError = true;
              _isLoading = false;
              _errorMessage =
                  'Error loading payment page: ${error.description}';
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            // Check payment status on every navigation
            _checkPaymentStatus(request.url);

            // Handle external URLs like app deeplinks
            if (request.url.startsWith('intent://') ||
                request.url.startsWith('gojek://') ||
                request.url.startsWith('shopeeid://') ||
                request.url.startsWith('https://gojek.link') ||
                request.url.startsWith('https://app.midtrans.com')) {
              _launchExternalApp(request.url);
              return NavigationDecision.prevent;
            }

            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.redirectUrl));
  }

  Future<void> _launchExternalApp(String url) async {
    debugPrint('Launching external URL: $url');
    try {
      final Uri uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        debugPrint('Could not launch $url');
        // Show a message to the user
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('Cannot open $url')));
      }
    } catch (e) {
      debugPrint('Error launching URL: $e');
    }
  }

  void _checkPaymentStatus(String url) {
    // Avoid processing payment multiple times
    if (_paymentProcessed) return;

    debugPrint('Checking payment status for URL: $url');

    // Success status checks for Midtrans
    if (url.contains('transaction_status=capture') ||
        url.contains('transaction_status=settlement') ||
        url.contains('status_code=200') ||
        url.contains('success') ||
        url.contains('.midtrans.com/snap/v2/vtweb/transaction_status/ok') ||
        url.contains('payment/finish')) {
      _paymentProcessed = true;
      widget.onPaymentComplete(true);

      // Show success dialog and pop after confirmation
      if (mounted) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => _buildResultDialog(true),
        ).then((_) {
          Navigator.pop(context, true);
        });
      }
    }
    // Failure status checks
    else if (url.contains('transaction_status=deny') ||
        url.contains('transaction_status=cancel') ||
        url.contains('transaction_status=expire') ||
        url.contains('transaction_status=failure') ||
        url.contains('status_code=4') ||
        url.contains('status_code=5') ||
        url.contains(
            '.midtrans.com/snap/v2/vtweb/transaction_status/(cancel|deny|error)') ||
        url.contains('payment/(cancel|error|unfinish)')) {
      _paymentProcessed = true;
      widget.onPaymentComplete(false);

      // Show failure dialog and pop after confirmation
      if (mounted) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => _buildResultDialog(false),
        ).then((_) {
          Navigator.pop(context, false);
        });
      }
    }
  }

  Widget _buildResultDialog(bool success) {
    return AlertDialog(
      title: Text(
        success ? 'Payment Successful' : 'Payment Failed',
        style: TextStyle(
          color: success ? Colors.green : Colors.red,
          fontWeight: FontWeight.bold,
        ),
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            success ? Icons.check_circle_outline : Icons.error_outline,
            color: success ? Colors.green : Colors.red,
            size: 64,
          ),
          const SizedBox(height: 16),
          Text(
            success
                ? 'Your payment has been successfully processed!'
                : 'There was a problem processing your payment.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            'Transaction ID: ${widget.transactionId}',
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: Text(
            'OK',
            style: TextStyle(color: success ? Colors.green : Colors.red),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        // Show confirmation before leaving the payment page
        final result = await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Cancel Payment?'),
            content: const Text(
                'Are you sure you want to cancel this payment? Your order will not be processed.'),
            actions: [
              TextButton(
                child: const Text('No, Continue Payment'),
                onPressed: () => Navigator.of(context).pop(false),
              ),
              TextButton(
                child: const Text('Yes, Cancel',
                    style: TextStyle(color: Colors.red)),
                onPressed: () => Navigator.of(context).pop(true),
              ),
            ],
          ),
        );

        if (result == true) {
          widget.onPaymentComplete(false);
          return true;
        }
        return false;
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Payment Gateway'),
          backgroundColor: Colors.white,
          foregroundColor: const Color(0xFFFF87B2),
          elevation: 2,
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: () {
                _controller.reload();
                setState(() {
                  _isLoading = true;
                  _hasError = false;
                });
              },
            ),
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
                            'You are being redirected to the secure payment gateway.'),
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
            // The WebView
            _hasError
                ? _buildErrorView()
                : WebViewWidget(controller: _controller),

            // Loading Indicator
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
      ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              color: Colors.red,
              size: 64,
            ),
            const SizedBox(height: 16),
            Text(
              _errorMessage,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 16),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _isLoading = true;
                  _hasError = false;
                });
                _controller.reload();
              },
              child: const Text('Try Again'),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () {
                widget.onPaymentComplete(false);
                Navigator.pop(context, false);
              },
              child: const Text('Cancel Payment'),
            ),
          ],
        ),
      ),
    );
  }
}
