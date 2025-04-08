import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/product.dart';

class ApiService {
  // Ganti dengan URL server Laravel Anda
  // Untuk emulator Android
  final String baseUrl = 'http://10.0.2.2:8000/api';
  // Untuk perangkat fisik, gunakan IP komputer Anda
  // final String baseUrl = 'http://192.168.1.100:8000/api';
  
  Future<List<Product>> getProducts() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/products'));
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = json.decode(response.body);
        
        if (data['success'] == true) {
          final List<dynamic> productsJson = data['data'];
          return productsJson.map((json) => Product.fromJson(json)).toList();
        } else {
          throw Exception('Failed to load products: ${data['message']}');
        }
      } else {
        throw Exception('Failed to load products. Status: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
  
  Future<Product> getProduct(int id) async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/products/$id'));
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = json.decode(response.body);
        
        if (data['success'] == true) {
          return Product.fromJson(data['data']);
        } else {
          throw Exception('Failed to load product: ${data['message']}');
        }
      } else {
        throw Exception('Failed to load product. Status: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
  
  Future<List<Product>> searchProducts(String query) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/products/search?query=$query'),
      );
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = json.decode(response.body);
        
        if (data['success'] == true) {
          final List<dynamic> productsJson = data['data'];
          return productsJson.map((json) => Product.fromJson(json)).toList();
        } else {
          throw Exception('Failed to search products: ${data['message']}');
        }
      } else {
        throw Exception('Failed to search products. Status: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
}
