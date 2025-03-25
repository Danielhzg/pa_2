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
  late TabController _tabController;
  final PageController _pageController = PageController(viewportFraction: 0.85);
  int _currentBannerIndex = 0;

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
      'color': Color(0xFFF8BBD0),
    },
    {
      'title': 'Wedding Special',
      'subtitle': 'Make your day perfect',
      'discount': '15% OFF',
      'image': 'assets/images/contoh.jpg',
      'color': Color(0xFFFFCCBC),
    },
    {
      'title': 'Gift Bouquets',
      'subtitle': 'Express your feelings',
      'discount': '10% OFF',
      'image': 'assets/images/contoh.jpg',
      'color': Color(0xFFD1C4E9),
    },
  ];

  final List<Map<String, dynamic>> _categories = [
    {'name': 'All', 'icon': LineIcons.borderAll},
    {'name': 'Wisuda', 'icon': LineIcons.graduationCap},
    {'name': 'Birthday', 'icon': LineIcons.birthdayCake},
    {'name': 'Wedding', 'icon': LineIcons.ring},
    {'name': 'Box Custom', 'icon': LineIcons.gift},
    {'name': 'Money', 'icon': LineIcons.moneyBill},
    {'name': 'Hampers', 'icon': LineIcons.shoppingBasket},
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
    _tabController = TabController(length: 3, vsync: this);
    _loadProducts();

    // Auto-scroll banner - start after a short delay
    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted) {
        _startBannerAutoScroll();
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
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
          category: i % 7 == 0
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

      final products = _createDummyProducts('new', 6)
          .where((p) => category == 'All' || p.category == category)
          .toList();

      if (mounted) {
        setState(() {
          _newProducts.clear();
          _newProducts.addAll(products);
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

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context, listen: false);
    final userName = authService.currentUser?.name?.split(' ').first ?? 'Guest';

    return Scaffold(
      extendBody: true, // Make body extend behind the navigation bar
      backgroundColor: const Color(0xFFF5F5F5), // Light grey background
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
              icon: Icon(LineIcons.commentDots), label: 'Chat'),
          BottomNavigationBarItem(icon: Icon(LineIcons.user), label: 'Profile'),
        ],
      ),
    );
  }

  Widget _buildMainHome(String userName) {
    return CustomScrollView(
      physics: const BouncingScrollPhysics(),
      slivers: [
        _buildAppBar(userName),
        SliverToBoxAdapter(
          child: _buildBannerCarousel(),
        ),
        SliverToBoxAdapter(
          child: _buildTabBar(),
        ),
        SliverToBoxAdapter(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.8,
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildFeaturedProducts(),
                _buildNewArrivals(),
                _buildTrendingProducts(),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildAppBar(String userName) {
    return SliverAppBar(
      expandedHeight: 120,
      floating: false,
      pinned: true,
      backgroundColor: Colors.white,
      elevation: 0,
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          padding: const EdgeInsets.fromLTRB(20, 60, 20, 0),
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
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Welcome back,',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.8),
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        userName,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  Row(
                    children: [
                      IconButton(
                        icon: const Icon(
                          LineIcons.heart,
                          color: Colors.white,
                        ),
                        onPressed: () {
                          Navigator.pushNamed(context, '/favorites');
                        },
                      ),
                      Stack(
                        children: [
                          IconButton(
                            icon: const Icon(
                              LineIcons.bell,
                              color: Colors.white,
                            ),
                            onPressed: () {},
                          ),
                          Positioned(
                            right: 12,
                            top: 10,
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
                ],
              ),
            ],
          ),
        ),
      ),
      bottom: PreferredSize(
        preferredSize: const Size.fromHeight(60),
        child: Container(
          height: 60,
          alignment: Alignment.center,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          child: GestureDetector(
            onTap: _handleSearch,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              height: 50,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(25),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const Icon(
                    LineIcons.search,
                    color: primaryColor,
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Search for bouquets...',
                    style: TextStyle(
                      color: Colors.grey.shade400,
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBannerCarousel() {
    return Container(
      height: 200,
      margin: const EdgeInsets.only(top: 20),
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

  Widget _buildTabBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      margin: const EdgeInsets.only(top: 15),
      child: TabBar(
        controller: _tabController,
        labelColor: primaryColor,
        unselectedLabelColor: lightTextColor,
        indicatorColor: primaryColor,
        indicatorSize: TabBarIndicatorSize.label,
        labelStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
        ),
        unselectedLabelStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.normal,
        ),
        tabs: const [
          Tab(
            text: 'Featured',
          ),
          Tab(
            text: 'New',
          ),
          Tab(
            text: 'Trending',
          ),
        ],
      ),
    );
  }

  Widget _buildFeaturedProducts() {
    return _isLoading
        ? const Center(child: CircularProgressIndicator())
        : _buildProductGrid(_featuredProducts);
  }

  Widget _buildNewArrivals() {
    return _isLoading
        ? const Center(child: CircularProgressIndicator())
        : _buildProductGrid(_newProducts);
  }

  Widget _buildTrendingProducts() {
    return _isLoading
        ? const Center(child: CircularProgressIndicator())
        : _buildProductGrid(_trendingProducts);
  }

  Widget _buildProductGrid(List<Product> products) {
    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 250),
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.75,
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
                  // Product image
                  Expanded(
                    flex: 3,
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
                                  horizontal: 10,
                                  vertical: 6,
                                ),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      Colors.red,
                                      Colors.redAccent,
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(12),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.red.withOpacity(0.3),
                                      blurRadius: 6,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                                child: Text(
                                  '${product.discount}% OFF',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
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
                                          product.category,
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
                  Expanded(
                    flex: 2,
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            product.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              color: darkTextColor,
                            ),
                          ),
                          const Spacer(),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              Flexible(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    if (product.isOnSale)
                                      Text(
                                        'Rp${(product.price / 1000).toStringAsFixed(0)}K',
                                        style: const TextStyle(
                                          fontSize: 10,
                                          color: lightTextColor,
                                          decoration:
                                              TextDecoration.lineThrough,
                                        ),
                                      ),
                                    Text(
                                      'Rp${(product.isOnSale ? (product.price * (100 - product.discount) / 100) : product.price) / 1000}K',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 15,
                                        color: primaryColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      primaryColor,
                                      primaryColor.withOpacity(0.7),
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(16),
                                  boxShadow: [
                                    BoxShadow(
                                      color: primaryColor.withOpacity(0.3),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                                child: const Icon(
                                  LineIcons.plus,
                                  color: Colors.white,
                                  size: 18,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
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
                  child: Center(
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
}
