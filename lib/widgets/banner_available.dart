import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/api_service.dart';
import '../utils/image_url_helper.dart'; // Tambahkan import untuk ImageUrlHelper

class BannerAvailable extends StatefulWidget {
  const BannerAvailable({super.key});

  @override
  State<BannerAvailable> createState() => _BannerAvailableState();
}

class _BannerAvailableState extends State<BannerAvailable> {
  final ApiService _apiService = ApiService();
  List<dynamic> banners = [];
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchBanners();
  }

  Future<void> _fetchBanners() async {
    try {
      final data = await _apiService.fetchCarousels();
      setState(() {
        banners = data;
        isLoading = false;
      });
      print('Fetched Banners: $banners'); // Log fetched banners
    } catch (e) {
      setState(() {
        isLoading = false;
      });
      print('Error fetching banners: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (banners.isEmpty) {
      return const Center(child: Text('No banners available'));
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.all(8.0),
          child: Text(
            'Banner Available',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
        SizedBox(
          height: 200,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: banners.length,
            itemBuilder: (context, index) {
              final banner = banners[index];
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0),
                child: Column(
                  children: [
                    CachedNetworkImage(
                      imageUrl:
                          ImageUrlHelper.buildImageUrl(banner['image'] ?? ''),
                      placeholder: (context, url) =>
                          const CircularProgressIndicator(),
                      errorWidget: (context, url, error) =>
                          const Icon(Icons.error),
                      height: 150,
                      width: 300,
                      fit: BoxFit.cover,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      banner['title'] ?? 'No Title',
                      style: const TextStyle(fontSize: 14),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
