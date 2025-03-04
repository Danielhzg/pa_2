import 'package:postgres/postgres.dart';
import 'dart:io';
import '../models/product.dart';
import '../models/cart_item.dart';

class DatabaseHelper {
  static final DatabaseHelper _instance = DatabaseHelper._internal();
  PostgreSQLConnection? _connection;
  bool _isConnecting = false;

  DatabaseHelper._internal();

  static DatabaseHelper get instance => _instance;

  Future<PostgreSQLConnection> getConnection() async {
    if (_connection != null && !_connection!.isClosed) {
      return _connection!;
    }

    // Prevent multiple simultaneous connection attempts
    if (_isConnecting) {
      while (_isConnecting) {
        await Future.delayed(const Duration(milliseconds: 100));
      }
      if (_connection != null && !_connection!.isClosed) {
        return _connection!;
      }
    }

    _isConnecting = true;

    try {
      final host = Platform.isAndroid ? '10.0.2.2' : 'localhost';

      _connection = PostgreSQLConnection(
        host,
        5432,
        'pa_2',
        username: 'postgres',
        password: 'daniel123',
        timeoutInSeconds: 30,
        queryTimeoutInSeconds: 30,
        isUnixSocket: false,
        allowClearTextPassword: true,
      );

      await _connection!.open();
      print('Database connected successfully');

      // Test the connection
      final result = await _connection!.query('SELECT version();');
      print('PostgreSQL Version: ${result.first[0]}');

      return _connection!;
    } catch (e) {
      print('Database connection error: $e');
      _connection = null;
      rethrow;
    } finally {
      _isConnecting = false;
    }
  }

  Future<void> closeConnection() async {
    try {
      if (_connection != null && !_connection!.isClosed) {
        await _connection!.close();
        _connection = null;
        print('Database connection closed');
      }
    } catch (e) {
      print('Error closing database connection: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> validateUser(
      String username, String password) async {
    PostgreSQLConnection? conn;
    try {
      conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        'SELECT user_id, username, email FROM users WHERE username = @username AND password = @password',
        substitutionValues: {
          'username': username,
          'password': password,
        },
      );

      if (results.isEmpty) {
        return {'success': false, 'message': 'Invalid username or password'};
      }

      final userData = results.first['users']!;
      return {
        'success': true,
        'user': {
          'id': userData['user_id'],
          'username': userData['username'],
          'email': userData['email'],
        }
      };
    } catch (e) {
      print('Error validating user: $e');
      return {'success': false, 'message': 'Database error: ${e.toString()}'};
    }
  }

  Future<Map<String, dynamic>> createUser(
    String username,
    String email,
    String nomorTelepon,
    String password,
  ) async {
    PostgreSQLConnection? conn;
    try {
      conn = await getConnection();

      return await conn.transaction((ctx) async {
        // Check username
        final usernameExists = await ctx.query(
          'SELECT 1 FROM users WHERE username = @username',
          substitutionValues: {'username': username},
        );
        if (usernameExists.isNotEmpty) {
          return {'success': false, 'message': 'Username already exists'};
        }

        // Check email
        final emailExists = await ctx.query(
          'SELECT 1 FROM users WHERE email = @email',
          substitutionValues: {'email': email},
        );
        if (emailExists.isNotEmpty) {
          return {'success': false, 'message': 'Email already exists'};
        }

        // Insert user
        await ctx.query(
          '''
          INSERT INTO users (username, email, nomor_telepon, password)
          VALUES (@username, @email, @nomorTelepon, @password)
          ''',
          substitutionValues: {
            'username': username,
            'email': email,
            'nomorTelepon': nomorTelepon,
            'password': password,
          },
        );

        return {'success': true, 'message': 'User created successfully'};
      });
    } catch (e) {
      print('Error creating user: $e');
      return {'success': false, 'message': 'Database error: ${e.toString()}'};
    }
  }

  Future<List<Product>> searchProducts(String query) async {
    PostgreSQLConnection? conn;
    try {
      conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        '''
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE LOWER(p.name) LIKE LOWER(@query)
        OR LOWER(p.description) LIKE LOWER(@query)
        ''',
        substitutionValues: {
          'query': '%$query%',
        },
      );

      return results.map((row) => Product.fromMap(row['p']!)).toList();
    } catch (e) {
      print('Error searching products: $e');
      return [];
    }
  }

