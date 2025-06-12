import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  await testCompleteOrderFlow();
}

Future<void> testCompleteOrderFlow() async {
  const String baseUrl = 'https://dec8-114-122-41-11.ngrok-free.app/api/v1';
  
  // Test credentials - replace with actual values
  const String email = 'test@example.com';
  const String password = 'password123';
  
  print('🧪 Testing Complete Order Flow...');
  print('Base URL: $baseUrl');
  
  try {
    // 1. Login to get auth token
    print('\n1️⃣ Logging in...');
    final loginResponse = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: json.encode({
        'email': email,
        'password': password,
      }),
    );
    
    print('Login Response Status: ${loginResponse.statusCode}');
    print('Login Response Body: ${loginResponse.body}');
    
    if (loginResponse.statusCode != 200) {
      print('❌ Login failed. Please check credentials.');
      return;
    }
    
    final loginData = json.decode(loginResponse.body);
    if (loginData['success'] != true) {
      print('❌ Login failed: ${loginData['message']}');
      return;
    }
    
    final authToken = loginData['data']['token'];
    final userId = loginData['data']['user']['id'];
    print('✅ Login successful. User ID: $userId');
    
    // 2. Create a test order
    print('\n2️⃣ Creating test order...');
    final orderData = {
      'items': [
        {
          'id': 1,
          'name': 'Test Product',
          'price': 50000,
          'quantity': 1,
        }
      ],
      'deliveryAddress': {
        'name': 'Test User',
        'phone': '081234567890',
        'address': 'Test Address',
        'email': email,
      },
      'subtotal': 50000,
      'shippingCost': 10000,
      'total': 60000,
      'paymentMethod': 'qris',
      'customer_name': 'Test User',
      'customer_email': email,
      'user_id': userId,
    };
    
    final createOrderResponse = await http.post(
      Uri.parse('$baseUrl/orders'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
      body: json.encode(orderData),
    );
    
    print('Create Order Response Status: ${createOrderResponse.statusCode}');
    print('Create Order Response Body: ${createOrderResponse.body}');
    
    if (createOrderResponse.statusCode != 201 && createOrderResponse.statusCode != 200) {
      print('❌ Order creation failed.');
      return;
    }
    
    final orderResponse = json.decode(createOrderResponse.body);
    if (orderResponse['success'] != true) {
      print('❌ Order creation failed: ${orderResponse['message']}');
      return;
    }
    
    final orderId = orderResponse['data']['id'];
    print('✅ Order created successfully. Order ID: $orderId');
    
    // 3. Get order details
    print('\n3️⃣ Getting order details...');
    final getOrderResponse = await http.get(
      Uri.parse('$baseUrl/orders/$orderId'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
    );
    
    print('Get Order Response Status: ${getOrderResponse.statusCode}');
    print('Get Order Response Body: ${getOrderResponse.body}');
    
    if (getOrderResponse.statusCode == 200) {
      final orderDetails = json.decode(getOrderResponse.body);
      if (orderDetails['success'] == true) {
        final currentStatus = orderDetails['data']['status'];
        final currentPaymentStatus = orderDetails['data']['payment_status'];
        print('✅ Current Order Status: $currentStatus');
        print('✅ Current Payment Status: $currentPaymentStatus');
      }
    }
    
    // 4. Update payment status to 'paid'
    print('\n4️⃣ Updating payment status to "paid"...');
    final updatePaymentResponse = await http.post(
      Uri.parse('$baseUrl/orders/$orderId/status'),
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
    
    print('Update Payment Response Status: ${updatePaymentResponse.statusCode}');
    print('Update Payment Response Body: ${updatePaymentResponse.body}');
    
    // 5. Update order status to 'processing'
    print('\n5️⃣ Updating order status to "processing"...');
    final updateStatusResponse = await http.post(
      Uri.parse('$baseUrl/orders/$orderId/status'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
      body: json.encode({
        'status': 'processing',
        'payment_status': 'paid',
        'updated_by': 'test_script',
      }),
    );
    
    print('Update Status Response Status: ${updateStatusResponse.statusCode}');
    print('Update Status Response Body: ${updateStatusResponse.body}');
    
    // 6. Get all user orders to verify
    print('\n6️⃣ Getting all user orders...');
    final getUserOrdersResponse = await http.get(
      Uri.parse('$baseUrl/orders'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
    );
    
    print('Get User Orders Response Status: ${getUserOrdersResponse.statusCode}');
    
    if (getUserOrdersResponse.statusCode == 200) {
      final ordersData = json.decode(getUserOrdersResponse.body);
      if (ordersData['success'] == true) {
        final orders = ordersData['data'] as List;
        print('✅ Found ${orders.length} orders for user');
        
        // Find our test order
        final testOrder = orders.firstWhere(
          (order) => order['id'] == orderId,
          orElse: () => null,
        );
        
        if (testOrder != null) {
          print('✅ Test order found in user orders:');
          print('   Order ID: ${testOrder['id']}');
          print('   Status: ${testOrder['status']}');
          print('   Payment Status: ${testOrder['payment_status']}');
          
          if (testOrder['status'] == 'processing' && testOrder['payment_status'] == 'paid') {
            print('\n🎉 SUCCESS: Order status flow working correctly!');
          } else {
            print('\n❌ FAILED: Order status not updated correctly');
          }
        } else {
          print('❌ Test order not found in user orders');
        }
      }
    }
    
  } catch (e) {
    print('❌ Error: $e');
  }
}
