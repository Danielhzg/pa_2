import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:math' show min;

class FavoriteProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  final AuthService _authService;

  // List of favorite products
  final List<Product> _favorites = [];
  // Map of product IDs to persist even when logged out
  Set<int> _localFavoriteIds = {};
  bool _isLoading = false;
  bool _initialized = false;

  FavoriteProvider(this._authService) {
    _initializeFavorites();

    // Listen for login/logout events
    _authService.addListener(_onAuthChanged);
  }

  void _initializeFavorites() async {
    // First load local favorites from SharedPreferences
    await _loadLocalFavorites();

    // Mark as initialized
    _initialized = true;

    // If user is logged in, also load server favorites
    if (_authService.isLoggedIn) {
      await loadFavorites();
    }

    notifyListeners();
  }

  @override
  void dispose() {
    _authService.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() {
    if (_authService.isLoggedIn) {
      // When user logs in, sync with server
      loadFavorites();
    } else {
      // When user logs out, keep the local favorites but reload UI
      _updateFavoritesFromLocalIds();
      notifyListeners();
    }
  }

  List<Product> get favorites => _favorites;
  bool get isLoading => _isLoading;
  bool get initialized => _initialized;
  Set<int> get localFavoriteIds => _localFavoriteIds;

  // Check if a product is in favorites
  bool isFavorite(int productId) {
    // Check if the product is in the local favorites set
    return _localFavoriteIds.contains(productId);
  }

  // Load local favorites from SharedPreferences
  Future<void> _loadLocalFavorites() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final localFavoriteIdsJson = prefs.getString('local_favorite_ids');

      if (localFavoriteIdsJson != null) {
        final List<dynamic> idsList = json.decode(localFavoriteIdsJson);
        _localFavoriteIds = Set<int>.from(idsList.map((id) => id as int));
        print(
            'Loaded ${_localFavoriteIds.length} local favorite IDs from storage');
      } else {
        _localFavoriteIds = {};
        print('No local favorites found in storage');
      }
    } catch (e) {
      print('Error loading local favorites: $e');
      _localFavoriteIds = {};
    }
  }

  // Save local favorites to SharedPreferences
  Future<void> _saveLocalFavorites() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final localFavoriteIdsJson =
          json.encode(List<int>.from(_localFavoriteIds));
      await prefs.setString('local_favorite_ids', localFavoriteIdsJson);
      print('Saved ${_localFavoriteIds.length} local favorite IDs to storage');
    } catch (e) {
      print('Error saving local favorites: $e');
    }
  }

  // Update favorites list from the local IDs
  void _updateFavoritesFromLocalIds() {
    // Mark all products in _favorites as favorited or not based on _localFavoriteIds
    for (var product in _favorites) {
      product.isFavorited = _localFavoriteIds.contains(product.id);
    }
  }

  // Load all favorites from the API
  Future<void> loadFavorites() async {
    if (!_authService.isLoggedIn) {
      print('Not loading server favorites: User is not logged in');
      return;
    }

    _isLoading = true;
    notifyListeners();

    try {
      // Check if token exists
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        print('ERROR: No auth token found when trying to load favorites');
        _isLoading = false;
        notifyListeners();
        return;
      }

      print(
          'Fetching favorite products from API with token: ${token.substring(0, min(10, token.length))}...');
      final favoriteData = await _apiService.getFavoriteProducts();
      print('Received ${favoriteData.length} favorite products from server');

      // Clear existing favorites
      _favorites.clear();

      if (favoriteData.isNotEmpty) {
        for (var item in favoriteData) {
          try {
            // Check if product data exists
            if (item != null &&
                item.containsKey('product') &&
                item['product'] != null) {
              // Create product from json and mark as favorited
              final product = Product.fromJson(item['product']);

              // Add to local favorites set to persist
              _localFavoriteIds.add(product.id);

              // Mark as favorited and add to favorites list
              product.isFavorited = true;
              _favorites.add(product);
              print('Added product ${product.id} to favorites');
            } else if (item != null && item.containsKey('product_id')) {
              // If we only have product_id, at least add it to local favorites
              int productId = item['product_id'];
              _localFavoriteIds.add(productId);
              print('Added product ID $productId to local favorites set');
            }
          } catch (e) {
            print('Error processing favorite item: $e');
            print('Problematic item: ${item.toString()}');
          }
        }
        print('Processed ${_favorites.length} favorite products');
      } else {
        print('No favorite products found in the response');
      }

      // Save updated local favorites
      await _saveLocalFavorites();
    } catch (e) {
      print('Error loading favorites: $e');
      // Don't clear the favorites on error to maintain UI consistency
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Toggle favorite status for a product
  Future<bool> toggleFavorite(Product product) async {
    try {
      print('Toggling favorite status for product ${product.id}');

      // Store the original favorite status in case we need to revert
      final originalStatus = product.isFavorited;

      // Update local state first for immediate feedback
      bool newFavoriteStatus = !product.isFavorited;
      product.isFavorited = newFavoriteStatus;

      // Update local favorites set
      if (newFavoriteStatus) {
        _localFavoriteIds.add(product.id);
      } else {
        _localFavoriteIds.remove(product.id);
      }

      // Save to local storage immediately
      await _saveLocalFavorites();

      if (newFavoriteStatus) {
        // If we're adding to favorites, add to local list if not already there
        if (!_favorites.any((p) => p.id == product.id)) {
          _favorites.add(product);
          print('Added product ${product.id} to local favorites');
        }
      } else {
        // If we're removing from favorites, remove from local list
        _favorites.removeWhere((p) => p.id == product.id);
        print('Removed product ${product.id} from local favorites');
      }

      // Notify UI of the change before API call
      notifyListeners();

      // If user is not logged in, just return the new status
      if (!_authService.isLoggedIn) {
        print('User not logged in, only updating local storage');
        return newFavoriteStatus;
      }

      // If user is logged in, also update the server
      try {
        // Try to get authentication token
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');

        if (token == null || token.isEmpty) {
          print(
              'ERROR: No auth token found when trying to toggle favorite on server');
          return newFavoriteStatus; // Still return local status
        }

        // Make API call to sync with server
        print(
            'Sending favorite toggle request to API for product ${product.id}...');
        final serverFavoriteStatus =
            await _apiService.toggleFavorite(product.id);
        print(
            'API response for product ${product.id}: isFavorited = $serverFavoriteStatus');

        // If server response differs from our expected state, fix it
        if (serverFavoriteStatus != product.isFavorited) {
          print(
              'Server response differs from local state, updating product ${product.id}...');

          // If the server state is different, trust the server and update local state
          product.isFavorited = serverFavoriteStatus;

          // Update local favorites set to match server
          if (serverFavoriteStatus) {
            _localFavoriteIds.add(product.id);
          } else {
            _localFavoriteIds.remove(product.id);
          }

          // Save updated local state
          await _saveLocalFavorites();

          // Ensure favorites list is consistent with server state
          if (serverFavoriteStatus) {
            if (!_favorites.any((p) => p.id == product.id)) {
              _favorites.add(product);
              print(
                  'Added product ${product.id} to local favorites based on API response');
            }
          } else {
            _favorites.removeWhere((p) => p.id == product.id);
            print(
                'Removed product ${product.id} from local favorites based on API response');
          }

          notifyListeners();

          // If the server rejected our change, show detailed logs
          if (serverFavoriteStatus == originalStatus) {
            print(
                'WARNING: Server rejected favorite change for product ${product.id}. Check authorization.');
          }
        }

        // Always return the server's state as the source of truth when logged in
        return serverFavoriteStatus;
      } catch (serverError) {
        print('Error syncing with server: $serverError');
        // On server error, still keep the local change
        return newFavoriteStatus;
      }
    } catch (e) {
      print('Error toggling favorite for product ${product.id}: $e');
      return product.isFavorited; // Return current state
    }
  }

  // Clear all favorites
  Future<void> clearAllFavorites() async {
    try {
      print('Clearing all favorites');

      // Clear local favorites first for immediate feedback
      _localFavoriteIds.clear();
      _favorites.clear();
      await _saveLocalFavorites();

      // Notify UI
      notifyListeners();

      // If user is logged in, also clear on server
      if (_authService.isLoggedIn) {
        try {
          // Check if token exists
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('auth_token');

          if (token == null || token.isEmpty) {
            print(
                'ERROR: No auth token found when trying to clear favorites on server');
            return;
          }

          // Since we might not have a direct API endpoint to clear all favorites,
          // we'll iterate through all favorites and remove them one by one
          final List<Product> productsToRemove = List.from(_favorites);

          // Remove each product individually from server
          for (var product in productsToRemove) {
            print('Removing product ${product.id} from favorites on server');
            await _apiService.toggleFavorite(product.id);
          }

          print('Successfully cleared all favorites on server');
        } catch (serverError) {
          print('Error clearing favorites on server: $serverError');
          // Local favorites are already cleared
        }
      }
    } catch (e) {
      print('Error clearing favorites: $e');
      throw Exception('Failed to clear favorites: $e');
    }
  }
}
