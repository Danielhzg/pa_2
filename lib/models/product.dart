class Product {
  final int id;
  final String name;
  final String description;
  final int price;
  final String imageUrl;
  final String category;
  final double rating;
  final bool isFeatured;
  final bool isOnSale;
  final int discount;
  final List<String>? additionalImages;
  final Map<String, dynamic>? specifications;
  final bool inStock;
  final int stockCount;
  final List<String>? colors;
  final List<String>? sizes;
  final List<String>? occasions;
  final List<String>? flowers;
  final String? deliveryInfo;
  final String? careInstructions;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.imageUrl,
    required this.category,
    this.rating = 0.0,
    this.isFeatured = false,
    this.isOnSale = false,
    this.discount = 0,
    this.additionalImages,
    this.specifications,
    this.inStock = true,
    this.stockCount = 10,
    this.colors,
    this.sizes,
    this.occasions,
    this.flowers,
    this.deliveryInfo,
    this.careInstructions,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      price: json['price'],
      imageUrl: json['image_url'],
      category: json['category'],
      rating: json['rating'] != null ? json['rating'].toDouble() : 0.0,
      isFeatured: json['is_featured'] ?? false,
      isOnSale: json['is_on_sale'] ?? false,
      discount: json['discount'] ?? 0,
      additionalImages: json['additional_images'] != null
          ? List<String>.from(json['additional_images'])
          : null,
      specifications: json['specifications'],
      inStock: json['in_stock'] ?? true,
      stockCount: json['stock_count'] ?? 10,
      colors: json['colors'] != null ? List<String>.from(json['colors']) : null,
      sizes: json['sizes'] != null ? List<String>.from(json['sizes']) : null,
      occasions: json['occasions'] != null
          ? List<String>.from(json['occasions'])
          : null,
      flowers:
          json['flowers'] != null ? List<String>.from(json['flowers']) : null,
      deliveryInfo: json['delivery_info'],
      careInstructions: json['care_instructions'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'image_url': imageUrl,
      'category': category,
      'rating': rating,
      'is_featured': isFeatured,
      'is_on_sale': isOnSale,
      'discount': discount,
      'additional_images': additionalImages,
      'specifications': specifications,
      'in_stock': inStock,
      'stock_count': stockCount,
      'colors': colors,
      'sizes': sizes,
      'occasions': occasions,
      'flowers': flowers,
      'delivery_info': deliveryInfo,
      'care_instructions': careInstructions,
    };
  }

  // Calculate the final price after discount
  int get finalPrice {
    if (isOnSale && discount > 0) {
      return (price * (100 - discount) / 100).toInt();
    }
    return price;
  }

  // Format price to currency display
  String get formattedPrice {
    return 'Rp${price.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')}';
  }

  // Format final price to currency display
  String get formattedFinalPrice {
    return 'Rp${finalPrice.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')}';
  }

  // Add fromMap method to maintain compatibility with database_helper.dart
  factory Product.fromMap(Map<String, dynamic> map) {
    return Product.fromJson(map);
  }
}
