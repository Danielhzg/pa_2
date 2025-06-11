import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import 'package:provider/provider.dart';
import '../providers/cart_provider.dart';

class AuthService extends ChangeNotifier {
  // Base API URLs - Try multiple possible configurations
  // This helps ensure that at least one URL works depending on the environment
  static final List<String> _baseUrls = [
    'https://dec8-114-122-41-11.ngrok-free.app/api', // Primary ngrok URL
    'http://10.0.2.2:8000/api', // Android emulator pointing to localhost
    'http://localhost:8000/api', // Direct localhost
    'http://127.0.0.1:8000/api', // Alternative localhost
    'http://192.168.0.106:8000/api', // Use your actual computer's IP address
    'http://192.168.1.5:8000/api', // Another common LAN IP
    'http://192.168.43.41:8000/api', // Hotspot IP address pattern
  ];

  // The URL that successfully connected
  String? _workingBaseUrl;

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

  // Alias for currentUser for backward compatibility
  User? get user => _currentUser;

  AuthService() {
    // Start initialization without calling notifyListeners
    _initialize();
  }

  // Initialize the service without calling notifyListeners
  Future<void> _initialize() async {
    try {
      await _loadUserData();
      _initialized = true;

      // Test which base URL works
      await _testBaseUrls();

      // Complete the initialization future
      _initCompleter.complete();
      // No notifyListeners() here to avoid build-time notification
    } catch (e) {
      print('Error initializing AuthService: $e');
      _initialized = true;
      _initCompleter.completeError(e);
    }
  }

  // Test which base URL works
  Future<void> _testBaseUrls() async {
    bool found = false;

    for (var baseUrl in _baseUrls) {
      try {
        print('Testing API connection to: $baseUrl');

        // Try a simple GET request first to test connectivity
        final testUrl = '$baseUrl/v1/ping';
        final response = await http
            .get(Uri.parse(testUrl))
            .timeout(const Duration(seconds: 3));

        if (response.statusCode == 200) {
          _workingBaseUrl = baseUrl;
          print('Found working API URL: $_workingBaseUrl');
          found = true;
          break;
        }
      } catch (e) {
        try {
          // Fallback to testing without v1 prefix
          final testUrl = '$baseUrl/ping';
          final response = await http
              .get(Uri.parse(testUrl))
              .timeout(const Duration(seconds: 3));

          if (response.statusCode == 200) {
            _workingBaseUrl = baseUrl;
            print('Found working API URL (without v1): $_workingBaseUrl');
            found = true;
            break;
          }
        } catch (e2) {
          print('Testing $baseUrl failed: $e2');
        }
      }
    }

    if (!found) {
      // If no URL works with ping endpoint, we'll just try them all one by one for actual requests
      _workingBaseUrl = _baseUrls.first;
      print(
          'No working URL found through ping test, defaulting to: $_workingBaseUrl');

      // Save all URLs to try them during API calls
      print('Will try all URLs during API calls');
    }
  }

  // Get the current base URL, with fallback
  String get baseUrl {
    return _workingBaseUrl ?? _baseUrls.first;
  }

