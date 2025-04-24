import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/product.dart';

class FavoritesProvider with ChangeNotifier {
  List<Product> _favorites = [];

  // Getter for favorites
  List<Product> get favorites => [..._favorites];

  // Check if a product is favorited
  bool isFavorite(int productId) {
    return _favorites.any((product) => product.id == productId);
  }

  // Toggle favorite status
  void toggleFavorite(Product product) {
    final isExist = _favorites.any((item) => item.id == product.id);
    if (isExist) {
      _favorites.removeWhere((item) => item.id == product.id);
    } else {
      _favorites.add(product);
    }
    _saveFavoritesToStorage();
    notifyListeners();
  }

  // Remove from favorites
  void removeFavorite(int productId) {
    _favorites.removeWhere((product) => product.id == productId);
    _saveFavoritesToStorage();
    notifyListeners();
  }

  // Clear all favorites
  void clear() {
    _favorites = [];
    _saveFavoritesToStorage();
    notifyListeners();
  }

  // Save favorites to SharedPreferences
  Future<void> _saveFavoritesToStorage() async {
    final prefs = await SharedPreferences.getInstance();
    final favoritesData = _favorites
        .map((product) => {
              'id': product.id,
              'name': product.name,
              'description': product.description,
              'price': product.price,
              'imageUrl': product.imageUrl,
              'categoryName': product.categoryName,
              'categoryId': product.categoryId,
              'rating': product.rating,
              'isFeatured': product.isFeatured,
              'isOnSale': product.isOnSale,
              'discount': product.discount,
              'stock': product.stock,
            })
        .toList();

    await prefs.setString('favorites', json.encode(favoritesData));
  }

  // Load favorites from SharedPreferences
  Future<void> loadFavoritesFromStorage() async {
    final prefs = await SharedPreferences.getInstance();
    if (!prefs.containsKey('favorites')) return;

    final favoritesData = json.decode(prefs.getString('favorites')!) as List;
    _favorites = favoritesData
        .map(
          (item) => Product(
            id: item['id'],
            name: item['name'],
            description: item['description'],
            price: (item['price'] as num).toDouble(),
            imageUrl: item['imageUrl'],
            categoryName: item['categoryName'],
            categoryId: item['categoryId'],
            rating: (item['rating'] as num).toDouble(),
            isFeatured: item['isFeatured'],
            isOnSale: item['isOnSale'],
            discount: item['discount'],
            stock: item['stock'] ?? 0,
          ),
        )
        .toList();
    notifyListeners();
  }
}
