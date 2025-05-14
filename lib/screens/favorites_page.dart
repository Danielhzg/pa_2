import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:line_icons/line_icons.dart';
import 'dart:ui'; // Add this import for BackdropFilter
import '../models/product.dart';
import '../providers/favorite_provider.dart';
import '../utils/image_url_helper.dart';
import '../widgets/loading_overlay.dart';

class FavoritesPage extends StatefulWidget {
  const FavoritesPage({super.key});

  @override
  State<FavoritesPage> createState() => _FavoritesPageState();
}

class _FavoritesPageState extends State<FavoritesPage>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _controller.forward();

    // Refresh favorites when page opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshFavorites();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _refreshFavorites() async {
    setState(() {
      _isLoading = true;
    });

    final favoriteProvider =
        Provider.of<FavoriteProvider>(context, listen: false);
    await favoriteProvider.loadFavorites();

    setState(() {
      _isLoading = false;
    });
  }

  String formatPrice(double price) {
    return 'Rp${price.toInt().toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (match) => '${match[1]}.')}';
  }

  @override
  Widget build(BuildContext context) {
    final favoriteProvider = Provider.of<FavoriteProvider>(context);
    final favorites = favoriteProvider.favorites;

    return Scaffold(
      backgroundColor: const Color(0xFFFAFAFA),
      appBar: AppBar(
        backgroundColor: const Color(0xFFFF87B2),
        title: const Text(
          'My Favorites',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
        elevation: 0,
        actions: [
          if (favorites.isNotEmpty)
            IconButton(
              icon: const Icon(LineIcons.trash, color: Colors.white),
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Clear all favorites?'),
                    content: const Text(
                        'This will remove all products from your favorites.'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.of(context).pop(),
                        child: const Text('Cancel'),
                      ),
                      TextButton(
                        onPressed: () async {
                          try {
                            // Show loading
                            Navigator.of(context).pop();
                            setState(() {
                              _isLoading = true;
                            });

                            // Clear all favorites
                            await favoriteProvider.clearAllFavorites();

                            if (!mounted) return;

                            // Show success message
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('All favorites cleared'),
                                behavior: SnackBarBehavior.floating,
                                backgroundColor: Colors.red,
                              ),
                            );
                          } catch (e) {
                            if (!mounted) return;

                            // Show error message
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('Error: $e'),
                                behavior: SnackBarBehavior.floating,
                                backgroundColor: Colors.red,
                              ),
                            );
                          } finally {
                            if (mounted) {
                              setState(() {
                                _isLoading = false;
                              });
                            }
                          }
                        },
                        child: const Text('Clear All',
                            style: TextStyle(color: Colors.red)),
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
      body: RefreshIndicator(
        color: const Color(0xFFFF87B2),
        onRefresh: _refreshFavorites,
        child: _isLoading
            ? const Center(
                child: CircularProgressIndicator(color: Color(0xFFFF87B2)))
            : favorites.isEmpty
                ? _buildEmptyState()
                : _buildFavoritesList(favorites, favoriteProvider),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const SizedBox(height: 40),
            const Icon(
              LineIcons.heartBroken,
              size: 100,
              color: Color(0xFFFFCCDD),
            ),
            const SizedBox(height: 24),
            const Text(
              'No Favorites Yet',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: Color(0xFFFF87B2),
              ),
            ),
            const SizedBox(height: 12),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 40),
              child: Text(
                'Start adding products to your favorites to see them here',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.grey,
                ),
              ),
            ),
            const SizedBox(height: 40),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFFF87B2),
                padding:
                    const EdgeInsets.symmetric(horizontal: 40, vertical: 15),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
              ),
              child: const Text(
                'Explore Products',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildFavoritesList(
      List<Product> favorites, FavoriteProvider favoriteProvider) {
    return AnimatedList(
      key: GlobalKey<AnimatedListState>(),
      padding: const EdgeInsets.all(12),
      initialItemCount: favorites.length,
      physics: const AlwaysScrollableScrollPhysics(),
      itemBuilder: (context, index, animation) {
        final product = favorites[index];
        return SlideTransition(
          position: Tween<Offset>(begin: const Offset(1, 0), end: Offset.zero)
              .animate(CurvedAnimation(
            parent: animation,
            curve: Curves.easeOut,
          )),
          child: FadeTransition(
            opacity: animation,
            child: _buildFavoriteItem(context, product, favoriteProvider),
          ),
        );
      },
    );
  }

  Widget _buildFavoriteItem(BuildContext context, Product product,
      FavoriteProvider favoriteProvider) {
    // Calculate price
    final double finalPrice = product.isOnSale
        ? product.price * (100 - product.discount) / 100
        : product.price;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Dismissible(
        key: Key('favorite_${product.id}'),
        background: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          decoration: BoxDecoration(
            color: Colors.red.shade400,
            borderRadius: BorderRadius.circular(16),
          ),
          alignment: Alignment.centerRight,
          child: const Icon(
            Icons.delete_outline,
            color: Colors.white,
            size: 30,
          ),
        ),
        direction: DismissDirection.endToStart,
        onDismissed: (direction) async {
          // Show animation when removing from favorites
          _showHeartAnimation(context, false);

          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('${product.name} removed from favorites'),
              behavior: SnackBarBehavior.floating,
              action: SnackBarAction(
                label: 'UNDO',
                onPressed: () {
                  // Re-add to favorites
                  favoriteProvider.toggleFavorite(product);

                  // Show heart animation
                  _showHeartAnimation(context, true);
                },
              ),
            ),
          );

          // Remove from favorites
          await favoriteProvider.toggleFavorite(product);
        },
        child: GestureDetector(
          onTap: () {
            Navigator.pushNamed(
              context,
              '/product_detail',
              arguments: product,
            );
          },
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 1,
                  blurRadius: 10,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Stack(
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Hero(
                        tag: 'favorite-${product.id}',
                        child: SizedBox(
                          width: 120,
                          height: 120,
                          child: Image.network(
                            ImageUrlHelper.buildImageUrl(product.imageUrl),
                            fit: BoxFit.cover,
                            errorBuilder: (ctx, _, __) => Container(
                              color: Colors.grey[200],
                              child: const Icon(Icons.image_not_supported,
                                  color: Colors.grey),
                            ),
                          ),
                        ),
                      ),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                product.name,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                formatPrice(finalPrice),
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                  color: Color(0xFFFF87B2),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Icon(
                                    LineIcons.tag,
                                    size: 14,
                                    color: Colors.grey[600],
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    product.categoryName,
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey[600],
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
                    top: 8,
                    right: 8,
                    child: TweenAnimationBuilder<double>(
                      tween: Tween<double>(
                        begin: 0.8,
                        end: 1.0,
                      ),
                      duration: const Duration(milliseconds: 300),
                      curve: Curves.elasticOut,
                      builder: (context, value, child) {
                        return Transform.scale(
                          scale: value,
                          child: InkWell(
                            onTap: () async {
                              // Show heart animation
                              _showHeartAnimation(context, false);

                              // Toggle favorite
                              await favoriteProvider.toggleFavorite(product);
                            },
                            child: Container(
                              padding: const EdgeInsets.all(6),
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
                              child: const Icon(
                                Icons.favorite,
                                color: Color(0xFFFF87B2),
                                size: 18,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  if (product.isOnSale)
                    Positioned(
                      top: 0,
                      left: 0,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: const BoxDecoration(
                          color: Colors.red,
                          borderRadius: BorderRadius.only(
                            topLeft: Radius.circular(16),
                            bottomRight: Radius.circular(16),
                          ),
                        ),
                        child: Text(
                          '-${product.discount}%',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
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

  // Show a heart animation when favoriting/unfavoriting
  void _showHeartAnimation(BuildContext context, bool isAdding) {
    OverlayState overlayState = Overlay.of(context);
    final size = MediaQuery.of(context).size;

    OverlayEntry? entry;
    entry = OverlayEntry(
      builder: (context) {
        return Positioned(
          left: size.width / 2 - 50,
          top: size.height / 2 - 50,
          child: Material(
            color: Colors.transparent,
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
                    child: Icon(
                      isAdding ? Icons.favorite : Icons.favorite_border,
                      color: const Color(0xFFFF87B2),
                      size: 100,
                    ),
                  ),
                );
              },
            ),
          ),
        );
      },
    );

    overlayState.insert(entry);
  }
}
