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
    required String fullName,
    required String email,
    required String phone,
    required String address,
    required String birthDate,
    required String password,
    required String passwordConfirmation,
  }) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      const url = '$_baseUrl/v1/register';
      final body = {
        'username': username,
        'full_name': fullName,
        'email': email,
        'phone': phone,
        'address': address,
        'birth_date': birthDate,
        'password': password,
        'password_confirmation': passwordConfirmation,
      };

      print('Attempting registration with:');
      print('URL: $url');
      print('Body: ${json.encode(body)}');

      final response = await http
          .post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          )
          .timeout(const Duration(seconds: 30));

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
      print('Attempting login with username: $username');
      print('Login endpoint: $_baseUrl/v1/login');

      final response = await http
          .post(
            Uri.parse(
                '$_baseUrl/v1/login'), // Menggunakan endpoint v1/login untuk konsistensi dengan endpoint lainnya
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode({
              'username': username,
              'password': password,
            }),
          )
          .timeout(const Duration(seconds: 15));

      print('Login Response Status: ${response.statusCode}');
      print('Login Response Body: ${response.body}');

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        // Cek apakah ada struktur data yang diharapkan
        if (responseData['success'] == true &&
            responseData.containsKey('data') &&
            responseData['data'] != null) {
          // Cek apakah struktur data sesuai dengan yang diharapkan
          if (responseData['data'].containsKey('user') &&
              responseData['data'].containsKey('token')) {
            final userData = responseData['data']['user'];
            final authToken = responseData['data']['token'];

            _currentUser = User.fromJson(userData);
            _token = authToken;
            await _saveUserData(_currentUser!, _token!);

            _isLoading = false;
            notifyListeners();
            return true;
          } else {
            print('Login response structure not as expected: $responseData');
          }
        } else {
          print(
              'Login failed: ${responseData['message'] ?? 'No success message provided'}');
        }
      } else {
        print(
            'Login failed with status: ${response.statusCode}, message: ${responseData['message'] ?? 'No message provided'}');
      }

      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      print('Exception during login: $e');
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
  Future<bool> getUser() async {
    if (_token == null) return false;

    // Ensure initialization has completed
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      // Mencoba mengambil data profile dari beberapa endpoint berbeda
      List<String> endpoints = [
        '$_baseUrl/v1/profile', // Endpoint utama untuk profile
        '$_baseUrl/v1/user-profile', // Alternatif endpoint untuk profile
        '$_baseUrl/user' // Endpoint fallback untuk user
      ];

      for (String endpoint in endpoints) {
        try {
          print('Trying to fetch profile from: $endpoint');

          final response = await http.get(
            Uri.parse(endpoint),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $_token',
            },
          ).timeout(const Duration(seconds: 10));

          print('Response from $endpoint - Status: ${response.statusCode}');
          if (response.statusCode == 200) {
            final responseData = json.decode(response.body);
            print('Response data format: $responseData');

            User? user;

            // Coba parse berbagai format respons API
            if (responseData['data'] != null && responseData['data'] is Map) {
              if (responseData['data']['user'] != null) {
                // Format: {"data": {"user": {...}}}
                user = User.fromJson(
                    Map<String, dynamic>.from(responseData['data']['user']));
              } else {
                // Format: {"data": {...}}
                user = User.fromJson(
                    Map<String, dynamic>.from(responseData['data']));
              }
            } else if (responseData['user'] != null) {
              // Format: {"user": {...}}
              user = User.fromJson(
                  Map<String, dynamic>.from(responseData['user']));
            } else if (responseData is Map && responseData.containsKey('id')) {
              // Format: {...} (langsung data user)
              user = User.fromJson(Map<String, dynamic>.from(responseData));
            }

            if (user != null) {
              _currentUser = user;
              await _saveUserData(_currentUser!, _token!);
              _isLoading = false;
              notifyListeners();
              return true;
            }
          } else if (response.statusCode == 401) {
            // Token tidak valid, lakukan logout
            print('Unauthorized access, logging out');
            await logout();
            return false;
          }
        } catch (e) {
          print('Error fetching from $endpoint: $e');
          // Lanjutkan ke endpoint berikutnya jika terjadi error
          continue;
        }
      }

      // Jika semua endpoint gagal, coba ambil dari local storage saja
      if (_currentUser != null) {
        print('Using cached user data');
        _isLoading = false;
        notifyListeners();
        return true;
      }

      // Jika tidak ada data yang bisa diambil
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      print('Error in getUser: $e');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Verify OTP
  Future<Map<String, dynamic>> verifyOtp(String email, String otp) async {
    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/v1/verify-otp'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode({
              'email': email,
              'otp': otp,
            }),
          )
          .timeout(const Duration(seconds: 30));

      final responseData = json.decode(response.body);

      print('Response from verifyOtp: $responseData');

      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': responseData['message'] ?? 'Verifikasi berhasil',
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Verifikasi gagal',
        };
      }
    } catch (e) {
      print('Error in verifyOtp: $e');
      return {
        'success': false,
        'message': 'Terjadi kesalahan. Silakan coba lagi.',
      };
    }
  }

  Future<Map<String, dynamic>> resendOtp({required String email}) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/v1/resend-otp'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode({'email': email}),
          )
          .timeout(const Duration(seconds: 30));

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

  Future<Map<String, dynamic>> getUserByEmail(String email) async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/v1/user/$email'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      final responseData = json.decode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': responseData['data'],
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to fetch user data',
        };
      }
    } catch (e) {
      print('Error in getUserByEmail: $e');
      return {
        'success': false,
        'message': 'An error occurred. Please try again.',
      };
    }
  }
}