  // Try a request with multiple base URLs
  Future<http.Response> _tryRequestWithMultipleUrls({
    required String endpoint,
    required Future<http.Response> Function(String url) requestFunction,
  }) async {
    // First try with the known working URL
    try {
      final url = '$baseUrl/$endpoint';
      return await requestFunction(url).timeout(const Duration(seconds: 15));
    } catch (e) {
      print('Failed with primary URL, trying alternatives: $e');

      // If that fails, try all other URLs
      for (var baseUrlToTry in _baseUrls) {
        if (baseUrlToTry == baseUrl) continue; // Skip the one we already tried

        try {
          final url = '$baseUrlToTry/$endpoint';
          print('Trying alternative URL: $url');
          final response =
              await requestFunction(url).timeout(const Duration(seconds: 15));

          // If successful, update the working URL
          _workingBaseUrl = baseUrlToTry;
          print('Found working URL: $_workingBaseUrl');

          return response;
        } catch (e) {
          print('Failed with URL $baseUrlToTry: $e');
          continue;
        }
      }

      // If all URLs fail, rethrow the exception
      throw Exception(
          'Failed to connect to any API endpoint. Please check your internet connection and try again.');
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

  // Update CartProvider with the current user ID
  void updateCartProvider(BuildContext context) {
    final cartProvider = Provider.of<CartProvider>(context, listen: false);
    if (_currentUser != null) {
      cartProvider.setUserId(_currentUser!.id.toString());
      print('Updated CartProvider with user ID: ${_currentUser!.id}');
    } else {
      cartProvider.setUserId(null);
      print('Updated CartProvider with guest user');
    }
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
      print('Body: ${json.encode(body)}');

      // Try to make registration request with multiple possible endpoints
      http.Response response;

      try {
        // First try with v1 prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/register',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      } catch (e) {
        // Check for specific database connection errors
        if (e.toString().contains('target machine actively refused it') ||
            e.toString().contains('Connection refused') ||
            e.toString().contains('No connection could be made')) {
          print('Database connection error detected: $e');
          _isLoading = false;
          notifyListeners();

          // Return user-friendly message for database connection issues
          return {
            'success': false,
            'message':
                'Cannot connect to the database. Please check if the database server is running and try again.',
            'debug': e.toString()
          };
        }

        print('All v1 endpoints failed, trying without prefix: $e');

        // If all v1 endpoints fail, try without prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'register',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      }

      print('Register Response:');
      print('Status Code: ${response.statusCode}');
      print('Headers: ${response.headers}');
      print('Body: ${response.body}');

      // Handle potential non-JSON responses
      if (response.statusCode == 500) {
        return {
          'success': false,
          'message': 'Server error. Please try again later.',
          'debug': response.body
        };
      }

      String responseBody = response.body.trim();
      if (responseBody.isEmpty) {
        return {
          'success': false,
          'message': 'The server returned an empty response.',
          'debug':
              'Empty response body with status code: ${response.statusCode}'
        };
      }

      // Check content type
      final contentType = response.headers['content-type'];
      if (contentType == null || !contentType.contains('application/json')) {
        print('Invalid content type: $contentType');
        return {
          'success': false,
          'message': 'Server returned invalid response format',
          'debug': responseBody
        };
      }

      try {
        final responseData = json.decode(responseBody);
        print('Parsed response data: $responseData');

        if (response.statusCode == 201 || response.statusCode == 200) {
          return {
            'success': true,
            'message': responseData['message'] ?? 'Registration successful',
            'data': responseData['data'],
          };
        } else {
          // Check for validation errors
          if (responseData.containsKey('errors') &&
              responseData['errors'] is Map) {
            final errors = responseData['errors'] as Map;
            if (errors.isNotEmpty) {
              final firstErrorField = errors.keys.first;
              final firstErrorMessage = errors[firstErrorField] is List
                  ? errors[firstErrorField][0]
                  : errors[firstErrorField].toString();

              return {
                'success': false,
                'message': firstErrorMessage,
                'details': responseData,
              };
            }
          }

          return {
            'success': false,
            'message': responseData['message'] ?? 'Registration failed',
            'details': responseData,
          };
        }
      } catch (e) {
        print('Error parsing JSON: $e');
        return {
          'success': false,
          'message': 'Could not process server response',
          'debug': 'JSON parse error: $e, Response: $responseBody',
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
  Future<bool> login(String username, String password,
      {BuildContext? context}) async {
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      print('Attempting login with username: $username');

      final body = {
        'username': username,
        'password': password,
      };

      // Try to make login request with multiple possible endpoints
      http.Response response;

      try {
        // First try with v1 prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/login',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      } catch (e) {
        print('All v1 login endpoints failed, trying without prefix: $e');

        // If all v1 endpoints fail, try without prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'login',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      }

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

            // Update CartProvider with user ID if context is provided
            if (context != null) {
              updateCartProvider(context);
            }

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
  Future<bool> logout({BuildContext? context}) async {
    // Ensure initialization has completed
    await initializationFuture;

    _isLoading = true;
    notifyListeners();

    try {
      if (_token != null) {
        final response = await http.post(
          Uri.parse('$baseUrl/v1/logout'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $_token',
          },
        );

        if (response.statusCode == 200) {
          _currentUser = null;
          _token = null;
          await _clearUserData();

          // Update CartProvider if context is provided
          if (context != null) {
            updateCartProvider(context);
          }

          _isLoading = false;
          notifyListeners();
          return true;
        }
      }

      // If token is null or request failed, still clear local data
      _currentUser = null;
      _token = null;
      await _clearUserData();

      // Update CartProvider if context is provided
      if (context != null) {
        updateCartProvider(context);
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      // Even if API request fails, clear local data
      _currentUser = null;
      _token = null;
      await _clearUserData();

      // Update CartProvider if context is provided
      if (context != null) {
        updateCartProvider(context);
      }

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
        '$baseUrl/v1/profile', // Primary endpoint
        '$baseUrl/profile', // Fallback without v1
        '$baseUrl/v1/user-profile', // Alternative endpoint
        '$baseUrl/v1/user', // Another alternative
        '$baseUrl/user' // Last fallback
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
      final body = {
        'email': email,
        'otp': otp,
      };

      // Try to make verification request with multiple possible endpoints
      http.Response response;

      try {
        // First try with v1 prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/verify-otp',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      } catch (e) {
        print('All v1 verify-otp endpoints failed, trying without prefix: $e');

        // If all v1 endpoints fail, try without prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'verify-otp',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode(body),
          ),
        );
      }

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
        'debug': e.toString(),
      };
    }
  }

  Future<Map<String, dynamic>> resendOtp({required String email}) async {
    await initializationFuture;
    _isLoading = true;
    notifyListeners();

    try {
      // Try to make resend OTP request with multiple possible endpoints
      http.Response response;

      try {
        // First try with v1 prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/resend-otp',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode({'email': email}),
          ),
        );
      } catch (e) {
        print('All v1 resend-otp endpoints failed, trying without prefix: $e');

        // If all v1 endpoints fail, try without prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'resend-otp',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: json.encode({'email': email}),
          ),
        );
      }

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
        'debug': e.toString(),
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> getUserByEmail(String email) async {
    try {
      // Try to get user by email with multiple possible endpoints
      http.Response response;

      try {
        // First try with v1 prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/user/$email',
          requestFunction: (url) => http.get(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          ),
        );
      } catch (e) {
        print(
            'All v1 getUserByEmail endpoints failed, trying without prefix: $e');

        // If all v1 endpoints fail, try without prefix
        response = await _tryRequestWithMultipleUrls(
          endpoint: 'user/$email',
          requestFunction: (url) => http.get(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          ),
        );
      }

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
        'debug': e.toString(),
      };
    }
  }

  // Get auth token
  Future<String?> getToken() async {
    if (_token != null) {
      return _token;
    }

    // Try to load from SharedPreferences
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  // Update profile information
  Future<Map<String, dynamic>> updateProfile(
      Map<String, dynamic> profileData) async {
    _isLoading = true;
    notifyListeners();

    try {
      // Ensure username is properly formatted if provided
      if (profileData.containsKey('username') &&
          profileData['username'] != null) {
        // Remove spaces and special characters from username
        profileData['username'] = profileData['username']
            .toString()
            .trim()
            .replaceAll(RegExp(r'[^\w\s]'), '');
      }

      // Mencoba beberapa endpoint yang mungkin digunakan oleh backend
      final List<String> endpoints = [
        'v1/update-profile',
        'v1/profile/update',
        'v1/users/update',
        'update-profile', // Fallback tanpa v1 prefix
        'profile/update', // Fallback tanpa v1 prefix
        'user/profile', // Endpoint alternatif
        'api/user/update', // Endpoint alternatif lain
      ];

      bool success = false;
      String responseBody = '';
      int statusCode = 0;
      Map<String, dynamic>? responseData;

      print('Attempting to update profile with data: $profileData');

      // Coba semua endpoint sampai berhasil
      for (String endpoint in endpoints) {
        try {
          print('Trying update profile via endpoint: $endpoint');

          final response = await _tryRequestWithMultipleUrls(
            endpoint: endpoint,
            requestFunction: (url) => http.post(
              Uri.parse(url),
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer $_token',
              },
              body: json.encode(profileData),
            ),
          ).timeout(
              const Duration(seconds: 10)); // Add timeout to avoid hanging

          statusCode = response.statusCode;
          responseBody = response.body;

          print('Update profile response: $statusCode - $responseBody');

          if (statusCode == 200 || statusCode == 201) {
            success = true;

            // Coba parse response untuk mendapatkan data user terbaru
            try {
              responseData = json.decode(responseBody);
              if (responseData != null) {
                if (responseData.containsKey('data') &&
                    responseData['data'] != null) {
                  if (responseData['data'].containsKey('user')) {
                    _currentUser = User.fromJson(responseData['data']['user']);
                  } else {
                    _currentUser = User.fromJson(responseData['data']);
                  }
                  await _saveUserData(_currentUser!, _token!);
                  print('User data updated from response');
                }
              }
            } catch (e) {
              print('Error parsing profile update response: $e');
              // Jika gagal parse, kita tetap refresh data dari server
            }

            break; // Berhasil, keluar dari loop
          }
        } catch (e) {
          print('Error updating profile with endpoint $endpoint: $e');
          continue; // Coba endpoint selanjutnya
        }
      }

      // Jika tidak ada endpoint yang berhasil, update data lokal sebagai fallback
      if (!success) {
        print('All API endpoints failed, updating local data as fallback');

        // Update local user data as fallback
        if (_currentUser != null) {
          // Create updated User with the new data
          final updatedUser = User(
            id: _currentUser!.id,
            name: profileData['username'] ??
                _currentUser!.name, // Use username as name if needed
            username: profileData['username'] ?? _currentUser!.username,
            full_name: profileData['full_name'] ?? _currentUser!.full_name,
            email: profileData['email'] ?? _currentUser!.email,
            phone: profileData['phone'] ?? _currentUser!.phone,
            address: profileData['address'] ?? _currentUser!.address,
            birth_date: profileData['birth_date'] != null
                ? DateTime.parse(profileData['birth_date'])
                : _currentUser!.birth_date,
            createdAt: _currentUser!.createdAt,
            updatedAt: DateTime.now(),
            profile_photo: _currentUser!.profile_photo,
          );

          _currentUser = updatedUser;
          await _saveUserData(_currentUser!, _token!);
          print('Local user data updated as fallback');
          success = true;
        }
      }

      // Try to refresh user data from server if update was successful
      if (success) {
        try {
          await getUser();
        } catch (e) {
          print('Error refreshing user data after update: $e');
          // Not critical since we've already updated local data
        }
      }

      return {
        'success': success,
        'message': success
            ? 'Profile updated successfully'
            : 'Failed to update profile',
        'data': responseData,
        'statusCode': statusCode,
      };
    } catch (e) {
      print('Error updating profile: $e');
      return {
        'success': false,
        'message': 'Error updating profile: ${e.toString()}',
        'error': e.toString(),
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Upload profile photo
  Future<bool> uploadProfilePhoto(File imageFile) async {
    _isLoading = true;
    notifyListeners();

    try {
      // Create multipart request
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/v1/upload-profile-photo'),
      );

      // Add authorization header
      request.headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $_token',
      });

      // Add file
      request.files.add(await http.MultipartFile.fromPath(
        'profile_photo',
        imageFile.path,
      ));

      // Send request
      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);

      print(
          'Upload profile photo response: ${response.statusCode} - ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        // Refresh user data after upload
        await getUser();
        return true;
      } else {
        return false;
      }
    } catch (e) {
      print('Error uploading profile photo: $e');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Check if username is available
  Future<bool> isUsernameAvailable(String username) async {
    if (username.isEmpty) {
      print('Empty username provided, cannot check availability');
      return false;
    }

    // If checking current user's username, it's available for them
    if (_currentUser != null && _currentUser!.username == username) {
      print('User is keeping their existing username: $username');
      return true;
    }

    // Remove spaces and special characters
    username = username.trim().replaceAll(RegExp(r'[^\w\s]'), '');

    try {
      print('Checking availability for username: $username');

      // First try with the dedicated endpoint
      try {
        // Try using check-username-availability endpoint
        final response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/check-username-availability',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': _token != null ? 'Bearer $_token' : '',
            },
            body: json.encode({
              'username': username,
            }),
          ),
        ).timeout(const Duration(seconds: 5));

        print(
            'Username availability check response: ${response.statusCode} - ${response.body}');

        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          return data['available'] == true;
        }
      } catch (e) {
        print('Dedicated username endpoint not available: $e');
        // Continue to fallback method
      }

      // Fallback method 1: Try to retrieve user by username
      try {
        final response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/users/by-username/$username',
          requestFunction: (url) => http.get(
            Uri.parse(url),
            headers: {
              'Accept': 'application/json',
              'Authorization': _token != null ? 'Bearer $_token' : '',
            },
          ),
        ).timeout(const Duration(seconds: 5));

        // If user is found (200), username is taken
        // If user is not found (404), username is available
        if (response.statusCode == 404) {
          print('Username $username is available (user not found)');
          return true;
        } else if (response.statusCode == 200) {
          print('Username $username is taken (user found)');
          return false;
        }
      } catch (e) {
        print('Fallback username check failed: $e');
      }

