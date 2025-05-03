import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/carousel.dart';
import '../utils/constants.dart';

class CarouselService {
  final String baseUrl = ApiConstants.baseUrl;

  /// Fetch all active carousels
  Future<List<Carousel>> getCarousels() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/v1/carousels'),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);

        if (responseData['success'] == true && responseData['data'] != null) {
          List<dynamic> carouselData = responseData['data'];
          List<Carousel> carousels =
              carouselData.map((item) => Carousel.fromJson(item)).toList();
          return carousels;
        } else {
          throw Exception(
              'Failed to load carousels: ${responseData['message']}');
        }
      } else {
        throw Exception('Failed to load carousels: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching carousels: $e');
    }
  }

  /// Fetch a specific carousel by ID
  Future<Carousel> getCarouselById(int id) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/v1/carousels/$id'),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);

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
