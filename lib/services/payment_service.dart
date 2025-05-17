import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';
import '../models/delivery_address.dart';
import '../models/cart_item.dart';
import 'package:flutter/foundation.dart';

class PaymentService {
  // Updated Midtrans API URLs - with fallback IP for sandbox.midtrans.com if needed
  final String snapUrl = 'https://app.sandbox.midtrans.com/snap/v1';
  final String snapUrlFallback =
      'https://103.30.236.18/snap/v1'; // IP address fallback
  final String coreApiUrl = 'https://api.sandbox.midtrans.com/v2';
  final String coreApiUrlFallback =
      'https://103.30.236.19/v2'; // IP address fallback
  final String clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';
  final String serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry';
  final String apiUrl = 'http://10.0.2.2:8000/api'; // Laravel API URL

  // Singleton instance
  static final PaymentService _instance = PaymentService._internal();
  factory PaymentService() => _instance;
  PaymentService._internal();

  bool _initialized = false;
  bool _useIpFallback = false; // Flag to use IP address instead of domain

  // Initialize payment service and verify connectivity
  Future<bool> initialize() async {
    if (_initialized) return true;

    debugPrint('Initializing PaymentService...');
    try {
      // Check basic internet connectivity
      final hasInternet = await checkInternetConnection();
      if (!hasInternet) {
        debugPrint('No internet connection detected!');
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
              'WARNING: All connection attempts to Midtrans failed. Payment functionality will be limited.');
        }
      }

