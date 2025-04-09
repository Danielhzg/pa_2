class Product {
  final int id;
  final String name;
  final String description;
  final double price;
  final int stock;
  final String imageUrl;
  final int categoryId;
  final String category;
  final double discount;
  final double rating;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.stock,
    required this.imageUrl,
    required this.categoryId,
    required this.category,
    required this.discount,
    required this.rating,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      description: json['description'] ?? '',
      price: json['price'].toDouble(),
      stock: json['stock'],
      imageUrl: json['image'] != null
          ? 'http://your-backend-url/storage/${json['image']}'
          : '',
      categoryId: json['category_id'] ?? 0,
      category: json['category']['name'] ?? 'Unknown',
      discount: json['discount']?.toDouble() ?? 0.0,
      rating: json['rating']?.toDouble() ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'stock': stock,
      'image': imageUrl,
      'category_id': categoryId,
      'category': category,
      'discount': discount,
      'rating': rating,
    };
  }

  factory Product.fromMap(Map<String, dynamic> map) {
    return Product(
      id: map['id'],
      name: map['name'],
      description: map['description'] ?? '',
      price: map['price'].toDouble(),
      stock: map['stock'],
      imageUrl: map['image'] ?? '',
      categoryId: map['category_id'] ?? 0,
      category: map['category'] ?? 'Unknown',
      discount: map['discount']?.toDouble() ?? 0.0,
      rating: map['rating']?.toDouble() ?? 0.0,
    );
  }
}
