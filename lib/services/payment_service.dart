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
  bool _useSimulationMode = false; // Flag untuk mode simulasi

  // Midtrans service instance
  final MidtransService _midtransService = MidtransService();

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
      final subtotal = totalAmount - shippingCost;

      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      // Parse shipping address into JSON format if needed
      Map<String, dynamic> deliveryAddressJson = {
        'address': shippingAddress,
        'phone': phoneNumber,
      };

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
        }),
      );

      debugPrint('Order API response code: ${orderResponse.statusCode}');
      if (orderResponse.statusCode != 200 && orderResponse.statusCode != 201) {
        debugPrint('Failed to create order: ${orderResponse.body}');
        return {
          'success': false,
          'message': 'Failed to create order: ${orderResponse.statusCode}',
          'details': orderResponse.body,
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
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
    String? qrCodeData,
  }) async {
    try {
      // Ensure we're initialized
      if (!_initialized) {
        await initialize();
      }

      // If in simulation mode, return simulated payment data
      if (_useSimulationMode) {
        return {
          'success': true,
          'payment_id': 'SIM-PAY-${DateTime.now().millisecondsSinceEpoch}',
          'status': 'pending',
          'qr_url': qrCodeUrl ??
              'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
          'qr_data': qrCodeData ??
              'SIMULATION-QRIS-${DateTime.now().millisecondsSinceEpoch}',
          'transaction_time': DateTime.now().toIso8601String(),
          'expiry_time':
              DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
          'simulation': true,
        };
      }

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

      // Create payment record in your backend API
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
      // Activate simulation mode for future requests
      _useSimulationMode = true;
      // Return simulated data on error
      return {
        'success': true,
        'payment_id': 'SIM-PAY-${DateTime.now().millisecondsSinceEpoch}',
        'status': 'pending',
        'qr_url': qrCodeUrl ??
            'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
        'qr_data': qrCodeData ??
            'SIMULATION-QRIS-${DateTime.now().millisecondsSinceEpoch}',
        'transaction_time': DateTime.now().toIso8601String(),
        'expiry_time':
            DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
        'simulation': true,
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

  // Check transaction status
  Future<Map<String, dynamic>> checkTransactionStatus(String orderId) async {
    try {
      // Ensure we're initialized
      if (!_initialized) {
        await initialize();
      }

      // If in simulation mode, return simulated status
      if (_useSimulationMode) {
        return {
          'transaction_time': DateTime.now().toIso8601String(),
          'transaction_status': 'pending',
          'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
          'status_message': 'Success, transaction is found (simulation)',
          'status_code': '200',
          'order_id': orderId,
          'gross_amount': '10000.00',
          'simulation': true,
        };
      }

      // Check status using MidtransService
      final statusData = await _midtransService.getTransactionStatus(orderId);

      // Update order status in your backend if needed
      try {
        await _updateOrderStatus(
          orderId: orderId,
          status: statusData['transaction_status'] ?? 'pending',
          paymentType: statusData['payment_type'] ?? '',
        );
      } catch (e) {
        debugPrint('Error updating order status: $e');
        // Continue even if update fails
      }

      return statusData;
    } catch (e) {
      debugPrint('Error checking transaction status: $e');
      // Activate simulation mode for future requests
      _useSimulationMode = true;
      // Return simulated status on error
      return {
        'transaction_time': DateTime.now().toIso8601String(),
        'transaction_status': 'pending',
        'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
        'status_message': 'Success, transaction is found (simulation)',
        'status_code': '200',
        'order_id': orderId,
        'gross_amount': '10000.00',
        'simulation': true,
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

  // Create order without payment process
  Future<Map<String, dynamic>> createOrder(
      Map<String, dynamic> orderData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // Ensure order data has status and payment_deadline set
      if (!orderData.containsKey('status')) {
        orderData['status'] = 'waiting_for_payment';
      }

      if (!orderData.containsKey('payment_status')) {
        orderData['payment_status'] = 'pending';
      }

      // Always set payment_deadline to 15 minutes from now for all orders
      orderData['payment_deadline'] =
          DateTime.now().add(const Duration(minutes: 15)).toIso8601String();

      // Log untuk debugging
      debugPrint('Mengirim data order ke API: ${jsonEncode(orderData)}');

      // Pastikan metode pembayaran dalam format yang benar (lowercase)
      if (orderData.containsKey('paymentMethod')) {
        String method = orderData['paymentMethod'].toString().toLowerCase();
        orderData['paymentMethod'] = method;
        // Tambahkan juga versi dengan underscore untuk kompatibilitas
        orderData['payment_method'] = method;
      }

      // Create order in Laravel API
      int maxRetries = 3;
      int attemptCount = 0;

      while (attemptCount < maxRetries) {
        attemptCount++;

        try {
          final orderResponse = await http.post(
            Uri.parse('$apiUrl/orders/create'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': token != null ? 'Bearer $token' : '',
              'Accept': 'application/json',
            },
            body: jsonEncode(orderData),
          );

          debugPrint('Order API response code: ${orderResponse.statusCode}');
          debugPrint('Order API response: ${orderResponse.body}');

          if (orderResponse.statusCode == 200 ||
              orderResponse.statusCode == 201) {
            return {
              'success': true,
              'message': 'Order created successfully',
              'data': jsonDecode(orderResponse.body)['data'] ?? {},
            };
          }

          // Jika gagal karena masalah tabel order_items
          if (orderResponse.statusCode == 500 &&
              (orderResponse.body.contains('order_items') ||
                  orderResponse.body
                      .contains('Base table or view not found'))) {
            debugPrint(
                'Error related to order_items table, trying direct database insertion...');

            // Tunggu beberapa detik agar sistem memiliki waktu untuk membuat tabel jika ada migration yang berjalan
            await Future.delayed(const Duration(seconds: 2));

            // Coba lagi dengan permintaan berikutnya
            continue;
          }

          // Jika gagal karena masalah user_id
          if (orderResponse.statusCode == 500 &&
              orderResponse.body.contains('user_id')) {
            debugPrint('Mencoba membuat order sebagai guest...');

            // Hapus user_id jika ada
            if (orderData.containsKey('user_id')) {
              orderData.remove('user_id');
            }

            // Coba lagi dengan permintaan baru
            final retryResponse = await http.post(
              Uri.parse('$apiUrl/orders/create'),
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
              },
              body: jsonEncode(orderData),
            );

            debugPrint('Retry API response code: ${retryResponse.statusCode}');
            debugPrint('Retry API response: ${retryResponse.body}');

            if (retryResponse.statusCode == 200 ||
                retryResponse.statusCode == 201) {
              return {
                'success': true,
                'message': 'Order created successfully as guest',
                'data': jsonDecode(retryResponse.body)['data'] ?? {},
              };
            }
          }

          // Jika sudah percobaan terakhir, kembalikan error
          if (attemptCount >= maxRetries) {
            return {
              'success': false,
              'message':
                  'Failed to create order after $maxRetries attempts: ${orderResponse.statusCode}',
              'details': orderResponse.body,
            };
          }

          // Tunggu sebelum mencoba lagi
          await Future.delayed(const Duration(seconds: 2));
        } catch (innerError) {
          debugPrint('Error attempt $attemptCount: $innerError');

          // Jika ini percobaan terakhir, throw exception untuk ditangani di catch utama
          if (attemptCount >= maxRetries) {
            rethrow;
          }

          // Tunggu sebelum mencoba lagi
          await Future.delayed(const Duration(seconds: 2));
        }
      }

      // Fallback error jika loop selesai tanpa return
      return {
        'success': false,
        'message': 'Failed to create order after multiple attempts'
      };
    } catch (e) {
      debugPrint('Order creation error: $e');
      return {
        'success': false,
        'message': 'Order creation error: $e',
      };
    }
  }
}
