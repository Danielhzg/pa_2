class ApiConstants {
  // Base URL for the API - Updated to ngrok URL
  static const String baseUrl = "https://dec8-114-122-41-11.ngrok-free.app";

  static const String localUrl = "http://localhost:8000";
  static const String networkUrl = "http://192.168.1.5:8000";
  static const String emulatorUrl = "http://10.0.2.2:8000";

  static String getBaseUrl() {
    return baseUrl;
  }

  // API endpoints
  static const String login = "/api/v1/login";
  static const String register = "/api/v1/register";
  static const String products = "/api/v1/products";
  static const String categories = "/api/v1/categories";
  static const String carousels = "/api/v1/carousels";
  static const String orders = "/api/v1/orders";
  static const String ordersCreate = "/api/v1/orders/create";
  static const String ordersTrack = "/api/v1/orders/track";
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
  static const int splashDuration = 2;
  static const int pageSizeDefault = 10;
}

