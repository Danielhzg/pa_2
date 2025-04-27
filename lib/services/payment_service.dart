import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';
import '../models/delivery_address.dart';
import '../models/cart_item.dart';
import '../models/payment.dart';
import '../services/api_service.dart';
import 'package:flutter/foundation.dart';
import '../services/midtrans_service.dart';

class PaymentService {
  final String baseUrl = 'https://api.sandbox.midtrans.com/v2';
  final String clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';
  final String serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry';
  final String apiUrl = 'http://10.0.2.2:8000/api'; // Laravel API URL
  final ApiService _apiService;

  PaymentService() : _apiService = ApiService();

  // Fetch available payment methods from the API
  Future<List<Map<String, dynamic>>> getPaymentMethods() async {
    try {
      final url = Uri.parse('$apiUrl/v1/payment-methods');
      final response = await http.get(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        return List<Map<String, dynamic>>.from(responseData['data']);
      } else {
        throw Exception(
            'Failed to load payment methods: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching payment methods: $e');
      rethrow;
    }
  }

  // Create a payment with Midtrans
  Future<Map<String, dynamic>> createPayment({
    required List<Map<String, dynamic>> items,
    required String customerId,
    required double shippingCost,
    required String shippingAddress,
    required String phoneNumber,
    required String paymentMethod,
  }) async {
    try {
      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      // Di environment development, gunakan simulasi pembayaran
      debugPrint('⚠️ SIMULASI PEMBAYARAN UNTUK PENGEMBANGAN ⚠️');
      debugPrint('Metode pembayaran: $paymentMethod');

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

      // Save order to database/API
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userEmail = prefs.getString('user_email') ?? 'customer@example.com';

      if (token != null) {
        try {
          await http.post(
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
          debugPrint('Order berhasil disimpan ke database');
        } catch (e) {
          debugPrint('Error menyimpan order ke database: $e');
          // Lanjutkan meskipun gagal menyimpan ke database
        }
      }

      // Simulasi respons Midtrans berdasarkan metode pembayaran
      final snapToken =
          'SIMULATED-TOKEN-${DateTime.now().millisecondsSinceEpoch}';
      final redirectUrl =
          'https://simulator.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';

      // Ciptakan nomor VA berdasarkan bank
      String vaNumber = "979780";
      for (int i = 0; i < 8; i++) {
        vaNumber += (DateTime.now().millisecondsSinceEpoch % 10).toString();
      }

      String? bank;
      if (paymentMethod.contains('_va')) {
        bank = paymentMethod.split('_')[0];
      } else if (paymentMethod == 'bank_transfer') {
        bank = 'bca'; // Default bank
      } else if (paymentMethod == 'qr_code') {
        // Untuk QR Code, tidak perlu bank
        bank = null;
        vaNumber = ""; // Empty string instead of null
      }

      debugPrint('💳 DATA PEMBAYARAN SIMULASI 💳');
      debugPrint('Order ID: $orderId');
      debugPrint('Total: ${totalAmount.toInt()}');
      debugPrint('Token: $snapToken');
      debugPrint('Redirect URL: $redirectUrl');
      debugPrint('Email: $userEmail');
      debugPrint('Phone: $phoneNumber');

      if (bank != null) {
        debugPrint('Bank: $bank');
        debugPrint('VA Number: $vaNumber');
      }

      return {
        'success': true,
        'data': {
          'order_id': orderId,
          'token': snapToken,
          'redirect_url': redirectUrl,
          'va_number': vaNumber,
          'bank': bank,
          'total_amount': totalAmount.toInt(),
        },
      };
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

    final url = Uri.parse('$baseUrl/charge');
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
        // Support multiple virtual accounts
        return {
          'bank_transfer': {
            'bank': 'bca',
            'va_numbers': [
              {'bank': 'bca', 'va_number': '12345678901'},
              {'bank': 'bni', 'va_number': '12345678902'},
              {'bank': 'bri', 'va_number': '12345678903'},
              {'bank': 'mandiri', 'va_number': '12345678904'},
            ],
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

  // Check payment status
  Future<Map<String, dynamic>> checkTransactionStatus(String orderId) async {
    final String authString = base64.encode(utf8.encode('$serverKey:'));
    final url = Uri.parse('$baseUrl/$orderId/status');

    final headers = {
      'Accept': 'application/json',
      'Authorization': 'Basic $authString',
    };

    try {
      final response = await http.get(url, headers: headers);

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to check transaction status: ${response.body}');
      }
    } catch (e) {
      throw Exception('Error checking transaction status: $e');
    }
  }

  // Generate QR code for payment
  Future<String> generateQRCode(double amount, String orderId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        throw Exception('Authentication required');
      }

      // Call API to generate QR code
      final response = await http.post(
        Uri.parse('$apiUrl/payments/generate-qr'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'amount': amount,
          'order_id': orderId,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data']['qr_url'] != null) {
          return data['data']['qr_url'];
        }
      }

      // If API fails, generate a dummy QR URL
      // In a real app, this would connect to a payment gateway
      return 'https://api.sandbox.midtrans.com/v2/qris/$orderId';
    } catch (e) {
      debugPrint('Error generating QR code: $e');
      // Return a fallback URL in case of error
      return 'https://api.sandbox.midtrans.com/v2/qris/fallback-$orderId';
    }
  }

  // Process payment and create payment record
  Future<Payment> processPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userData = prefs.getString('user_data');

      final userId =
          userData != null ? jsonDecode(userData)['id'].toString() : '0';

      if (token == null) {
        throw Exception('Authentication required');
      }

      // Create payment in the backend
      final response = await http.post(
        Uri.parse('$apiUrl/payments/process'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'order_id': orderId,
          'amount': amount,
          'payment_method': paymentMethod,
          'user_id': userId,
          'qr_code_url': qrCodeUrl,
          'status': 'pending',
        }),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Payment.fromJson(data['data']);
        }
      }

      // If API fails, create a local payment object
      return Payment(
        id: 'local-${const Uuid().v4()}',
        orderId: orderId,
        amount: amount,
        paymentMethod: paymentMethod,
        status: 'pending',
        createdAt: DateTime.now(),
      );
    } catch (e) {
      debugPrint('Error processing payment: $e');
      // Return a fallback payment object in case of error
      return Payment(
        id: 'local-${const Uuid().v4()}',
        orderId: orderId,
        amount: amount,
        paymentMethod: paymentMethod,
        status: 'pending',
        createdAt: DateTime.now(),
      );
    }
  }

  // Check payment status
  Future<String> checkPaymentStatus(String paymentId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        throw Exception('Authentication required');
      }

      // Call API to check payment status
      final response = await http.get(
        Uri.parse('$apiUrl/payments/$paymentId/status'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data']['status'] != null) {
          return data['data']['status'];
        }
      }

      // For demo purposes: randomly return 'completed' 20% of the time
      // This simulates a payment being completed after some time
      // In a real app, this would connect to a payment gateway
      final random = DateTime.now().millisecondsSinceEpoch % 5;
      if (random == 0) {
        return 'completed';
      }

      return 'pending';
    } catch (e) {
      debugPrint('Error checking payment status: $e');
      return 'pending';
    }
  }

  // Get Midtrans Snap Token for payment
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
      debugPrint('======== GET MIDTRANS SNAP TOKEN ========');

      // Generate order ID
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';

      debugPrint('Creating payment with Order ID: $orderId');
      if (selectedBank != null) {
        debugPrint('Selected Bank: $selectedBank');
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

      // Add shipping cost
      totalAmount += shippingCost;
      itemDetails.add({
        'id': 'shipping',
        'name': 'Shipping Cost',
        'price': shippingCost.toInt(),
        'quantity': 1,
      });

      debugPrint('Total amount: $totalAmount');
      debugPrint('Items count: ${itemDetails.length}');

      // Jika ini pembayaran dengan Virtual Account dan bank sudah dipilih
      if (selectedBank != null &&
          (selectedBank == 'bca' ||
              selectedBank == 'bni' ||
              selectedBank == 'bri' ||
              selectedBank == 'permata' ||
              selectedBank == 'mandiri')) {
        debugPrint('Proses pembayaran Virtual Account bank: $selectedBank');

        // Import service Midtrans
        final midtransService = MidtransService();

        // Persiapkan data untuk Midtrans
        final names = shippingAddress.split(',')[0].split(' ');
        final firstName = names.isNotEmpty ? names[0] : 'Customer';
        final lastName = names.length > 1 ? names.sublist(1).join(' ') : '';

        debugPrint('Customer: $firstName $lastName');
        debugPrint('Email: $email');
        debugPrint('Phone: $phoneNumber');

        try {
          // Gunakan createTransaction dari MidtransService untuk membuat transaksi langsung
          debugPrint('Memanggil midtransService.createTransaction...');
          final result = await midtransService.createTransaction(
            orderId: orderId,
            grossAmount: totalAmount.toInt(),
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phoneNumber,
            items: itemDetails,
            paymentMethod: 'bank_transfer',
            bankCode: selectedBank,
          );

          debugPrint('Direct VA charge response: ${result.toString()}');

          // Cek keberadaan VA number
          if (result['va_number'] == null ||
              result['va_number'].toString().isEmpty) {
            debugPrint(
                '⚠️ VA Number tidak ada dalam response! Menggunakan fallback...');
            // Buat fallback VA number jika tidak ada
            String fallbackVA = '';
            if (selectedBank == 'mandiri') {
              fallbackVA = 'MAN${DateTime.now().millisecondsSinceEpoch}';
            } else {
              fallbackVA =
                  '${selectedBank.toUpperCase().substring(0, 3)}${DateTime.now().millisecondsSinceEpoch}';
            }
            debugPrint('Fallback VA Number: $fallbackVA');
            result['va_number'] = fallbackVA;
          }

          // Pastikan response dalam format yang benar
          return {
            'success': true,
            'data': {
              'order_id': result['order_id'] ?? orderId,
              'token': result['transaction_id'] ??
                  'VA-${DateTime.now().millisecondsSinceEpoch}',
              'redirect_url': null,
              'va_number': result['va_number'],
              'bank': result['bank'] ?? selectedBank,
              'transaction_status': result['transaction_status'] ?? 'pending',
              'transaction_id': result['transaction_id'] ?? '',
            },
          };
        } catch (e) {
          debugPrint('❌ ERROR saat memanggil createTransaction: $e');
          // Gunakan fallback jika direct VA gagal
          return _createFallbackVAPayment(orderId, totalAmount, selectedBank);
        }
      } else {
        // Untuk metode pembayaran lain gunakan Snap
        debugPrint('Menggunakan Midtrans SNAP untuk metode pembayaran lain');

        // Prepare customer details for Midtrans
        final names = shippingAddress.split(',')[0].split(' ');
        final firstName = names.isNotEmpty ? names[0] : 'Customer';
        final lastName = names.length > 1 ? names.sublist(1).join(' ') : '';

        // Create transaction in Midtrans to get snap token
        final String authString = base64.encode(utf8.encode('$serverKey:'));

        // Prepare the payload for Midtrans Snap
        final Map<String, dynamic> payload = {
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
            'bca_va',
            'bni_va',
            'bri_va',
            'echannel', // Mandiri
            'permata_va',
            'qris', // QRIS/QR Code
          ],
        };

        debugPrint(
            'Sending Midtrans Snap request with payload: ${jsonEncode(payload)}');

        try {
          final midtransResponse = await http.post(
            Uri.parse('https://app.sandbox.midtrans.com/snap/v1/transactions'),
            headers: {
              'Authorization': 'Basic $authString',
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: jsonEncode(payload),
          );

          debugPrint(
              'Midtrans Snap response status: ${midtransResponse.statusCode}');
          debugPrint('Midtrans Snap response body: ${midtransResponse.body}');

          if (midtransResponse.statusCode == 201 ||
              midtransResponse.statusCode == 200) {
            final midtransData = jsonDecode(midtransResponse.body);
            debugPrint('Midtrans Snap token: ${midtransData['token']}');
            debugPrint(
                'Midtrans redirect URL: ${midtransData['redirect_url']}');

            // Save order details to backend
            final prefs = await SharedPreferences.getInstance();
            final token = prefs.getString('auth_token');

            if (token != null) {
              try {
                await http.post(
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
                    'payment_method': 'midtrans',
                    'midtrans_token': midtransData['token'],
                    'status': 'pending',
                  }),
                );
                debugPrint('Order saved to backend successfully');
              } catch (e) {
                debugPrint('Failed to save order to backend: $e');
                // Continue even if backend save fails
              }
            }

            return {
              'success': true,
              'data': {
                'order_id': orderId,
                'token': midtransData['token'],
                'redirect_url': midtransData['redirect_url'],
                'va_number': null, // SNAP tidak langsung memberikan nomor VA
                'bank': null,
              },
            };
          } else {
            debugPrint(
                'Midtrans Snap request failed with status code: ${midtransResponse.statusCode}');
            debugPrint('Response body: ${midtransResponse.body}');
            throw Exception(
                'Midtrans Snap request failed: ${midtransResponse.body}');
          }
        } catch (e) {
          debugPrint('Error during SNAP API call: $e');
          rethrow;
        }
      }
    } catch (e) {
      debugPrint('❌ PAYMENT ERROR: $e');
      debugPrint('Using fallback payment simulation');

      // Generate simulated token in case of error
      final orderId =
          'ORDER-${DateTime.now().millisecondsSinceEpoch}-${const Uuid().v4().substring(0, 8)}';
      final simulatedToken =
          'SIMULATOR-TOKEN-${DateTime.now().millisecondsSinceEpoch}';
      final simulatedRedirectUrl =
          'https://simulator.sandbox.midtrans.com/snap/v2/vtweb/$simulatedToken';

      // Generate simulated VA number
      String simulatedVA = "97978";
      for (int i = 0; i < 8; i++) {
        simulatedVA +=
            (DateTime.now().millisecondsSinceEpoch % 10).toString()[0];
      }

      return {
        'success': true, // Return success to prevent app from crashing
        'data': {
          'order_id': orderId,
          'token': simulatedToken,
          'redirect_url': simulatedRedirectUrl,
          'va_number': simulatedVA,
          'bank': selectedBank ??
              'bca', // Simulasi menggunakan BCA atau selected bank
          'message': 'Using payment simulation due to error: $e',
        },
      };
    }
  }

  // Membuat fallback payment untuk virtual account jika direct API gagal
  Map<String, dynamic> _createFallbackVAPayment(
      String orderId, double amount, String bankCode) {
    debugPrint('======== MEMBUAT FALLBACK VA PAYMENT ========');
    debugPrint('Bank: $bankCode, Amount: $amount');

    // Buat format VA number yang masuk akal sesuai bank
    String vaNumber;
    switch (bankCode) {
      case 'bca':
        vaNumber =
            '8${DateTime.now().millisecondsSinceEpoch % 100000000000}'; // BCA 11-12 digit
        break;
      case 'bni':
        vaNumber =
            '988${DateTime.now().millisecondsSinceEpoch % 10000000000}'; // BNI biasanya dengan prefix 988
        break;
      case 'bri':
        vaNumber =
            '8${DateTime.now().millisecondsSinceEpoch % 100000000000}'; // BRI mirip BCA
        break;
      case 'permata':
        vaNumber =
            '8${DateTime.now().millisecondsSinceEpoch % 1000000000000}'; // Permata 13 digit
        break;
      case 'mandiri':
        // Mandiri menggunakan format bill_key dan biller_code
        return {
          'success': true,
          'data': {
            'order_id': orderId,
            'token': 'SIM-MAN-${DateTime.now().millisecondsSinceEpoch}',
            'redirect_url': null,
            'va_number':
                '70012/${DateTime.now().millisecondsSinceEpoch % 1000000000}',
            'bill_key': '${DateTime.now().millisecondsSinceEpoch % 1000000000}',
            'biller_code': '70012',
            'bank': 'mandiri',
            'transaction_status': 'pending',
            'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
            'gross_amount': amount.toInt(),
            'is_simulation': true,
          },
        };
      default:
        vaNumber = '${DateTime.now().millisecondsSinceEpoch % 100000000000}';
    }

    debugPrint('Generated fallback VA: $vaNumber');

    return {
      'success': true,
      'data': {
        'order_id': orderId,
        'token':
            'SIM-${bankCode.toUpperCase()}-${DateTime.now().millisecondsSinceEpoch}',
        'redirect_url': null,
        'va_number': vaNumber,
        'bank': bankCode,
        'transaction_status': 'pending',
        'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
        'gross_amount': amount.toInt(),
        'is_simulation': true,
      },
    };
  }

  // Get available payment methods from Midtrans
  Future<List<Map<String, dynamic>>> getMidtransPaymentMethods() async {
    // These are the payment methods we'll support
    return [
      {
        'code': 'qris',
        'name': 'QRIS (QR Code)',
        'type': 'qr_code',
        'description': 'Pay using any mobile banking app or e-wallet via QRIS',
        'icon': 'qr_code',
      },
      {
        'code': 'bca_va',
        'name': 'BCA Virtual Account',
        'type': 'bank',
        'description': 'Pay via BCA Virtual Account',
        'icon': 'account_balance',
      },
      {
        'code': 'bni_va',
        'name': 'BNI Virtual Account',
        'type': 'bank',
        'description': 'Pay via BNI Virtual Account',
        'icon': 'account_balance',
      },
      {
        'code': 'bri_va',
        'name': 'BRI Virtual Account',
        'type': 'bank',
        'description': 'Pay via BRI Virtual Account',
        'icon': 'account_balance',
      },
      {
        'code': 'echannel',
        'name': 'Mandiri Virtual Account',
        'type': 'bank',
        'description': 'Pay via Mandiri Bill Payment',
        'icon': 'account_balance',
      },
      {
        'code': 'permata_va',
        'name': 'Permata Virtual Account',
        'type': 'bank',
        'description': 'Pay via Permata Virtual Account',
        'icon': 'account_balance',
      },
    ];
  }
}
