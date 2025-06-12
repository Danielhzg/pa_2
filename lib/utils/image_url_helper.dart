import '../utils/constants.dart';

/// Helper class untuk membangun URL gambar dengan benar
class ImageUrlHelper {
  /// Base URL API untuk gambar (bisa disesuaikan dengan lingkungan)
  static final String baseImageUrl = '${ApiConstants.getBaseUrl()}/storage/';
  static const String ngrokBaseUrl =
      'https://dec8-114-122-41-11.ngrok-free.app/storage/'; // Primary ngrok URL
  static const String alternateBaseUrl =
      'http://192.168.1.5:8000/storage/'; // Untuk perangkat fisik
  static const String localBaseUrl =
      'http://localhost:8000/storage/'; // Untuk pengembangan lokal
  static const String emulatorBaseUrl =
      'http://10.0.2.2:8000/storage/'; // Khusus untuk emulator
  static const String networkUrl =
      'http://192.168.0.106:8000/storage/'; // URL jaringan alternatif

  // URL khusus untuk carousel
  static const String specificCarouselUrl =
      'https://dec8-114-122-41-11.ngrok-free.app/storage/carousels/'; 

  /// Placeholder URL untuk gambar yang tidak tersedia
  static const String placeholderUrl =
      'https://via.placeholder.com/800x400?text=Image+Not+Available';

  /// Membangun URL gambar lengkap dari path relatif
  static String buildImageUrl(String imagePath) {
    // Logging untuk debugging
    print('Building URL from image path: $imagePath');

    // Jika path kosong, gunakan placeholder
    if (imagePath.isEmpty) {
      print('Empty image path, using placeholder');
      return placeholderUrl;
    }

    // Khusus untuk carousel, gunakan URL spesifik jika filename saja diberikan
    if (!imagePath.contains('/') &&
        !imagePath.startsWith('http') &&
        !imagePath.contains('storage/')) {
      String carouselUrl = '$specificCarouselUrl$imagePath';
      print('Direct carousel image: $carouselUrl');
      return carouselUrl;
    }

    // Jika sudah URL lengkap, validasi dan gunakan langsung jika valid
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      // Verifikasi URL valid dengan regex
      if (_isValidImageUrl(imagePath)) {
        print('Valid full URL: $imagePath');
        return imagePath;
      } else {
        print('Full URL format is invalid: $imagePath');
        // Coba ekstrak bagian path dari URL yang tidak valid
        String extractedPath = _extractPathFromInvalidUrl(imagePath);
        if (extractedPath.isNotEmpty) {
          String fullUrl = '$emulatorBaseUrl$extractedPath';
          print('Extracted path and rebuilt URL: $fullUrl');
          return fullUrl;
        }
      }
    }

    // Bersihkan path dari prefiks yang tidak perlu
    String cleanedPath = imagePath;
    if (cleanedPath.startsWith('/storage/')) {
      cleanedPath = cleanedPath.substring(9); // Hapus '/storage/'
    } else if (cleanedPath.startsWith('storage/')) {
      cleanedPath = cleanedPath.substring(8); // Hapus 'storage/'
    } else if (cleanedPath.startsWith('/')) {
      cleanedPath = cleanedPath.substring(1); // Hapus awalan '/' saja
    } else if (cleanedPath.startsWith('public/')) {
      cleanedPath = cleanedPath.substring(7); // Hapus 'public/'
    } else if (cleanedPath.startsWith('/public/')) {
      cleanedPath = cleanedPath.substring(8); // Hapus '/public/'
    }

    // Untuk path carousels, pastikan menggunakan format yang benar
    if (cleanedPath.contains('carousels/') ||
        cleanedPath.startsWith('carousel_')) {
      print('Detected carousel image: $cleanedPath');

      // Jika hanya nama file, tambahkan path carousels/
      if (!cleanedPath.contains('/') && !cleanedPath.contains('carousels/')) {
        cleanedPath = 'carousels/$cleanedPath';
        print('Added carousels/ prefix: $cleanedPath');
      }

      // Coba semua URL base yang mungkin untuk carousel
      List<String> possibleUrls = [
        '$ngrokBaseUrl$cleanedPath',
        '$baseImageUrl$cleanedPath',
        '$emulatorBaseUrl$cleanedPath',
        '$alternateBaseUrl$cleanedPath',
        '$localBaseUrl$cleanedPath',
        '$networkUrl$cleanedPath',
        'http://10.0.2.2:8000/storage/carousels/$cleanedPath',
        'http://localhost:8000/storage/carousels/$cleanedPath',
      ];

      print('Trying multiple URLs for carousel: $possibleUrls');
      return possibleUrls[0]; // Gunakan URL pertama sebagai default
    }

    // Coba dengan baseImageUrl terlebih dahulu (untuk emulator)
    String fullUrl = '$emulatorBaseUrl$cleanedPath';

    // Log URL final untuk debugging
    print('Final URL used: $fullUrl');

