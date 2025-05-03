class ApiConstants {
  // Base URL for the API
  static const String baseUrl =
      "https://your-server-url.com"; // Replace with your actual API URL

  // API endpoints
  static const String login = "/api/v1/login";
  static const String register = "/api/v1/register";
  static const String products = "/api/v1/products";
  static const String categories = "/api/v1/categories";
  static const String carousels = "/api/v1/carousels";
  static const String orders = "/api/v1/orders";
  static const String profile = "/api/v1/user";
}

class AppConstants {
  // App name
  static const String appName = "Bloom Bouquet";

  // Shared preference keys
  static const String authToken = "auth_token";
  static const String userId = "user_id";
  static const String userEmail = "user_email";
  static const String userName = "user_name";

  // Other constants
  static const int splashDuration = 2; // in seconds
  static const int pageSizeDefault = 10;
}
