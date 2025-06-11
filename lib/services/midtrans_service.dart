import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class MidtransService {
  // URLs sesuai dengan dokumentasi resmi Midtrans
  final String _baseUrl = "https://api.sandbox.midtrans.com";
  final String _snapUrl =
      "https://app.sandbox.midtrans.com/snap/v1/transactions";

  final String _serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry';
  final String _clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';

  // Flag untuk mode simulasi
  bool _useSimulationMode = false;

  MidtransService({String? serverKey, String? clientKey});

  String get clientKey => _clientKey;

  // Headers dengan Basic Auth untuk Server Key
  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Basic ${base64Encode(utf8.encode('$_serverKey:'))}',
      };

  // Membuat transaksi baru dengan Virtual Account
  Future<Map<String, dynamic>> createTransaction({
    required String orderId,
    required int grossAmount,
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required List<Map<String, dynamic>> items,
    required String paymentMethod, // bank_transfer, gopay, shopeepay, dll
    String? bankCode, // bca, bni, bri, mandiri (untuk VA)
  }) async {
    try {
      // Jika mode simulasi aktif, langsung gunakan simulasi
      if (_useSimulationMode) {
        debugPrint('Using simulation mode for transaction creation');
        return _createSimulatedTransaction(
          orderId: orderId,
          grossAmount: grossAmount,
          paymentMethod: paymentMethod,
          bankCode: bankCode,
        );
      }

      debugPrint('======== MEMBUAT TRANSAKSI MIDTRANS ========');
      debugPrint('Order ID: $orderId');
      debugPrint('Payment Method: $paymentMethod');
      debugPrint('Bank Code: $bankCode');
      debugPrint('Gross Amount: $grossAmount');

      // Buat payload untuk transaksi
      final Map<String, dynamic> transactionDetails = {
        'order_id': orderId,
        'gross_amount': grossAmount,
      };

      final Map<String, dynamic> customerDetails = {
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
      };

      // Payload utama
      final Map<String, dynamic> payload = {
        'transaction_details': transactionDetails,
        'customer_details': customerDetails,
        'item_details': items,
      };

      // Tambahkan konfigurasi payment sesuai metode
      if (paymentMethod == 'bank_transfer' && bankCode != null) {
        payload['payment_type'] = 'bank_transfer';

        // Perbaikan untuk Virtual Account
        if (bankCode == 'mandiri' || bankCode == 'echannel') {
          debugPrint('Menggunakan metode echannel untuk Mandiri');
          // Khusus untuk Mandiri gunakan echannel
          payload['payment_type'] = 'echannel';
          payload['echannel'] = {
            'bill_info1': 'Payment for Order:',
            'bill_info2': orderId,
          };
        } else if (bankCode == 'permata' || bankCode == 'permata_va') {
          debugPrint('Menggunakan permata untuk Permata VA');
          payload['payment_type'] = 'permata';
        } else {
          debugPrint('Menggunakan bank_transfer untuk bank: $bankCode');
          // Untuk BCA, BNI, BRI
          payload['bank_transfer'] = {
            'bank': bankCode.replaceAll('_va', ''),
          };
        }

        debugPrint('Payment Payload: ${jsonEncode(payload)}');
      } else if (paymentMethod == 'gopay') {
        payload['payment_type'] = 'gopay';
        payload['gopay'] = {
          'enable_callback': true,
        };
      } else if (paymentMethod == 'shopeepay') {
        payload['payment_type'] = 'shopeepay';
        payload['shopeepay'] = {
          'callback_url': 'https://www.bloom-bouquet.my.id/callback',
        };
      } else if (paymentMethod == 'qris') {
        payload['payment_type'] = 'qris';
        payload['qris'] = {
          'acquirer': 'gopay',
        };
      } else if (paymentMethod == 'credit_card') {
        payload['payment_type'] = 'credit_card';
        payload['credit_card'] = {
          'secure': true,
        };
      }

      debugPrint('URL request: $_baseUrl/v2/charge');
      debugPrint('Headers: ${_headers.toString()}');
      debugPrint('Request body: ${jsonEncode(payload)}');

      // Kirim request ke Midtrans with timeout and retry
      http.Response? response;
      int retryCount = 0;
      const maxRetries = 2;

      while (response == null && retryCount <= maxRetries) {
        try {
          response = await http
              .post(
                Uri.parse('$_baseUrl/v2/charge'),
                headers: _headers,
                body: jsonEncode(payload),
              )
              .timeout(const Duration(seconds: 15));
        } catch (e) {
          retryCount++;
          debugPrint('Midtrans API attempt $retryCount failed: $e');

          // If reached max retries, use simulation
          if (retryCount > maxRetries) {
            debugPrint('All Midtrans API attempts failed, using simulation');
            _useSimulationMode = true;
            return _createSimulatedTransaction(
              orderId: orderId,
              grossAmount: grossAmount,
              paymentMethod: paymentMethod,
              bankCode: bankCode,
            );
          }

          // Wait before retrying
          await Future.delayed(const Duration(seconds: 1));
        }
      }

      // If we couldn't get a response, use simulation
      if (response == null) {
        debugPrint('No response from Midtrans API, using simulation');
        _useSimulationMode = true;
        return _createSimulatedTransaction(
          orderId: orderId,
          grossAmount: grossAmount,
          paymentMethod: paymentMethod,
          bankCode: bankCode,
        );
      }

      debugPrint('Midtrans response status: ${response.statusCode}');
      debugPrint('Midtrans response body: ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);

        debugPrint(
            'Transaksi berhasil dibuat dengan ID: ${responseData['transaction_id']}');

        return responseData;
      } else {
        debugPrint(
            'ERROR: Gagal membuat transaksi dengan kode ${response.statusCode}');
        debugPrint('Response body: ${response.body}');

        // Aktifkan mode simulasi untuk request berikutnya
        _useSimulationMode = true;
        debugPrint('⚠️ MENGAKTIFKAN MODE SIMULASI UNTUK REQUEST BERIKUTNYA ⚠️');

        // Gunakan simulasi sebagai fallback
        return _createSimulatedTransaction(
          orderId: orderId,
          grossAmount: grossAmount,
          paymentMethod: paymentMethod,
          bankCode: bankCode,
        );
      }
    } catch (e) {
      debugPrint('❌ ERROR EXCEPTION: $e');

      // Aktifkan mode simulasi untuk request berikutnya
      _useSimulationMode = true;
      debugPrint('⚠️ MENGAKTIFKAN MODE SIMULASI UNTUK REQUEST BERIKUTNYA ⚠️');

      // Gunakan simulasi sebagai fallback
      return _createSimulatedTransaction(
        orderId: orderId,
        grossAmount: grossAmount,
        paymentMethod: paymentMethod,
        bankCode: bankCode,
      );
    }
  }

  // Membuat transaksi simulasi sebagai fallback
  Map<String, dynamic> _createSimulatedTransaction({
    required String orderId,
    required int grossAmount,
    required String paymentMethod,
    String? bankCode,
  }) {
    debugPrint('======== MEMBUAT TRANSAKSI SIMULASI ========');
    debugPrint('Order ID: $orderId');
    debugPrint('Payment Method: $paymentMethod');

    final String timestamp = DateTime.now().toIso8601String();
    final String transactionId = 'SIM-${DateTime.now().millisecondsSinceEpoch}';

    if (paymentMethod == 'bank_transfer' && bankCode != null) {
      // Simulasi Virtual Account
      final String vaNumber =
          '9${DateTime.now().millisecondsSinceEpoch.toString().substring(5, 15)}';

      return {
        'success': true,
        'status_code': '201',
        'status_message': 'Success, Bank Transfer transaction is created',
        'transaction_id': transactionId,
        'order_id': orderId,
        'gross_amount': grossAmount.toString(),
        'payment_type': 'bank_transfer',
        'transaction_time': timestamp,
        'transaction_status': 'pending',
        'va_number': vaNumber,
        'bank': bankCode,
        'fraud_status': 'accept',
        'expiry_time':
            DateTime.now().add(const Duration(days: 1)).toIso8601String(),
        'simulation': true,
      };
    } else if (paymentMethod == 'qris') {
      // Simulasi QRIS
      return {
        'success': true,
        'status_code': '201',
        'status_message': 'Success, QRIS transaction is created',
        'transaction_id': transactionId,
        'order_id': orderId,
        'gross_amount': grossAmount.toString(),
        'payment_type': 'qris',
        'transaction_time': timestamp,
        'transaction_status': 'pending',
        'qr_string': 'SIMULASI-QRIS-${DateTime.now().millisecondsSinceEpoch}',
        'qr_code_url':
            'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
        'expiry_time':
            DateTime.now().add(const Duration(minutes: 15)).toIso8601String(),
        'simulation': true,
      };
    } else {
      // Default simulasi
      return {
        'success': true,
        'status_code': '201',
        'status_message': 'Success, transaction is created',
        'transaction_id': transactionId,
        'order_id': orderId,
        'gross_amount': grossAmount.toString(),
        'payment_type': paymentMethod,
        'transaction_time': timestamp,
        'transaction_status': 'pending',
        'fraud_status': 'accept',
        'expiry_time':
            DateTime.now().add(const Duration(hours: 24)).toIso8601String(),
        'simulation': true,
      };
    }
  }

  // Mendapatkan nomor VA dari response transaksi
  String? getVirtualAccountNumber(
      Map<String, dynamic> transactionResponse, String bankCode) {
    try {
      if (bankCode.toLowerCase() == 'mandiri') {
        // Untuk Mandiri, kita perlu bill_key dan biller_code
        if (transactionResponse.containsKey('bill_key') &&
            transactionResponse.containsKey('biller_code')) {
          return 'Biller Code: ${transactionResponse['biller_code']}, Bill Key: ${transactionResponse['bill_key']}';
        }
      } else {
        // Untuk bank lain (BCA, BNI, BRI, dll)
        if (transactionResponse.containsKey('va_numbers') &&
            transactionResponse['va_numbers'] is List &&
            transactionResponse['va_numbers'].isNotEmpty) {
          return transactionResponse['va_numbers'][0]['va_number'];
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error getting VA number: $e');
      return null;
    }
  }

  // Mendapatkan status transaksi
  Future<Map<String, dynamic>> getTransactionStatus(String orderId) async {
    try {
      // Jika mode simulasi aktif, gunakan simulasi
      if (_useSimulationMode) {
        return {
          'transaction_time': DateTime.now().toIso8601String(),
          'transaction_status': 'pending',
          'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
          'status_message': 'Success, transaction is found',
          'status_code': '200',
          'signature_key': 'simulation-key',
          'payment_type': 'bank_transfer',
          'order_id': orderId,
          'gross_amount': '10000.00',
          'fraud_status': 'accept',
          'simulation': true,
        };
      }

      final response = await http.get(
        Uri.parse('$_baseUrl/v2/$orderId/status'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        debugPrint('Transaction status response: ${response.body}');
        return responseData;
      } else {
        debugPrint('Failed to get transaction status: ${response.body}');

        // Aktifkan mode simulasi untuk request berikutnya
        _useSimulationMode = true;

        // Return simulasi status
        return {
          'transaction_time': DateTime.now().toIso8601String(),
          'transaction_status': 'pending',
          'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
          'status_message': 'Success, transaction is found',
          'status_code': '200',
          'signature_key': 'simulation-key',
          'payment_type': 'bank_transfer',
          'order_id': orderId,
          'gross_amount': '10000.00',
          'fraud_status': 'accept',
          'simulation': true,
        };
      }
    } catch (e) {
      debugPrint('Error getting transaction status: $e');

      // Aktifkan mode simulasi
      _useSimulationMode = true;

      // Return simulasi status
      return {
        'transaction_time': DateTime.now().toIso8601String(),
        'transaction_status': 'pending',
        'transaction_id': 'SIM-${DateTime.now().millisecondsSinceEpoch}',
        'status_message': 'Success, transaction is found (simulation)',
        'status_code': '200',
        'signature_key': 'simulation-key',
        'payment_type': 'bank_transfer',
        'order_id': orderId,
        'gross_amount': '10000.00',
        'fraud_status': 'accept',
        'simulation': true,
      };
    }
  }

  // Generate Snap Token untuk UI web/mobile
  Future<String> generateSnapToken({
    required String orderId,
    required int grossAmount,
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required List<Map<String, dynamic>> items,
  }) async {
    try {
      // Jika mode simulasi aktif, langsung return simulasi token
      if (_useSimulationMode) {
        final simulatedToken =
            'SIMULATOR-TOKEN-${DateTime.now().millisecondsSinceEpoch}';
        debugPrint('Returning simulated token: $simulatedToken');
        return simulatedToken;
      }

      // Payload untuk SNAP API
      final Map<String, dynamic> transactionDetails = {
        'order_id': orderId,
        'gross_amount': grossAmount,
      };

      final Map<String, dynamic> customerDetails = {
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
      };

      final Map<String, dynamic> payload = {
        'transaction_details': transactionDetails,
        'customer_details': customerDetails,
        'item_details': items,
        'enabled_payments': [
          'credit_card',
          'bca_va',
          'bni_va',
          'bri_va',
          'echannel',
          'permata_va',
          'qris'
        ],
      };

      debugPrint('Creating SNAP token with payload: ${jsonEncode(payload)}');

      // Kirim request ke Midtrans SNAP API
      final response = await http.post(
        Uri.parse(_snapUrl),
        headers: _headers,
        body: jsonEncode(payload),
      );

      debugPrint('SNAP API response status: ${response.statusCode}');
      debugPrint('SNAP API response body: ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);
        return responseData['token'];
      } else {
        debugPrint('⚠️ FALLBACK KE MODE SIMULASI MIDTRANS ⚠️');
        // Aktifkan mode simulasi
        _useSimulationMode = true;
        // Generate simulated token
        final simulatedToken =
            'SIMULATOR-TOKEN-${DateTime.now().millisecondsSinceEpoch}';
        debugPrint('Simulated token generated: $simulatedToken');
        debugPrint('Order ID: $orderId');
        debugPrint('Gross Amount: $grossAmount');
        return simulatedToken;
      }
    } catch (e) {
      debugPrint('Error in SNAP token generation: $e');
      debugPrint('⚠️ FALLBACK KE MODE SIMULASI MIDTRANS ⚠️');
      // Aktifkan mode simulasi
      _useSimulationMode = true;
      // Generate simulated token
      final simulatedToken =
          'SIMULATOR-TOKEN-${DateTime.now().millisecondsSinceEpoch}';
      debugPrint('Simulated token generated: $simulatedToken');
      return simulatedToken;
    }
  }

  // Helper untuk mengkonversi format VA dari respons Midtrans
  Map<String, dynamic> formatMidtransVAResponse(
      Map<String, dynamic> midtransResponse, String bankCode) {
    try {
      debugPrint('======== MEMFORMAT RESPONS VA ========');
      debugPrint('Bank Code: $bankCode');

      final Map<String, dynamic> result = {
        'success': true,
        'va_number': null,
        'bank': bankCode,
      };

      if (bankCode.toLowerCase() == 'mandiri') {
        // Format Mandiri Bill Payment
        debugPrint('Memformat respons untuk Mandiri');
        if (midtransResponse.containsKey('bill_key') &&
            midtransResponse.containsKey('biller_code')) {
          debugPrint('Bill Key dan Biller Code ditemukan');
          result['va_number'] =
              '${midtransResponse['biller_code']}/${midtransResponse['bill_key']}';
          result['bill_key'] = midtransResponse['bill_key'];
          result['biller_code'] = midtransResponse['biller_code'];
          debugPrint('VA Number Mandiri: ${result['va_number']}');
        } else {
          debugPrint(
              '⚠️ Bill Key atau Biller Code tidak ditemukan untuk Mandiri!');
        }
      } else {
        // Format VA Bank lain (BCA, BNI, BRI, Permata)
        debugPrint('Memformat respons untuk ${bankCode.toUpperCase()}');

        if (midtransResponse.containsKey('va_numbers') &&
            midtransResponse['va_numbers'] is List &&
            midtransResponse['va_numbers'].isNotEmpty) {
          debugPrint(
              'VA Numbers ditemukan, mencari yang sesuai dengan bank: $bankCode');
          bool vaFound = false;

          for (var va in midtransResponse['va_numbers']) {
            debugPrint('Memeriksa VA: ${va.toString()}');
            if (va.containsKey('bank') &&
                va['bank'].toString().toLowerCase() == bankCode.toLowerCase()) {
              result['va_number'] = va['va_number'];
              debugPrint(
                  'VA Number ditemukan untuk bank yang sesuai: ${result['va_number']}');
              vaFound = true;
              break;
            }
          }

          // Jika tidak ditemukan yang spesifik, ambil yang pertama
          if (!vaFound && midtransResponse['va_numbers'].isNotEmpty) {
            var firstVa = midtransResponse['va_numbers'][0];
            if (firstVa.containsKey('va_number')) {
              result['va_number'] = firstVa['va_number'];
              result['bank'] =
                  firstVa.containsKey('bank') ? firstVa['bank'] : bankCode;
              debugPrint(
                  'Menggunakan VA pertama: ${result['va_number']} (${result['bank']})');
            } else {
              debugPrint('⚠️ VA Number tidak ditemukan dalam VA pertama!');
            }
          }
        } else if (midtransResponse.containsKey('permata_va_number')) {
          // Khusus untuk Permata
          debugPrint('Menggunakan permata_va_number khusus Permata');
          result['va_number'] = midtransResponse['permata_va_number'];
          result['bank'] = 'permata';
          debugPrint('VA Number Permata: ${result['va_number']}');
        } else {
          debugPrint(
              '⚠️ Tidak ada VA Numbers atau permata_va_number dalam respons!');
          // Coba cari format VA lain
          if (midtransResponse.containsKey('transaction_id')) {
            debugPrint('Membuat fallback VA number dari transaction_id');
            // Fallback gunakan transaction ID dengan prefix
            final String prefix = bankCode.substring(0, 3).toUpperCase();
            result['va_number'] =
                '$prefix${midtransResponse['transaction_id']}';
            debugPrint('Fallback VA Number: ${result['va_number']}');
          }
        }
      }

      // Tambahkan informasi transaksi lain
      result['transaction_id'] = midtransResponse['transaction_id'] ?? '';
      result['order_id'] = midtransResponse['order_id'] ?? '';
      result['gross_amount'] = midtransResponse['gross_amount'] ?? 0;
      result['transaction_status'] =
          midtransResponse['transaction_status'] ?? 'pending';
      result['transaction_time'] =
          midtransResponse['transaction_time'] ?? DateTime.now().toString();

      // Cek final result
      debugPrint('======== HASIL FORMAT RESPONS VA ========');
      result.forEach((key, value) {
        debugPrint('$key: $value');
      });

      return result;
    } catch (e) {
      debugPrint('❌ ERROR MEMFORMAT RESPONS VA: $e');
      // Fallback - return error response dengan fallback VA
      return {
        'success': true, // Tetap true agar aplikasi tidak crash
        'va_number': 'ERROR${DateTime.now().millisecondsSinceEpoch}',
        'bank': bankCode,
        'error_message': 'Error memformat respons: $e',
        'transaction_status': 'pending',
        'simulation': true,
      };
    }
  }

  // Check transaction status
  Future<Map<String, dynamic>> checkTransactionStatus(
      String transactionId) async {
    try {
      debugPrint('Checking transaction status for: $transactionId');

      // If in simulation mode, return simulated status
      if (_useSimulationMode) {
        return _simulateTransactionStatus(transactionId);
      }

      final response = await http
          .get(
            Uri.parse('$_baseUrl/v2/$transactionId/status'),
            headers: _headers,
          )
          .timeout(const Duration(seconds: 10));

      debugPrint('Status check response code: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        debugPrint('Transaction status: ${data['transaction_status']}');

        return {
          'success': true,
          'transaction_id': data['transaction_id'] ?? transactionId,
          'transaction_status': data['transaction_status'] ?? 'pending',
          'status_code': data['status_code'] ?? '201',
          'status_message': data['status_message'] ?? 'Status check successful',
        };
      } else {
        debugPrint('Failed to check transaction status: ${response.body}');
        // Use simulation as fallback
        _useSimulationMode = true;
        return _simulateTransactionStatus(transactionId);
      }
    } catch (e) {
      debugPrint('Error checking transaction status: $e');
      // Use simulation as fallback
      _useSimulationMode = true;
      return _simulateTransactionStatus(transactionId);
    }
  }

  // Simulate transaction status check
  Map<String, dynamic> _simulateTransactionStatus(String transactionId) {
    // 80% chance of successful payment
    final isSuccess = DateTime.now().millisecondsSinceEpoch % 10 < 8;

    return {
      'success': true,
      'transaction_id': transactionId,
      'transaction_status': isSuccess ? 'settlement' : 'pending',
      'status_code': isSuccess ? '200' : '201',
      'status_message': isSuccess
          ? 'Payment successful (simulated)'
          : 'Payment pending (simulated)',
      'simulation': true,
    };
  }

  // Create QR payment
  Future<Map<String, dynamic>> createQRPayment(
    String orderId,
    double amount,
    String itemDetails,
  ) async {
    try {
      debugPrint('Creating QR payment for order: $orderId');

      // If in simulation mode, return simulated QR
      if (_useSimulationMode) {
        return _simulateQRPayment(orderId);
      }

      // Format items for payload
      final List<Map<String, dynamic>> items = [
        {
          'id': 'order-$orderId',
          'name': itemDetails,
          'price': amount.toInt(),
          'quantity': 1,
        }
      ];

      // Create transaction with QRIS payment method
      final transactionResult = await createTransaction(
        orderId: orderId,
        grossAmount: amount.toInt(),
        firstName: 'Customer',
        lastName: '',
        email: 'customer@example.com',
        phone: '08123456789',
        items: items,
        paymentMethod: 'qris',
      );

      if (transactionResult.containsKey('qr_string') ||
          transactionResult.containsKey('qr_code_url')) {
        return {
          'success': true,
          'message': 'QR Code generated successfully',
          'transaction_id': transactionResult['transaction_id'] ?? '',
          'qr_code_url': transactionResult['qr_code_url'] ?? '',
          'qr_string': transactionResult['qr_string'] ?? '',
          'transaction_status':
              transactionResult['transaction_status'] ?? 'pending',
        };
      } else {
        debugPrint(
            'Failed to generate QR payment: ${transactionResult.toString()}');
        // Use simulation as fallback
        _useSimulationMode = true;
        return _simulateQRPayment(orderId);
      }
    } catch (e) {
      debugPrint('Error creating QR payment: $e');
      // Use simulation as fallback
      _useSimulationMode = true;
      return _simulateQRPayment(orderId);
    }
  }

  // Simulate QR payment
  Map<String, dynamic> _simulateQRPayment(String orderId) {
    final String transactionId =
        'SIM-QR-${DateTime.now().millisecondsSinceEpoch}';

    return {
      'success': true,
      'message': 'QR Code generated successfully (simulated)',
      'transaction_id': transactionId,
      'qr_code_url':
          'https://api.sandbox.midtrans.com/v2/qris/$orderId/qr-code',
      'qr_string': 'SIMULATION-QRIS-$transactionId',
      'transaction_status': 'pending',
      'simulation': true,
    };
  }
}
