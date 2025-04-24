/// Helper class untuk membangun URL gambar dengan benar
class ImageUrlHelper {
  /// Base URL API untuk gambar (bisa disesuaikan dengan lingkungan)
  static const String baseImageUrl =
      'http://10.0.2.2:8000/storage/'; // Emulator Android
  static const String alternateBaseUrl =
      'http://192.168.1.8:8000/storage/'; // Untuk perangkat fisik

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

    // Jika sudah URL lengkap, gunakan langsung
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      print('Already a full URL: $imagePath');
      return imagePath;
    }

    // Bersihkan path dari prefiks yang tidak perlu
    if (imagePath.startsWith('/storage/')) {
      imagePath = imagePath.substring(9); // Hapus '/storage/'
    } else if (imagePath.startsWith('storage/')) {
      imagePath = imagePath.substring(8); // Hapus 'storage/'
    } else if (imagePath.startsWith('/')) {
      imagePath = imagePath.substring(1); // Hapus awalan '/' saja
    }

    // Coba dengan baseImageUrl terlebih dahulu (untuk emulator)
    String fullUrl = '$baseImageUrl$imagePath';

    // Log URL final untuk debugging
    print('Final image URL: $fullUrl');

    // Menyimpan URL alternatif jika dibutuhkan
    String alternateUrl = '$alternateBaseUrl$imagePath';
    print('Alternate image URL (if needed): $alternateUrl');

    return fullUrl;
  }

  /// Metode alternatif untuk perangkat fisik
  static String buildAlternateImageUrl(String imagePath) {
    // Sama seperti buildImageUrl tapi menggunakan alternateBaseUrl
    if (imagePath.isEmpty) {
      return placeholderUrl;
    }

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

    return '$alternateBaseUrl$imagePath';
  }
}
