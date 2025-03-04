import 'package:flutter/material.dart';
import '../models/product.dart';
import '../widgets/product_search.dart';
import '../utils/database_helper.dart';
import '../models/cart_item.dart';
import 'product_detail_page.dart';

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
      appBar: AppBar(
        title: const Text(
          'Bloom Bouquet',
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: Colors.pink,
          ),
        ),
        elevation: 0,
        backgroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_cart, color: Colors.pink),
            onPressed: () {
              Navigator.pushNamed(context, '/cart');
            },
          ),
          IconButton(
            icon: const Icon(Icons.person, color: Colors.pink),
            onPressed: () {
              // Navigator.pushNamed(context, '/profile');
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Profile feature coming soon')),
              );
            },
          ),
        ],
      ),
      body: _isLoading ? _buildLoadingIndicator() : _buildBody(),
      bottomNavigationBar: _buildBottomNavBar(),
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
            _buildSearchBar(),
            _buildCategories(),
            _buildFeaturedSection(),
            _buildNewArrivalsSection(),
          ],
        ),
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: InkWell(
        onTap: _handleSearch,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          height: 50,
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey),
            borderRadius: BorderRadius.circular(25),
          ),
          child: const Row(
            children: [
              Icon(Icons.search, color: Colors.grey),
              SizedBox(width: 8),
              Text(
                'Search bouquets...',
                style: TextStyle(color: Colors.grey),
              ),
            ],
          ),
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
        _buildSectionHeader('Featured Bouquets', onSeeAll: () {
          // TODO: Implement featured products page
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Featured products page coming soon')),
          );
        }),
        SizedBox(
          height: 280,
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
    return GestureDetector(
      onTap: () => _handleProductTap(product),
      child: Container(
        width: 200,
        margin: const EdgeInsets.only(right: 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[300]!),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Hero(
              tag: 'product-${product.id}',
              child: Container(
                height: 180,
                decoration: BoxDecoration(
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(12)),
                  color: Colors.grey[200],
                  image: DecorationImage(
                    image: NetworkImage(product.imageUrl),
                    fit: BoxFit.cover,
                    onError: (exception, stackTrace) {
                      print('Error loading image: $exception');
                    },
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Rp ${product.price.toInt().toString()}',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Colors.pink,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      ScaleTransition(
                        scale: _animation,
                        child: IconButton(
                          icon: const Icon(Icons.add_shopping_cart,
                              color: Colors.pink),
                          onPressed: () => _addToCart(product),
                        ),
                      ),
                      TextButton(
                        onPressed: () => _handleProductTap(product),
                        child: const Text(
                          'Details',
                          style: TextStyle(color: Colors.pink),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProductCard(Product product) {
    return GestureDetector(
      onTap: () => _handleProductTap(product),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[300]!),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Hero(
                tag: 'product-${product.id}',
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius:
                        const BorderRadius.vertical(top: Radius.circular(12)),
                    color: Colors.grey[200],
                    image: DecorationImage(
                      image: NetworkImage(product.imageUrl),
                      fit: BoxFit.cover,
                      onError: (exception, stackTrace) {
                        print('Error loading image: $exception');
                      },
                    ),
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Rp ${product.price.toInt().toString()}',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Colors.pink,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  ScaleTransition(
                    scale: _animation,
                    child: IconButton(
                      icon: const Icon(Icons.add_shopping_cart,
                          color: Colors.pink),
                      onPressed: () => _addToCart(product),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  BottomNavigationBar _buildBottomNavBar() {
    return BottomNavigationBar(
      currentIndex: _selectedIndex,
      selectedItemColor: Colors.pink,
      unselectedItemColor: Colors.grey,
      items: const [
        BottomNavigationBarItem(
          icon: Icon(Icons.home),
          label: 'Home',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.favorite),
          label: 'Favorites',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.shopping_bag),
          label: 'Orders',
        ),
      ],
      onTap: (index) {
        setState(() => _selectedIndex = index);
        if (index != 0) {
          // Show coming soon message for unimplemented tabs
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('This feature is coming soon')),
          );
        }
      },
    );
  }
}
