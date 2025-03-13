import 'package:flutter/material.dart';
import '../models/product.dart';
import '../widgets/product_search.dart';
import '../utils/database_helper.dart';
import '../models/cart_item.dart';
import 'product_detail_page.dart';
import '../widgets/custom_bottom_nav.dart';
import 'cart_page.dart';
import 'chat_page.dart';
import 'profile_page.dart';
import '../widgets/home_carousel.dart';
import 'favorites_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage>
    with SingleTickerProviderStateMixin {
  int _selectedIndex = 0;
  String _selectedCategory = 'All';
  bool _isLoading = false;
  final List<Product> _featuredProducts = [];
  final List<Product> _newProducts = [];
  late AnimationController _animationController;
  late Animation<double> _animation;

  static const mainColor = Color(0xFFFF87B2);
  static const secondaryColor = Color(0xFFFFC0D9);
  static const accentColor = Color(0xFFFFE5EE);

  @override
  void initState() {
    super.initState();
    _initializeAnimation();
    _loadProducts();
  }

  void _initializeAnimation() {
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
    _animation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    );
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  // Temporary method to create dummy products for testing
  List<Product> _createDummyProducts(String type, int count) {
    List<Product> products = [];
    for (int i = 1; i <= count; i++) {
      products.add(
        Product(
          id: type == 'featured' ? i : i + 100,
          name: type == 'featured' ? 'Featured Bouquet $i' : 'New Bouquet $i',
          description: 'This is a beautiful bouquet perfect for all occasions.',
          price:
              type == 'featured' ? 299000 + (i * 50000) : 199000 + (i * 50000),
          imageUrl:
              'https://images.unsplash.com/photo-1596438459194-f275f413d6ff?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=717&q=80',
          category: i % 4 == 0
              ? 'Wedding'
              : (i % 3 == 0
                  ? 'Birthday'
                  : (i % 2 == 0 ? 'Anniversary' : 'Graduation')),
        ),
      );
    }
    return products;
  }

  Future<void> _loadProducts() async {
    setState(() => _isLoading = true);
    try {
      // For testing purposes, create dummy products
      // In production, replace with actual database calls:
      // final featured = await DatabaseHelper.instance.getFeaturedProducts();
      // final newArrivals = await DatabaseHelper.instance.getNewArrivals();

      await Future.delayed(const Duration(milliseconds: 800));

      final featured = _createDummyProducts('featured', 5);
      final newArrivals = _createDummyProducts('new', 4);

      if (mounted) {
        setState(() {
          _featuredProducts.clear();
          _featuredProducts.addAll(featured);
          _newProducts.clear();
          _newProducts.addAll(newArrivals);
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
      // In production, use:
      // final products = await DatabaseHelper.instance.getProductsByCategory(category);

      await Future.delayed(const Duration(milliseconds: 500));

      final products = _createDummyProducts('new', 4)
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading products by category: $e')),
        );
      }
    }
  }

  void _handleCategorySelect(String category) {
    setState(() => _selectedCategory = category);
    _loadProductsByCategory(category);
  }

  void _handleProductTap(Product product) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ProductDetailPage(product: product),
      ),
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

  Future<void> _addToCart(Product product) async {
    try {
      _animationController.forward().then((_) {
        _animationController.reverse();
      });

      // In production, use:
      // final cartItem = CartItem(
      //   id: DateTime.now().millisecondsSinceEpoch,
      //   productId: product.id,
      //   name: product.name,
      //   price: product.price,
      //   imageUrl: product.imageUrl,
      //   quantity: 1,
      // );
      // await DatabaseHelper.instance.addToCart(cartItem, 1);

      await Future.delayed(const Duration(milliseconds: 300));

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${product.name} added to cart'),
          backgroundColor: Colors.green,
          duration: const Duration(seconds: 2),
          action: SnackBarAction(
            label: 'VIEW CART',
            textColor: Colors.white,
            onPressed: () => Navigator.pushNamed(context, '/cart'),
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error adding to cart: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildMainHome(),
          const CartPage(),
          const ChatPage(),
          const ProfilePage(),
        ],
      ),
      bottomNavigationBar: CustomBottomNavBar(
        selectedIndex: _selectedIndex,
        onItemSelected: (index) {
          setState(() => _selectedIndex = index);
        },
      ),
    );
  }

  Widget _buildMainHome() {
    return Scaffold(
      appBar: PreferredSize(
        preferredSize: const Size.fromHeight(130),
        child: Container(
          decoration: BoxDecoration(
            color: mainColor,
            boxShadow: [
              BoxShadow(
                color: Colors.grey.withOpacity(0.2),
                blurRadius: 4,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: SafeArea(
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.asset(
                          'assets/images/logo.png',
                          width: 40,
                          height: 40,
                          fit: BoxFit.cover,
                        ),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Text(
                          'Welcome to Bloom Bouquet',
                          style: TextStyle(
                            fontSize: 18,
                            height: 1.2,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                          maxLines: 2,
                        ),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16.0),
                  child: Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: _handleSearch,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            height: 40,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.1),
                                  blurRadius: 4,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.search,
                                    color: Colors.grey, size: 20),
                                SizedBox(width: 8),
                                Text(
                                  'Search bouquets...',
                                  style: TextStyle(
                                      color: Colors.grey, fontSize: 14),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        height: 40,
                        width: 40,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.1),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: IconButton(
                          icon: const Icon(Icons.favorite_border),
                          color: mainColor,
                          iconSize: 20,
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => const FavoritesPage(),
                              ),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      body: _isLoading ? _buildLoadingIndicator() : _buildBody(),
    );
  }

  Widget _buildLoadingIndicator() {
    return const Center(
      child: CircularProgressIndicator(
        valueColor: AlwaysStoppedAnimation<Color>(Colors.pink),
      ),
    );
  }

  Widget _buildBody() {
    return RefreshIndicator(
      onRefresh: _loadProducts,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          children: [
            Container(
              decoration: BoxDecoration(
                color: mainColor.withOpacity(0.1),
                borderRadius: const BorderRadius.vertical(
                  bottom: Radius.circular(24),
                ),
              ),
              child: const Column(
                children: [
                  SizedBox(height: 16),
                  HomeCarousel(),
                  SizedBox(height: 24),
                ],
              ),
            ),
            _buildCategories(),
            _buildFeaturedSection(),
            _buildNewArrivalsSection(),
          ],
        ),
      ),
    );
  }

  Widget _buildCategories() {
    final categories = [
      'All',
      'Wedding',
      'Birthday',
      'Anniversary',
      'Graduation'
    ];

    return SizedBox(
      height: 40,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: categories.length,
        itemBuilder: (context, index) {
          final category = categories[index];
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              label: Text(category),
              selected: _selectedCategory == category,
              onSelected: (selected) {
                if (selected) _handleCategorySelect(category);
              },
              selectedColor: Colors.pink,
              labelStyle: TextStyle(
                color:
                    _selectedCategory == category ? Colors.white : Colors.black,
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildFeaturedSection() {
    return Column(
      children: [
        _buildSectionHeader('Best Seller', onSeeAll: () {
          // TODO: Implement featured products page
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Best Seller page coming soon')),
          );
        }),
        SizedBox(
          height: 250, // Reduced height to match product card
          child: _featuredProducts.isEmpty
              ? const Center(child: Text('No featured products available'))
              : ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _featuredProducts.length,
                  itemBuilder: (context, index) =>
                      _buildFeaturedCard(_featuredProducts[index]),
                ),
        ),
      ],
    );
  }

  Widget _buildNewArrivalsSection() {
    return Column(
      children: [
        _buildSectionHeader('New Arrivals', onSeeAll: () {
          // TODO: Implement new arrivals page
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('New arrivals page coming soon')),
          );
        }),
        _newProducts.isEmpty
            ? const Center(
                child: Padding(
                  padding: EdgeInsets.all(20.0),
                  child: Text('No products available'),
                ),
              )
            : GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 0.75,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                ),
                itemCount: _newProducts.length,
                itemBuilder: (context, index) =>
                    _buildProductCard(_newProducts[index]),
              ),
      ],
    );
  }

  Widget _buildSectionHeader(String title, {VoidCallback? onSeeAll}) {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          TextButton(
            onPressed: onSeeAll,
            child: const Text(
              'See All',
              style: TextStyle(
                color: Colors.pink,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFeaturedCard(Product product) {
    return Card(
      elevation: 4,
      shadowColor: Colors.black.withOpacity(0.2),
      margin: const EdgeInsets.only(right: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(15),
      ),
      child: InkWell(
        onTap: () => _handleProductTap(product),
        borderRadius: BorderRadius.circular(15),
        child: SizedBox(
          width: 180, // Reduced width
          child: Column(
            children: [
              // Image section with fixed height
              SizedBox(
                height: 140,
                child: Stack(
                  children: [
                    ClipRRect(
                      borderRadius:
                          const BorderRadius.vertical(top: Radius.circular(15)),
                      child: SizedBox(
                        width: double.infinity,
                        child: Hero(
                          tag: 'product-${product.id}',
                          child: Image.network(
                            product.imageUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) =>
                                Container(
                              color: Colors.grey[200],
                              child: const Icon(Icons.error_outline),
                            ),
                          ),
                        ),
                      ),
                    ),
                    Positioned(
                      top: 4,
                      right: 4,
                      child: Container(
                        width: 28,
                        height: 28,
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
                        child: IconButton(
                          icon: const Icon(Icons.favorite_border, size: 14),
                          color: mainColor,
                          padding: EdgeInsets.zero,
                          onPressed: () {
                            // TODO: Implement add to favorites
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Content section
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            product.name,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Rp ${product.price.toInt()}',
                            style: const TextStyle(
                              fontSize: 12,
                              color: mainColor,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                      Container(
                        width: double.infinity,
                        height: 32,
                        decoration: BoxDecoration(
                          color: mainColor.withOpacity(0.9),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(6),
                            onTap: () {
                              _animationController.forward().then((_) {
                                _animationController.reverse();
                              });
                              _addToCart(product);
                            },
                            child: const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 8),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.shopping_cart_outlined,
                                    color: Colors.white,
                                    size: 14,
                                  ),
                                  SizedBox(width: 4),
                                  Text(
                                    'Add to Cart',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                    ),
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
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProductCard(Product product) {
    return Card(
      elevation: 4,
      shadowColor: Colors.black.withOpacity(0.2),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(15),
      ),
      child: InkWell(
        onTap: () => _handleProductTap(product),
        borderRadius: BorderRadius.circular(15),
        child: SizedBox(
          height: 250, // Reduced total height
          child: Column(
            children: [
              // Image section with smaller height
              SizedBox(
                height: 120, // Reduced height
                child: Stack(
                  children: [
                    ClipRRect(
                      borderRadius:
                          const BorderRadius.vertical(top: Radius.circular(15)),
                      child: SizedBox(
                        width: double.infinity,
                        child: Hero(
                          tag: 'product-${product.id}',
                          child: Image.network(
                            product.imageUrl,
                            height: 120,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) =>
                                Container(
                              color: Colors.grey[200],
                              child: const Icon(Icons.error_outline),
                            ),
                          ),
                        ),
                      ),
                    ),
                    // Favorite button
                    Positioned(
                      top: 4,
                      right: 4,
                      child: Container(
                        width: 28,
                        height: 28,
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
                        child: IconButton(
                          icon: const Icon(Icons.favorite_border, size: 14),
                          color: mainColor,
                          padding: EdgeInsets.zero,
                          onPressed: () {
                            // TODO: Implement add to favorites
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Content section
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            product.name,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Rp ${product.price.toInt()}',
                            style: const TextStyle(
                              fontSize: 12,
                              color: mainColor,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                      // Add to cart button
                      Container(
                        width: double.infinity,
                        height: 32,
                        decoration: BoxDecoration(
                          color: mainColor.withOpacity(0.9),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(6),
                            onTap: () {
                              _animationController.forward().then((_) {
                                _animationController.reverse();
                              });
                              _addToCart(product);
                            },
                            child: const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 8),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.shopping_cart_outlined,
                                    color: Colors.white,
                                    size: 14,
                                  ),
                                  SizedBox(width: 4),
                                  Text(
                                    'Add to Cart',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                    ),
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
            ],
          ),
        ),
      ),
    );
  }
}