      _initialized = true;
      return true;
    } catch (e) {
      debugPrint('Error initializing PaymentService: $e');
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
      final url = Uri.parse('$apiUrl/payment-methods');
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
        return {
          'success': true,
          'data': [
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
          ]
        };
      }
    } catch (e) {
      // Return hardcoded payment methods on error
      debugPrint('Error loading payment methods: $e');
      return {
        'success': true,
        'data': [
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
            'code': 'gopay',
            'name': 'GoPay',
            'logo': 'gopay.png',
          },
        ]
      };
    }
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
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userData = prefs.getString('user_data');
      final userEmail = userData != null
          ? jsonDecode(userData)['email']
          : 'customer@example.com';

      if (token == null) {
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
      itemDetails.add({
        'id': 'shipping',
        'name': 'Shipping Cost',
        'price': shippingCost.toInt(),
        'quantity': 1,
      });

      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      // Create order in Laravel API
      final orderResponse = await http.post(
        Uri.parse('$apiUrl/orders/create'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'order_id': orderId,
          'items': itemDetails,
          'shipping_address': shippingAddress,
          'phone_number': phoneNumber,
          'total_amount': totalAmount,
          'shipping_cost': shippingCost,
          'payment_method': paymentMethod,
          'status': 'pending',
        }),
      );

      if (orderResponse.statusCode != 200 && orderResponse.statusCode != 201) {
        debugPrint('Failed to create order: ${orderResponse.body}');
        return {
          'success': false,
          'message': 'Failed to create order',
        };
      }

      // Prepare customer details for Midtrans
      final names = shippingAddress.split(',')[0].split(' ');
      final firstName = names.isNotEmpty ? names[0] : 'Customer';
      final lastName = names.length > 1 ? names.sublist(1).join(' ') : '';

      // Create transaction in Midtrans
      final String authString = base64.encode(utf8.encode('$serverKey:'));
      final midtransResponse = await http.post(
        Uri.parse('$snapUrl/transactions'),
        headers: {
          'Authorization': 'Basic $authString',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
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
            'alfamart',
            'indomaret',
          ],
        }),
      );

      if (midtransResponse.statusCode == 201 ||
          midtransResponse.statusCode == 200) {
        final midtransData = jsonDecode(midtransResponse.body);

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
        return {
          'success': false,
          'message': 'Failed to create payment: ${midtransResponse.statusCode}',
        };
      }
    } catch (e) {
      debugPrint('Payment error: $e');
      return {
        'success': false,
        'message': 'Payment processing error: $e',
      };
    }
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

        // Save order to API
        await _saveOrderToApi(
          orderId: orderId,
          userId: userId.toString(),
          items: items,
          address: address,
          totalAmount: totalAmount,
          shippingCost: shippingCost,
          paymentMethod: paymentMethod,
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

  // Save order to your Laravel API
  Future<void> _saveOrderToApi({
    required String orderId,
    required String userId,
    required List<CartItem> items,
    required DeliveryAddress address,
    required double totalAmount,
    required double shippingCost,
    required String paymentMethod,
    required String paymentStatus,
    required String transactionId,
  }) async {
    final url = Uri.parse('http://10.0.2.2:8000/api/orders');
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    // Get authentication token if available
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }

    // Format items for Laravel API
    final List<Map<String, dynamic>> orderItems = items
        .map((item) => {
              'product_id': item.productId,
              'name': item.name,
              'price': item.price,
              'quantity': item.quantity,
              'subtotal': item.price * item.quantity,
            })
        .toList();

    final body = {
      'order_id': orderId,
      'user_id': userId,
      'items': orderItems,
      'address': {
        'name': address.name,
        'phone': address.phone,
        'address': address.address,
        'city': address.city,
        'district': address.district,
        'postal_code': address.postalCode,
        'latitude': address.latitude,
        'longitude': address.longitude,
      },
      'subtotal': totalAmount - shippingCost,
      'shipping_cost': shippingCost,
      'total_amount': totalAmount,
      'payment_method': paymentMethod,
      'payment_status': paymentStatus,
      'transaction_id': transactionId,
    };

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(body),
      );

      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception('Failed to save order to API: ${response.body}');
      }
    } catch (e) {
      // Log error but don't throw, as payment was already processed
      print('Error saving order to API: $e');
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

      // First create the order in our own system to ensure it exists with waiting_for_payment status
      try {
        final orderResponse = await http
            .post(
              Uri.parse('$apiUrl/orders'),
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer $token',
              },
              body: jsonEncode({
                'id': orderId,
                'items': items,
                'deliveryAddress': {
                  'name': '$firstName $lastName',
                  'address': shippingAddress,
                  'phone': phoneNumber,
                },
                'subtotal': totalAmount - shippingCost,
                'shippingCost': shippingCost,
                'total': totalAmount,
                'paymentMethod': selectedBank ?? 'other',
                'status': 'waiting_for_payment', // Explicitly set status
              }),
            )
            .timeout(const Duration(seconds: 10));

        if (orderResponse.statusCode != 201 &&
            orderResponse.statusCode != 200) {
          debugPrint(
              'Warning: Failed to pre-create order: ${orderResponse.body}');
          // Continue anyway, we'll retry this after payment
        } else {
          debugPrint(
              '✓ Order pre-created successfully with waiting_for_payment status');
        }
      } catch (e) {
        debugPrint('Warning: Failed to pre-create order: $e');
        // Continue anyway, we'll retry this after payment
      }

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
      {double? amount}) async {
    try {
      // Get authentication token
      final String authString = base64.encode(utf8.encode('$serverKey:'));

      // First check if this is a valid order ID
      final statusUrl = Uri.parse('$coreApiUrl/$orderId/status');
      final statusResponse = await http.get(
        statusUrl,
        headers: {
          'Authorization': 'Basic $authString',
          'Accept': 'application/json',
        },
      );
      debugPrint('Checking order status URL: ${statusUrl.toString()}');

      // Get the amount for this transaction
      double transactionAmount = amount ?? 0;

      // Try to get the order details to extract the amount
      try {
        final orderResponse = await http.get(
          Uri.parse('$apiUrl/orders/$orderId'),
          headers: {'Content-Type': 'application/json'},
        );

        if (orderResponse.statusCode == 200) {
          final orderData = jsonDecode(orderResponse.body);
          if (orderData['total_amount'] != null) {
            transactionAmount =
                double.tryParse(orderData['total_amount'].toString()) ??
                    transactionAmount;
          }
        }
      } catch (e) {
        debugPrint('Error fetching order details: $e');
      }

      if (statusResponse.statusCode == 200) {
        // Transaction exists, get the amount from the response
        final statusData = jsonDecode(statusResponse.body);
        transactionAmount =
            double.tryParse(statusData['gross_amount'].toString()) ??
                transactionAmount;

        // Make sure we have a valid amount
        if (transactionAmount <= 0) {
          transactionAmount = amount ?? 10000;
        }
      }

      // Ensure we have a valid amount
      if (transactionAmount <= 0) {
        transactionAmount = 10000;
      }
      debugPrint('Using transaction amount: $transactionAmount');

      if (statusResponse.statusCode != 200) {
        debugPrint('Order not found or invalid: ${statusResponse.body}');
        // Create a new QRIS transaction with the correct amount
        final transaction = await http.post(
          Uri.parse('$coreApiUrl/charge'),
          headers: {
            'Authorization': 'Basic $authString',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({
            'payment_type': 'qris',
            'transaction_details': {
              'order_id': 'QRIS-$orderId',
              'gross_amount':
                  transactionAmount.toInt(), // Use the correct amount
            },
            'qris': {'acquirer': 'gopay'},
            'expiry': {'duration': 15, 'unit': 'minute'}
          }),
        );
        if (transaction.statusCode == 200 || transaction.statusCode == 201) {
          final responseData = jsonDecode(transaction.body);

          // Add expiry time 15 minutes from now
          final DateTime expiryTime =
              DateTime.now().add(const Duration(minutes: 15));
          final String expiryTimeStr =
              responseData['expiry_time'] ?? expiryTime.toIso8601String();
          return {
            'qr_code_url': responseData['actions']?.firstWhere(
                  (action) => action['name'] == 'generate-qr-code',
                  orElse: () => {'url': ''},
                )['url'] ??
                '',
            'qr_code_data':
                responseData['qris_data'] ?? responseData['payment_code'] ?? '',
            'expiry_time': expiryTimeStr,
            'transaction_id': responseData['transaction_id'] ?? '',
            'amount': transactionAmount,
          };
        } else {
          debugPrint('Failed to create QRIS transaction: ${transaction.body}');
          return _createFallbackQR(orderId, amount: transactionAmount);
        }
      }
      // Transaction exists, try to get the QR code information
      final statusData = jsonDecode(statusResponse.body);
      if (statusData['payment_type'] == 'qris') {
        // This is a QRIS transaction, extract QR data
        final DateTime expiryTime =
            DateTime.now().add(const Duration(minutes: 15));
        final String expiryTimeStr =
            statusData['expiry_time'] ?? expiryTime.toIso8601String();

        return {
          'qr_code_url': statusData['actions']?.firstWhere(
                (action) => action['name'] == 'generate-qr-code',
                orElse: () => {'url': ''},
              )['url'] ??
              '',
          'qr_code_data':
              statusData['qris_data'] ?? statusData['payment_code'] ?? '',
          'expiry_time': expiryTimeStr,
          'transaction_id': statusData['transaction_id'] ?? '',
          'amount': transactionAmount,
        };
      } else {
        // Create a new QRIS transaction for this order with the correct amount
        final qrisUrl = Uri.parse('$coreApiUrl/charge');
        final qrisResponse = await http.post(
          qrisUrl,
          headers: {
            'Authorization': 'Basic $authString',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({
            'payment_type': 'qris',
            'transaction_details': {
              'order_id': 'QRIS-$orderId',
              'gross_amount':
                  transactionAmount.toInt(), // Use transaction amount
            },
            'qris': {'acquirer': 'gopay'},
            'expiry': {'duration': 15, 'unit': 'minute'}
          }),
        );
        if (qrisResponse.statusCode == 200 || qrisResponse.statusCode == 201) {
          final responseData = jsonDecode(qrisResponse.body);

          // Add expiry time 15 minutes from now
          final DateTime expiryTime =
              DateTime.now().add(const Duration(minutes: 15));
          final String expiryTimeStr =
              responseData['expiry_time'] ?? expiryTime.toIso8601String();
          return {
            'qr_code_url': responseData['actions']?.firstWhere(
                  (action) => action['name'] == 'generate-qr-code',
                  orElse: () => {'url': ''},
                )['url'] ??
                '',
            'qr_code_data':
                responseData['qris_data'] ?? responseData['payment_code'] ?? '',
            'expiry_time': expiryTimeStr,
            'transaction_id': responseData['transaction_id'] ?? '',
            'amount': transactionAmount,
          };
        }
      }
      // If all else fails, return fallback QR data
      return _createFallbackQR(orderId, amount: transactionAmount);
    } catch (e) {
      debugPrint('Error generating QR code: $e');
      return _createFallbackQR(orderId, amount: amount);
    }
  }

  // Helper method to create fallback QR data
  Map<String, dynamic> _createFallbackQR(String orderId, {double? amount}) {
    final amountStr = amount != null ? amount.toInt().toString() : '0';
    final DateTime expiryTime = DateTime.now().add(const Duration(minutes: 15));
    return {
      'qr_code_url': '',
      'qr_code_data':
          'QRIS.ID|ORDER.$orderId|AMOUNT.$amountStr|TIME.${DateTime.now().millisecondsSinceEpoch}',
      'expiry_time': expiryTime.toIso8601String(),
      'amount': amount ?? 0,
    };
  }

  // Process payment
  Future<Map<String, dynamic>> processPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
    String? qrCodeData,
  }) async {
    try {
      // Get authentication token
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userData = prefs.getString('user_data');

      if (token == null) {
        return {
          'success': false,
          'message': 'Authentication required',
        };
      }

      // First check if this transaction has already been created in Midtrans
      final String authString = base64.encode(utf8.encode('$serverKey:'));
      final statusUrl = Uri.parse('$coreApiUrl/$orderId/status');

      debugPrint('Checking transaction status URL: ${statusUrl.toString()}');

      try {
        final statusResponse = await http.get(
          statusUrl,
          headers: {
            'Authorization': 'Basic $authString',
            'Accept': 'application/json',
          },
        );

        // If transaction exists in Midtrans
        if (statusResponse.statusCode == 200) {
          final transactionData = jsonDecode(statusResponse.body);

          // Create payment record in your backend API
          final backendPayment = await _createPaymentRecord(
              orderId: orderId,
              amount: amount,
              paymentMethod: paymentMethod,
              qrCodeUrl: qrCodeUrl,
              qrCodeData: qrCodeData,
              transactionId: transactionData['transaction_id'],
              status: transactionData['transaction_status'] ?? 'pending');

          return {
            'success': true,
            'payment_id': backendPayment['id'] ?? orderId,
            'status': transactionData['transaction_status'] ?? 'pending',
            'qr_url': qrCodeUrl,
            'qr_data': qrCodeData,
            'transaction_time': transactionData['transaction_time'],
            'expiry_time': transactionData['expiry_time'],
          };
        }
      } catch (e) {
        debugPrint('Error checking existing transaction: $e');
        // Continue to create new transaction
      }

      // If payment method is QRIS but we don't have QR data, create a new QRIS transaction
      if (paymentMethod.toLowerCase() == 'qris' &&
          (qrCodeData == null || qrCodeData.isEmpty)) {
        // Create a QRIS transaction in Midtrans
        final qrisResponse = await http.post(
          Uri.parse('$coreApiUrl/charge'),
          headers: {
            'Authorization': 'Basic $authString',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({
            'payment_type': 'qris',
            'transaction_details': {
              'order_id': orderId,
              'gross_amount': amount.toInt(),
            },
            'qris': {'acquirer': 'gopay'}
          }),
        );

        debugPrint(
            'QRIS transaction URL: ${Uri.parse('$coreApiUrl/charge').toString()}');

        if (qrisResponse.statusCode == 200 || qrisResponse.statusCode == 201) {
          final qrisData = jsonDecode(qrisResponse.body);

          final updatedQrCodeUrl = qrisData['actions']?.firstWhere(
                (action) => action['name'] == 'generate-qr-code',
                orElse: () => {'url': ''},
              )['url'] ??
              '';

          final updatedQrCodeData =
              qrisData['qris_data'] ?? qrisData['payment_code'] ?? '';

          // Create payment record in your backend API
          final backendPayment = await _createPaymentRecord(
              orderId: orderId,
              amount: amount,
              paymentMethod: 'QRIS',
              qrCodeUrl: updatedQrCodeUrl,
              qrCodeData: updatedQrCodeData,
              transactionId: qrisData['transaction_id'],
              status: qrisData['transaction_status'] ?? 'pending');

          return {
            'success': true,
            'payment_id': backendPayment['id'] ?? orderId,
            'status': qrisData['transaction_status'] ?? 'pending',
            'qr_url': updatedQrCodeUrl,
            'qr_data': updatedQrCodeData,
            'transaction_time': qrisData['transaction_time'],
            'expiry_time': qrisData['expiry_time'],
          };
        }
      }

      // For non-QRIS or if QRIS creation failed, create generic payment record
      final backendPayment = await _createPaymentRecord(
          orderId: orderId,
          amount: amount,
          paymentMethod: paymentMethod,
          qrCodeUrl: qrCodeUrl,
          qrCodeData: qrCodeData,
          status: 'pending');

      return {
        'success': true,
        'payment_id': backendPayment['id'] ?? orderId,
        'status': 'pending',
        'qr_url': qrCodeUrl,
        'qr_data': qrCodeData,
      };
    } catch (e) {
      debugPrint('Error processing payment: $e');
      // Return basic information on error
      return {
        'success': true, // Still return success to continue flow
        'payment_id': orderId,
        'status': 'pending',
        'qr_url': qrCodeUrl,
        'qr_data': qrCodeData,
        'error': e.toString(),
      };
    }
  }

  // Helper method to create payment record in your backend
  Future<Map<String, dynamic>> _createPaymentRecord({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
    String? qrCodeData,
    String? transactionId,
    required String status,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        return {'id': orderId, 'status': status};
      }

      final url = Uri.parse('$apiUrl/payments/process');
      final headers = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      };

      final body = {
        'order_id': orderId,
        'amount': amount,
        'payment_method': paymentMethod,
        'qr_code_url': qrCodeUrl,
        'qr_code_data': qrCodeData,
        'transaction_id': transactionId,
        'status': status,
      };

      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(body),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return jsonDecode(response.body);
      } else {
        debugPrint('API error creating payment record: ${response.body}');
        return {'id': orderId, 'status': status};
      }
    } catch (e) {
      debugPrint('Error creating payment record: $e');
      return {'id': orderId, 'status': status};
    }
  }

  // Check payment status
  Future<Map<String, dynamic>> checkTransactionStatus(String orderId) async {
    final String authString = base64.encode(utf8.encode('$serverKey:'));
    final url = Uri.parse('$coreApiUrl/$orderId/status');

    final headers = {
      'Accept': 'application/json',
      'Authorization': 'Basic $authString',
    };

    try {
      debugPrint('Checking transaction status for order: $orderId');
      debugPrint('Status check URL: ${url.toString()}');
      final response = await http.get(url, headers: headers);

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        debugPrint('Transaction status: ${responseData['transaction_status']}');

        // Update order status in your backend if needed
        try {
          await _updateOrderStatus(
            orderId: orderId,
            status: responseData['transaction_status'] ?? 'pending',
            paymentType: responseData['payment_type'] ?? '',
          );
        } catch (e) {
          debugPrint('Error updating order status: $e');
          // Continue even if update fails
        }

        return responseData;
      } else {
        debugPrint('Failed to check transaction status: ${response.body}');

        // Check if this might be a QRIS transaction with different ID
        if (orderId.startsWith('ORDER-')) {
          final qrisOrderId = 'QRIS-$orderId';
          final qrisUrl = Uri.parse('$coreApiUrl/$qrisOrderId/status');

          try {
            final qrisResponse = await http.get(qrisUrl, headers: headers);

            if (qrisResponse.statusCode == 200) {
              final qrisData = jsonDecode(qrisResponse.body);
              debugPrint(
                  'Found QRIS transaction: ${qrisData['transaction_status']}');
              return qrisData;
            }
          } catch (e) {
            debugPrint('Error checking QRIS transaction: $e');
          }
        }

        throw Exception(
            'Failed to check transaction status: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('Error checking transaction status: $e');
      // Return a basic response so the UI doesn't crash
      return {
        'transaction_status': 'pending',
        'status_code': '404',
        'status_message': 'Error checking status: $e',
      };
    }
  }

  // Update order status in your backend
  Future<void> _updateOrderStatus({
    required String orderId,
    required String status,
    required String paymentType,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        return;
      }

      final url = Uri.parse('$apiUrl/orders/update-status');
      await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'order_id': orderId,
          'status': status,
          'payment_type': paymentType,
        }),
      );
    } catch (e) {
      debugPrint('Error updating order status in backend: $e');
    }
  }
}
