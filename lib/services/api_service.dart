import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:math' show min;
import '../models/product.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class ApiService {
  // Use the dynamic base URL from constants
  String get baseUrl => '${ApiConstants.getBaseUrl()}/api';

  // Additional URLs to try if the main one fails
  final List<String> fallbackUrls = [
    'https://dec8-114-122-41-11.ngrok-free.app/api',
    'http://10.0.2.2:8000/api',
    'http://localhost:8000/api',
    'http://127.0.0.1:8000/api',
    'http://192.168.0.106:8000/api',
    'http://192.168.1.5:8000/api'
  ];

  // Check if database connection is available
  Future<bool> isDatabaseConnected() async {
    try {
      final response = await _tryMultipleUrls('v1/ping',
          headers: _getHeaders(), timeout: const Duration(seconds: 3));

      if (response.statusCode == 200) {
        return true;
      }

      // Try to check the response body for specific database errors
      try {
        final Map<String, dynamic> responseData = json.decode(response.body);
        // If there's a specific database connection error message
        if (responseData.containsKey('error') &&
            responseData['error']
                .toString()
                .contains('SQLSTATE[HY000] [2002]')) {
          return false;
        }
      } catch (e) {
        // JSON parsing failed, but that doesn't confirm database issues
        print('Error parsing ping response: $e');
      }

      return false;
    } catch (e) {
      print('Database connectivity check failed: $e');
      return false;
    }
  }

  // Try request with multiple URLs
  Future<http.Response> _tryMultipleUrls(String endpoint,
      {Map<String, String>? headers,
      String? body,
      String method = 'GET',
      Duration? timeout}) async {
    Exception? lastException;

    for (var url in [baseUrl, ...fallbackUrls]) {
      try {
        final Uri uri = Uri.parse('$url/$endpoint');
        http.Response response;

        // Apply timeout if specified
        Future<http.Response> request;

        switch (method) {
          case 'POST':
            request = http.post(uri, headers: headers, body: body);
            break;
          case 'PUT':
            request = http.put(uri, headers: headers, body: body);
            break;
          case 'DELETE':
            request = http.delete(uri, headers: headers);
            break;
          default:
            request = http.get(uri, headers: headers);
        }

        // Apply timeout if specified
        if (timeout != null) {
          response = await request.timeout(timeout);
        } else {
          response = await request;
        }

        // If successful, return the response
        return response;
      } catch (e) {
        print('Error with URL $url: $e');
        lastException = Exception(e.toString());
        // Continue to the next URL
      }
    }

    // If all URLs fail, throw the last exception
    throw lastException ?? Exception('Failed to connect to any server URL');
  }

  // Helper method to create standard headers
  Map<String, String> _getHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
  }

  // Handle response with common error handling
  dynamic _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      try {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData.containsKey('success') &&
            responseData['success'] == true) {
          return responseData['data'] ?? responseData;
        } else {
          String message = responseData['message'] ?? 'Unknown error';
          // Check for database connection issues
          if (message.contains('SQLSTATE[HY000]') ||
              response.body.contains('SQLSTATE[HY000]')) {
            throw Exception(
                'Database connection error. Please check if MySQL is running.');
          }
          throw Exception('API error: $message');
        }
      } catch (e) {
        if (e is FormatException) {
          throw Exception('Invalid response format from server');
        }
        rethrow;
      }
    } else {
      String errorMessage = 'HTTP Error: ${response.statusCode}';
      try {
        final Map<String, dynamic> errorData = json.decode(response.body);
        errorMessage = errorData['message'] ?? errorMessage;
      } catch (e) {
        // Use default error message if parsing fails
      }
      throw Exception(errorMessage);
    }
  }

  Future<List<dynamic>> fetchProducts() async {
    try {
      final response = await _tryMultipleUrls(
        'v1/products',
        headers: _getHeaders(),
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
      final response = await http.get(
        Uri.parse('$baseUrl/v1/products/search?query=$query'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      print('Search API Response Status: ${response.statusCode}');
      print('Search query: $query');

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);

        if (responseData['success'] == true && responseData['data'] != null) {
          final List<dynamic> data = responseData['data'];
          return data.map((json) => Product.fromJson(json)).toList();
        } else {
          // Jika tidak ada data yang ditemukan, kembalikan list kosong
          return [];
        }
      } else {
        throw Exception('Failed to search products: ${response.statusCode}');
      }
    } catch (e) {
      print('Error searching products: $e');
      throw Exception('Failed to search products: $e');
    }
  }

  Future<List<Product>> getAllProducts() async {
    try {
      final response = await _tryMultipleUrls(
        'products',
        headers: _getHeaders(),
      );

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
      print('\n==== FETCHING CAROUSELS ====');
      print('Sending request to: $baseUrl/v1/carousels');

      // Coba beberapa URL berbeda jika URL utama gagal
      final List<String> urlsToTry = [
        '$baseUrl/v1/carousels',
        '${ApiConstants.getBaseUrl()}/api/v1/carousels',
        'https://dec8-114-122-41-11.ngrok-free.app/api/v1/carousels',
        'http://10.0.2.2:8000/api/v1/carousels',
        'http://localhost:8000/api/v1/carousels',
        'http://192.168.0.106:8000/api/v1/carousels'
      ];

      http.Response? response;
      String? responseBody;
      int statusCode = 0;

      // Coba setiap URL sampai berhasil
      for (String url in urlsToTry) {
        try {
          print('Trying URL: $url');
          final tempResponse = await http.get(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          ).timeout(const Duration(seconds: 5));

          statusCode = tempResponse.statusCode;
          print('Response status: $statusCode');

          if (statusCode == 200) {
            response = tempResponse;
            responseBody = tempResponse.body;
            print('Successful response from: $url');
            break;
          }
        } catch (e) {
          print('Error with URL $url: $e');
          continue;
        }
      }

      if (response == null || responseBody == null) {
        print('All carousel URLs failed. No valid response.');
        return [];
      }

      print('Carousel API Response Status: $statusCode');
      print('Carousel API Response Body: $responseBody');

      // Parsing JSON
      dynamic decodedResponse;
      try {
        decodedResponse = json.decode(responseBody);
        print('JSON decode successful');
      } catch (e) {
        print('JSON decode error: $e');
        return [];
      }

      List<dynamic> carouselData = [];

      // Periksa struktur respons
      if (decodedResponse is Map) {
        print('Response is a Map with keys: ${decodedResponse.keys.toList()}');

        if (decodedResponse.containsKey('data') &&
            decodedResponse['data'] is List) {
          // Format respons dengan 'data' sebagai key
          carouselData = decodedResponse['data'];
          print('Found "data" key with ${carouselData.length} items');
        } else if (decodedResponse.containsKey('carousels') &&
            decodedResponse['carousels'] is List) {
          // Format respons dengan 'carousels' sebagai key
          carouselData = decodedResponse['carousels'];
          print('Found "carousels" key with ${carouselData.length} items');
        } else if (decodedResponse.containsKey('success') &&
            decodedResponse.containsKey('message')) {
          // API responses with success/message format
          print('API response format: ${decodedResponse['message']}');

          if (decodedResponse.containsKey('data') &&
              decodedResponse['data'] is List) {
            carouselData = decodedResponse['data'];
            print('Using data field with ${carouselData.length} items');
          }
        }
      } else if (decodedResponse is List) {
        // Format respons langsung sebagai array
        carouselData = decodedResponse;
        print('Response is a direct List with ${carouselData.length} items');
      }

      if (carouselData.isEmpty) {
        print('No carousel data found in the response');
        return [];
      }

      // Konversi data carousel ke format yang seragam
      final List<Map<String, dynamic>> result = [];
      for (var item in carouselData) {
        // Log setiap item carousel untuk debugging detail
        print('\nProcessing carousel item: ${item.toString()}');

        // Extract image path with detailed logging
        String imageValue = '';

        if (item.containsKey('image') && item['image'] != null) {
          imageValue = item['image'].toString();
          print('Found "image" field: $imageValue');
        } else if (item.containsKey('image_url') && item['image_url'] != null) {
          imageValue = item['image_url'].toString();
          print('Found "image_url" field: $imageValue');
        } else if (item.containsKey('imageUrl') && item['imageUrl'] != null) {
          imageValue = item['imageUrl'].toString();
          print('Found "imageUrl" field: $imageValue');
        } else if (item.containsKey('url') && item['url'] != null) {
          imageValue = item['url'].toString();
          print('Found "url" field: $imageValue');
        }

        // Process image URL for various formats
        if (imageValue.isNotEmpty) {
          // If just a filename without path, add carousels/ prefix
          if (!imageValue.contains('/') &&
              !imageValue.startsWith('http') &&
              !imageValue.contains('storage/')) {
            print('Image appears to be just a filename: $imageValue');
            // Keep the original value, path will be handled by ImageUrlHelper
          }
        } else {
          print('WARNING: No image field found in carousel item');
        }

        // Cek secara khusus untuk carousel promo
        if ((item['title']?.toString().contains('Promo') == true) ||
            (item['description']?.toString().contains('Promo') == true) ||
            (item['title']?.toString().contains('%') == true) ||
            (item['description']?.toString().contains('%') == true)) {
          print('Found PROMO CAROUSEL: ${item.toString()}');
        }

        var processedItem = {
          'id': item['id'] is String
              ? int.tryParse(item['id']) ?? 0
              : (item['id'] ?? 0),
          'title': item['title']?.toString() ?? 'No Title',
          'description': item['description']?.toString() ?? 'No Description',
          'image': imageValue,
          'order': item['order'] is String
              ? int.tryParse(item['order']) ?? 0
              : (item['order'] ?? 0),
        };

        print('Added processed item: $processedItem');
        result.add(processedItem);
      }

      print('\nProcessed ${result.length} carousel items');
      if (result.isEmpty) {
        print('WARNING: No carousel items processed successfully');
      } else if (result.length < carouselData.length) {
        print('WARNING: Some carousel items failed to process');
      }

      return result;
    } catch (e) {
      print('Error fetching carousels: $e');
      return [];
    }
  }

  // General HTTP methods with authentication
  Future<Map<String, dynamic>> get(String endpoint,
      {bool withAuth = false}) async {
    try {
      print('Making API GET request to: $endpoint');
      final headers = await _getAuthHeaders(withAuth);
      final response =
          await _tryMultipleUrls(endpoint, headers: headers, method: 'GET');

      if (response.statusCode >= 200 && response.statusCode < 300) {
        try {
          final jsonData = json.decode(response.body);
          print(
              'API response received: ${jsonData.toString().substring(0, min(100, jsonData.toString().length))}...');
          return _handleResponse(response);
        } catch (e) {
          throw Exception(
              'Invalid JSON response: ${response.body.substring(0, min(100, response.body.length))}');
        }
      } else {
        throw Exception(
            'HTTP Error ${response.statusCode}: ${response.body.substring(0, min(100, response.body.length))}');
      }
    } catch (e) {
      print('GET request error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> post(String endpoint, dynamic data,
      {bool withAuth = false}) async {
    try {
      final headers = await _getAuthHeaders(withAuth);
      final body = json.encode(data);
      final response = await _tryMultipleUrls(endpoint,
          headers: headers, body: body, method: 'POST');
      return _handleResponse(response);
    } catch (e) {
      print('POST request error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> put(String endpoint, dynamic data,
      {bool withAuth = false}) async {
    try {
      final headers = await _getAuthHeaders(withAuth);
      final body = json.encode(data);
      final response = await _tryMultipleUrls(endpoint,
          headers: headers, body: body, method: 'PUT');
      return _handleResponse(response);
    } catch (e) {
      print('PUT request error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> delete(String endpoint,
      {bool withAuth = false}) async {
    try {
      final headers = await _getAuthHeaders(withAuth);
      final response =
          await _tryMultipleUrls(endpoint, headers: headers, method: 'DELETE');
      return _handleResponse(response);
    } catch (e) {
      print('DELETE request error: $e');
      rethrow;
    }
  }

  // Get auth headers if needed
  Future<Map<String, String>> _getAuthHeaders(bool withAuth) async {
    final headers = _getHeaders();

    if (withAuth) {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      } else {
        print('Warning: Auth token not found but withAuth is true');
      }
    }

    return headers;
  }

  // Get favorite products for the current user
  Future<List<dynamic>> getFavoriteProducts() async {
    try {
      print('Calling API endpoint: v1/favorites');

      // Check if token exists
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        print('ERROR: No auth token found when trying to get favorites');
        return [];
      }

      print(
          'Auth token found when getting favorites: ${token.substring(0, min(10, token.length))}...');

      // Use a direct HTTP call instead of the get method to better control the response handling
      final response = await http.get(
        Uri.parse('$baseUrl/v1/favorites'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      print('API response status code: ${response.statusCode}');

      if (response.statusCode >= 200 && response.statusCode < 300) {
        try {
          // Parse the response body safely
          final jsonData = json.decode(response.body);
          print('API response parsed successfully');

          if (jsonData['success'] == true && jsonData['data'] is List) {
            final data = jsonData['data'] as List;
            print('Retrieved ${data.length} favorite products');
            return data;
          } else {
            print(
                'Response format unexpected: ${jsonData.toString().substring(0, min(100, jsonData.toString().length))}...');
            return [];
          }
        } catch (e) {
          print('JSON parsing error: $e');
          print(
              'Raw response body: ${response.body.substring(0, min(100, response.body.length))}...');
          return [];
        }
      } else {
        print('HTTP error status: ${response.statusCode}');
        return [];
      }
    } catch (e) {
      print('Error fetching favorite products: $e');
      return [];
    }
  }

  // Toggle favorite status (add/remove from favorites)
  Future<bool> toggleFavorite(int productId) async {
    try {
      print('Toggling favorite for product ID: $productId');

      // Check if token exists
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        print('ERROR: No auth token found when trying to toggle favorite');
        return false;
      }

      print(
          'Auth token found: ${token.substring(0, min(10, token.length))}...');

      try {
        final result = await post(
            'v1/favorites/toggle', {'product_id': productId},
            withAuth: true);
        print(
            'Toggle response: ${result.toString().substring(0, min(100, result.toString().length))}...');

        if (result.containsKey('is_favorited')) {
          final isFavorited = result['is_favorited'] as bool;
          print('Product $productId is now favorited: $isFavorited');
          return isFavorited;
        }

        print('No is_favorited field in response');
        return false;
      } catch (requestError) {
        print('API request error: $requestError');

        // Before giving up, check if product is currently favorited
        try {
          final statusResult =
              await get('v1/favorites/check/$productId', withAuth: true);
          if (statusResult.containsKey('is_favorited')) {
            final currentStatus = statusResult['is_favorited'] as bool;
            print(
                'Current favorite status for product $productId is: $currentStatus');
            return currentStatus;
          }
        } catch (checkError) {
          print('Also failed to check favorite status: $checkError');
        }

        rethrow;
      }
    } catch (e) {
      print('Error toggling favorite status: $e');
      return false;
    }
  }

  // Check if a product is favorited
  Future<bool> checkFavoriteStatus(int productId) async {
    try {
      print('Checking favorite status for product ID: $productId');
      final result = await get('v1/favorites/check/$productId', withAuth: true);
      print(
          'Check favorite status response: ${result.toString().substring(0, min(100, result.toString().length))}...');

      if (result.containsKey('is_favorited')) {
        final isFavorited = result['is_favorited'];
        print('Product $productId favorited status: $isFavorited');
        return isFavorited;
      }
      print('No is_favorited field in response');
      return false;
    } catch (e) {
      print('Error checking favorite status: $e');
      return false;
    }
  }
}
