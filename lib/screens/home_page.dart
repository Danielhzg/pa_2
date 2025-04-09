import 'package:flutter/material.dart';
import 'dart:ui';
import 'package:provider/provider.dart';
import 'package:flutter_snake_navigationbar/flutter_snake_navigationbar.dart';
import 'package:line_icons/line_icons.dart';
import '../models/product.dart';
import '../services/auth_service.dart';
import '../widgets/product_search.dart';
import 'cart_page.dart';
import 'chat_page.dart';
import 'profile_page.dart';
import 'dart:async';
import '../services/api_service.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> with TickerProviderStateMixin {
  int _selectedIndex = 0;
  String _selectedCategory = 'All';
  bool _isLoading = false;
  final List<Product> _featuredProducts = [];
  final List<Product> _newProducts = [];
  final List<Product> _trendingProducts = [];
  final List<Product> _filteredProducts = [];
  late TabController _tabController;
  final PageController _pageController = PageController(viewportFraction: 0.85);
  int _currentBannerIndex = 0;
  late String _username;

  // Colors
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color secondaryColor = Color(0xFFFFC0D9);
  static const Color accentColor = Color(0xFFFFE5EE);
  static const Color darkTextColor = Color(0xFF333333);
  static const Color lightTextColor = Color(0xFF717171);

  final List<Map<String, dynamic>> _banners = [
    {
      'title': 'Spring Collection',
      'subtitle': 'Fresh seasonal flowers',
      'discount': '20% OFF',
      'image': 'assets/images/contoh.jpg',
      'color': const Color(0xFFF8BBD0),
    },
    {
      'title': 'Wedding Special',
      'subtitle': 'Make your day perfect',
      'discount': '15% OFF',
      'image': 'assets/images/contoh.jpg',
      'color': const Color(0xFFFFCCBC),
    },
    {
      'title': 'Gift Bouquets',
      'subtitle': 'Express your feelings',
      'discount': '10% OFF',
      'image': 'assets/images/contoh.jpg',
      'color': const Color(0xFFD1C4E9),
    },
  ];

  final List<Map<String, dynamic>> _categories = [
    {'name': 'All'},
    {'name': 'Wisuda'},
    {'name': 'Makanan'},
    {'name': 'Money'},
    {'name': 'Hampers'},
  ];

  Timer? _bannerTimer;

  void _startBannerAutoScroll() {
    _bannerTimer?.cancel();
    _bannerTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      if (mounted && _pageController.hasClients) {
        final nextPage = (_currentBannerIndex + 1) % _banners.length;
        _pageController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  @override
  void initState() {
    super.initState();
    _fetchProducts();
    _loadUsername();

    // Auto-scroll banner - start after a short delay
    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted) {
        _startBannerAutoScroll();
      }
    });
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoading = true);
    try {
      final List<dynamic> rawProducts = await ApiService().fetchProducts();
      final List<Product> products = rawProducts
          .map((productJson) => Product.fromJson(productJson))
          .toList(); // Convert JSON to Product objects
      setState(() {
        _filteredProducts.clear();
        _filteredProducts.addAll(products); // Ensure type compatibility
        _isLoading = false;
      });
    } catch (e) {
      print('Error fetching products: $e');
      setState(() => _isLoading = false);
    }
  }

  void _loadUsername() {
    final authService = Provider.of<AuthService>(context, listen: false);
    setState(() {
      _username = authService.currentUser?.username ?? 'Guest';
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    _bannerTimer?.cancel();
    super.dispose();
  }

  // Temporary method to create dummy products for testing
  List<Product> _createDummyProducts(String type, int count) {
    // Use local assets instead of network images
    const String imageUrl = 'assets/images/contoh.jpg';

    List<Product> products = [];
    for (int i = 1; i <= count; i++) {
      products.add(
        Product(
          id: type == 'featured' ? i : (type == 'trending' ? i + 200 : i + 100),
          name: type == 'featured'
              ? 'Elegant Bloom $i'
              : (type == 'trending'
                  ? 'Trendy Bouquet $i'
                  : 'Fresh Arrangement $i'),
          description:
              'A stunning arrangement of premium flowers, perfect for any occasion.',
          price: type == 'featured'
              ? 299000 + (i * 50000)
              : (type == 'trending'
                  ? 349000 + (i * 30000)
                  : 199000 + (i * 40000)),
          imageUrl: imageUrl,
          categoryName: i % 7 == 0
              ? 'Box Custom'
              : i % 6 == 0
                  ? 'Hampers'
                  : i % 5 == 0
                      ? 'Money'
                      : i % 4 == 0
                          ? 'Wedding'
                          : i % 3 == 0
                              ? 'Birthday'
                              : i % 2 == 0
                                  ? 'Wisuda'
                                  : 'All',
          categoryId: i % 7, // Provide a valid categoryId
          rating: 4.0 + (i % 10) / 10,
          isFeatured: type == 'featured',
          isOnSale: i % 3 == 0,
          discount: i % 3 == 0 ? 15 : 0,
        ),
      );
    }
    return products;
  }

  Future<void> _loadProducts() async {
    setState(() => _isLoading = true);
    try {
      // In production, replace with actual API calls
      await Future.delayed(const Duration(milliseconds: 800));

      final featured = _createDummyProducts('featured', 5);
      final newArrivals = _createDummyProducts('new', 6);
      final trending = _createDummyProducts('trending', 4);

      if (mounted) {
        setState(() {
          _featuredProducts.clear();
          _featuredProducts.addAll(featured);
          _newProducts.clear();
          _newProducts.addAll(newArrivals);
          _trendingProducts.clear();
          _trendingProducts.addAll(trending);
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading products: $e');
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading products: $e')),
        );
      }
    }
  }

  Future<void> _loadProductsByCategory(String category) async {
    setState(() => _isLoading = true);
    try {
      await Future.delayed(const Duration(milliseconds: 500));

      List<Product> products = [];
      // Create different products based on categories
      if (category == 'All') {
        products = _createDummyProducts('new', 10);
      } else if (category == 'Wisuda') {
        products = _createDummyProducts('new', 6)
            .where((p) => p.categoryName == 'Wisuda')
            .toList();
      } else if (category == 'Makanan') {
        products = _createDummyProducts('new', 8)
            .where((p) =>
                p.categoryName == 'Birthday') // Using Birthday for Makanan
            .toList();
      } else if (category == 'Money') {
        products = _createDummyProducts('new', 4)
            .where((p) => p.categoryName == 'Money')
            .toList();
      } else if (category == 'Hampers') {
        products = _createDummyProducts('new', 5)
            .where((p) => p.categoryName == 'Hampers')
            .toList();
      }

      if (mounted) {
        setState(() {
          _filteredProducts.clear();
          _filteredProducts.addAll(products);
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading products by category: $e');
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _handleCategorySelect(String category) {
    setState(() => _selectedCategory = category);
    _loadProductsByCategory(category);
  }

  void _handleProductTap(Product product) {
    Navigator.pushNamed(
      context,
      '/product-detail',
      arguments: {'product': product},
    );
  }

  void _handleSearch() async {
    final Product? result = await showSearch<Product?>(
      context: context,
      delegate: ProductSearch(),
    );

    if (result != null && mounted) {
      _handleProductTap(result);
    }
  }

  Widget _buildMainHome(String userName) {
    return ListView(
      physics: const BouncingScrollPhysics(),
      padding: EdgeInsets.zero,
      children: [
        _buildHomeHeader(userName), // Custom header that's not part of AppBar
        _buildBannerCarousel(),
        _buildCategorySelector(),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                _selectedCategory == 'All'
                    ? 'All Products'
                    : '$_selectedCategory Collection',
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: darkTextColor,
                ),
              ),
              TextButton(
                onPressed: () {
                  // Handle view all
                },
                child: const Text(
                  'View All',
                  style: TextStyle(
                    color: primaryColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
        _isLoading
            ? const Center(
                child: Padding(
                padding: EdgeInsets.all(40),
                child: CircularProgressIndicator(),
              ))
            : _buildProductGrid(
                _filteredProducts.isEmpty ? _newProducts : _filteredProducts),
      ],
    );
  }

  // New method for the custom header
  Widget _buildHomeHeader(String userName) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 45, 20, 20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            primaryColor.withOpacity(0.9),
            primaryColor.withOpacity(0.6),
          ],
        ),
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(30),
          bottomRight: Radius.circular(30),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                height: 40,
                width: 40,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                padding: const EdgeInsets.all(5),
                child: Image.asset(
                  'assets/images/logo.png',
                  fit: BoxFit.contain,
                ),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Bloom Bouquet',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'Hello $userName, send happiness in a bloom',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
              const Spacer(),
              Stack(
                children: [
                  IconButton(
                    icon: const Icon(
                      LineIcons.bell,
                      color: Colors.white,
                      size: 22,
                    ),
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    onPressed: () {},
                  ),
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 15),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: _handleSearch,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    height: 36,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.9),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          LineIcons.search,
                          color: primaryColor,
                          size: 16,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'Search for bouquets...',
                          style: TextStyle(
                            color: Colors.grey.shade400,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Container(
                height: 36,
                width: 36,
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.9),
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: IconButton(
                  icon: const Icon(
                    LineIcons.heart,
                    color: primaryColor,
                    size: 16,
                  ),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                  onPressed: () {
                    Navigator.pushNamed(context, '/favorites');
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildBannerCarousel() {
    return Container(
      height: 200,
      margin: const EdgeInsets.only(top: 15), // Reduced top margin (was 20)
      child: PageView.builder(
        controller: _pageController,
        itemCount: _banners.length,
        onPageChanged: (index) {
          setState(() {
            _currentBannerIndex = index;
          });
        },
        itemBuilder: (context, index) {
          final banner = _banners[index];
          return AnimatedContainer(
            duration: const Duration(milliseconds: 500),
            margin: EdgeInsets.symmetric(
              horizontal: _currentBannerIndex == index ? 10 : 20,
              vertical: _currentBannerIndex == index ? 0 : 10,
            ),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(25),
              boxShadow: [
                BoxShadow(
                  color: banner['color'].withOpacity(0.4),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(25),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  // Background with gradient and image
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          banner['color'].withOpacity(0.6),
                          banner['color'].withOpacity(0.3),
                        ],
                      ),
                    ),
                  ),

                  // Image with lighter overlay
                  Opacity(
                    opacity: 0.8,
                    child: Image.asset(
                      banner['image'],
                      fit: BoxFit.cover,
                    ),
                  ),

                  // Content
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                banner['title'],
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                  shadows: [
                                    Shadow(
                                      color: Colors.black26,
                                      offset: Offset(0, 2),
                                      blurRadius: 4,
                                    ),
                                  ],
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 6),
                              Text(
                                banner['subtitle'],
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.9),
                                  fontSize: 14,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 15),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 6,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.25),
                                  borderRadius: BorderRadius.circular(15),
                                ),
                                child: Text(
                                  banner['discount'],
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white.withOpacity(0.2),
                            border: Border.all(
                              color: Colors.white.withOpacity(0.5),
                              width: 2,
                            ),
                          ),
                          child: Center(
                            child: Icon(
                              LineIcons.spa,
                              size: 40,
                              color: Colors.white.withOpacity(0.9),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildPageIndicator() {
    return Row(
      children: List.generate(_banners.length, (index) {
        return Container(
          width: _currentBannerIndex == index ? 12 : 6,
          height: 6,
          margin: const EdgeInsets.symmetric(horizontal: 3),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(3),
            color: _currentBannerIndex == index
                ? primaryColor
                : primaryColor.withOpacity(0.3),
          ),
        );
      }),
    );
  }

  Widget _buildCategorySelector() {
    return Container(
      height: 45, // Reduced height (was 50)
      margin: const EdgeInsets.only(top: 10), // Reduced top margin (was 24)
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _categories.length,
        itemBuilder: (context, index) {
          final category = _categories[index];
          final isSelected = _selectedCategory == category['name'];

          return GestureDetector(
            onTap: () => _handleCategorySelect(category['name']),
            child: Container(
              margin: const EdgeInsets.only(right: 12),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                padding:
                    const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                decoration: BoxDecoration(
                  color: isSelected ? primaryColor : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: isSelected
                          ? primaryColor.withOpacity(0.3)
                          : Colors.grey.withOpacity(0.15),
                      spreadRadius: 1,
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                  border: Border.all(
                    color: isSelected ? primaryColor : Colors.grey.shade200,
                    width: 1,
                  ),
                ),
                child: Text(
                  category['name'],
                  style: TextStyle(
                    color: isSelected ? Colors.white : Colors.grey.shade800,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildProductGrid(List<Product> products) {
    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(
          16, 8, 16, 250), // Reduced top padding (was 16)
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.8, // Adjusted for more square-like appearance
        crossAxisSpacing: 15,
        mainAxisSpacing: 22,
      ),
      itemCount: products.length,
      itemBuilder: (context, index) {
        final product = products[index];
        return _buildProductCard(product);
      },
    );
  }

  Widget _buildProductCard(Product product) {
    // Calculate the final price after discount
    final double finalPrice = product.isOnSale
        ? (product.price * (100 - product.discount) / 100).toDouble()
        : product.price.toDouble();

    return GestureDetector(
      onTap: () => _handleProductTap(product),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.15),
              spreadRadius: 2,
              blurRadius: 10,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(22),
          child: Stack(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product image - reduced height
                  SizedBox(
                    height: 120, // Fixed height for product image
                    width: double.infinity,
                    child: Hero(
                      tag: 'product-${product.id}',
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          Container(
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                              image: DecorationImage(
                                image: AssetImage(product.imageUrl),
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                          if (product.isOnSale)
                            Positioned(
                              top: 10,
                              left: 10,
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  gradient: const LinearGradient(
                                    colors: [
                                      Colors.red,
                                      Colors.redAccent,
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(10),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.red.withOpacity(0.3),
                                      blurRadius: 6,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                                child: const Text(
                                  'SALE',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 9,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          // Glass effect at the bottom of the image
                          Positioned(
                            bottom: 0,
                            left: 0,
                            right: 0,
                            child: ClipRRect(
                              child: BackdropFilter(
                                filter: ImageFilter.blur(
                                  sigmaX: 10.0,
                                  sigmaY: 10.0,
                                ),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 6,
                                  ),
                                  color: Colors.black.withOpacity(0.3),
                                  child: Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 8,
                                          vertical: 4,
                                        ),
                                        decoration: BoxDecoration(
                                          color: Colors.white.withOpacity(0.2),
                                          borderRadius:
                                              BorderRadius.circular(10),
                                        ),
                                        child: Text(
                                          product.categoryName,
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 10,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                      ),
                                      Row(
                                        children: [
                                          const Icon(
                                            LineIcons.star,
                                            color: Colors.amber,
                                            size: 16,
                                          ),
                                          const SizedBox(width: 2),
                                          Text(
                                            product.rating.toString(),
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 12,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Product details
                  Container(
                    padding: const EdgeInsets.all(8), // Further reduced padding
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min, // Use minimum space
                      children: [
                        Text(
                          product.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 12, // Smaller font
                            color: darkTextColor,
                          ),
                        ),
                        const SizedBox(height: 2), // Smaller spacing
                        // Only show final price
                        Text(
                          'Rp${(finalPrice / 1000).toStringAsFixed(0)}K',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13, // Smaller font
                            color: primaryColor,
                          ),
                        ),
                        const SizedBox(height: 4), // Smaller spacing
                        // Add to Cart button
                        SizedBox(
                          width: double.infinity,
                          height: 26, // Smaller height
                          child: ElevatedButton(
                            onPressed: () {
                              // Add to cart functionality
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content:
                                      Text('Added ${product.name} to cart'),
                                  backgroundColor: primaryColor,
                                  behavior: SnackBarBehavior.floating,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                ),
                              );
                            },
                            style: ElevatedButton.styleFrom(
                              foregroundColor: Colors.white,
                              backgroundColor: primaryColor,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                              padding: EdgeInsets.zero,
                            ),
                            child: const Text(
                              'Add to Cart',
                              style: TextStyle(
                                fontSize: 10, // Smaller font
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              Positioned(
                top: 10,
                right: 10,
                child: Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.8),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.1),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Icon(
                      LineIcons.heartAlt,
                      size: 20,
                      color: primaryColor,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final userName = _username; // Use the loaded username
    return Scaffold(
      extendBody: true, // Make body extend behind the navigation bar
      backgroundColor: const Color(0xFFF5F5F5), // Light grey background
      // No appBar property - we're using a custom header within the body
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildMainHome(userName), // Pass the username to the header
          const CartPage(),
          const ChatPage(),
          const ProfilePage(),
        ],
      ),
      bottomNavigationBar: SnakeNavigationBar.color(
        behaviour: SnakeBarBehaviour.floating,
        snakeShape: SnakeShape.circle,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(25),
        ),
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 8),
        height: 70,
        backgroundColor: Colors.white,
        snakeViewColor: primaryColor,
        selectedItemColor: Colors.white,
        unselectedItemColor: lightTextColor,
        showUnselectedLabels: false,
        showSelectedLabels: true,
        elevation: 8,
        shadowColor: Colors.black.withOpacity(0.2),
        currentIndex: _selectedIndex,
        onTap: (index) {
          setState(() => _selectedIndex = index);
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(LineIcons.home), label: 'Home'),
          BottomNavigationBarItem(
              icon: Icon(LineIcons.shoppingBag), label: 'Cart'),
          BottomNavigationBarItem(
              icon: Icon(LineIcons.commentDots), label: 'Chat'),
          BottomNavigationBarItem(icon: Icon(LineIcons.user), label: 'Profile'),
        ],
      ),
    );
  }
}
