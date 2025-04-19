import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';

class AuthService extends ChangeNotifier {
  // Base API URL - Change this to your Laravel API URL
  static const String _baseUrl =
      'http://10.0.2.2:8000/api'; // Sesuaikan dengan port 8000

  User? _currentUser;
  String? _token;
  bool _isLoading = false;
  bool _initialized = false;

  // Use a Completer to track initialization
  final Completer<void> _initCompleter = Completer<void>();

  User? get currentUser => _currentUser;
  String? get token => _token;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _token != null;
  bool get initialized => _initialized;
  Future<void> get initializationFuture => _initCompleter.future;

  AuthService() {
    // Start initialization without calling notifyListeners
    _initialize();
  }

  // Initialize the service without calling notifyListeners
  Future<void> _initialize() async {
    try {
      await _loadUserData();
      _initialized = true;
      // Complete the initialization future
      _initCompleter.complete();
      // No notifyListeners() here to avoid build-time notification
    } catch (e) {
      print('Error initializing AuthService: $e');
      _initialized = true;
      _initCompleter.completeError(e);
    }
  }

  // Load user data from SharedPreferences
  Future<void> _loadUserData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      final authToken = prefs.getString('auth_token');

      if (userData != null && authToken != null) {
        _currentUser = User.fromJson(json.decode(userData));
        _token = authToken;
        // No notifyListeners() here
      }
    } catch (e) {
      print('Error loading user data: $e');
    }
  }

  // Save user data to SharedPreferences
  Future<void> _saveUserData(User user, String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user_data', json.encode(user.toJson()));
    await prefs.setString('auth_token', token);
  }

  // Clear user data from SharedPreferences
  Future<void> _clearUserData() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user_data');
    await prefs.remove('auth_token');
  }

  // Register a new user
  Future<Map<String, dynamic>> register({
    required String username,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      final url = '$_baseUrl/v1/register';
      final body = {
        'username': username,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      };
      
      print('Attempting registration with:');
      print('URL: $url');
      print('Body: ${json.encode(body)}');

      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: json.encode(body),
      ).timeout(const Duration(seconds: 30));

      print('Register Response:');
      print('Status Code: ${response.statusCode}');
      print('Headers: ${response.headers}');
      print('Body: ${response.body}');

      // Handle potential non-JSON responses
      if (response.statusCode == 500) {
        return {
          'success': false,
          'message': 'Server error. Please try again later.',
          'debug': response.body.substring(0, 100)
        };
      }

      final contentType = response.headers['content-type'];
      if (contentType == null || !contentType.contains('application/json')) {
        print('Invalid content type: $contentType');
        return {
          'success': false,
          'message': 'Server returned invalid response format',
          'debug': response.body.substring(0, 100)
        };
      }

      final responseData = json.decode(response.body);
      print('Parsed response data: $responseData');

      if (response.statusCode == 201 || response.statusCode == 200) {
        return {
          'success': true,
          'message': responseData['message'] ?? 'Registration successful',
          'data': responseData['data'],
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Registration failed',
          'details': responseData,
        };
      }
    } on FormatException catch (e) {
      print('Format error: $e');
      return {
        'success': false,
        'message': 'Server response format error',
        'debug': e.toString(),
      };
    } on SocketException catch (e) {
      print('Network error: $e');
      return {
        'success': false,
        'message': 'Connection error. Please check your internet connection.',
        'debug': e.toString(),
      };
    } on TimeoutException catch (e) {
      print('Timeout error: $e');
      return {
        'success': false,
        'message': 'Connection timeout. Please try again.',
        'debug': e.toString(),
      };
    } catch (e) {
      print('Registration error: $e');
      return {
        'success': false,
        'message': 'Registration failed. Please try again.',
        'debug': e.toString(),
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Login user with username and password
  Future<bool> login(String username, String password) async {
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/auth/login'), // Endpoint login
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'password': password,
        }),
      );

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        final userData = responseData['data']['user'];
        final authToken = responseData['data']['token'];

        _currentUser = User.fromJson(userData);
        _token = authToken;
        await _saveUserData(_currentUser!, _token!);

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Logout user
  Future<bool> logout() async {
    // Ensure initialization has completed
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      if (_token != null) {
        final response = await http.post(
          Uri.parse('$_baseUrl/logout'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $_token',
          },
        );

        if (response.statusCode == 200) {
          _currentUser = null;
          _token = null;
          await _clearUserData();

          _isLoading = false;
          notifyListeners();
          return true;
        }
      }

      // If token is null or request failed, still clear local data
      _currentUser = null;
      _token = null;
      await _clearUserData();

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      // Even if API request fails, clear local data
      _currentUser = null;
      _token = null;
      await _clearUserData();

      _isLoading = false;
      notifyListeners();
      return true;
    }
  }

  // Get authenticated user
  Future<void> getUser() async {
    if (_token == null) return;

    // Ensure initialization has completed
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/user'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $_token',
        },
      ).timeout(const Duration(seconds: 10));

      print('GetUser response status: ${response.statusCode}');
      print('GetUser response body: ${response.body}');

      if (response.statusCode == 200) {
        // Try to parse the response as JSON
        try {
          final responseData = json.decode(response.body);
          _currentUser = User.fromJson(responseData['data']['user']);
          await _saveUserData(_currentUser!, _token!);
        } catch (e) {
          print('Error parsing user data: $e');
        }
      } else {
        // Add proper error handling for non-200 responses
        print('Failed to get user: Status ${response.statusCode}');

        // If unauthorized, clear token
        if (response.statusCode == 401) {
          await logout();
        }
      }
    } catch (e) {
      print('Error getting user data: $e');
      // Don't clear token on network errors to avoid unnecessary logouts
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Verify OTP
  Future<Map<String, dynamic>> verifyOtp(String email, String otp) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/v1/verify-otp'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: json.encode({
          'email': email,
          'otp': otp,
        }),
      ).timeout(const Duration(seconds: 30));

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        if (responseData['data']?['user'] != null && responseData['data']?['token'] != null) {
          _currentUser = User.fromJson(responseData['data']['user']);
          _token = responseData['data']['token'];
          await _saveUserData(_currentUser!, _token!);
        }

        return {
          'success': true,
          'message': responseData['message'] ?? 'Verifikasi berhasil',
        };
      } else if (response.statusCode == 400) {
        // Handle specific error cases
        if (responseData['message']?.contains('expired') ?? false) {
          return {
            'success': false,
            'message': 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.',
            'expired': true,
          };
        } else if (responseData['message']?.contains('attempts') ?? false) {
          return {
            'success': false,
            'message': responseData['message'] ?? 'Terlalu banyak percobaan. Silakan minta kode baru.',
            'tooManyAttempts': true,
          };
        }
        return {
          'success': false,
          'message': responseData['message'] ?? 'Kode OTP tidak valid',
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Verifikasi gagal',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'Koneksi timeout. Silakan coba lagi.',
        'isTimeout': true,
      };
    } on SocketException {
      return {
        'success': false,
        'message': 'Tidak ada koneksi internet.',
        'isOffline': true,
      };
    } catch (e) {
      print('Error in verifyOtp: $e');
      return {
        'success': false,
        'message': 'Terjadi kesalahan. Silakan coba lagi.',
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> resendOtp({required String email}) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/v1/resend-otp'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: json.encode({'email': email}),
      ).timeout(const Duration(seconds: 30));

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': responseData['message'] ?? 'OTP baru telah dikirim',
        };
      } else if (response.statusCode == 429) {
        return {
          'success': false,
          'message': 'Terlalu banyak permintaan. Silakan tunggu beberapa saat.',
          'isRateLimited': true,
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Gagal mengirim OTP baru',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'Koneksi timeout. Silakan coba lagi.',
        'isTimeout': true,
      };
    } on SocketException {
      return {
        'success': false,
        'message': 'Tidak ada koneksi internet.',
        'isOffline': true,
      };
    } catch (e) {
      print('Error in resendOtp: $e');
      return {
        'success': false,
        'message': 'Terjadi kesalahan. Silakan coba lagi.',
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
