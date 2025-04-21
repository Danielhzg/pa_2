import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:carousel_slider/carousel_slider.dart'; // Import untuk CarouselSlider
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
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    _fetchCarousels();
  }

  Future<void> _fetchCarousels() async {
    try {
      final data = await _apiService.fetchCarousels();
      print('Fetched ${data.length} carousel items');

      // Log setiap item carousel untuk debugging
      for (var item in data) {
        print(
            'Carousel item: ${item['id']} - ${item['title']} - Image Path: ${item['image']}');
      }

      setState(() {
        carouselItems = data;
        isLoading = false;
      });
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
      return Container(
        height: 200,
        alignment: Alignment.center,
        child: const Text('Tidak ada item carousel tersedia'),
      );
    }

    return Column(
      children: [
        CarouselSlider(
          options: CarouselOptions(
            height: 180,
            viewportFraction: 1.0,
            enlargeCenterPage: false,
            autoPlay: true,
            autoPlayInterval: const Duration(seconds: 3),
            onPageChanged: (index, reason) {
              setState(() {
                _currentIndex = index;
              });
            },
          ),
          items: carouselItems.map((item) {
            // Debugging untuk item carousel
            print(
                'Processing carousel item in widget: ${item['id']} - ${item['title']}');

            // Ambil path gambar dan buat URL
            var imagePath = item['image']?.toString() ?? '';
            String imageUrl;

            // Cek apakah ini adalah carousel promo 10%
            bool isPromo10 =
                (item['title']?.toString().contains('10%') == true ||
                    item['description']?.toString().contains('10%') == true);

            if (isPromo10) {
              print('This is a promo 10% carousel, special handling applied');
            }

         
            if (imagePath.startsWith('http')) {
              // Jika sudah URL lengkap
              imageUrl = imagePath;
            } else if (imagePath.isEmpty) {
              // Jika path kosong, gunakan placeholder
              imageUrl =
                  'https://via.placeholder.com/800x400?text=Image+Not+Available';
              print('Empty image path for carousel ${item['id']}');
            } else {
             
              if (imagePath.startsWith('/')) {
                imagePath = imagePath.substring(1);
              }
              imageUrl = 'http://10.0.2.2:8000/storage/$imagePath';
            }

            print('Final carousel image URL: $imageUrl');

            return Builder(
              builder: (BuildContext context) {
                return Container(
                  width: MediaQuery.of(context).size.width,
                  margin: const EdgeInsets.symmetric(horizontal: 2),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Stack(
                      children: [
                        // Gambar carousel
                        CachedNetworkImage(
                          imageUrl: imageUrl,
                          fit: BoxFit.cover,
                          width: double.infinity,
                          height: double.infinity,
                          placeholder: (context, url) => Container(
                            color: Colors.grey[200],
                            child: const Center(
                              child: CircularProgressIndicator(),
                            ),
                          ),
                          errorWidget: (context, url, error) {
                            print('Error loading image: $url - $error');
                            return Container(
                              color: Colors.grey[300],
                              child: const Center(
                                child: Icon(Icons.error, size: 40),
                              ),
                            );
                          },
                        ),
                        // Overlay untuk teks
                        Positioned(
                          bottom: 0,
                          left: 0,
                          right: 0,
                          child: Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.bottomCenter,
                                end: Alignment.topCenter,
                                colors: [
                                  Colors.black.withOpacity(0.7),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                            padding: const EdgeInsets.symmetric(
                                vertical: 10, horizontal: 15),
                            child: Text(
                              item['title'] ?? 'No Title',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            );
          }).toList(),
        ),
        const SizedBox(height: 10),
        // Indikator carousel
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: carouselItems.asMap().entries.map((entry) {
            return Container(
              width: 8.0,
              height: 8.0,
              margin: const EdgeInsets.symmetric(horizontal: 4.0),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _currentIndex == entry.key
                    ? const Color(0xFFFF87B2)
                    : Colors.grey.withOpacity(0.5),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
