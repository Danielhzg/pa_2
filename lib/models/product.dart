class Product {
  final int id;
  final String name;
  final String description;
  final double price;
  final String imageUrl;
  final String categoryName; // Add categoryName field
  final int categoryId; // Add categoryId field
  final double rating;
  final bool isFeatured;
  final bool isOnSale;
  final int discount;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.imageUrl,
    required this.categoryName, // Ensure categoryName is required
    required this.categoryId, // Ensure categoryId is required
    required this.rating,
    required this.isFeatured,
    required this.isOnSale,
    required this.discount,
  });

  // Add a factory constructor for JSON deserialization
  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      price: json['price'].toDouble(),
      imageUrl: json['imageUrl'],
      categoryName: json['categoryName'],
      categoryId: json['categoryId'],
      rating: json['rating'].toDouble(),
      isFeatured: json['isFeatured'],
      isOnSale: json['isOnSale'],
      discount: json['discount'],
    );
  }
}
