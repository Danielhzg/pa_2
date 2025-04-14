import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/product.dart';

class ApiService {
  // Gunakan URL API Laravel Anda
  // Untuk emulator Android
  final String baseUrl = 'http://10.0.2.2:8000/api';

  Future<List<dynamic>> fetchProducts() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/v1/products'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        // Pastikan struktur respons sesuai dengan API Laravel
        if (responseData['success'] == true) {
          return responseData['data'];
        } else {
          throw Exception('API error: ${responseData['message']}');
        }
      } else {
        throw Exception('Failed to load products: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching products: $e');
      throw Exception('Failed to connect to the server');
    }
  }

  Future<List<dynamic>> fetchProductsByCategory(String categoryId) async {
    try {
      // Add logging to track request details
      print('Fetching products for category ID: $categoryId');

      // Adjust the endpoint URL to match your backend API structure
      final response = await http.get(
        Uri.parse('$baseUrl/v1/products/category/$categoryId'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      // Log response status code for debugging
      print('Category products response code: ${response.statusCode}');

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        print('Category API response: ${responseData['success']}');

        if (responseData['success'] == true && responseData['data'] != null) {
          return responseData['data'];
        } else {
          throw Exception(
              'API error: ${responseData['message'] ?? "Unknown error"}');
        }
      } else {
        // Get more details from error response
        String errorDetails = '';
        try {
          errorDetails = json.decode(response.body)['message'] ?? '';
        } catch (e) {
          // Ignore parsing errors
        }

        throw Exception(
            'Failed to load products by category (${response.statusCode}): $errorDetails');
      }
    } catch (e) {
      print('Error fetching products by category: $e');
      throw Exception('Failed to connect to the server: $e');
    }
  }

  // Tambahkan method untuk mengambil kategori dari API
  Future<List<dynamic>> fetchCategories() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/v1/categories'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);

        if (responseData['success'] == true) {
          return responseData['data'];
        } else {
          throw Exception('API error: ${responseData['message']}');
        }
      } else {
        throw Exception('Failed to load categories: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching categories: $e');
      throw Exception('Failed to connect to the server');
    }
  }

  Future<List<Product>> searchProducts(String query) async {
    try {
      final response = await http
          .get(Uri.parse('http://10.0.2.2:8000/api/products?search=$query'));
      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data.map((json) => Product.fromJson(json)).toList();
      } else {
        throw Exception('Failed to load products');
      }
    } catch (e) {
      print('Error searching products: $e');
      throw Exception('Failed to search products: $e');
    }
  }

  Future<List<Product>> getAllProducts() async {
    try {
      final response =
          await http.get(Uri.parse('http://10.0.2.2:8000/api/products'));
      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonResponse = json.decode(response.body);
        // Access the "data" key or the appropriate key that contains the list of products
        final List<dynamic> data = jsonResponse['data'];
        return data.map((json) => Product.fromJson(json)).toList();
      } else {
        throw Exception('Failed to load products');
      }
    } catch (e) {
      print('Error fetching products: $e');
      throw Exception('Failed to fetch products: $e');
    }
  }

  Future<List<dynamic>> fetchCarousels() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/v1/carousels'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        print('Carousel API Response: $responseData'); // Log the response
        if (responseData['success'] == true) {
          return responseData['data'];
        } else {
          throw Exception('API error: ${responseData['message']}');
        }
      } else {
        throw Exception('Failed to load carousels: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching carousels: $e');
      throw Exception('Failed to connect to the server');
    }
  }
}
