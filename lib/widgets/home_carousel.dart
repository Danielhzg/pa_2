import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/api_service.dart';

class HomeCarousel extends StatefulWidget {
  const HomeCarousel({super.key});

  @override
  State<HomeCarousel> createState() => _HomeCarouselState();
}

class _HomeCarouselState extends State<HomeCarousel> {
  final ApiService _apiService = ApiService();
  List<dynamic> carouselItems = [];
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchCarousels();
  }

  Future<void> _fetchCarousels() async {
    try {
      final data = await _apiService.fetchCarousels();
      setState(() {
        carouselItems = data;
        isLoading = false;
      });
      print('Fetched Carousel Items: $carouselItems'); // Log fetched items
    } catch (e) {
      setState(() {
        isLoading = false;
      });
      print('Error fetching carousels: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (carouselItems.isEmpty) {
      return const Center(child: Text('No carousel items available'));
    }

    return SizedBox(
      height: 200,
      child: PageView.builder(
        itemCount: carouselItems.length,
        itemBuilder: (context, index) {
          final item = carouselItems[index];
          return Column(
            children: [
              CachedNetworkImage(
                imageUrl:
                    'http://your-laravel-api-url/storage/${item['image']}',
                placeholder: (context, url) =>
                    const CircularProgressIndicator(),
                errorWidget: (context, url, error) => const Icon(Icons.error),
                height: 150,
                fit: BoxFit.cover,
              ),
              Text(item['title'] ?? 'No Title'),
            ],
          );
        },
      ),
    );
  }
}
