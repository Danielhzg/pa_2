import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:math' show min;

class FavoriteProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  final AuthService _authService;

  // List of favorite products
  List<Product> _favorites = [];
  bool _isLoading = false;

  FavoriteProvider(this._authService) {
    if (_authService.isLoggedIn) {
      loadFavorites();
    }

    // Listen for login/logout events
    _authService.addListener(_onAuthChanged);
  }

  @override
  void dispose() {
    _authService.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() {
    if (_authService.isLoggedIn) {
      loadFavorites();
    } else {
      _favorites = [];
      notifyListeners();
    }
  }

  List<Product> get favorites => _favorites;
  bool get isLoading => _isLoading;

  // Check if a product is in favorites
  bool isFavorite(int productId) {
    return _favorites.any((product) => product.id == productId);
  }

  // Load all favorites from the API
  Future<void> loadFavorites() async {
    if (!_authService.isLoggedIn) {
      print('Not loading favorites: User is not logged in');
      _favorites = [];
      notifyListeners();
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
      print('Received ${favoriteData.length} favorite products');

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
              product.isFavorited = true;
              _favorites.add(product);
              print('Added product ${product.id} to favorites');
            } else if (item != null && item.containsKey('product_id')) {
              // If we only have product_id, try to fetch the product details
              int productId = item['product_id'];
              print(
                  'Favorite contains only product_id: $productId, trying to get full product data');

              // Here you could add logic to fetch product details if needed
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
    if (!_authService.isLoggedIn) {
      print('Cannot toggle favorite: user is not logged in');
      return false;
    }

    try {
      print('Toggling favorite status for product ${product.id}');

      // Store the original favorite status in case we need to revert
      final originalStatus = product.isFavorited;

      // Update local state first for immediate feedback
      bool newFavoriteStatus = !product.isFavorited;
      product.isFavorited = newFavoriteStatus;

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

      // Try to get authentication token
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        print('ERROR: No auth token found when trying to toggle favorite');
        throw Exception('No authentication token found. Please log in again.');
      }

      // Make API call to sync with server
      print(
          'Sending favorite toggle request to API for product ${product.id}...');
      final serverFavoriteStatus = await _apiService.toggleFavorite(product.id);
      print(
          'API response for product ${product.id}: isFavorited = $serverFavoriteStatus');

      // If server response differs from our expected state, fix it
      if (serverFavoriteStatus != product.isFavorited) {
        print(
            'Server response differs from local state, updating product ${product.id}...');

        // If the server state is different, trust the server and update local state
        product.isFavorited = serverFavoriteStatus;

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

      // Always return the server's state as the source of truth
      return serverFavoriteStatus;
    } catch (e) {
      print('Error toggling favorite for product ${product.id}: $e');

      // On error, revert back to the server state by fetching all favorites again
      try {
        print('Attempting to refresh favorites after error');
        await loadFavorites();

        // After refreshing, check if this product is in our favorites list
        final isFavorited = _favorites.any((p) => p.id == product.id);

        // Make sure the product status matches our list
        if (product.isFavorited != isFavorited) {
          product.isFavorited = isFavorited;
          notifyListeners();
        }

        return isFavorited;
      } catch (refreshError) {
        print('Failed to refresh favorites after error: $refreshError');

        // If refreshing fails, at least revert this specific product's status
        product.isFavorited = !product.isFavorited;

        if (product.isFavorited) {
          if (!_favorites.any((p) => p.id == product.id)) {
            _favorites.add(product);
          }
        } else {
          _favorites.removeWhere((p) => p.id == product.id);
        }

        notifyListeners();
      }

      return product.isFavorited;
    }
  }

  // Clear all favorites
  Future<void> clearAllFavorites() async {
    if (!_authService.isLoggedIn) {
      print('Cannot clear favorites: user is not logged in');
      return;
    }

    try {
      print('Clearing all favorites');

      // Check if token exists
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        print('ERROR: No auth token found when trying to clear favorites');
        throw Exception('No authentication token found. Please log in again.');
      }

      // Since we might not have a direct API endpoint to clear all favorites,
      // we'll iterate through all favorites and remove them one by one
      final List<Product> productsToRemove = List.from(_favorites);

      // Update local state immediately for better user experience
      _favorites.clear();
      notifyListeners();

      // Remove each product individually from server
      for (var product in productsToRemove) {
        print('Removing product ${product.id} from favorites');
        await _apiService.toggleFavorite(product.id);
      }

      print('Successfully cleared all favorites');
    } catch (e) {
      print('Error clearing favorites: $e');

      // Refresh favorites to ensure UI reflects server state
      await loadFavorites();

      throw Exception('Failed to clear favorites: $e');
    }
  }
}
