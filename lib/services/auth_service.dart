import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../utils/database_helper.dart';

class AuthService with ChangeNotifier {
  static const String TOKEN_KEY = 'auth_token';
  static const String USER_DATA_KEY = 'user_data';

  bool _isAuthenticated = false;
  String? _token;
  Map<String, dynamic>? _userData;

  bool get isAuthenticated => _isAuthenticated;
  String? get token => _token;
  Map<String, dynamic>? get userData => _userData;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(TOKEN_KEY);
    if (_token != null) {
      print('AuthService init: Setting token: $_token');
      DatabaseHelper.instance.setToken(_token!);
      _isAuthenticated = true;

      // Load saved user data
      String? savedUserData = prefs.getString(USER_DATA_KEY);
      if (savedUserData != null) {
        _userData = json.decode(savedUserData);
        print('AuthService init: Loaded saved user data: $_userData');
      }

      // Refresh user data from server
      await refreshUserData();
    }
    notifyListeners();
  }

  Future<bool> login(String username, String password) async {
    try {
      final result =
          await DatabaseHelper.instance.validateUser(username, password);

      if (result['success']) {
        _token = result['token'];
        _userData = result['user'];
        _isAuthenticated = true;

        DatabaseHelper.instance.setToken(_token!);

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(TOKEN_KEY, _token!);
        await prefs.setString(USER_DATA_KEY, json.encode(_userData));

        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      print('Login error: $e');
      return false;
    }
  }

  Future<bool> register(
      String username, String email, String phone, String password) async {
    try {
      final result = await DatabaseHelper.instance
          .createUser(username, email, phone, password);
      return result['success'];
    } catch (e) {
      print('Registration error: $e');
      return false;
    }
  }

  Future<void> logout() async {
    _token = null;
    _userData = null;
    _isAuthenticated = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(TOKEN_KEY);
    notifyListeners();
  }

  Future<void> refreshUserData() async {
    try {
      if (_token == null) {
        print('refreshUserData: No token available');
        return;
      }

      print('refreshUserData: Fetching user profile...');
      final result = await DatabaseHelper.instance.getUserProfile();
      print('refreshUserData: Result: $result');

      if (result['success']) {
        _userData = result['user'];
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(USER_DATA_KEY, json.encode(_userData));
        print('refreshUserData: Updated user data: $_userData');
        notifyListeners();
      }
    } catch (e) {
      print('Error refreshing user data: $e');
    }
  }
}
