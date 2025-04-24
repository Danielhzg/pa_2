import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/payment.dart';

class PaymentService {
  // Use the same base URL as in ApiService
  final String baseUrl = 'http://10.0.2.2:8000/api';

  // Generate QR code for payment based on the total amount
  Future<String> generateQRCode(double amount, String orderId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/v1/payments/qr-code'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'amount': amount,
          'orderId': orderId,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          // Return the URL of the generated QR code
          return data['data']['qrCodeUrl'];
        } else {
          throw Exception('Failed to generate QR code: ${data['message']}');
        }
      } else {
        throw Exception('Failed to generate QR code: ${response.statusCode}');
      }
    } catch (e) {
      print('Error generating QR code: $e');
      throw Exception('Failed to generate QR code: $e');
    }
  }

  // Process payment
  Future<Payment> processPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? qrCodeUrl,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/v1/payments/process'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'orderId': orderId,
          'amount': amount,
          'paymentMethod': paymentMethod,
          'qrCodeUrl': qrCodeUrl,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return Payment.fromJson(data['data']);
        } else {
          throw Exception('Payment processing failed: ${data['message']}');
        }
      } else {
        throw Exception('Payment processing failed: ${response.statusCode}');
      }
    } catch (e) {
      print('Error processing payment: $e');
      throw Exception('Payment processing failed: $e');
    }
  }

  // Check payment status
  Future<String> checkPaymentStatus(String paymentId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/v1/payments/$paymentId/status'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['data'] != null) {
          return data['data']['status'];
        } else {
          throw Exception('Failed to check payment status: ${data['message']}');
        }
      } else {
        throw Exception(
            'Failed to check payment status: ${response.statusCode}');
      }
    } catch (e) {
      print('Error checking payment status: $e');
      throw Exception('Failed to check payment status: $e');
    }
  }
}
