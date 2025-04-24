import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:math';
import '../models/delivery_address.dart';
import '../models/user.dart';
import 'package:uuid/uuid.dart';

class DeliveryProvider with ChangeNotifier {
  List<DeliveryAddress> _addresses = [];
  DeliveryAddress? _selectedAddress;
  double _shippingCost = 0;

  // Mock store location (would come from backend in production)
  final double storeLatitude = -6.2088; // Example: Jakarta coordinates
  final double storeLongitude = 106.8456;

  List<DeliveryAddress> get addresses => _addresses;
  DeliveryAddress? get selectedAddress => _selectedAddress;
  double get shippingCost => _shippingCost;

  // Initialize with user address from registration
  Future<void> initAddresses() async {
    if (_addresses.isEmpty) {
      await loadAddresses();

      // If still empty after loading, try to get user data
      if (_addresses.isEmpty) {
        await _createAddressFromUserData();
      }

      // If still empty after trying user data, add a demo address
      if (_addresses.isEmpty) {
        _addresses = [
          DeliveryAddress(
            id: '1',
            name: 'Home',
            phone: '081234567890',
            address: 'Jl. Merdeka No. 123',
            city: 'Jakarta',
            district: 'Central Jakarta',
            postalCode: '10110',
            latitude: -6.2088,
            longitude: 106.8456,
            isDefault: true,
          ),
        ];
      }

      _selectedAddress = _addresses.firstWhere((addr) => addr.isDefault,
          orElse: () => _addresses.first);

      // Calculate initial shipping cost
      calculateShippingCost();

      await saveAddresses();
    }
  }

  // Create address from user's registration data
  Future<void> _createAddressFromUserData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');

      if (userData != null) {
        final user = User.fromJson(json.decode(userData));

        // Check if user has address data
        if (user.address != null && user.address!.isNotEmpty) {
          // Parse the address string - this is simplified and assumes a format
          // In a real app, you'd have more structured address data
          final addressParts = user.address!.split(',');

          String street = user.address!;
          String district = 'District';
          String city = 'City';
          String postalCode = '10000';

          if (addressParts.length >= 2) {
            street = addressParts[0].trim();
            district = addressParts[1].trim();
            if (addressParts.length >= 3) {
              city = addressParts[2].trim();
              if (addressParts.length >= 4) {
                postalCode = addressParts[3].trim();
              }
            }
          }

          // Create a delivery address from the user data
          final address = DeliveryAddress(
            id: const Uuid().v4(),
            name: user.full_name ?? 'Home',
            phone: user.phone ?? '',
            address: street,
            city: city,
            district: district,
            postalCode: postalCode,
            // Default coordinates - in a real app, you would geocode the address
            latitude: -6.2088,
            longitude: 106.8456,
            isDefault: true,
          );

          _addresses.add(address);
          _selectedAddress = address;
        }
      }
    } catch (e) {
      print('Error creating address from user data: $e');
    }
  }

  // Calculate shipping cost based on distance (in km)
  void calculateShippingCost() {
    if (_selectedAddress == null) {
      _shippingCost = 0;
      return;
    }

    final distance = calculateDistance(storeLatitude, storeLongitude,
        _selectedAddress!.latitude, _selectedAddress!.longitude);

    // Base delivery fee
    const baseFee = 10000.0;

    // Additional fee per km
    const feePerKm = 2000.0;

    // Calculate total fee (base + distance based)
    _shippingCost = baseFee + (distance * feePerKm);

    // Minimum shipping cost
    if (_shippingCost < baseFee) {
      _shippingCost = baseFee;
    }

    // Maximum shipping cost cap (optional)
    const maxShippingCost = 50000.0;
    if (_shippingCost > maxShippingCost) {
      _shippingCost = maxShippingCost;
    }

    notifyListeners();
  }

  // Calculate distance between two points using Haversine formula
  double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const int earthRadius = 6371; // Earth radius in kilometers

    double latDistance = _toRadians(lat2 - lat1);
    double lonDistance = _toRadians(lon2 - lon1);

    double a = sin(latDistance / 2) * sin(latDistance / 2) +
        cos(_toRadians(lat1)) *
            cos(_toRadians(lat2)) *
            sin(lonDistance / 2) *
            sin(lonDistance / 2);

    double c = 2 * atan2(sqrt(a), sqrt(1 - a));

    return earthRadius * c; // Distance in km
  }

  double _toRadians(double degree) {
    return degree * pi / 180;
  }

  // Select an address
  void selectAddress(String addressId) {
    final address = _addresses.firstWhere((addr) => addr.id == addressId);
    _selectedAddress = address;
    calculateShippingCost();
    notifyListeners();
  }

  // Add a new address
  Future<void> addAddress(DeliveryAddress address) async {
    _addresses.add(address);

    // If it's the first address or is marked as default, select it
    if (_addresses.length == 1 || address.isDefault) {
      _selectedAddress = address;
      calculateShippingCost();
    }

    await saveAddresses();
    notifyListeners();
  }

  // Update an existing address
  Future<void> updateAddress(DeliveryAddress updatedAddress) async {
    final index = _addresses.indexWhere((addr) => addr.id == updatedAddress.id);
    if (index >= 0) {
      _addresses[index] = updatedAddress;

      // If the updated address is currently selected, update selection
      if (_selectedAddress?.id == updatedAddress.id) {
        _selectedAddress = updatedAddress;
        calculateShippingCost();
      }

      await saveAddresses();
      notifyListeners();
    }
  }

  // Delete an address
  Future<void> deleteAddress(String addressId) async {
    _addresses.removeWhere((addr) => addr.id == addressId);

    // If deleted address was selected, select another one
    if (_selectedAddress?.id == addressId) {
      _selectedAddress = _addresses.isNotEmpty
          ? (_addresses.firstWhere((addr) => addr.isDefault,
              orElse: () => _addresses.first))
          : null;
      calculateShippingCost();
    }

    await saveAddresses();
    notifyListeners();
  }

  // Save addresses to persistent storage
  Future<void> saveAddresses() async {
    final prefs = await SharedPreferences.getInstance();
    final addressesData = _addresses.map((addr) => addr.toJson()).toList();
    await prefs.setString('delivery_addresses', json.encode(addressesData));

    // Save selected address ID
    if (_selectedAddress != null) {
      await prefs.setString('selected_address_id', _selectedAddress!.id);
    } else {
      await prefs.remove('selected_address_id');
    }
  }

  // Load addresses from persistent storage
  Future<void> loadAddresses() async {
    final prefs = await SharedPreferences.getInstance();
    if (prefs.containsKey('delivery_addresses')) {
      try {
        final addressesData =
            json.decode(prefs.getString('delivery_addresses')!);
        _addresses = (addressesData as List)
            .map((data) => DeliveryAddress.fromJson(data))
            .toList();

        // Load selected address
        final selectedId = prefs.getString('selected_address_id');
        if (selectedId != null && _addresses.isNotEmpty) {
          _selectedAddress = _addresses.firstWhere(
            (addr) => addr.id == selectedId,
            orElse: () => _addresses.first,
          );
        } else if (_addresses.isNotEmpty) {
          _selectedAddress = _addresses.first;
        }

        calculateShippingCost();
      } catch (e) {
        print('Error loading addresses: $e');
      }
    }
  }
}
