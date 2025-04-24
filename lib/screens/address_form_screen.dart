import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/delivery_address.dart';
import '../providers/delivery_provider.dart';

class AddressFormScreen extends StatefulWidget {
  final DeliveryAddress? address;

  const AddressFormScreen({super.key, this.address});

  @override
  State<AddressFormScreen> createState() => _AddressFormScreenState();
}

class _AddressFormScreenState extends State<AddressFormScreen> {
  final _formKey = GlobalKey<FormState>();

  late String _name;
  late String _phone;
  late String _address;
  late String _city;
  late String _district;
  late String _postalCode;
  late bool _isDefault;

  // Mock coordinates (in a real app, would use geocoding API)
  late double _latitude;
  late double _longitude;

  @override
  void initState() {
    super.initState();

    if (widget.address != null) {
      _name = widget.address!.name;
      _phone = widget.address!.phone;
      _address = widget.address!.address;
      _city = widget.address!.city;
      _district = widget.address!.district;
      _postalCode = widget.address!.postalCode;
      _isDefault = widget.address!.isDefault;
      _latitude = widget.address!.latitude;
      _longitude = widget.address!.longitude;
    } else {
      _name = '';
      _phone = '';
      _address = '';
      _city = '';
      _district = '';
      _postalCode = '';
      _isDefault = false;
      // Default coordinates (Jakarta)
      _latitude = -6.2088;
      _longitude = 106.8456;
    }
  }

  Future<void> _saveAddress() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    _formKey.currentState!.save();

    // In a real app, we would use a geocoding service to get coordinates
    // For now, we just slightly alter the mock coordinates to simulate different locations
    if (widget.address == null) {
      // Generate random coordinates near Jakarta for demo
      _latitude = -6.2088 + ((_name.length % 10) * 0.001);
      _longitude = 106.8456 + ((_address.length % 10) * 0.001);
    }

    final deliveryProvider =
        Provider.of<DeliveryProvider>(context, listen: false);

    if (widget.address == null) {
      // Create new address
      final newAddress = DeliveryAddress(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        name: _name,
        phone: _phone,
        address: _address,
        city: _city,
        district: _district,
        postalCode: _postalCode,
        latitude: _latitude,
        longitude: _longitude,
        isDefault: _isDefault,
      );

      await deliveryProvider.addAddress(newAddress);
    } else {
      // Update existing address
      final updatedAddress = DeliveryAddress(
        id: widget.address!.id,
        name: _name,
        phone: _phone,
        address: _address,
        city: _city,
        district: _district,
        postalCode: _postalCode,
        latitude: _latitude,
        longitude: _longitude,
        isDefault: _isDefault,
      );

      await deliveryProvider.updateAddress(updatedAddress);
    }

    if (mounted) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.address == null
                ? 'Address added successfully'
                : 'Address updated successfully',
          ),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.address == null ? 'Add Address' : 'Edit Address'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFFFF87B2),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          children: [
            TextFormField(
              initialValue: _name,
              decoration: const InputDecoration(
                labelText: 'Address Name (e.g. Home, Office)',
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 16,
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter a name for this address';
                }
                return null;
              },
              onSaved: (value) {
                _name = value!.trim();
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              initialValue: _phone,
              decoration: const InputDecoration(
                labelText: 'Phone Number',
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 16,
                ),
              ),
              keyboardType: TextInputType.phone,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter a phone number';
                }
                return null;
              },
              onSaved: (value) {
                _phone = value!.trim();
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              initialValue: _address,
              decoration: const InputDecoration(
                labelText: 'Street Address',
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 16,
                ),
              ),
              maxLines: 2,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your street address';
                }
                return null;
              },
              onSaved: (value) {
                _address = value!.trim();
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              initialValue: _district,
              decoration: const InputDecoration(
                labelText: 'District/Area',
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 16,
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your district/area';
                }
                return null;
              },
              onSaved: (value) {
                _district = value!.trim();
              },
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    initialValue: _city,
                    decoration: const InputDecoration(
                      labelText: 'City',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your city';
                      }
                      return null;
                    },
                    onSaved: (value) {
                      _city = value!.trim();
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    initialValue: _postalCode,
                    decoration: const InputDecoration(
                      labelText: 'Postal Code',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                    ),
                    keyboardType: TextInputType.number,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter postal code';
                      }
                      return null;
                    },
                    onSaved: (value) {
                      _postalCode = value!.trim();
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            SwitchListTile(
              title: const Text('Set as Default Address'),
              value: _isDefault,
              activeColor: const Color(0xFFFF87B2),
              contentPadding: const EdgeInsets.symmetric(horizontal: 0),
              onChanged: (value) {
                setState(() {
                  _isDefault = value;
                });
              },
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: _saveAddress,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFFF87B2),
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: Text(
                widget.address == null ? 'Add Address' : 'Save Changes',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
