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

      print('Carousel API Response Status: ${response.statusCode}');
      print('Carousel API Response Body: ${response.body}');

      if (response.statusCode == 200) {
        dynamic decodedResponse = json.decode(response.body);
        List<dynamic> carouselData = [];

        // Periksa struktur respons
        if (decodedResponse is Map) {
          if (decodedResponse.containsKey('data') &&
              decodedResponse['data'] is List) {
            // Format respons dengan 'data' sebagai key
            carouselData = decodedResponse['data'];
          } else if (decodedResponse.containsKey('carousels') &&
              decodedResponse['carousels'] is List) {
            // Format respons dengan 'carousels' sebagai key
            carouselData = decodedResponse['carousels'];
          }
        } else if (decodedResponse is List) {
          // Format respons langsung sebagai array
          carouselData = decodedResponse;
        }

        // Konversi data carousel ke format yang seragam
        final List<Map<String, dynamic>> result = [];
        for (var item in carouselData) {
          // Log setiap item carousel untuk debugging detail
          print('Processing carousel item: ${item.toString()}');

          var imageValue = item['image']?.toString() ?? '';
          print('Carousel image path: $imageValue');

          // Cek secara khusus untuk carousel promo 10%
          if (item['title']?.toString().contains('10%') == true ||
              item['description']?.toString().contains('10%') == true) {
            print('FOUND PROMO 10% CAROUSEL: ${item.toString()}');
          }

          result.add({
            'id': item['id'] is String
                ? int.tryParse(item['id']) ?? 0
                : (item['id'] ?? 0),
            'title': item['title']?.toString() ?? 'No Title',
            'description': item['description']?.toString() ?? 'No Description',
            'image': imageValue,
            'order': item['order'] is String
                ? int.tryParse(item['order']) ?? 0
                : (item['order'] ?? 0),
          });
        }

        print('Processed ${result.length} carousel items');
        return result;
      } else {
        print('Failed to load carousels: ${response.statusCode}');
        return [];
      }
    } catch (e) {
      print('Error fetching carousels: $e');
      return [];
    }
  }
}
