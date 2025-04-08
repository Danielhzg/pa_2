class Product {
  final int id;
  final String name;
  final String? description;
  final double price;
  final String? image;
  final int stock;
  final int? categoryId;
  final Map<String, dynamic>? category;
  final String? createdAt;
  final String? updatedAt;

  Product({
    required this.id,
    required this.name,
    this.description,
    required this.price,
    this.image,
    required this.stock,
    this.categoryId,
    this.category,
    this.createdAt,
    this.updatedAt,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      price: double.parse(json['price'].toString()),
      image: json['image'],
      stock: json['stock'],
      categoryId: json['category_id'],
      category: json['category'] != null
          ? Map<String, dynamic>.from(json['category'])
          : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'image': image,
      'stock': stock,
      'category_id': categoryId,
      'category': category,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
