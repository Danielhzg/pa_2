import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';
import '../models/delivery_address.dart';
import '../models/cart_item.dart';
import 'package:flutter/foundation.dart';
import 'midtrans_service.dart';
import 'package:flutter/material.dart';
import '../models/order.dart';
import 'auth_service.dart';
import 'notification_service.dart';
import 'order_service.dart';
import '../utils/constants.dart';
import 'dart:math' as math;

class PaymentConfig {
  final bool enableSimulation;

  const PaymentConfig({
    this.enableSimulation = true,
  });
}

class PaymentService {
  // Updated Midtrans API URLs dengan URL yang benar
  final String snapUrl = 'https://app.sandbox.midtrans.com/snap/v1';
  final String snapUrlFallback =
      'https://app.sandbox.midtrans.com/snap/v1'; // Menggunakan URL normal sebagai fallback
  final String coreApiUrl = 'https://api.sandbox.midtrans.com/v2';
  final String coreApiUrlFallback =
      'https://api.sandbox.midtrans.com/v2'; // Menggunakan URL normal sebagai fallback
  final String clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';
  final String serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry';
  // Update API URL using ngrok URL
  final String apiBaseUrl =
      'https://dec8-114-122-41-11.ngrok-free.app/api'; // Primary ngrok URL - Laravel API URL

  // Singleton instance
  static final PaymentService _instance = PaymentService._internal();
  factory PaymentService() => _instance;
  PaymentService._internal();

  bool _initialized = false;
  bool _useIpFallback = false; // Flag to use IP address instead of domain
  bool _useSimulationMode = false; // Flag untuk mode simulasi

  // Midtrans service instance
  final MidtransService _midtransService = MidtransService();

  final PaymentConfig _config = const PaymentConfig(enableSimulation: true);
  OrderService? _orderService;

  // Initialize payment service and verify connectivity
  Future<bool> initialize() async {
    if (_initialized) return true;

    debugPrint('Initializing PaymentService...');
    try {
      // Check basic internet connectivity
      final hasInternet = await checkInternetConnection();
      if (!hasInternet) {
        debugPrint('No internet connection detected!');
        _useSimulationMode = true;
        return false;
      }

      // Verify connectivity to Midtrans API
      final midtransConnected = await pingMidtransAPI();
      if (midtransConnected) {
        debugPrint('Successfully connected to Midtrans API!');
        _useIpFallback = false;
      } else {
        debugPrint(
            'WARNING: Could not connect to Midtrans API with domain. Trying IP fallback...');
        // Try with IP address fallback
        final fallbackConnected = await pingMidtransAPIFallback();
        if (fallbackConnected) {
          _useIpFallback = true;
          debugPrint('Successfully connected to Midtrans API via IP fallback!');
        } else {
          debugPrint(
              'WARNING: All connection attempts to Midtrans failed. Activating simulation mode.');
          _useSimulationMode = true;
        }
      }

      _initialized = true;
      return true;
    } catch (e) {
      debugPrint('Error initializing PaymentService: $e');
      _useSimulationMode = true;
      return false;
    }
  }

  // Check basic internet connectivity
  Future<bool> checkInternetConnection() async {
    try {
      final result = await InternetAddress.lookup('google.com');
      return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
    } on SocketException catch (_) {
      return false;
    } catch (e) {
      debugPrint('Error checking internet connection: $e');
      return false;
    }
  }

  // Method to ping Midtrans API using domain name
  Future<bool> pingMidtransAPI() async {
    try {
      debugPrint('Pinging Midtrans API to verify connectivity...');

      // Use a simple request to test connectivity
      final String authString = base64.encode(utf8.encode('$serverKey:'));

      // Try with a shorter timeout
      final coreResponse = await http.get(
        Uri.parse('$coreApiUrl/ping'),
        headers: {
          'Authorization': 'Basic $authString',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 3));

      debugPrint('Core API ping response: ${coreResponse.statusCode}');
      return coreResponse.statusCode < 500;
    } on SocketException catch (e) {
      debugPrint('Socket error pinging Midtrans API: $e');
      return false;
    } on TimeoutException catch (e) {
      debugPrint('Timeout pinging Midtrans API: $e');
      return false;
    } catch (e) {
      debugPrint('Error pinging Midtrans API: $e');
      return false;
    }
  }

  // Method to ping Midtrans API using IP address fallback
  Future<bool> pingMidtransAPIFallback() async {
    try {
      debugPrint('Pinging Midtrans API via IP fallback...');

      // Use a simple request to test connectivity to the IP address
      final String authString = base64.encode(utf8.encode('$serverKey:'));

      // Try with a shorter timeout
      final coreResponse = await http.get(
        Uri.parse('$coreApiUrlFallback/ping'),
        headers: {
          'Authorization': 'Basic $authString',
          'Accept': 'application/json',
          'Host':
              'api.sandbox.midtrans.com', // Add host header for proper routing
        },
      ).timeout(const Duration(seconds: 3));

      debugPrint('Core API fallback ping response: ${coreResponse.statusCode}');
      return coreResponse.statusCode < 500;
    } catch (e) {
      debugPrint('Error pinging Midtrans API via fallback: $e');
      return false;
    }
  }

  // Fetch available payment methods from the API
  Future<Map<String, dynamic>> getPaymentMethods() async {
    try {
      // Ensure we're initialized
      if (!_initialized) {
        await initialize();
      }

      final url = Uri.parse('$apiBaseUrl/payment-methods');
      final response = await http.get(
        url,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        // If API fails, return hardcoded payment methods
        return _getDefaultPaymentMethods();
      }
    } catch (e) {
      // Return hardcoded payment methods on error
      debugPrint('Error loading payment methods: $e');
      return _getDefaultPaymentMethods();
    }
  }

