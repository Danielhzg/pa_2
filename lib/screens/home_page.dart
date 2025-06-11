import 'package:flutter/material.dart';
import 'dart:ui';
import 'package:provider/provider.dart';
import 'package:flutter_snake_navigationbar/flutter_snake_navigationbar.dart';
import 'package:line_icons/line_icons.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/product.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart'; // Add notification service import
import '../widgets/product_search.dart';
import '../utils/image_url_helper.dart'; // Tambahkan import untuk ImageUrlHelper
import '../providers/favorite_provider.dart'; // Updated from favorites_provider
import 'cart_page.dart'; // Add this import for CartPage
import 'chat_page.dart';
import 'profile_page.dart';
import 'dart:async';
import '../services/api_service.dart';
import '../utils/database_helper.dart';
import '../providers/cart_provider.dart'; // Tambahkan import untuk CartProvider
import 'favorites_page.dart'; // Import favorites page
import '../widgets/favorite_popup.dart'; // Import the new favorite popup widget
import '../widgets/loading_overlay.dart'; // Import the new loading overlay widget

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage>
    with TickerProviderStateMixin, WidgetsBindingObserver {
  int _selectedIndex = 0;
  String _selectedCategory = 'All';
  bool _isLoading = false;
  final List<Product> _filteredProducts = [];
  late PageController _pageController;
  int _currentBannerIndex = 0;
  late String _username;
  List<Map<String, dynamic>> _categories = [
    {'name': 'All'}
  ]; // Mulai dengan kategori 'All'
  bool _loadingCategories = true;

  // Colors
  static const Color primaryColor = Color(0xFFFF87B2);
  static const Color secondaryColor = Color(0xFFFFC0D9);
  static const Color accentColor = Color(0xFFFFE5EE);
  static const Color darkTextColor = Color(0xFF333333);
  static const Color lightTextColor = Color(0xFF717171);

  List<dynamic> _banners = [];
  bool _loadingBanners = true;
  final ApiService _apiService = ApiService();
  Timer? _bannerTimer;

  // Timer for favorites sync
  Timer? _favoritesSyncTimer;
  void _startBannerAutoScroll() {
    if (_banners.isEmpty) {
      print('No banners available to auto-scroll.');
      return;
    }

    // Cancel any existing timer
    _bannerTimer?.cancel();

    // Create a new periodic timer that scrolls every 4 seconds (consistent with carousel widgets)
    _bannerTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (mounted && _pageController.hasClients) {
        // Calculate the next page index
        final nextPage = (_currentBannerIndex + 1) % _banners.length;

        // Animate to the next page
        _pageController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOut,
        );

        // Update the current banner index
        setState(() {
          _currentBannerIndex = nextPage;
        });
      }
    });

    print('Auto-scroll started for ${_banners.length} banners');
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this); // Add lifecycle observer
    _pageController = PageController(viewportFraction: 0.85);
    _fetchCategories(); // Fetch categories from API
    _fetchProducts(); // Fetch products from API
    _fetchBanners().then((_) {
      // Only start auto-scroll after banners are fetched successfully
      if (_banners.isNotEmpty && mounted) {
        _startBannerAutoScroll();
      }
    });
    _loadUsername();

    // Set up periodic check for favorites to ensure UI is consistent
    _setupFavoritesSyncTimer();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this); // Remove lifecycle observer
    _pageController.dispose();
    _bannerTimer?.cancel();
    _favoritesSyncTimer?.cancel(); // Cancel favorites timer on dispose
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);

    // Stop auto-scrolling when app is paused, resume when app is resumed
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _bannerTimer?.cancel();
    } else if (state == AppLifecycleState.resumed) {
      if (_banners.isNotEmpty) {
        _startBannerAutoScroll();
      }
    }
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoading = true);
    try {
      final products = await _apiService.fetchProducts();
      if (mounted) {
        setState(() {
          _filteredProducts.clear();
          _filteredProducts
              .addAll(products.map((p) => Product.fromJson(p)).toList());
          _isLoading = false;
        });

        // Check favorite status for all products if user is logged in
        _updateFavoriteStatus();
      }
    } catch (e) {
      print('Error fetching products: $e');
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load products: $e')),
        );
      }
    }
  }

  Future<void> _fetchBanners() async {
    setState(() => _loadingBanners = true);
    try {
      final banners = await _apiService.fetchCarousels();

      if (mounted) {
        setState(() {
          // Log the raw data for debugging
          print('Raw banners data: $banners');

          // Ensure all entries are processed, even if some properties are missing
          _banners = banners.map((banner) {
            return {
              'id': banner['id'] ?? 0,
              'title': banner['title'] ?? 'No Title',
              'description': banner['description'] ?? 'No Description',
              'image': banner['image'] ?? '',
              'order': banner['order'] ?? 0,
            };
          }).toList();

          // Sort banners by the 'order' property
          _banners.sort((a, b) => a['order'].compareTo(b['order']));

          _loadingBanners = false;
        });
      }
    } catch (e) {
      print('Error fetching banners: $e');
      if (mounted) {
        setState(() => _loadingBanners = false);
      }
    }
  }

  void _loadUsername() {
    final authService = Provider.of<AuthService>(context, listen: false);
    setState(() {
      _username = authService.currentUser?.username ?? 'Guest';
    });
  }

  Future<void> _loadProductsByCategory(String categoryName) async {
    setState(() => _isLoading = true);
    try {
      final apiService = ApiService();
      List<Product> products = [];

      if (categoryName == 'All') {
        // For "All" category, fetch all products
        final allProducts = await apiService.fetchProducts();
        products = allProducts.map((p) => Product.fromJson(p)).toList();
      } else {
        // Find the category ID from our categories list
        int? categoryId;
        for (var category in _categories) {
          if (category['name'] == categoryName) {
            categoryId = category['id'];
            break;
          }
        }

        // Debug output to check if we have a valid category ID
        print('Selected category: $categoryName, ID: $categoryId');

        if (categoryId != null) {
          try {
            // Use the category ID to fetch products for this specific category
            final filteredProducts =
                await apiService.fetchProductsByCategory(categoryId.toString());
            products =
                filteredProducts.map((p) => Product.fromJson(p)).toList();
            print(
                'Loaded ${products.length} products for category $categoryName');
          } catch (categoryError) {
            print('Error loading specific category: $categoryError');
            // Fallback to all products if category filtering fails
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Category filter unavailable: $categoryError'),
                duration: const Duration(seconds: 3),
              ),
            );

            // Load all products as fallback
            final allProducts = await apiService.fetchProducts();
            products = allProducts.map((p) => Product.fromJson(p)).toList();

            // Filter products by category name on the client side as backup
            products = products
                .where((p) =>
                    p.categoryName.toLowerCase() == categoryName.toLowerCase())
                .toList();
          }
        } else {
          throw Exception('Invalid category ID for: $categoryName');
        }
      }

      if (mounted) {
        setState(() {
          _filteredProducts.clear();
          _filteredProducts.addAll(products);
          _isLoading = false;
        });

        // Check favorite status for products
        _updateFavoriteStatus();
      }
    } catch (e) {
      print('Error loading products by category: $e');
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Failed to load products by category: $e')));
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

  Future<void> _fetchCategories() async {
    setState(() => _loadingCategories = true);
    try {
      final apiService = ApiService();
      final categoriesData = await apiService.fetchCategories();

      // Ubah format data kategori ke format yang dibutuhkan
      final List<Map<String, dynamic>> categories = [
        {'name': 'All'}
      ];
      for (var category in categoriesData) {
        categories.add({'name': category['name'], 'id': category['id']});
      }

      setState(() {
        _categories = categories;
        _loadingCategories = false;
      });
    } catch (e) {
      print('Error fetching categories: $e');
      setState(() => _loadingCategories = false);
      // Tetap gunakan kategori 'All' jika gagal mengambil dari API
    }
  }

  // Update favorite status for all products
  void _updateFavoriteStatus() {
    if (!mounted) return;

    try {
      final favoriteProvider =
          Provider.of<FavoriteProvider>(context, listen: false);

      // Make sure favorites provider is initialized
      if (!favoriteProvider.initialized) return;

      // Get the local favorite IDs that persist between sessions
      final localFavoriteIds = favoriteProvider.localFavoriteIds;

      // Update featured products
      for (var product in _filteredProducts) {
        product.isFavorited = localFavoriteIds.contains(product.id);
      }

      // Update all products
      for (var product in _filteredProducts) {
        product.isFavorited = localFavoriteIds.contains(product.id);
      }

      // Update filtered products if they exist
      for (var product in _filteredProducts) {
        product.isFavorited = localFavoriteIds.contains(product.id);
      }

      // Update category products if they exist
      for (var category in _categories) {
        final categoryId = category['id'];
        final products = _filteredProducts
            .where((p) => p.categoryName == category['name'])
            .toList();
        if (products.isNotEmpty) {
          for (var product in products) {
            product.isFavorited = localFavoriteIds.contains(product.id);
          }
        }
      }

      // Force UI refresh if needed
      setState(() {});
    } catch (e) {
      print('Error updating favorite status: $e');
    }
  }

  Widget _buildMainHome(String userName) {
    return ListView(
      physics: const BouncingScrollPhysics(),
      padding: EdgeInsets.zero,
      children: [
        _buildHomeHeader(userName),
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
                    color: Color.fromRGBO(255, 135, 178, 1),
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
            : _filteredProducts.isEmpty
                ? const Center(
                    child: Padding(
                    padding: EdgeInsets.all(40),
                    child: Text(
                      'No products available',
                      style: TextStyle(
                        color: lightTextColor,
                        fontSize: 16,
                      ),
                    ),
                  ))
                : _buildProductGrid(_filteredProducts),
      ],
    );
  }

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
              Consumer<NotificationService>(
                builder: (context, notificationService, child) {
                  final unreadCount = notificationService.unreadCount;
                  return Stack(
                    children: [
                      IconButton(
                        icon: const Icon(
                          LineIcons.bell,
                          color: Colors.white,
                          size: 22,
                        ),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        onPressed: () {
                          // Navigate to notifications page
                          Navigator.pushNamed(context, '/notifications');
                        },
                      ),
                      // Show notification badge if there are unread notifications
                      if (unreadCount > 0)
                        Positioned(
                          right: 0,
                          top: 0,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                            ),
                            constraints: const BoxConstraints(
                              minWidth: 16,
                              minHeight: 16,
                            ),
                            child: Text(
                              unreadCount > 99 ? '99+' : unreadCount.toString(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  );
                },
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
              Stack(
                children: [
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
                      icon: Consumer<FavoriteProvider>(
                        builder: (context, favoriteProvider, _) {
                          final hasFavorites =
                              favoriteProvider.favorites.isNotEmpty;
                          return Icon(
                            hasFavorites
                                ? Icons.favorite
                                : Icons.favorite_border,
                            color: primaryColor,
                            size: 16,
                          );
                        },
                      ),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      onPressed: () {
                        final authService =
                            Provider.of<AuthService>(context, listen: false);
                        if (!authService.isLoggedIn) {
                          // Show login prompt if user is not logged in
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content:
                                  const Text('Please login to view favorites'),
                              action: SnackBarAction(
                                label: 'LOGIN',
                                onPressed: () =>
                                    Navigator.pushNamed(context, '/login'),
                              ),
                              behavior: SnackBarBehavior.floating,
                              margin: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                              duration: const Duration(seconds: 2),
                            ),
                          );
                          return;
                        }

                        // Load latest favorites before showing popup
                        final favoriteProvider = Provider.of<FavoriteProvider>(
                            context,
                            listen: false);
                        favoriteProvider.loadFavorites();

                        // Show favorite popup
                        showDialog(
                          context: context,
                          builder: (BuildContext context) {
                            return const FavoritePopup();
                          },
                        );
                      },
                    ),
                  ),

                  // Display badge with favorite count if there are favorites
                  Consumer<FavoriteProvider>(
                    builder: (context, favoriteProvider, _) {
                      final count = favoriteProvider.favorites.length;
                      final isLoggedIn =
                          Provider.of<AuthService>(context).isLoggedIn;

                      if (!isLoggedIn || count == 0) {
                        return const SizedBox.shrink();
                      }

                      return Positioned(
                        right: -1,
                        top: -1,
                        child: TweenAnimationBuilder<double>(
                          tween: Tween(begin: 0.5, end: 1.0),
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.elasticOut,
                          builder: (context, value, child) {
                            return Transform.scale(
                              scale: value,
                              child: Container(
                                padding: const EdgeInsets.all(4),
                                decoration: BoxDecoration(
                                  color: Colors.red,
                                  shape: BoxShape.circle,
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.1),
                                      blurRadius: 2,
                                      spreadRadius: 1,
                                    ),
                                  ],
                                ),
                                constraints: const BoxConstraints(
                                  minWidth: 14,
                                  minHeight: 14,
                                ),
                                child: Text(
                                  count.toString(),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 9,
                                    fontWeight: FontWeight.bold,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ),
                            );
                          },
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildBannerCarousel() {
    if (_loadingBanners) {
      return const SizedBox(
        height: 200,
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_banners.isEmpty) {
      print('No banners available to display');
      return const SizedBox(height: 20); // Return an empty space if no banners
    }

    // Debug info
    print('Building carousel with ${_banners.length} banners');
    for (var banner in _banners) {
      print('Banner data: ${banner.toString()}');
    }

    return Container(
      height: 200,
      margin: const EdgeInsets.only(top: 15),
      child: Stack(
        children: [
          PageView.builder(
            controller: _pageController,
            itemCount: _banners.length,
            onPageChanged: (index) {
              // Update the current banner index
              setState(() {
                _currentBannerIndex = index;
              });

              // When user manually swipes, restart the timer to prevent
              // auto-scrolling right after manual interaction
              if (_bannerTimer != null) {
                _bannerTimer?.cancel();
                _startBannerAutoScroll();
              }
            },
            itemBuilder: (context, index) {
              final banner = _banners[index];

              // Dapatkan semua URL alternatif
              final String rawImagePath = banner['image'] ?? '';
              final List<String> imageUrls =
                  ImageUrlHelper.getAllPossibleImageUrls(rawImagePath);
              final String primaryImageUrl = imageUrls.isNotEmpty
                  ? imageUrls[0]
                  : ImageUrlHelper.placeholderUrl;

              final title = banner['title'] ?? '';
              final description = banner['description'] ?? '';

              // Log the image URLs for debugging
              print('Banner $index raw image path: $rawImagePath');
              print('Banner $index primary URL: $primaryImageUrl');
              print('Banner $index all URLs: $imageUrls');

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
                      color: Colors.black.withOpacity(0.2),
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
                      // Try multiple image URLs if the main one fails
                      CachedNetworkImage(
                        imageUrl: primaryImageUrl,
                        fit: BoxFit.cover,
                        placeholder: (context, url) => const Center(
                          child: CircularProgressIndicator(),
                        ),
                        errorWidget: (context, url, error) {
                          print('Error loading banner image: $error');
                          print('Failed URL: $primaryImageUrl');

                          // Try second URL if available
                          if (imageUrls.length > 1) {
                            return CachedNetworkImage(
                              imageUrl: imageUrls[1],
                              fit: BoxFit.cover,
                              placeholder: (context, url) => const Center(
                                child: CircularProgressIndicator(),
                              ),
                              errorWidget: (context, url, error) {
                                // Try third URL if available
                                if (imageUrls.length > 2) {
                                  return CachedNetworkImage(
                                    imageUrl: imageUrls[2],
                                    fit: BoxFit.cover,
                                    placeholder: (context, url) => const Center(
                                      child: CircularProgressIndicator(),
                                    ),
                                    errorWidget: (context, url, error) =>
                                        Container(
                                      color: Colors.grey[200],
                                      child: Center(
                                        child: Column(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(Icons.image_not_supported,
                                                color: Colors.grey[400],
                                                size: 40),
                                            const SizedBox(height: 8),
                                            Text(
                                              'Image not available',
                                              style: TextStyle(
                                                  color: Colors.grey[600]),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  );
                                }
                                return const Center(
                                  child: Icon(Icons.error, color: Colors.red),
                                );
                              },
                            );
                          }

                          return Container(
                            color: Colors.grey[200],
                            child: Center(
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.broken_image,
                                      color: Colors.grey, size: 40),
                                  const SizedBox(height: 8),
                                  Text(
                                    'Cannot load image',
                                    style: TextStyle(color: Colors.grey[600]),
                                  ),
                                  if (rawImagePath.isNotEmpty)
                                    Text(
                                      'Path: $rawImagePath',
                                      style: TextStyle(
                                          color: Colors.grey[500],
                                          fontSize: 10),
                                    ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.bottomCenter,
                            end: Alignment.topCenter,
                            colors: [
                              Colors.black.withOpacity(0.6),
                              Colors.transparent,
                            ],
                          ),
                        ),
                      ),
                      Positioned(
                        bottom: 0,
                        left: 0,
                        right: 0,
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 5),
                              Text(
                                description,
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.9),
                                  fontSize: 14,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),

          // Left navigation button - Enhanced for mobile
          Positioned(
            left: 8,
            top: 0,
            bottom: 0,
            child: Center(
              child: GestureDetector(
                onTap: () {
                  final currentPage = _currentBannerIndex;
                  final previousPage =
                      currentPage > 0 ? currentPage - 1 : _banners.length - 1;
                  _pageController.animateToPage(
                    previousPage,
                    duration: const Duration(milliseconds: 300),
                    curve: Curves.easeInOut,
                  );
                },
                child: Container(
                  width: 48, // Larger touch target for mobile
                  height: 48,
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.9),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: Colors.grey.withOpacity(0.3),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.15),
                        spreadRadius: 1,
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.arrow_back_ios_new,
                    color: Colors.black87,
                    size: 20,
                  ),
                ),
              ),
            ),
          ),

          // Right navigation button - Enhanced for mobile
          Positioned(
            right: 8,
            top: 0,
            bottom: 0,
            child: Center(
              child: GestureDetector(
                onTap: () {
                  final currentPage = _currentBannerIndex;
                  final nextPage = (currentPage + 1) % _banners.length;
                  _pageController.animateToPage(
                    nextPage,
                    duration: const Duration(milliseconds: 300),
                    curve: Curves.easeInOut,
                  );
                },
                child: Container(
                  width: 48, // Larger touch target for mobile
                  height: 48,
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.9),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: Colors.grey.withOpacity(0.3),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.15),
                        spreadRadius: 1,
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.arrow_forward_ios,
                    color: Colors.black87,
                    size: 20,
                  ),
                ),
              ),
            ),
          ),
        ],
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
    if (_loadingCategories) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 10),
          child: SizedBox(
            height: 45,
            child: CircularProgressIndicator(
              strokeWidth: 2,
            ),
          ),
        ),
      );
    }

    return Container(
      height: 45,
      margin: const EdgeInsets.only(top: 10),
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
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 250),
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.8,
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
                  SizedBox(
                    height: 120,
                    width: double.infinity,
                    child: Hero(
                      tag: 'product-${product.id}',
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          Container(
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                            ),
                            child: _buildProductImage(product.imageUrl),
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
                                  alignment: Alignment.centerLeft,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 4,
                                    ),
                                    decoration: BoxDecoration(
                                      color: Colors.black.withOpacity(0.5),
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(
                                        color: Colors.grey.withOpacity(0.6),
                                        width: 0.5,
                                      ),
                                    ),
                                    child: Text(
                                      product.categoryName,
                                      style: const TextStyle(
                                        color: Color(0xFFFFD700),
                                        fontSize: 10,
                                        fontWeight: FontWeight.w600,
                                        letterSpacing: 0.5,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.all(8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                product.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                  color: darkTextColor,
                                ),
                              ),
                            ),
                            const SizedBox(width: 4),
                            _buildSimpleStockIndicator(product.stock),
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Rp${finalPrice.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]}.')}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: primaryColor,
                          ),
                        ),
                        const SizedBox(height: 4),
                        SizedBox(
                          width: double.infinity,
                          height: 26,
                          child: ElevatedButton(
                            onPressed: () {
                              if (product.stock > 0) {
                                // Add to cart functionality
                                final cartProvider = Provider.of<CartProvider>(
                                    context,
                                    listen: false);
                                cartProvider.addToCart(product, 1);

                                // Show success message
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Row(
                                      children: [
                                        const Icon(
                                          LineIcons.check,
                                          color: Colors.white,
                                          size: 20,
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Text(
                                            'Added ${product.name} to cart',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(
                                              fontSize: 13,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    backgroundColor: primaryColor,
                                    behavior: SnackBarBehavior.floating,
                                    margin: const EdgeInsets.fromLTRB(
                                        20, 0, 20, 20),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    action: SnackBarAction(
                                      label: 'VIEW CART',
                                      textColor: Colors.white,
                                      onPressed: () {
                                        setState(() => _selectedIndex =
                                            1); // Switch to cart page
                                      },
                                    ),
                                    duration: const Duration(seconds: 2),
                                  ),
                                );
                              } else {
                                // Show out of stock message
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content:
                                        Text('${product.name} is out of stock'),
                                    backgroundColor: Colors.red,
                                    behavior: SnackBarBehavior.floating,
                                    margin: const EdgeInsets.fromLTRB(
                                        20, 0, 20, 20),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    duration: const Duration(seconds: 2),
                                  ),
                                );
                              }
                            },
                            style: ElevatedButton.styleFrom(
                              foregroundColor: Colors.white,
                              backgroundColor: product.stock > 0
                                  ? primaryColor
                                  : Colors.grey,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                              padding: EdgeInsets.zero,
                            ),
                            child: Text(
                              product.stock > 0
                                  ? 'Add to Cart'
                                  : 'Out of Stock',
                              style: const TextStyle(
                                fontSize: 10,
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
                child: GestureDetector(
                  onTap: () async {
                    final authService =
                        Provider.of<AuthService>(context, listen: false);
                    final favoriteProvider =
                        Provider.of<FavoriteProvider>(context, listen: false);

                    try {
                      // Toggle favorite with nice animation
                      // This doesn't need login check anymore as our provider handles offline favorites

                      // Get product reference for overlay animation
                      final RenderBox box =
                          context.findRenderObject() as RenderBox;
                      final position = box.localToGlobal(Offset.zero);

                      // Show animation overlay
                      _showHeartAnimation(context, position,
                          size: box.size, isAdding: !product.isFavorited);

                      // Toggle favorite and wait for the result
                      final isFavorited =
                          await favoriteProvider.toggleFavorite(product);

                      // Update the product's isFavorited status
                      setState(() {
                        product.isFavorited = isFavorited;
                      });

                      // Show feedback to user only if they're logged in (to avoid confusion)
                      if (authService.isLoggedIn) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(isFavorited
                                ? 'Added to favorites'
                                : 'Removed from favorites'),
                            backgroundColor: const Color(0xFFFF87B2),
                            behavior: SnackBarBehavior.floating,
                            margin: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                            duration: const Duration(seconds: 2),
                          ),
                        );
                      }
                    } catch (e) {
                      // Show error message
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Error updating favorites: $e'),
                          backgroundColor: Colors.red,
                          behavior: SnackBarBehavior.floating,
                        ),
                      );
                    }
                  },
                  child: TweenAnimationBuilder<double>(
                      tween: Tween<double>(
                        begin: 0.8,
                        end: 1.0,
                      ),
                      curve: Curves.elasticOut,
                      duration: const Duration(milliseconds: 500),
                      builder: (context, value, child) {
                        return Transform.scale(
                          scale: value,
                          child: Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.9),
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.1),
                                  blurRadius: 4,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: Center(
                              child: AnimatedSwitcher(
                                duration: const Duration(milliseconds: 300),
                                transitionBuilder: (child, animation) {
                                  return ScaleTransition(
                                    scale: animation,
                                    child: child,
                                  );
                                },
                                child: Icon(
                                  product.isFavorited
                                      ? Icons.favorite
                                      : Icons.favorite_border,
                                  key: ValueKey<bool>(product.isFavorited),
                                  size: 20,
                                  color: product.isFavorited
                                      ? primaryColor
                                      : Colors.grey,
                                ),
                              ),
                            ),
                          ),
                        );
                      }),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSimpleStockIndicator(int stock) {
    IconData iconData;
    Color iconColor = const Color(0xFFFFD700); // Warna emas (gold)

    if (stock > 10) {
      iconData = LineIcons.boxOpen;
    } else if (stock > 0) {
      iconData = LineIcons.boxOpen;
    } else {
      iconData = LineIcons.box;
      iconColor = Colors.grey; // Abu-abu untuk stok habis
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.grey.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: stock > 0
              ? const Color(0xFFFFD700).withOpacity(0.5)
              : Colors.grey.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            iconData,
            color: iconColor,
            size: 12,
          ),
          const SizedBox(width: 2),
          Text(
            stock.toString(),
            style: TextStyle(
              color: stock > 0 ? const Color(0xFFFFD700) : Colors.grey,
              fontSize: 10,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStockIndicator(int stock) {
    IconData iconData;
    Color iconColor = const Color(0xFFFFD700); // Warna emas (gold)

    if (stock > 10) {
      iconData = LineIcons.boxOpen;
    } else if (stock > 0) {
      iconData = LineIcons.boxOpen;
    } else {
      iconData = LineIcons.box;
      iconColor = Colors.grey; // Abu-abu untuk stok habis
    }

    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(5),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.2),
            shape: BoxShape.circle,
          ),
          child: Icon(
            iconData,
            color: iconColor,
            size: 16,
          ),
        ),
        const SizedBox(width: 4),
        Text(
          stock.toString(),
          style: const TextStyle(
            color: Colors.white,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  Widget _buildProductImage(String imageUrl) {
    // Logging untuk debugging URL gambar
    print("Original imageUrl: $imageUrl");

    // Gunakan ImageUrlHelper untuk membangun URL gambar yang benar
    String finalUrl = ImageUrlHelper.buildImageUrl(imageUrl);

    print("Final URL used: $finalUrl");

    return CachedNetworkImage(
      imageUrl: finalUrl,
      fit: BoxFit.cover,
      placeholder: (context, url) => Container(
        color: Colors.grey[200],
        child: const Center(
          child: CircularProgressIndicator(),
        ),
      ),
      errorWidget: (context, url, error) {
        print("Error loading image: $error, URL: $finalUrl");
        return Container(
          color: Colors.grey[200],
          child: const Center(
            child: Icon(
              LineIcons.exclamationCircle,
              size: 40,
              color: Colors.red,
            ),
          ),
        );
      },
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      color: Colors.grey[200],
      child: const Center(
        child: Icon(
          LineIcons.image,
          size: 40,
          color: Colors.grey,
        ),
      ),
    );
  }

  // Show a heart animation when favoriting/unfavoriting
  void _showHeartAnimation(BuildContext context, Offset position,
      {required Size size, required bool isAdding}) {
    OverlayState? overlayState = Overlay.of(context);

    OverlayEntry? entry;
    entry = OverlayEntry(
      builder: (context) {
        return Positioned(
          left: position.dx,
          top: position.dy,
          width: size.width,
          height: size.height,
          child: Material(
            color: Colors.transparent,
            child: Center(
              child: TweenAnimationBuilder<double>(
                tween: Tween<double>(
                  begin: 0.5,
                  end: isAdding
                      ? 2.0
                      : 0.0, // Grow when adding, shrink when removing
                ),
                curve: isAdding ? Curves.elasticOut : Curves.easeInBack,
                duration: Duration(milliseconds: isAdding ? 800 : 500),
                onEnd: () {
                  entry?.remove();
                },
                builder: (context, value, child) {
                  return Transform.scale(
                    scale: value,
                    child: Opacity(
                      opacity: isAdding
                          ? value.clamp(0.0, 1.0)
                          : (1.0 - value).clamp(0.0, 1.0),
                      child: const Icon(
                        Icons.favorite,
                        color: primaryColor,
                        size: 40,
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
        );
      },
    );

    overlayState.insert(entry);
  }

  // Set up periodic check for favorites changes
  void _setupFavoritesSyncTimer() {
    // Check for favorites changes every 5 seconds
    _favoritesSyncTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (mounted) {
        _updateFavoriteStatus();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final userName = _username;
    return Scaffold(
      extendBody: true,
      backgroundColor: const Color(0xFFF5F5F5),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildMainHome(userName),
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
              icon: Icon(LineIcons.commentAlt), label: 'Bantuan'),
          BottomNavigationBarItem(icon: Icon(LineIcons.user), label: 'Profile'),
        ],
      ),
    );
  }
}
