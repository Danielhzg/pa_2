import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/cart_item.dart';
import '../models/product.dart';

class CartProvider with ChangeNotifier {
  List<CartItem> _items = [];
  bool _isLoading = false; // Add loading state
  String? _userId; // Add userId to track the current user

  List<CartItem> get items => _items;
  bool get isLoading => _isLoading; // Getter for loading state

  int get itemCount => _items.length;

  // Check if all items are selected
  bool get allItemsSelected {
    if (_items.isEmpty) return false;
    return _items.every((item) => item.isSelected);
  }

  double get totalAmount {
    double total = 0;
    for (var item in _items) {
      if (item.isSelected) {
        total += item.total;
      }
    }
    return total;
  }

  int get selectedItemCount {
    return _items.where((item) => item.isSelected).length;
  }

  // Set current user ID - call this when user logs in or out
  void setUserId(String? userId) {
    if (_userId != userId) {
      _userId = userId;
      // Load cart for the new user
      loadCartFromStorage();
    }
  }

  // Menambahkan produk ke keranjang
  void addToCart(Product product, int quantity) {
    // Cek apakah produk sudah ada di keranjang
    final existingItemIndex =
        _items.indexWhere((item) => item.productId == product.id.toString());

    if (existingItemIndex >= 0) {
      // Jika produk sudah ada, tambahkan quantity
      _items[existingItemIndex].quantity += quantity;
    } else {
      // Jika produk belum ada, tambahkan sebagai item baru
      _items.add(
        CartItem(
          id: DateTime.now().millisecondsSinceEpoch.toString(),
          productId: product.id.toString(),
          name: product.name,
          price: product.isOnSale
              ? product.price * (100 - product.discount) / 100
              : product.price,
          imageUrl: product.imageUrl,
          quantity: quantity,
          isSelected: true,
        ),
      );
    }

    // Simpan ke persistent storage
    _saveCartToStorage();
    notifyListeners();
  }

  // Toggle selection status
  void toggleItemSelection(int productId) {
    final itemIndex =
        _items.indexWhere((item) => item.productId == productId.toString());
    if (itemIndex >= 0) {
      _items[itemIndex].isSelected = !_items[itemIndex].isSelected;
      _saveCartToStorage();
      notifyListeners();
    }
  }

  // Select all items
  void selectAll(bool selected) {
    for (var item in _items) {
      item.isSelected = selected;
    }
    _saveCartToStorage();
    notifyListeners();
  }

  // Update jumlah item
  void updateQuantity(int productId, int newQuantity) {
    if (newQuantity < 1) return;

    final itemIndex =
        _items.indexWhere((item) => item.productId == productId.toString());
    if (itemIndex >= 0) {
      _items[itemIndex].quantity = newQuantity;
      _saveCartToStorage();
      notifyListeners();
    }
  }

  // Hapus item dari keranjang
  void removeItem(String productId) {
    _items.removeWhere((item) => item.productId == productId);
    _saveCartToStorage();
    notifyListeners();
  }

  // Remove only selected items from cart
  void removeSelectedItems() {
    _items.removeWhere((item) => item.isSelected);
    _saveCartToStorage();
    notifyListeners();
  }

  // Kosongkan keranjang
  void clear() {
    _items = [];
    _saveCartToStorage();
    notifyListeners();
  }

  // Get storage key that's unique for each user
  String _getCartStorageKey() {
    return _userId != null ? 'cart_$_userId' : 'cart_guest';
  }

  // Menyimpan data keranjang ke SharedPreferences
  Future<void> _saveCartToStorage() async {
    final prefs = await SharedPreferences.getInstance();
    final cartData = _items
        .map((item) => {
              'id': item.id,
              'productId': item.productId,
              'name': item.name,
              'price': item.price,
              'imageUrl': item.imageUrl,
              'quantity': item.quantity,
              'isSelected': item.isSelected,
            })
        .toList();

    final storageKey = _getCartStorageKey();
    print('Saving cart for user key: $storageKey with ${_items.length} items');
    await prefs.setString(storageKey, json.encode(cartData));
  }

  // Memuat data keranjang dari SharedPreferences
  Future<void> loadCartFromStorage() async {
    _isLoading = true;
    // Use WidgetsBinding for the first notifyListeners to ensure it's not called during build
    WidgetsBinding.instance.addPostFrameCallback((_) {
      notifyListeners();
    });

    try {
      // Get user ID from SharedPreferences if not already set
      if (_userId == null) {
        final prefs = await SharedPreferences.getInstance();
        final userData = prefs.getString('user_data');
        if (userData != null) {
          final user = json.decode(userData);
          _userId = user['id'].toString();
        }
      }

      final storageKey = _getCartStorageKey();
      print('Loading cart for user key: $storageKey');

      final prefs = await SharedPreferences.getInstance();
      if (!prefs.containsKey(storageKey)) {
        // If no cart exists for this user, clear the cart
        _items = [];
        _isLoading = false;
        // Use WidgetsBinding for notifyListeners to ensure it's not called during build
        WidgetsBinding.instance.addPostFrameCallback((_) {
          notifyListeners();
        });
        return;
      }

      final cartData = json.decode(prefs.getString(storageKey)!);
      _items = (cartData as List)
          .map((item) => CartItem(
                id: item['id'],
                productId: item['productId'],
                name: item['name'],
                price: item['price'].toDouble(),
                imageUrl: item['imageUrl'],
                quantity: item['quantity'],
                isSelected: item['isSelected'] ?? true,
              ))
          .toList();

      print('Loaded ${_items.length} items for user key: $storageKey');
    } catch (e) {
      print('Error loading cart: $e');
      // In case of error, ensure the cart is empty
      _items = [];
    } finally {
      _isLoading = false;
      // Use WidgetsBinding for the final notifyListeners to ensure it's not called during build
      WidgetsBinding.instance.addPostFrameCallback((_) {
        notifyListeners();
      });
    }
  }
}