  // Default payment methods when API fails
  Map<String, dynamic> _getDefaultPaymentMethods() {
    return {
      'success': true,
      'data': [
        {
          'code': 'qris',
          'name': 'QRIS (QR Code)',
          'logo': 'qris.png',
        },
        {
          'code': 'credit_card',
          'name': 'Credit Card',
          'logo': 'credit_card.png',
        },
        {
          'code': 'bca_va',
          'name': 'BCA Virtual Account',
          'logo': 'bca.png',
        },
        {
          'code': 'bni_va',
          'name': 'BNI Virtual Account',
          'logo': 'bni.png',
        },
        {
          'code': 'bri_va',
          'name': 'BRI Virtual Account',
          'logo': 'bri.png',
        },
        {
          'code': 'gopay',
          'name': 'GoPay',
          'logo': 'gopay.png',
        },
      ]
    };
  }

  // Create a payment with Midtrans and save order to Laravel API
  Future<Map<String, dynamic>> createPayment({
    required List<Map<String, dynamic>> items,
    required String customerId,
    required double shippingCost,
    required String shippingAddress,
    required String phoneNumber,
    required String paymentMethod,
  }) async {
    try {
      debugPrint('Starting payment creation process...');
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userData = prefs.getString('user_data');
      final userEmail = userData != null
          ? jsonDecode(userData)['email']
          : 'customer@example.com';

      if (token == null) {
        debugPrint('No authentication token found');
        return {
          'success': false,
          'message': 'Authentication required',
        };
      }

      // Calculate order amounts
      double totalAmount = 0;
      List<Map<String, dynamic>> itemDetails = [];

      for (var item in items) {
        final price = item['price'] is int
            ? item['price'].toDouble()
            : double.parse(item['price'].toString());
        final quantity = item['quantity'] is int
            ? item['quantity']
            : int.parse(item['quantity'].toString());

        totalAmount += price * quantity;

        itemDetails.add({
          'id': item['id'].toString(),
          'name': item['name'],
          'price': price.toInt(),
          'quantity': quantity,
        });
      }

      // Add shipping cost
      totalAmount += shippingCost;
      final subtotal = totalAmount - shippingCost;

      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      debugPrint('Generated order ID: $orderId');

      // Parse shipping address into JSON format if needed
      Map<String, dynamic> deliveryAddressJson = {
        'address': shippingAddress,
        'phone': phoneNumber,
      };

      // Prepare order payload
      final orderPayload = {
        'order_id': orderId,
        'items': itemDetails,
        'deliveryAddress': deliveryAddressJson,
        'shipping_address': shippingAddress,
        'phone_number': phoneNumber,
        'subtotal': subtotal,
        'shippingCost': shippingCost,
        'shipping_cost': shippingCost,
        'total': totalAmount,
        'total_amount': totalAmount,
        'paymentMethod': paymentMethod,
        'payment_method': paymentMethod,
        'status': 'waiting_for_payment',
      };

      debugPrint(
          'Sending order creation request to API: $apiBaseUrl/orders/create');
      debugPrint('Order payload: ${jsonEncode(orderPayload)}');

      // Create order in Laravel API with timeout and retry
      http.Response? orderResponse;
      int retryCount = 0;
      const maxRetries = 2;

      while (orderResponse == null && retryCount <= maxRetries) {
        try {
          orderResponse = await http
              .post(
                Uri.parse('$apiBaseUrl${ApiConstants.ordersCreate}'),
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': 'Bearer $token',
                  'Accept': 'application/json',
                },
                body: jsonEncode(orderPayload),
              )
              .timeout(const Duration(seconds: 10));
        } catch (e) {
          retryCount++;
          debugPrint('Order creation attempt $retryCount failed: $e');

          if (retryCount > maxRetries) {
            debugPrint(
                'All order creation attempts failed, using local order processing');
            // If API is unreachable, process locally and proceed with Midtrans
            break;
          }

          // Wait before retrying
          await Future.delayed(const Duration(seconds: 1));
        }
      }

      if (orderResponse != null) {
        debugPrint('Order API response code: ${orderResponse.statusCode}');
        debugPrint('Order API response body: ${orderResponse.body}');

        if (orderResponse.statusCode != 200 &&
            orderResponse.statusCode != 201) {
          debugPrint(
              'Failed to create order through API: ${orderResponse.body}');
          // Continue with Midtrans anyway, we'll handle the order later
        }
      } else {
        debugPrint(
            'No response from order creation API, proceeding with Midtrans');
      }

      // Prepare customer details for Midtrans
      final names = shippingAddress.split(',')[0].split(' ');
      final firstName = names.isNotEmpty ? names[0] : 'Customer';
      final lastName = names.length > 1 ? names.sublist(1).join(' ') : '';

      // Create transaction in Midtrans
      final String authString = base64.encode(utf8.encode('$serverKey:'));

      // Log yang jelas untuk troubleshooting
      debugPrint('Sending Midtrans request to: $snapUrl/transactions');
      debugPrint('Midtrans Auth: Basic $authString');

      // Payload Midtrans dengan format yang benar
      final midtransPayload = {
        'transaction_details': {
          'order_id': orderId,
          'gross_amount': totalAmount.toInt(),
        },
        'customer_details': {
          'first_name': firstName,
          'last_name': lastName,
          'email': userEmail,
          'phone': phoneNumber,
          'billing_address': {
            'address': shippingAddress,
          },
          'shipping_address': {
            'address': shippingAddress,
          },
        },
        'item_details': itemDetails,
        'enabled_payments': [
          'credit_card',
          'bca_va',
          'bni_va',
          'bri_va',
          'echannel',
          'permata_va',
          'gopay',
          'shopeepay',
          'qris',
        ],
      };

      debugPrint('Midtrans payload: ${jsonEncode(midtransPayload)}');

      // Send request to Midtrans with timeout
      http.Response? midtransResponse;
      retryCount = 0;

      while (midtransResponse == null && retryCount <= maxRetries) {
        try {
          midtransResponse = await http
              .post(
                Uri.parse('$snapUrl/transactions'),
                headers: {
                  'Authorization': 'Basic $authString',
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                },
                body: jsonEncode(midtransPayload),
              )
              .timeout(const Duration(seconds: 15));
        } catch (e) {
          retryCount++;
          debugPrint('Midtrans request attempt $retryCount failed: $e');

          if (retryCount > maxRetries) {
            debugPrint('All Midtrans attempts failed, using simulation mode');
            // Fall back to simulation
            final simulationResponse =
                _generateSimulationResponse(orderId, totalAmount.toInt());

            return {
              'success': true,
              'simulation': true,
              'data': simulationResponse,
            };
          }

          // Wait before retrying
          await Future.delayed(const Duration(seconds: 1));
        }
      }

      if (midtransResponse == null) {
        debugPrint(
            'No response from Midtrans, falling back to simulation mode');
        final simulationResponse =
            _generateSimulationResponse(orderId, totalAmount.toInt());

        return {
          'success': true,
          'simulation': true,
          'data': simulationResponse,
        };
      }

      debugPrint('Midtrans response status: ${midtransResponse.statusCode}');
      debugPrint('Midtrans response body: ${midtransResponse.body}');

      if (midtransResponse.statusCode == 201 ||
          midtransResponse.statusCode == 200) {
        final midtransData = jsonDecode(midtransResponse.body);

        // Try to update order payment status if API is available
        try {
          await http
              .post(
                Uri.parse(
                    '$apiBaseUrl${ApiConstants.orders}/update-payment-status'),
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': 'Bearer $token',
                  'Accept': 'application/json',
                },
                body: jsonEncode({
                  'order_id': orderId,
                  'payment_token': midtransData['token'],
                  'redirect_url': midtransData['redirect_url'],
                }),
              )
              .timeout(const Duration(seconds: 5));
        } catch (e) {
          // Ignore errors here - it's not critical
          debugPrint('Failed to update order payment status: $e');
        }

        return {
          'success': true,
          'data': {
            'order_id': orderId,
            'redirect_url': midtransData['redirect_url'],
            'token': midtransData['token'],
          },
        };
      } else {
        debugPrint('Midtrans error: ${midtransResponse.body}');

        // Fallback to simulation mode if Midtrans fails
        final simulationResponse =
            _generateSimulationResponse(orderId, totalAmount.toInt());

        return {
          'success': true,
          'simulation': true,
          'data': simulationResponse,
        };
      }
    } catch (e) {
      debugPrint('Payment error: $e');
      // Even if all fails, return a simulation response to let the user continue
      final orderId = 'ORDER-${DateTime.now().millisecondsSinceEpoch}';
      final simulationResponse = _generateSimulationResponse(orderId, 0);

      return {
        'success': true,
        'simulation': true,
        'message': 'Using simulation mode due to error: $e',
        'data': simulationResponse,
      };
    }
  }

  // Generate simulation response when Midtrans fails
  Map<String, dynamic> _generateSimulationResponse(String orderId, int amount) {
    return {
      'order_id': orderId,
      'redirect_url':
          'https://simulator.sandbox.midtrans.com/snap/v2/vtweb/$orderId',
      'token': 'simulation-token-${DateTime.now().millisecondsSinceEpoch}',
    };
  }

  // Create a payment transaction
  Future<Map<String, dynamic>> createTransaction({
    required List<CartItem> items,
    required DeliveryAddress address,
    required double totalAmount,
    required double shippingCost,
    required String paymentMethod,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final userData = prefs.getString('user_data');
    final userId = userData != null ? jsonDecode(userData)['id'] : 'guest_user';

    final String orderId = const Uuid().v4();
    final String authString = base64.encode(utf8.encode('$serverKey:'));

    final url = Uri.parse('$coreApiUrl/charge');
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Basic $authString',
    };

    final Map<String, dynamic> customerDetails = {
      'first_name': address.name.split(' ').first,
      'last_name': address.name.split(' ').length > 1
          ? address.name.split(' ').sublist(1).join(' ')
          : '',
      'email':
          'customer@example.com', // In production, use actual customer email
      'phone': address.phone,
      'billing_address': {
        'first_name': address.name.split(' ').first,
        'last_name': address.name.split(' ').length > 1
            ? address.name.split(' ').sublist(1).join(' ')
            : '',
        'phone': address.phone,
        'address': address.address,
        'city': address.city,
        'postal_code': address.postalCode,
        'country_code': 'IDN'
      },
      'shipping_address': {
        'first_name': address.name.split(' ').first,
        'last_name': address.name.split(' ').length > 1
            ? address.name.split(' ').sublist(1).join(' ')
            : '',
        'phone': address.phone,
        'address': address.address,
        'city': address.city,
        'postal_code': address.postalCode,
        'country_code': 'IDN'
      }
    };

    final List<Map<String, dynamic>> itemDetails = items
        .map((item) => {
              'id': item.productId,
              'name': item.name,
              'price': item.price.toInt(),
              'quantity': item.quantity,
            })
        .toList();

    // Add shipping as a separate item
    itemDetails.add({
      'id': 'shipping-cost',
      'name': 'Shipping Cost',
      'price': shippingCost.toInt(),
      'quantity': 1,
    });

    Map<String, dynamic> requestBody = {
      'payment_type': _getPaymentType(paymentMethod),
      'transaction_details': {
        'order_id': orderId,
        'gross_amount': totalAmount.toInt(),
      },
      'customer_details': customerDetails,
      'item_details': itemDetails,
      'callbacks': {
        'finish': 'https://bloombouquet.app/callback-finish',
      }
    };

    // Add specific parameters based on payment method
    requestBody.addAll(_getPaymentSpecificParams(paymentMethod));

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(requestBody),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);

        // Update order payment status instead of creating new order
        await _updateOrderPaymentStatus(
          orderId: orderId,
          paymentStatus: 'pending',
          transactionId: responseData['transaction_id'] ?? '',
        );

        return responseData;
      } else {
        throw Exception('Failed to create transaction: ${response.body}');
      }
    } catch (e) {
      throw Exception('Error creating transaction: $e');
    }
  }

  // Get payment type based on selected payment method
  String _getPaymentType(String paymentMethod) {
    switch (paymentMethod) {
      case 'Credit Card':
        return 'credit_card';
      case 'Bank Transfer':
        return 'bank_transfer';
      case 'E-Wallet':
        return 'gopay';
      case 'Cash on Delivery':
        return 'cstore';
      default:
        return 'credit_card';
    }
  }

  // Get specific parameters for different payment methods
  Map<String, dynamic> _getPaymentSpecificParams(String paymentMethod) {
    switch (paymentMethod) {
      case 'Bank Transfer':
        return {
          'bank_transfer': {
            'bank': 'bca',
          }
        };
      case 'E-Wallet':
        return {
          'gopay': {
            'enable_callback': true,
          }
        };
      case 'Cash on Delivery':
        return {
          'cstore': {
            'store': 'indomaret',
            'message': 'Silakan bayar di Indomaret terdekat',
          }
        };
      default:
        return {
          'credit_card': {
            'secure': true,
            'save_card': false,
          }
        };
    }
  }

  // Update order payment status (no longer creates new orders)
  Future<void> _updateOrderPaymentStatus({
    required String orderId,
    required String paymentStatus,
    required String transactionId,
  }) async {
    try {
      // Get authentication token
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        debugPrint('No auth token available for payment status update');
        return;
      }

      final url = Uri.parse(
          '${ApiConstants.getBaseUrl()}/api/orders/$orderId/payment-status');
      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };

      final body = {
        'payment_status': paymentStatus,
        'transaction_id': transactionId,
      };

      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(body),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        debugPrint('Payment status updated successfully for order: $orderId');
      } else {
        debugPrint(
            'Failed to update payment status: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      debugPrint('Error updating payment status: $e');
    }
  }

  // Get Midtrans payment methods
  Future<List<Map<String, dynamic>>> getMidtransPaymentMethods() async {
    try {
      // You can fetch this from API or return static list
      return [
        {
          'code': 'credit_card',
          'name': 'Credit Card',
          'logo': 'credit_card.png',
        },
        {
          'code': 'bca_va',
          'name': 'BCA Virtual Account',
          'logo': 'bca.png',
        },
        {
          'code': 'bni_va',
          'name': 'BNI Virtual Account',
          'logo': 'bni.png',
        },
        {
          'code': 'bri_va',
          'name': 'BRI Virtual Account',
          'logo': 'bri.png',
        },
        {
          'code': 'gopay',
          'name': 'GoPay',
          'logo': 'gopay.png',
        },
        {
          'code': 'shopeepay',
          'name': 'ShopeePay',
          'logo': 'shopeepay.png',
        },
        {
          'code': 'qris',
          'name': 'QRIS',
          'logo': 'qris.png',
        },
      ];
    } catch (e) {
      debugPrint('Error loading Midtrans payment methods: $e');
      throw Exception('Failed to load payment methods');
    }
  }

  // Get Midtrans snap token for transaction
  Future<Map<String, dynamic>> getMidtransSnapToken({
    required List<Map<String, dynamic>> items,
    required String customerId,
    required double shippingCost,
    required String shippingAddress,
    required String phoneNumber,
    required String email,
    String? selectedBank,
  }) async {
    try {
      // First, check for internet connection
      final hasInternet = await checkInternetConnection();
      if (!hasInternet) {
        return {
          'success': false,
          'message':
              'No internet connection. Please check your network and try again.',
        };
      }

      // Initialize payment service if not already initialized
      if (!_initialized) {
        await initialize();
      }

      // Calculate total amount
      double totalAmount = 0;
      List<Map<String, dynamic>> itemDetails = [];

      for (var item in items) {
        final price = item['price'] is int
            ? item['price'].toDouble()
            : double.parse(item['price'].toString());
        final quantity = item['quantity'] is int
            ? item['quantity']
            : int.parse(item['quantity'].toString());

        totalAmount += price * quantity;

        itemDetails.add({
          'id': item['id'].toString(),
          'name': item['name'],
          'price': price.toInt(),
          'quantity': quantity,
        });
      }

      // Add shipping cost to total
      totalAmount += shippingCost;
      itemDetails.add({
        'id': 'shipping',
        'name': 'Shipping Cost',
        'price': shippingCost.toInt(),
        'quantity': 1,
      });

      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      // Prepare customer details
      final names = shippingAddress.split(',')[0].split(' ');
      final firstName = names.isNotEmpty ? names[0] : 'Customer';
      final lastName = names.length > 1 ? names.sublist(1).join(' ') : '';

      // Get auth token for API calls
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // Order should already exist from checkout process
      // No need to create order here - just process payment
      debugPrint('Processing payment for existing order: $orderId');

      // Prepare authentication for Midtrans
      final String authString = base64.encode(utf8.encode('$serverKey:'));

      // Choose URL based on connectivity status (domain or IP fallback)
      final String snapApiUrl = _useIpFallback ? snapUrlFallback : snapUrl;
      final String coreApiEndpoint =
          _useIpFallback ? coreApiUrlFallback : coreApiUrl;

      // Prepare headers with host header if using IP fallback
      final Map<String, String> headers = {
        'Authorization': 'Basic $authString',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      // Add host header if using IP fallback
      if (_useIpFallback) {
        headers['Host'] = selectedBank != null
            ? 'api.sandbox.midtrans.com'
            : 'app.sandbox.midtrans.com';
      }

      // Decide which endpoint to use based on payment method
      Uri endpointUrl;
      Map<String, dynamic> requestBody = {};

      if (selectedBank != null && selectedBank.isNotEmpty) {
        // For VA payments, use Core API direct charge
        endpointUrl = Uri.parse('$coreApiEndpoint/charge');

        requestBody = {
          'payment_type': 'bank_transfer',
          'transaction_details': {
            'order_id': orderId,
            'gross_amount': totalAmount.toInt(),
          },
          'customer_details': {
            'first_name': firstName,
            'last_name': lastName,
            'email': email,
            'phone': phoneNumber,
            'billing_address': {
              'address': shippingAddress,
            },
            'shipping_address': {
              'address': shippingAddress,
            },
          },
          'item_details': itemDetails,
          'bank_transfer': {
            'bank': selectedBank.toLowerCase(),
          },
        };
      } else {
        // For other payment methods, use Snap
        endpointUrl = Uri.parse('$snapApiUrl/transactions');

        requestBody = {
          'transaction_details': {
            'order_id': orderId,
            'gross_amount': totalAmount.toInt(),
          },
          'customer_details': {
            'first_name': firstName,
            'last_name': lastName,
            'email': email,
            'phone': phoneNumber,
            'billing_address': {
              'address': shippingAddress,
            },
            'shipping_address': {
              'address': shippingAddress,
            },
          },
          'item_details': itemDetails,
          'enabled_payments': [
            'credit_card',
            'bca_va',
            'bni_va',
            'bri_va',
            'gopay',
            'shopeepay',
            'qris',
          ],
        };
      }

      debugPrint('Making API request to Midtrans...');
      debugPrint(
          'Using ${_useIpFallback ? "IP fallback" : "domain"} URL: ${endpointUrl.toString()}');

      // Make API request to Midtrans with shorter timeout
      final response = await http
          .post(
            endpointUrl,
            headers: headers,
            body: jsonEncode(requestBody),
          )
          .timeout(const Duration(seconds: 10));

      debugPrint('Midtrans API URL: ${endpointUrl.toString()}');
      debugPrint('Midtrans response code: ${response.statusCode}');
      debugPrint('Midtrans response body: ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);

        // Handle VA payment specific response
        if (selectedBank != null) {
          String? vaNumber;

          if (selectedBank.toLowerCase() == 'bca') {
            vaNumber = responseData['va_numbers']?[0]?['va_number'];
          } else if (selectedBank.toLowerCase() == 'bni') {
            vaNumber = responseData['va_numbers']?[0]?['va_number'];
          } else if (selectedBank.toLowerCase() == 'bri') {
            vaNumber = responseData['va_numbers']?[0]?['va_number'];
          } else if (selectedBank.toLowerCase() == 'permata') {
            vaNumber = responseData['permata_va_number'];
          } else {
            vaNumber = responseData['payment_code'];
          }

          return {
            'success': true,
            'data': {
              'order_id': orderId,
              'transaction_id': responseData['transaction_id'] ?? '',
              'va_number': vaNumber,
              'bank': selectedBank,
              'payment_type': responseData['payment_type'] ?? 'bank_transfer',
              'status_code': responseData['status_code'] ?? '201',
            },
          };
        } else {
          // Handle SNAP response
          return {
            'success': true,
            'data': {
              'order_id': orderId,
              'redirect_url': responseData['redirect_url'] ?? '',
              'token': responseData['token'] ?? '',
            },
          };
        }
      } else {
        debugPrint('Midtrans API error: ${response.body}');
        return {
          'success': false,
          'message': 'Failed to create payment: ${response.statusCode}',
        };
      }
    } on SocketException catch (e) {
      debugPrint('Network error: $e');
      return {
        'success': false,
        'message':
            'Connection error: Unable to reach the payment server. Please check your internet connection and try again.',
      };
    } on TimeoutException catch (e) {
      debugPrint('Request timed out: $e');
      return {
        'success': false,
        'message':
            'Request timed out. The payment server is taking too long to respond. Please try again later.',
      };
    } catch (e) {
      debugPrint('Error creating Midtrans payment: $e');
      return {
        'success': false,
        'message': 'Error: $e',
      };
    }
  }

  // Get QR Code for payment
  Future<Map<String, dynamic>> getQRCode(String orderId,
      {required double amount}) async {
    try {
      // Ensure we're initialized
      if (!_initialized) {
        await initialize();
      }

      // If in simulation mode, return simulated QR code
      if (_useSimulationMode) {
        return {
          'success': true,
          'qr_code_data':
              'SIMULATION-QRIS-${DateTime.now().millisecondsSinceEpoch}',
          'qr_code_url':
              'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
          'expiry_time':
              DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
          'simulation': true,
        };
      }

      // Get user data for the transaction
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      final userEmail = userData != null
          ? jsonDecode(userData)['email']
          : 'customer@example.com';
      final userName =
          userData != null ? jsonDecode(userData)['name'] : 'Customer';

      // Create transaction with QRIS payment method
      final qrisTransaction = await _midtransService.createTransaction(
        orderId: orderId,
        grossAmount: amount.toInt(),
        firstName: userName.split(' ').first,
        lastName:
            userName.split(' ').length > 1 ? userName.split(' ').last : '',
        email: userEmail,
        phone: '08123456789',
        items: [
          {
            'id': '1',
            'price': amount,
            'quantity': 1,
            'name': 'Order Payment',
          }
        ],
        paymentMethod: 'qris',
      );

      if (qrisTransaction.containsKey('qr_string') ||
          qrisTransaction.containsKey('qr_code_url')) {
        return {
          'success': true,
          'qr_code_data': qrisTransaction['qr_string'] ?? '',
          'qr_code_url': qrisTransaction['qr_code_url'] ?? '',
          'expiry_time': qrisTransaction['expiry_time'] ?? '',
        };
      } else {
        debugPrint(
            'QRIS transaction creation failed: ${qrisTransaction.toString()}');
        // Fallback to simulation
        _useSimulationMode = true;
        return {
          'success': true,
          'qr_code_data':
              'SIMULATION-QRIS-${DateTime.now().millisecondsSinceEpoch}',
          'qr_code_url':
              'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
          'expiry_time':
              DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
          'simulation': true,
        };
      }
    } catch (e) {
      debugPrint('Error generating QR code: $e');
      // Activate simulation mode for future requests
      _useSimulationMode = true;
      // Return simulated QR code on error
      return {
        'success': true,
        'qr_code_data':
            'SIMULATION-QRIS-${DateTime.now().millisecondsSinceEpoch}',
        'qr_code_url':
            'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
        'expiry_time':
            DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
        'simulation': true,
      };
    }
  }

  // Process payment
  Future<Map<String, dynamic>> processPayment({
    required Order order,
    required String paymentMethod,
    required BuildContext context,
  }) async {
    try {
      // Get auth token for API requests
      final authService = AuthService();
      final token = await authService.getToken();

      if (token == null) {
        return {
          'success': false,
          'message': 'Authentication required',
          'error': 'No auth token available',
        };
      }

      // Prepare order creation payload
      final payload = {
        'order_id': order.id,
        'total_amount': order.total,
        'payment_method': paymentMethod,
        'items': order.items
            .map((item) => {
                  'product_id': item.id,
                  'quantity': item.quantity,
                  'price': item.price,
                })
            .toList(),
        'shipping_address': order.deliveryAddress.address,
        'customer_note': 'Order placed via mobile app',
      };

      debugPrint('Creating order with payload: ${jsonEncode(payload)}');

      // Order should already exist from checkout process
      // Skip order creation and proceed directly to payment processing
      debugPrint('Processing payment for existing order: ${order.id}');

      final orderResponse = {
        'order_id': order.id,
        'success': true,
      };

      // Process payment through Midtrans
      Map<String, dynamic> paymentResponse;
      if (paymentMethod == 'qris' || paymentMethod == 'gopay') {
        paymentResponse = await _createQRPayment(
          order.id,
          order.total,
          order.items
              .map((item) => '${item.name} x${item.quantity}')
              .join(', '),
        );
      } else {
        // For other payment methods (implement as needed)
        paymentResponse = {
          'success': false,
          'message': 'Payment method not supported yet',
        };
      }

      // If payment processing is successful, notify user
      if (paymentResponse['success'] == true) {
        // Add notification for successful order creation
        final notificationService = NotificationService();
        await notificationService.addNotification(
          title: 'Order Created',
          message:
              'Your order #${order.id.substring(0, 8)} has been created successfully.',
          orderId: order.id,
          status: order.status,
        );
      }

      return {
        'success': paymentResponse['success'] ?? false,
        'message': paymentResponse['message'] ?? 'Unknown error',
        'payment_url': paymentResponse['payment_url'],
        'qr_code_url': paymentResponse['qr_code_url'],
        'transaction_id': paymentResponse['transaction_id'],
        'order_id': orderResponse['order_id'] ?? order.id,
        'status': paymentResponse['transaction_status'] ?? 'pending',
      };
    } catch (e) {
      debugPrint('Error in processPayment: $e');

      // Fallback to simulation if enabled
      if (_config.enableSimulation) {
        debugPrint('Using simulation mode after payment error');
        final simulatedResponse = _simulatePayment(order, paymentMethod);

        // Add notification for simulated payment
        if (simulatedResponse['success'] == true) {
          final notificationService = NotificationService();
          await notificationService.addNotification(
            title: 'Order Created (Simulation)',
            message:
                'Your order #${order.id.substring(0, 8)} has been created in simulation mode.',
            orderId: order.id,
            status: order.status,
          );
        }

        return simulatedResponse;
      }

      return {
        'success': false,
        'message': 'Payment processing error',
        'error': e.toString(),
      };
    }
  }

  // Verify payment status with notification integration
  Future<Map<String, dynamic>> verifyPayment({
    required String orderId,
    required String transactionId,
    BuildContext? context,
  }) async {
    try {
      final midtransService = MidtransService();
      final result =
          await midtransService.checkTransactionStatus(transactionId);

      // If payment is successful, notify the user
      if (result['success'] == true &&
          (result['transaction_status'] == 'settlement' ||
              result['transaction_status'] == 'capture')) {
        // Get order details to include in notification
        final orderService = await getOrderService();
        final order = await orderService?.fetchOrderById(orderId);

        if (order != null) {
          final notificationService = NotificationService();
          await notificationService.notifyPaymentComplete(order);
        }
      }

      return result;
    } catch (e) {
      debugPrint('Error verifying payment: $e');

      // Use simulation for verification if enabled
      if (_config.enableSimulation) {
        debugPrint('Using simulation mode for payment verification');
        final result = _simulatePaymentVerification(transactionId);

        // Send notification for simulated payment verification
        if (result['success'] == true &&
            (result['transaction_status'] == 'settlement' ||
                result['transaction_status'] == 'capture')) {
          final orderService = await getOrderService();
          final order = await orderService?.fetchOrderById(orderId);

          if (order != null) {
            final notificationService = NotificationService();
            await notificationService.notifyPaymentComplete(order);
          }
        }

        return result;
      }

      return {
        'success': false,
        'message': 'Payment verification error',
        'error': e.toString(),
      };
    }
  }

  // Get order service lazily
  Future<OrderService?> getOrderService() async {
    if (_orderService != null) {
      return _orderService;
    }

    try {
      final authService = AuthService();
      _orderService = OrderService(authService);
      return _orderService;
    } catch (e) {
      debugPrint('Error getting OrderService: $e');
      return null;
    }
  }

  // Simulate order creation for testing
  Map<String, dynamic> _simulateOrderCreation(
      Order order, String paymentMethod) {
    return {
      'order_id': order.id,
      'status': 'pending',
      'payment_method': paymentMethod,
      'total_amount': order.total,
      'created_at': DateTime.now().toIso8601String(),
      'simulation': true,
    };
  }

  // Simulate payment for testing
  Map<String, dynamic> _simulatePayment(Order order, String paymentMethod) {
    const uuid = Uuid();
    final transactionId = uuid.v4();

    // Create simulated QR code URL for QRIS payments
    String? qrCodeUrl;
    if (paymentMethod == 'qris' || paymentMethod == 'gopay') {
      qrCodeUrl =
          'https://api.sandbox.midtrans.com/v2/qris/${order.id}/qr-code';
    }

    return {
      'success': true,
      'message': 'Payment processed in simulation mode',
      'order_id': order.id,
      'transaction_id': transactionId,
      'qr_code_url': qrCodeUrl,
      'payment_url': paymentMethod == 'credit_card'
          ? 'https://sandbox.midtrans.com/snap/v2/vtweb/${order.id}'
          : null,
      'transaction_status': 'pending',
      'simulation': true,
    };
  }

  // Simulate payment verification for testing
  Map<String, dynamic> _simulatePaymentVerification(String transactionId) {
    // 80% chance of successful payment
    final isSuccess = DateTime.now().millisecondsSinceEpoch % 10 < 8;

    return {
      'success': true,
      'transaction_id': transactionId,
      'transaction_status': isSuccess ? 'settlement' : 'pending',
      'status_code': isSuccess ? '200' : '201',
      'status_message': isSuccess ? 'Payment successful' : 'Payment pending',
      'simulation': true,
    };
  }

  // Helper method to create QR payment through Midtrans
  Future<Map<String, dynamic>> _createQRPayment(
    String orderId,
    double amount,
    String itemDetails,
  ) async {
    try {
      final midtransService = MidtransService();
      return await midtransService.createQRPayment(
        orderId,
        amount,
        itemDetails,
      );
    } catch (e) {
      debugPrint('Error creating QR payment: $e');
      return {
        'success': false,
        'message': 'Failed to generate QR code',
        'error': e.toString(),
      };
    }
  }

  // Create an order in the API or simulated
  Future<Map<String, dynamic>> createOrder(
      Map<String, dynamic> orderData) async {
    try {
      // Get auth token for API requests
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // Generate unique request ID to prevent duplicates
      final requestId =
          'order_${DateTime.now().millisecondsSinceEpoch}_${const Uuid().v4().substring(0, 8)}';

      // API endpoint - use the correct endpoint for order creation
      final url =
          Uri.parse('${ApiConstants.getBaseUrl()}/api/v1/orders/create');

      // Ensure order has correct status explicitly set
      orderData['status'] = 'waiting_for_payment';
      orderData['payment_status'] = 'pending';
      orderData['is_read'] =
          false; // Ensure order appears as unread in admin dashboard
      orderData['notify_admin'] = true; // Notify admin about new order
      orderData['admin_notification'] = {
        'title': 'New Order Received',
        'message':
            'Customer placed a new order #${orderData['order_id'].toString().substring(0, 8)}',
        'priority': 'high',
      }; // Additional admin notification data

      debugPrint('Creating order with data: ${jsonEncode(orderData)}');

      // Send request to API with proper headers including request ID
      final response = await http
          .post(
            url,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': token != null ? 'Bearer $token' : '',
              'X-Request-ID': requestId, // Add request ID to prevent duplicates
            },
            body: jsonEncode(orderData),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);
        debugPrint('Order created successfully: ${response.body}');

        // Notify order service to refresh orders
        final orderService = await getOrderService();
        if (orderService != null) {
          await orderService.refreshOrders();
        }

        // Send additional notification to admin directly
        try {
          await http
              .post(
                Uri.parse('${ApiConstants.baseUrl}/api/admin/notifications'),
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'Authorization': token != null ? 'Bearer $token' : '',
                },
                body: jsonEncode({
                  'order_id': orderData['order_id'],
                  'title': 'New Order Received',
                  'message':
                      'New order #${orderData['order_id'].toString().substring(0, 8)} has been placed',
                  'is_read': false,
                  'priority': 'high',
                }),
              )
              .timeout(const Duration(seconds: 5));
          debugPrint('Admin notification sent successfully');
        } catch (e) {
          debugPrint(
              'Warning: Failed to send additional admin notification: $e');
          // Not critical, continue execution
        }

        return {
          'success': true,
          'order_id': responseData['data']?['id'] ??
              responseData['id'] ??
              orderData['order_id'],
          'message': 'Order created successfully',
        };
      } else {
        debugPrint(
            'Failed to create order: ${response.statusCode} - ${response.body}');

        // If API request fails, return simulation
        if (_config.enableSimulation) {
          return {
            'success': true,
            'simulation': true,
            'order_id': orderData['order_id'],
            'message': 'Order created in simulation mode',
          };
        } else {
          return {
            'success': false,
            'message': 'Failed to create order: ${response.statusCode}',
            'error': response.body,
          };
        }
      }
    } catch (e) {
      debugPrint('Error creating order: $e');

      // Return simulation on error if enabled
      if (_config.enableSimulation) {
        return {
          'success': true,
          'simulation': true,
          'order_id': orderData['order_id'],
          'message': 'Order created in simulation mode due to error',
        };
      } else {
        return {
          'success': false,
          'message': 'Error creating order',
          'error': e.toString(),
        };
      }
    }
  }

  // Check transaction status from Midtrans
  Future<Map<String, dynamic>> checkTransactionStatus(String orderId) async {
    try {
      // Check if we're in simulation mode
      if (_useSimulationMode || !await checkInternetConnection()) {
        debugPrint('Using simulation mode for transaction status check');
        return _simulateTransactionStatus(orderId);
      }

      // Get auth token for API requests
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // First try to get status from our own API
      try {
        final response = await http.get(
          Uri.parse(
              '${ApiConstants.getBaseUrl()}${ApiConstants.orders}/$orderId/status'),
          headers: {
            'Accept': 'application/json',
            'Authorization': token != null ? 'Bearer $token' : '',
          },
        ).timeout(const Duration(seconds: 5));

        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data['success'] == true) {
            debugPrint(
                'Got transaction status from API: ${data['data']['status']}');

            // If payment is successful but order status is still waiting_for_payment,
            // update it to processing
            final paymentStatus = data['data']['payment_status'];
            final orderStatus = data['data']['status'];

            if ((paymentStatus == 'paid' || paymentStatus == 'settlement') &&
                orderStatus == 'waiting_for_payment') {
              debugPrint(
                  'Payment successful but order status not updated. Updating to processing...');
              await updateOrderStatus(orderId, 'processing', 'paid');
            }

            return {
              'transaction_status': data['data']['payment_status'],
              'order_status': data['data']['status'],
              'from_api': true,
            };
          }
        }
      } catch (e) {
        debugPrint('Error checking status from API: $e');
        // Continue to check with Midtrans directly
      }

      // If API check failed, check with Midtrans directly
      final String authString = base64.encode(utf8.encode('$serverKey:'));
      final url = Uri.parse('$coreApiUrl/$orderId/status');

      debugPrint('Checking transaction status with Midtrans: $url');

      final response = await http.get(
        url,
        headers: {
          'Authorization': 'Basic $authString',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        debugPrint(
            'Midtrans transaction status: ${data['transaction_status']}');

        // If payment is successful, update order status to processing
        if (data['transaction_status'] == 'settlement' ||
            data['transaction_status'] == 'capture' ||
            data['transaction_status'] == 'paid') {
          debugPrint(
              'Payment successful. Updating order status to processing...');
          await updateOrderStatus(orderId, 'processing', 'paid');
        }

        return data;
      } else {
        debugPrint(
            'Failed to check transaction status: ${response.statusCode}');
        return {
          'transaction_status': 'unknown',
          'status_code': response.statusCode.toString(),
          'error': response.body,
        };
      }
    } catch (e) {
      debugPrint('Error checking transaction status: $e');
      return {
        'transaction_status': 'error',
        'error_message': e.toString(),
      };
    }
  }

  // Update order status after successful payment
  Future<Map<String, dynamic>> updateOrderStatus(
      String orderId, String status, String paymentStatus) async {
    try {
      // Get auth token for API requests
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        return {
          'success': false,
          'message': 'Authentication required',
        };
      }

      // API endpoint for updating order status
      final url = Uri.parse(
          '${ApiConstants.getBaseUrl()}${ApiConstants.orders}/$orderId/status');

      debugPrint(
          'Updating order status: $orderId to $status, payment status: $paymentStatus');

      // Send request to API
      final response = await http
          .post(
            url,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: jsonEncode({
              'status': status,
              'payment_status': paymentStatus,
              'send_notification': true,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        debugPrint('Order status updated successfully: ${response.body}');

        return {
          'success': true,
          'message': 'Order status updated successfully',
          'data': responseData,
        };
      } else {
        debugPrint(
            'Failed to update order status: ${response.statusCode} - ${response.body}');
        return {
          'success': false,
          'message': 'Failed to update order status',
          'error': response.body,
        };
      }
    } catch (e) {
      debugPrint('Error updating order status: $e');
      return {
        'success': false,
        'message': 'Error updating order status',
        'error': e.toString(),
      };
    }
  }

  // Simulate transaction status for testing
  Map<String, dynamic> _simulateTransactionStatus(String orderId) {
    // For simulation, we'll simulate a successful payment 80% of the time
    final random = math.Random();
    final isSuccess = random.nextDouble() < 0.8;

    if (isSuccess) {
      debugPrint('Simulating successful payment for order: $orderId');

      // Also update the order status to processing
      updateOrderStatus(orderId, 'processing', 'paid').then((_) {
        debugPrint('Updated order status to processing in simulation mode');
      }).catchError((error) {
        debugPrint('Error updating order status in simulation: $error');
      });

      return {
        'transaction_status': 'settlement',
        'status_code': '200',
        'status_message': 'Payment successful',
        'simulation': true,
      };
    } else {
      return {
        'transaction_status': 'pending',
        'status_code': '201',
        'status_message': 'Payment pending',
        'simulation': true,
      };
    }
  }

  // Process payment with specified QR code data
  Future<Map<String, dynamic>> processQRPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
    String? qrCodeData,
  }) async {
    try {
      debugPrint(
          'Processing QR payment for order: $orderId with method: $paymentMethod');

      // Get the order service
      final orderService = await getOrderService();

      // Try to update order payment status in backend
      try {
        // Get auth token for API requests
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');

        if (token != null) {
          await http
              .post(
                Uri.parse('$apiBaseUrl${ApiConstants.orders}/$orderId/payment'),
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': 'Bearer $token',
                  'Accept': 'application/json',
                },
                body: jsonEncode({
                  'payment_method': paymentMethod,
                  'amount': amount,
                  'payment_details': {
                    'qr_code_url': qrCodeUrl,
                    'qr_code_data': qrCodeData,
                  },
                }),
              )
              .timeout(const Duration(seconds: 5));
        }
      } catch (e) {
        debugPrint('Failed to update payment status in backend: $e');
        // Continue anyway, as we have the payment data locally
      }

      // Return payment information
      return {
        'success': true,
        'payment_id': 'PMT-${DateTime.now().millisecondsSinceEpoch}',
        'order_id': orderId,
        'status': 'pending',
        'qr_url': qrCodeUrl,
        'qr_data': qrCodeData,
        'amount': amount,
        'payment_method': paymentMethod,
      };
    } catch (e) {
      debugPrint('Error processing payment: $e');
      return {
        'success': false,
        'message': 'Error processing payment: $e',
        'payment_id': 'ERROR-${DateTime.now().millisecondsSinceEpoch}',
        'status': 'error',
        'qr_url': qrCodeUrl,
        'qr_data': qrCodeData,
      };
    }
  }

  // Simulate payment success for testing
  Future<Map<String, dynamic>> simulatePaymentSuccess(String orderId) async {
    try {
      debugPrint('Simulating payment success for order: $orderId');

      // Get auth token for API requests
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        return {
          'success': false,
          'message': 'Authentication required',
        };
      }

      // API endpoint for simulating payment success
      final url = Uri.parse(
          '${ApiConstants.getBaseUrl()}/api/payment/simulate-success');

      debugPrint('Calling payment simulation API: $url');

      // Send request to API
      final response = await http
          .post(
            url,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: jsonEncode({
              'order_id': orderId,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        debugPrint('Payment simulation successful: ${response.body}');

        return {
          'success': true,
          'message': 'Payment simulation completed successfully',
          'data': responseData,
        };
      } else {
        debugPrint(
            'Failed to simulate payment: ${response.statusCode} - ${response.body}');
        return {
          'success': false,
          'message': 'Failed to simulate payment',
          'error': response.body,
        };
      }
    } catch (e) {
      debugPrint('Error simulating payment: $e');
      return {
        'success': false,
        'message': 'Error simulating payment',
        'error': e.toString(),
      };
    }
  }
}
