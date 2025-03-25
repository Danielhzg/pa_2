import 'dart:convert';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';

class AuthService extends ChangeNotifier {
  // Base API URL - Change this to your Laravel API URL
  static const String _baseUrl = 'http://10.0.2.2:8000/api/v1';

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
    String? name,
    required String username,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    // Ensure initialization has completed
    await initializationFuture;

    // Now we can safely call notifyListeners
    _isLoading = true;
    notifyListeners();

    print('Attempting to register user: $username');
    print('API URL: $_baseUrl/register');

    try {
      // Prepare the request body with exactly the fields expected by the server
      final Map<String, dynamic> requestBody = {
        'name': name ?? username,
        'username': username,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      };

      print('Registration request payload: ${json.encode(requestBody)}');

      final response = await http
          .post(
            Uri.parse('$_baseUrl/register'),
            headers: {'Content-Type': 'application/json'},
            body: json.encode(requestBody),
          )
          .timeout(const Duration(seconds: 15));

      print('Registration response status: ${response.statusCode}');
      print('Registration response body: ${response.body}');

      // Try to decode the response
      Map<String, dynamic> responseData;
      try {
        responseData = json.decode(response.body);
        print('Decoded response data: $responseData');
      } catch (e) {
        print('Error decoding response: $e');
        print('Raw response: ${response.body}');
        _isLoading = false;
        notifyListeners();
        return {
          'success': false,
          'message': 'Server error. Please try again later.',
          'error': 'Failed to decode server response',
        };
      }

      if (response.statusCode == 201) {
        // Successfully created user
        final userData = responseData['data']['user'];
        final authToken = responseData['data']['token'];
        print('User data received: $userData');

        _currentUser = User.fromJson(userData);
        _token = authToken;
        await _saveUserData(_currentUser!, _token!);

        _isLoading = false;
        notifyListeners();
        return {'success': true};
      } else {
        // Registration failed - provide detailed error information
        _isLoading = false;
        notifyListeners();
        return {
          'success': false,
          'message': responseData['message'] ?? 'Registration failed',
          'details': responseData,
          'status_code': response.statusCode,
        };
      }
    } catch (e) {
      print('Register network error: $e');
      _isLoading = false;
      notifyListeners();
      return {
        'success': false,
        'message':
            'Connection error. Please check your internet connection and try again.',
        'error': e.toString(),
      };
    }
  }

  // Login user with username and password
  Future<bool> login(String username, String password) async {
    // Ensure initialization has completed
    await initializationFuture;

    // Now we can safely call notifyListeners
    _isLoading = true;
    notifyListeners();

    print('Attempting to login user: $username');
    print('API URL: $_baseUrl/login');

    try {
      print('Sending login request...');
      final response = await http
          .post(
            Uri.parse('$_baseUrl/login'),
            headers: {'Content-Type': 'application/json'},
            body: json.encode({
              'username': username,
              'password': password,
            }),
          )
          .timeout(const Duration(seconds: 15));

      print('Login response status: ${response.statusCode}');
      print('Login response body: ${response.body}');

      // Try to decode the response
      Map<String, dynamic> responseData;
      try {
        responseData = json.decode(response.body);
        print('Decoded login response: $responseData');
      } catch (e) {
        print('Error decoding login response: $e');
        print('Raw response: ${response.body}');
        _isLoading = false;
        notifyListeners();
        return false;
      }

      if (response.statusCode == 200) {
        final userData = responseData['data']['user'];
        final authToken = responseData['data']['token'];
        print('User data received: $userData');

        _currentUser = User.fromJson(userData);
        _token = authToken;
        await _saveUserData(_currentUser!, _token!);

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        print('Login failed: ${responseData['message']}');
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      print('Login connection error: $e');
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
}
