class User {
  final int? id;
  final String? name;
  final String? username;
  final String? full_name;
  final String? email;
  final String? phone;
  final String? address;
  final DateTime? birth_date;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final String? profile_photo;

  User({
    this.id,
    this.name,
    this.username,
    this.full_name,
    this.email,
    this.phone,
    this.address,
    this.birth_date,
    this.createdAt,
    this.updatedAt,
    this.profile_photo,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      username: json['username'],
      full_name: json['full_name'],
      email: json['email'],
      phone: json['phone'],
      address: json['address'],
      birth_date: json['birth_date'] != null
          ? DateTime.parse(json['birth_date'])
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'])
          : null,
      profile_photo: json['profile_photo'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'username': username,
      'full_name': full_name,
      'email': email,
      'phone': phone,
      'address': address,
      'birth_date': birth_date?.toIso8601String(),
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'profile_photo': profile_photo,
    };
  }
}