    return fullUrl;
  }

  /// Helper untuk ekstrak path dari URL yang mungkin tidak valid
  static String _extractPathFromInvalidUrl(String url) {
    // Coba ekstrak bagian setelah /storage/ jika ada
    if (url.contains('/storage/')) {
      int index = url.indexOf('/storage/');
      if (index >= 0) {
        return url.substring(index + 9); // +9 untuk lewati '/storage/'
      }
    }
    return '';
  }

  /// Validasi URL gambar dengan regex sederhana
  static bool _isValidImageUrl(String url) {
    // Regex untuk validasi URL
    RegExp urlRegex = RegExp(
      r'^(http|https)://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(:[0-9]+)?(/.*)?$',
      caseSensitive: false,
    );

    return urlRegex.hasMatch(url);
  }

  /// Metode alternatif untuk perangkat fisik
  static String buildAlternateImageUrl(String imagePath) {
    // Jika path kosong, gunakan placeholder
    if (imagePath.isEmpty) {
      return placeholderUrl;
    }

    // Jika sudah URL lengkap, gunakan langsung
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      return imagePath;
    }

    // Bersihkan path
    if (imagePath.startsWith('/storage/')) {
      imagePath = imagePath.substring(9);
    } else if (imagePath.startsWith('storage/')) {
      imagePath = imagePath.substring(8);
    } else if (imagePath.startsWith('/')) {
      imagePath = imagePath.substring(1);
    }

    // Gunakan URL alternatif untuk perangkat fisik
    print('Using alternate base URL: $alternateBaseUrl for image: $imagePath');
    return '$alternateBaseUrl$imagePath';
  }

  /// Metode untuk mencoba beberapa URL base dan mengembalikan yang pertama berhasil
  static List<String> getAllPossibleImageUrls(String imagePath) {
    if (imagePath.isEmpty) {
      return [placeholderUrl];
    }

    // Khusus untuk carousel, jika hanya nama file diberikan
    if (!imagePath.contains('/') &&
        !imagePath.startsWith('http') &&
        !imagePath.contains('storage/')) {
      return [
        '$specificCarouselUrl$imagePath',
        '$emulatorBaseUrl/carousels/$imagePath',
        '$baseImageUrl/carousels/$imagePath',
        '$alternateBaseUrl/carousels/$imagePath',
        '$localBaseUrl/carousels/$imagePath',
        '$networkUrl/carousels/$imagePath',
        placeholderUrl
      ];
    }

    // Jika sudah URL lengkap, tetap sertakan URL alternatif sebagai backup
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      // Ekstrak path dari URL lengkap jika berasal dari server kita
      String path = imagePath;

      // Jika URL dari domain kita, ekstrak path-nya untuk URL alternatif
      if (imagePath.contains('/storage/')) {
        int index = imagePath.indexOf('/storage/');
        if (index >= 0) {
          path = imagePath.substring(index + 9); // +9 untuk lewati '/storage/'
        }
      }

      return [
        imagePath,
        '$emulatorBaseUrl$path',
        '$alternateBaseUrl$path',
        '$localBaseUrl$path',
        '$networkUrl$path',
        placeholderUrl
      ];
    }

    // Bersihkan path
    String cleanPath = imagePath;
    if (cleanPath.startsWith('/storage/')) {
      cleanPath = cleanPath.substring(9);
    } else if (cleanPath.startsWith('storage/')) {
      cleanPath = cleanPath.substring(8);
    } else if (cleanPath.startsWith('/')) {
      cleanPath = cleanPath.substring(1);
    } else if (cleanPath.startsWith('public/')) {
      cleanPath = cleanPath.substring(7);
    } else if (cleanPath.startsWith('/public/')) {
      cleanPath = cleanPath.substring(8);
    }

    // Tambahkan carousels/ prefix untuk path yang mungkin carousel
    if (cleanPath.contains('carousel') && !cleanPath.contains('carousels/')) {
      String carouselPath = 'carousels/$cleanPath';
      print('Added carousels/ prefix: $carouselPath');

      return [
        '$emulatorBaseUrl$carouselPath',
        '$baseImageUrl$carouselPath',
        '$alternateBaseUrl$carouselPath',
        '$localBaseUrl$carouselPath',
        '$networkUrl$carouselPath',
        '$emulatorBaseUrl$cleanPath',
        '$baseImageUrl$cleanPath',
        '$alternateBaseUrl$cleanPath',
        '$localBaseUrl$cleanPath',
        '$networkUrl$cleanPath',
        placeholderUrl
      ];
    }

    print('All possible URLs for image: $cleanPath');
    return [
      '$emulatorBaseUrl$cleanPath',
      '$baseImageUrl$cleanPath',
      '$alternateBaseUrl$cleanPath',
      '$localBaseUrl$cleanPath',
      '$networkUrl$cleanPath',
      placeholderUrl
    ];
  }
}