  Future<bool> checkConnection() async {
    try {
      final conn = await getConnection();
      final result = await conn.query('SELECT 1');
      return result.isNotEmpty;
    } catch (e) {
      print('Connection check failed: $e');
      return false;
    }
  }

  Future<void> addToCart(CartItem item, int userId) async {
    try {
      final conn = await getConnection();
      await conn.transaction((ctx) async {
        // Check if product exists and has enough stock
        final stockResult = await ctx.query(
          'SELECT stock_quantity FROM products WHERE product_id = @productId',
          substitutionValues: {
            'productId': item.productId,
          },
        );

        if (stockResult.isEmpty) {
          throw Exception('Product not found');
        }

        final stockQuantity = stockResult.first[0] as int;
        if (stockQuantity < item.quantity) {
          throw Exception('Not enough stock');
        }

        // Add to cart
        await ctx.query(
          '''
          INSERT INTO cart (product_id, user_id, quantity)
          VALUES (@productId, @userId, @quantity)
          ON CONFLICT (product_id, user_id) 
          DO UPDATE SET quantity = cart.quantity + @quantity
          ''',
          substitutionValues: {
            'productId': item.productId,
            'userId': userId,
            'quantity': item.quantity,
          },
        );

        // Update stock
        await ctx.query(
          '''
          UPDATE products 
          SET stock_quantity = stock_quantity - @quantity
          WHERE product_id = @productId
          ''',
          substitutionValues: {
            'productId': item.productId,
            'quantity': item.quantity,
          },
        );
      });
    } catch (e) {
      print('Error adding to cart: $e');
      rethrow;
    }
  }

  Future<List<Product>> getFeaturedProducts() async {
    try {
      final conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        '''
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_featured = true
        LIMIT 5
        ''',
      );

      return results.map((row) => Product.fromMap(row['p']!)).toList();
    } catch (e) {
      print('Error getting featured products: $e');
      return [];
    }
  }

  Future<List<Product>> getNewArrivals() async {
    try {
      final conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        '''
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC
        LIMIT 4
        ''',
      );

      return results.map((row) => Product.fromMap(row['p']!)).toList();
    } catch (e) {
      print('Error getting new arrivals: $e');
      return [];
    }
  }

  Future<List<Product>> getProductsByCategory(String category) async {
    try {
      final conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        '''
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE c.name = @category OR @category = 'All'
        ''',
        substitutionValues: {
          'category': category,
        },
      );

      return results.map((row) => Product.fromMap(row['p']!)).toList();
    } catch (e) {
      print('Error getting products by category: $e');
      return [];
    }
  }

  Future<void> updateCartItemQuantity(int productId, int quantity) async {
    try {
      final conn = await getConnection();
      await conn.query(
        '''
        UPDATE cart 
        SET quantity = @quantity
        WHERE product_id = @productId AND user_id = @userId
        ''',
        substitutionValues: {
          'productId': productId,
          'userId': 1, // Replace with actual user ID from session
          'quantity': quantity,
        },
      );
    } catch (e) {
      print('Error updating cart item: $e');
      rethrow;
    }
  }

  Future<void> removeFromCart(int productId) async {
    try {
      final conn = await getConnection();
      await conn.query(
        '''
        DELETE FROM cart 
        WHERE product_id = @productId AND user_id = @userId
        ''',
        substitutionValues: {
          'productId': productId,
          'userId': 1, // Replace with actual user ID from session
        },
      );
    } catch (e) {
      print('Error removing from cart: $e');
      rethrow;
    }
  }

  Future<List<CartItem>> getCartItems() async {
    try {
      final conn = await getConnection();
      final results = await conn.mappedResultsQuery(
        '''
        SELECT c.*, p.name, p.price, p.image_url
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = @userId
        ''',
        substitutionValues: {
          'userId': 1, // Replace with actual user ID from session
        },
      );

      return results.map((row) {
        final cart = row['cart']!;
        final product = row['products']!;
        return CartItem(
          id: cart['id'],
          productId: cart['product_id'],
          name: product['name'],
          price: product['price'],
          imageUrl: product['image_url'],
          quantity: cart['quantity'],
        );
      }).toList();
    } catch (e) {
      print('Error getting cart items: $e');
      return [];
    }
  }
}
