import 'dart:convert';
import 'package:http/http.dart' as http;

// Simple script to test the carousel API endpoint from Dart

void main() async {
  print('Testing carousel API endpoint...');

  // API URL - Updated to use ngrok
  const apiUrl = 'https://dec8-114-122-41-11.ngrok-free.app/api/v1/carousels';
  print('API URL: $apiUrl');

  try {
    // Make the API request
    final response = await http.get(
      Uri.parse(apiUrl),
      headers: {'Content-Type': 'application/json'},
    );

    // Print status code
    print('HTTP Status Code: ${response.statusCode}');

    // Check if the request was successful
    if (response.statusCode == 200) {
      // Try to parse the response
      try {
        final data = json.decode(response.body);

        // Pretty print the response
        print('API Response:');
        print(const JsonEncoder.withIndent('  ').convert(data));

        // Check if carousel data exists
        if (data['data'] != null && data['data'] is List) {
          final carousels = data['data'] as List;
          print('\nFound ${carousels.length} carousels');

          // Print details for each carousel
          for (int i = 0; i < carousels.length; i++) {
            final carousel = carousels[i];
            print('\nCarousel #${i + 1}:');
            print('  ID: ${carousel['id'] ?? 'N/A'}');
            print('  Title: ${carousel['title'] ?? 'N/A'}');
            print('  Image URL: ${carousel['image_url'] ?? 'N/A'}');
            print('  Full Image URL: ${carousel['full_image_url'] ?? 'N/A'}');
            print('  Active: ${carousel['is_active'] == true ? 'Yes' : 'No'}');
          }
        } else {
          print('\nNo carousel data found or invalid response structure');
        }
      } catch (e) {
        print('Error decoding JSON: $e');
        print(
            'Raw response: ${response.body.substring(0, response.body.length > 1000 ? 1000 : response.body.length)}');
      }
    } else {
      print('Request failed with status: ${response.statusCode}');
      print('Response body: ${response.body}');
    }
  } catch (e) {
    print('Error making API request: $e');
  }
}
