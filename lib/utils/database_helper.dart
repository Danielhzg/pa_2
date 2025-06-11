import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:sqflite/sqflite.dart';
import '../models/product.dart';
import '../models/cart_item.dart';
import '../services/api_service.dart';

class DatabaseHelper {
  static final DatabaseHelper _instance = DatabaseHelper._internal();
  DatabaseHelper._internal();
  static DatabaseHelper get instance => _instance;

  // Primary ngrok URL:
  static const String baseUrl = 'https://dec8-114-122-41-11.ngrok-free.app/api';

  // For Android emulator, use:
  // static const String baseUrl = 'http://10.0.2.2:5000/api';

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
      print('Attempting login with username: $username');
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'password': password,
        }),
      );

      print('Login response status: ${response.statusCode}');
      print('Login response body: ${response.body}');

      final data = json.decode(response.body);

      if (response.statusCode == 200) {
        // Mengambil token dari response yang benar
        final token = data['data']['token'];
        setToken(token);
        return {
          'success': true,
          'user': data['data']['user'],
          'token': token,
        };
      } else {
        print('Login failed with message: ${data['message']}');
        return {
          'success': false,
          'message': data['message'] ?? 'Login failed',
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
      print(
          'Attempting register with data: username=$username, email=$email, phone=$nomorTelepon');
      final response = await http.post(
        Uri.parse('$baseUrl/auth/register'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'email': email,
          'phone': nomorTelepon,
          'password': password,
        }),
      );

      print('Register response status: ${response.statusCode}');
      print('Register response body: ${response.body}');

      final data = json.decode(response.body);

      if (response.statusCode == 201) {
        // Jika registrasi berhasil, langsung set token
        final token = data['data']['token'];
        setToken(token);

        return {
          'success': true,
          'message': 'Registration successful',
          'user': data['data']['user'],
          'token': token,
        };
      } else {
        print('Registration failed with message: ${data['message']}');
        return {
          'success': false,
          'message': data['message'] ?? 'Registration failed',
          'errors': data['errors'] ?? {},
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
  Future<List<Product>> fetchProductsFromApi() async {
    try {
      // Gunakan ApiService untuk mendapatkan data produk
      final ApiService apiService = ApiService();
      final List<dynamic> productsJson = await apiService.fetchProducts();

      // Konversi JSON ke objek Product
      final products =
          productsJson.map((json) => Product.fromJson(json)).toList();

      // Simpan produk ke database lokal untuk penggunaan offline
      for (var product in products) {
        await insertProduct(product);
      }

      return products;
    } catch (e) {
      print('Error fetching products from API: $e');
      // Jika gagal mengambil dari API, coba ambil dari database lokal
      return getProducts();
    }
  }

  Future<List<Product>> getFeaturedProducts() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/products/featured'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data.map<Product>((json) => Product.fromJson(json)).toList();
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
        Uri.parse('${ApiService().baseUrl}/v1/products/search?query=$query'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['success'] == true) {
          final List<dynamic> productsData = responseData['data'];
          return productsData.map((json) => Product.fromJson(json)).toList();
        }
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

  Future<void> createTables(Database db) async {
    await db.execute('''
      CREATE TABLE products (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        image_url TEXT,
        category_id INTEGER NOT NULL,
        category_name TEXT NOT NULL, // Add category_name for display
        is_on_sale INTEGER DEFAULT 0,
        discount REAL DEFAULT 0.0,
        rating REAL DEFAULT 0.0,
        is_featured INTEGER DEFAULT 0
      )
    ''');
  }

  Future<Database> get database async {
    final dbPath = await getDatabasesPath();
    return openDatabase(
      '$dbPath/app_database.db',
      version: 1,
      onCreate: (db, version) async {
        await createTables(db);
      },
    );
  }

  Future<int> insertProduct(Product product) async {
    final db = await database;
    return await db.insert(
      'products',
      {
        'id': product.id,
        'name': product.name,
        'description': product.description,
        'price': product.price,
        'image_url': product.imageUrl,
        'category_id': product.categoryId,
        'category_name': product.categoryName,
        'is_on_sale': product.isOnSale ? 1 : 0,
        'discount': product.discount,
        'rating': product.rating,
        'is_featured': product.isFeatured ? 1 : 0,
      },
    );
  }

  Future<List<Product>> getProducts() async {
    final db = await database;
    final data = await db.query('products');
    return data.map<Product>((json) => Product.fromJson(json)).toList();
  }
}
