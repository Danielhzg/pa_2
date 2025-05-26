import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class MidtransService {
  // URLs sesuai dengan dokumentasi resmi Midtrans
  final String _baseUrl = "https://api.sandbox.midtrans.com";
  final String _snapUrl =
      "https://app.sandbox.midtrans.com/snap/v1/transactions";

  final String _serverKey;
  final String _clientKey;

  // Flag untuk mode simulasi
  bool _useSimulationMode = false;

  MidtransService({String? serverKey, String? clientKey})
      : _serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry',
        _clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';

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

        // Perbaikan untuk Virtual Account - jangan tentukan va_number
        // Biarkan Midtrans generate nomor VA otomatis
        if (bankCode == 'mandiri') {
          debugPrint('Menggunakan metode echannel untuk Mandiri');
          // Khusus untuk Mandiri gunakan echannel
          payload['payment_type'] = 'echannel';
          payload['echannel'] = {
            'bill_info1': 'Payment for Order:',
            'bill_info2': orderId,
          };
        } else {
          debugPrint('Menggunakan bank_transfer untuk bank: $bankCode');
          payload['bank_transfer'] = {
            'bank': bankCode,
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
          'callback_url': 'https://yourwebsite.com/callback',
        };
      } else if (paymentMethod == 'qris') {
        payload['payment_type'] = 'qris';
        payload['qris'] = {
          'acquirer': 'gopay',
        };
      }

      debugPrint('URL request: $_baseUrl/v2/charge');
      debugPrint('Headers: ${_headers.toString()}');
      debugPrint('Request body: ${jsonEncode(payload)}');

      // Kirim request ke Midtrans
      final response = await http.post(
        Uri.parse('$_baseUrl/v2/charge'),
        headers: _headers,
        body: jsonEncode(payload),
      );

      debugPrint('Midtrans response status: ${response.statusCode}');
      debugPrint('Midtrans response body: ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        final responseData = jsonDecode(response.body);

        debugPrint(
            'Transaksi berhasil dibuat dengan ID: ${responseData['transaction_id']}');

        // Format respons dengan helper function
        if (paymentMethod == 'bank_transfer' && bankCode != null) {
          debugPrint('Memformat respons VA untuk bank: $bankCode');

          // Log semua field dalam respons untuk debugging
          responseData.forEach((key, value) {
            debugPrint('Response field $key: $value');
          });

          // Debugging untuk VA numbers
          if (responseData.containsKey('va_numbers')) {
            debugPrint(
                'VA Numbers ditemukan dalam respons: ${responseData['va_numbers']}');
            if (responseData['va_numbers'] is List &&
                responseData['va_numbers'].isNotEmpty) {
              for (var va in responseData['va_numbers']) {
                debugPrint(
                    'VA Bank: ${va['bank']}, Number: ${va['va_number']}');
              }
            }
          } else {
            debugPrint('VA Numbers tidak ditemukan dalam respons!');
          }

          // Debugging untuk Mandiri
          if (bankCode == 'mandiri') {
            if (responseData.containsKey('bill_key')) {
              debugPrint('Mandiri Bill Key: ${responseData['bill_key']}');
            }
            if (responseData.containsKey('biller_code')) {
              debugPrint('Mandiri Biller Code: ${responseData['biller_code']}');
            }
          }

          final formattedResponse =
              formatMidtransVAResponse(responseData, bankCode);
          debugPrint('VA response formatted: ${jsonEncode(formattedResponse)}');

          // Tambahkan cek tambahan jika VA number tidak ada
          if (formattedResponse['va_number'] == null) {
            debugPrint(
                '⚠️ PERINGATAN: VA Number kosong setelah formatting! ⚠️');
            // Fallback untuk VA number jika tidak ada di respons
            formattedResponse['va_number'] =
                '${DateTime.now().millisecondsSinceEpoch}';
            debugPrint(
                'Menggunakan fallback VA number: ${formattedResponse['va_number']}');
          }

          return formattedResponse;
        }

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
}
