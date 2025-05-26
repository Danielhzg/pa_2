import '../utils/image_url_helper.dart';
import '../utils/constants.dart';

class Carousel {
  final int id;
  final String title;
  final String? description;
  final String imageUrl;
  final bool isActive;
  final DateTime createdAt;
  final DateTime updatedAt;

  Carousel({
    required this.id,
    required this.title,
    this.description,
    required this.imageUrl,
    required this.isActive,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Carousel.fromJson(Map<String, dynamic> json) {
    String imageUrl = '';

    // Log the entire JSON for debugging
    print('Carousel JSON: $json');

    // Handle different image URL formats, prioritizing full_image_url
    if (json.containsKey('full_image_url') && json['full_image_url'] != null && json['full_image_url'].toString().isNotEmpty) {
      imageUrl = json['full_image_url'].toString();
      print('Using full_image_url: $imageUrl');
    } else if (json.containsKey('image_url') && json['image_url'] != null && json['image_url'].toString().isNotEmpty) {
      // If full_image_url is null, try to construct it from image_url
      String relativeImageUrl = json['image_url'].toString();
      imageUrl = '${ApiConstants.getBaseUrl()}/storage/$relativeImageUrl';
      print('Constructed from image_url: $imageUrl');
    } else if (json.containsKey('imageUrl') && json['imageUrl'] != null && json['imageUrl'].toString().isNotEmpty) {
      imageUrl = json['imageUrl'].toString();
      print('Using imageUrl: $imageUrl');
    } else if (json.containsKey('image') && json['image'] != null && json['image'].toString().isNotEmpty) {
      // Handle direct 'image' field - common in API responses
      String imagePath = json['image'].toString();
      
      // Check if it's already a full URL
      if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
        imageUrl = imagePath;
      } else {
        // Ensure path is properly formatted for storage URL
        if (!imagePath.contains('storage/') && !imagePath.contains('/storage/')) {
          // Add carousels/ prefix if it's just a filename without path
          if (!imagePath.contains('/')) {
            imagePath = 'carousels/$imagePath';
          }
        }
        imageUrl = '${ApiConstants.getBaseUrl()}/storage/$imagePath';
      }
      print('Using image field: $imagePath -> $imageUrl');
    }

    // If still empty, try another approach with direct path check
    if (imageUrl.isEmpty &&
        json.containsKey('image_url') &&
        json['image_url'] != null) {
      String path = json['image_url'].toString();
      // Make sure to properly prefix the storage path
      imageUrl = '${ApiConstants.getBaseUrl()}/storage/$path';
      print('Fallback to constructed URL: $imageUrl');
    }

    // Process the image URL even if it's already a full URL
    // This ensures we have a properly formatted URL that will work on the device
    if (imageUrl.isNotEmpty) {
      // Log the original URL for debugging
      print('Original imageUrl: $imageUrl');
      
      // Special handling for carousel images
      if (imageUrl.contains('carousels/') || json['title']?.toString().toLowerCase().contains('promo') == true) {
        print('Detected carousel image, using special handling');
        // Get all possible URLs and use the first one
        List<String> urls = ImageUrlHelper.getAllPossibleImageUrls(imageUrl);
        imageUrl = urls.first;
      } else {
        imageUrl = ImageUrlHelper.buildImageUrl(imageUrl);
      }
    } else {
      // If we still have no URL, use a placeholder
      print('No image URL found, using placeholder');
      imageUrl = ImageUrlHelper.placeholderUrl;
    }

    return Carousel(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      imageUrl: imageUrl,
      isActive: json['is_active'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'image_url': imageUrl,
      'is_active': isActive,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }
}
