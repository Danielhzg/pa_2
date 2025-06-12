import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  await testOrderStatusUpdate();
}

Future<void> testOrderStatusUpdate() async {
  const String baseUrl = 'https://dec8-114-122-41-11.ngrok-free.app/api/v1';
  
  // Test data - replace with actual order ID and token
  const String orderId = 'ORDER-1'; // Replace with actual order ID
  const String authToken = 'your_auth_token_here'; // Replace with actual token
  
  print('Testing Order Status Update...');
  print('Base URL: $baseUrl');
  print('Order ID: $orderId');
  
  try {
    // 1. First, get current order status
    print('\n1. Getting current order status...');
    final getResponse = await http.get(
      Uri.parse('$baseUrl/orders/$orderId'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
    );
    
    print('GET Response Status: ${getResponse.statusCode}');
    print('GET Response Body: ${getResponse.body}');
    
    if (getResponse.statusCode == 200) {
      final orderData = json.decode(getResponse.body);
      if (orderData['success'] == true) {
        final currentStatus = orderData['data']['status'];
        final currentPaymentStatus = orderData['data']['payment_status'];
        print('Current Status: $currentStatus');
        print('Current Payment Status: $currentPaymentStatus');
        
        // 2. Update payment status to 'paid'
        print('\n2. Updating payment status to "paid"...');
        final updatePaymentResponse = await http.post(
          Uri.parse('$baseUrl/orders/$orderId/payment-status'),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer $authToken',
          },
          body: json.encode({
            'payment_status': 'paid',
            'updated_by': 'test_script',
          }),
        );
        
        print('Payment Update Response Status: ${updatePaymentResponse.statusCode}');
        print('Payment Update Response Body: ${updatePaymentResponse.body}');
        
        // 3. Update order status to 'processing'
        print('\n3. Updating order status to "processing"...');
        final updateStatusResponse = await http.post(
          Uri.parse('$baseUrl/orders/$orderId/status'),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer $authToken',
          },
          body: json.encode({
            'status': 'processing',
            'updated_by': 'test_script',
          }),
        );
        
        print('Status Update Response Status: ${updateStatusResponse.statusCode}');
        print('Status Update Response Body: ${updateStatusResponse.body}');
        
        // 4. Get updated order status
        print('\n4. Getting updated order status...');
        final finalGetResponse = await http.get(
          Uri.parse('$baseUrl/orders/$orderId'),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer $authToken',
          },
        );
        
        print('Final GET Response Status: ${finalGetResponse.statusCode}');
        print('Final GET Response Body: ${finalGetResponse.body}');
        
        if (finalGetResponse.statusCode == 200) {
          final finalOrderData = json.decode(finalGetResponse.body);
          if (finalOrderData['success'] == true) {
            final finalStatus = finalOrderData['data']['status'];
            final finalPaymentStatus = finalOrderData['data']['payment_status'];
            print('\nFinal Status: $finalStatus');
            print('Final Payment Status: $finalPaymentStatus');
            
            if (finalStatus == 'processing' && finalPaymentStatus == 'paid') {
              print('\n✅ SUCCESS: Order status updated correctly!');
            } else {
              print('\n❌ FAILED: Order status not updated correctly');
            }
          }
        }
      }
    }
    
  } catch (e) {
    print('Error: $e');
  }
}
