class DeliveryAddress {
  final String id;
  final String name;
  final String phone;
  final String address;
  final String city;
  final String district;
  final String postalCode;
  final double latitude;
  final double longitude;
  final bool isDefault;

  DeliveryAddress({
    required this.id,
    required this.name,
    required this.phone,
    required this.address,
    required this.city,
    required this.district,
    required this.postalCode,
    required this.latitude,
    required this.longitude,
    this.isDefault = false,
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'address': address,
      'city': city,
      'district': district,
      'postalCode': postalCode,
      'latitude': latitude,
      'longitude': longitude,
      'isDefault': isDefault,
    };
  }

  factory DeliveryAddress.fromJson(Map<String, dynamic> json) {
    return DeliveryAddress(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      address: json['address'],
      city: json['city'],
      district: json['district'],
      postalCode: json['postalCode'],
      latitude: json['latitude'],
      longitude: json['longitude'],
      isDefault: json['isDefault'] ?? false,
    );
  }

  String get fullAddress {
    return '$address, $district, $city, $postalCode';
  }

  // Create a copy with updated values
  DeliveryAddress copyWith({
    String? name,
    String? phone,
    String? address,
    String? city,
    String? district,
    String? postalCode,
    double? latitude,
    double? longitude,
    bool? isDefault,
  }) {
    return DeliveryAddress(
      id: id,
      name: name ?? this.name,
      phone: phone ?? this.phone,
      address: address ?? this.address,
      city: city ?? this.city,
      district: district ?? this.district,
      postalCode: postalCode ?? this.postalCode,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      isDefault: isDefault ?? this.isDefault,
    );
  }
}