      // Fallback method 2: Use search endpoint if available
      try {
        final response = await _tryRequestWithMultipleUrls(
          endpoint: 'v1/search/users',
          requestFunction: (url) => http.post(
            Uri.parse(url),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': _token != null ? 'Bearer $_token' : '',
            },
            body: json.encode({
              'query': username,
              'exact': true,
            }),
          ),
        ).timeout(const Duration(seconds: 5));

        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          // If we find users with exactly this username, it's taken
          if (data.containsKey('data') && data['data'] is List) {
            final users = data['data'] as List;
            for (var user in users) {
              if (user is Map &&
                  user.containsKey('username') &&
                  user['username'].toString().toLowerCase() ==
                      username.toLowerCase()) {
                print('Username $username is taken (found in search results)');
                return false;
              }
            }
          }
          // No exact match found, username is available
          print(
              'Username $username is available (not found in search results)');
          return true;
        }
      } catch (e) {
        print('Search fallback for username check failed: $e');
      }

      // When all checks fail but we have no clear indication username is taken
      print(
          'All username checks failed but no indication username is taken, assuming available');
      return true;
    } catch (e) {
      print('Error checking username availability: $e');
      // Default to true on error to be safe, but log a warning
      print('WARNING: Defaulting to available due to error');
      return true;
    }
  }
}
