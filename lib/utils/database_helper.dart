import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/product.dart';
import '../models/cart_item.dart';

class DatabaseHelper {
  static final DatabaseHelper _instance = DatabaseHelper._internal();
  DatabaseHelper._internal();
  static DatabaseHelper get instance => _instance;

  // For Android emulator, use:
  static const String baseUrl = 'http://10.0.2.2:5000/api';

  // For iOS simulator, use:
  // static const String baseUrl = 'http://localhost:5000/api';

  // For real device testing, use your computer's IP address:
  // static const String baseUrl = 'http://192.168.1.xxx:5000/api';

  String? _token;

  void setToken(String token) {
    _token = token;
  }

  Map<String, String> get _headers {
    return {
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  // Authentication endpoints
  Future<Map<String, dynamic>> validateUser(
      String username, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'password': password,
        }),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200) {
        setToken(data['token']); // Save token for future requests
        return {
          'success': true,
          'user': data['user'],
          'token': data['token'],
        };
      } else {
        return {
          'success': false,
          'message': data['error'] ?? 'Login failed',
        };
      }
    } catch (e) {
      print('Error in validateUser: $e');
      return {
        'success': false,
        'message': 'Network error: Unable to connect to server',
      };
    }
  }

  Future<Map<String, dynamic>> createUser(String username, String email,
      String nomorTelepon, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/register'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'email': email,
          'nomor_telepon': nomorTelepon,
          'password': password,
        }),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 201) {
        return {
          'success': true,
          'message': data['message'],
          'user': data['user'],
        };
      } else {
        return {
          'success': false,
          'message': data['error'] ?? 'Registration failed',
        };
      }
    } catch (e) {
      print('Error in createUser: $e');
      return {
        'success': false,
        'message': 'Network error: Unable to connect to server',
      };
    }
  }

  // Product endpoints
  Future<List<Product>> getFeaturedProducts() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/products/featured'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data.map((json) => Product.fromMap(json)).toList();
      }
      return [];
    } catch (e) {
      print('Error getting featured products: $e');
      return [];
    }
  }

  // Cart endpoints
  Future<List<CartItem>> getCartItems() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/cart'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data
            .map((json) => CartItem(
                  id: json['id'],
                  productId: json['product_id'],
                  name: json['product']['name'],
                  price: json['product']['price'],
                  imageUrl: json['product']['image_url'],
                  quantity: json['quantity'],
                ))
            .toList();
      }
      return [];
    } catch (e) {
      print('Error getting cart items: $e');
      return [];
    }
  }

  // Search endpoints
  Future<List<Product>> searchProducts(String query) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/products/search?query=$query'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data.map((json) => Product.fromMap(json)).toList();
      }
      return [];
    } catch (e) {
      print('Error searching products: $e');
      return [];
    }
  }

  Future<Map<String, dynamic>> getUserProfile() async {
    try {
      if (_token == null) {
        print('getUserProfile: No token available');
        return {
          'success': false,
          'message': 'No authentication token',
        };
      }

      print('getUserProfile: Fetching with token: $_token');
      final response = await http.get(
        Uri.parse('$baseUrl/user/profile'),
        headers: _headers,
      );

      final data = json.decode(response.body);
      print('getUserProfile: Response status: ${response.statusCode}');
      print('getUserProfile: Response data: $data');

      if (response.statusCode == 200) {
        return {
          'success': true,
          'user': data['user'],
        };
      } else {
        return {
          'success': false,
          'message': data['error'] ?? 'Failed to get user profile',
        };
      }
    } catch (e) {
      print('Error in getUserProfile: $e');
      return {
        'success': false,
        'message': 'Network error: Unable to connect to server',
      };
    }
  }

  Future<Map<String, dynamic>> updateUserProfile(
      Map<String, dynamic> data) async {
    try {
      final response = await http.put(
        Uri.parse('$baseUrl/user/profile'),
        headers: _headers,
        body: json.encode(data),
      );

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': true,
          'user': responseData['user'],
        };
      } else {
        return {
          'success': false,
          'message': responseData['error'] ?? 'Failed to update profile',
        };
      }
    } catch (e) {
      print('Error in updateUserProfile: $e');
      return {
        'success': false,
        'message': 'Network error: Unable to connect to server',
      };
    }
  }
}
