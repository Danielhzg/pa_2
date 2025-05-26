import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/carousel.dart';
import '../utils/constants.dart';

class CarouselService {
  String get baseUrl => ApiConstants.getBaseUrl();

  /// Fetch all active carousels
  Future<List<Carousel>> getCarousels() async {
    try {
      print('Fetching carousels from: $baseUrl/api/v1/carousels');

      final response = await http.get(
        Uri.parse('$baseUrl/api/v1/carousels'),
        headers: {'Content-Type': 'application/json'},
      );

      print('Carousel API response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        // Log raw response first for debugging
        print('Carousel API raw response: ${response.body}');

        try {
          final Map<String, dynamic> responseData = json.decode(response.body);

          // Debug the full response structure
          print(
              'Carousel API response structure: ${responseData.keys.toList()}');

          if (responseData['success'] == true && responseData['data'] != null) {
            List<dynamic> carouselData = responseData['data'];
            print('Found ${carouselData.length} carousels in response');

            // Log the first carousel data to see its structure
            if (carouselData.isNotEmpty) {
              print('First carousel data: ${carouselData.first}');

              // Check specifically for image-related fields
              Map<String, dynamic> firstItem = carouselData.first;
              print('Image fields in first carousel:');
              print('- image_url: ${firstItem['image_url']}');
              print('- full_image_url: ${firstItem['full_image_url']}');
            }

            List<Carousel> carousels = [];

            // Process each carousel with error handling
            for (var item in carouselData) {
              try {
                Carousel carousel = Carousel.fromJson(item);
                carousels.add(carousel);
                print(
                    'Processed carousel ${carousel.id}: Image URL = ${carousel.imageUrl}');
              } catch (e) {
                print('Error processing carousel: $e');
                print('Problematic carousel data: $item');
              }
            }

            return carousels;
          } else {
            print(
                'API returned success=false or no data: ${responseData['message'] ?? "No message provided"}');

            // Try to handle responses without success field (direct array)
            if (responseData is List) {
              print('Response is a direct array, trying to parse it');
              List<dynamic> directData = responseData as List<dynamic>;

              List<Carousel> carousels = [];
              for (var item in directData) {
                try {
                  Carousel carousel = Carousel.fromJson(item);
                  carousels.add(carousel);
                } catch (e) {
                  print('Error processing direct carousel: $e');
                }
              }

              if (carousels.isNotEmpty) {
                return carousels;
              }
            }

            // Return empty list instead of throwing exception
            return [];
          }
        } catch (e) {
          print('Error parsing carousel response: $e');
          print('Response body was: ${response.body}');
          return [];
        }
      } else {
        print(
            'Failed to load carousels - HTTP ${response.statusCode}: ${response.body}');
        // Try a direct API call as fallback if server error
        if (response.statusCode >= 500) {
          try {
            print(
                'Trying alternate direct API URL for carousels due to server error');
            final altResponse = await http.get(
              Uri.parse('${ApiConstants.localUrl}/api/v1/carousels'),
              headers: {'Content-Type': 'application/json'},
            );

            if (altResponse.statusCode == 200) {
              final Map<String, dynamic> altData =
                  json.decode(altResponse.body);
              if (altData['success'] == true && altData['data'] != null) {
                List<dynamic> carouselData = altData['data'];
                List<Carousel> carousels = carouselData
                    .map((item) => Carousel.fromJson(item))
                    .toList();
                return carousels;
              }
            }
          } catch (fallbackError) {
            print('Fallback attempt also failed: $fallbackError');
          }
        }
        // Return empty list instead of throwing exception
        return [];
      }
    } catch (e) {
      print('Error fetching carousels: $e');
      // Return empty list instead of throwing exception
      return [];
    }
  }

  /// Fetch a specific carousel by ID
  Future<Carousel> getCarouselById(int id) async {
    try {
      print('Fetching carousel by ID $id from: $baseUrl/api/v1/carousels/$id');

      final response = await http.get(
        Uri.parse('$baseUrl/api/v1/carousels/$id'),
        headers: {'Content-Type': 'application/json'},
      );

      print('Carousel API response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        print('Carousel API response: ${response.body}');

        if (responseData['success'] == true && responseData['data'] != null) {
          return Carousel.fromJson(responseData['data']);
        } else {
          throw Exception(
              'Failed to load carousel: ${responseData['message']}');
        }
      } else {
        throw Exception('Failed to load carousel: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching carousel: $e');
    }
  }
}
