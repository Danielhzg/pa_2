import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:line_icons/line_icons.dart';
import '../models/product.dart';
import '../providers/favorite_provider.dart';
import '../utils/image_url_helper.dart';

class FavoritePopup extends StatefulWidget {
  const FavoritePopup({super.key});

  @override
  State<FavoritePopup> createState() => _FavoritePopupState();
}

class _FavoritePopupState extends State<FavoritePopup> {
  @override
  void initState() {
    super.initState();
    // Refresh favorites when popup opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final favoriteProvider =
          Provider.of<FavoriteProvider>(context, listen: false);
      favoriteProvider.loadFavorites();
    });
  }

  String formatPrice(double price) {
    return 'Rp${price.toInt().toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (match) => '${match[1]}.')}';
  }

  @override
  Widget build(BuildContext context) {
    final favoriteProvider = Provider.of<FavoriteProvider>(context);
    final favorites = favoriteProvider.favorites;
    final isLoading = favoriteProvider.isLoading;

    return Dialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20.0),
      ),
      elevation: 0,
      backgroundColor: Colors.transparent,
      child: contentBox(context, favorites, favoriteProvider, isLoading),
    );
  }

  Widget contentBox(BuildContext context, List<Product> favorites,
      FavoriteProvider favoriteProvider, bool isLoading) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            spreadRadius: 3,
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'My Favorites',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFFFF87B2),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close, color: Colors.grey),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          ),
          const Divider(),
          if (isLoading)
            const Padding(
              padding: EdgeInsets.all(20.0),
              child: CircularProgressIndicator(color: Color(0xFFFF87B2)),
            )
          else if (favorites.isEmpty)
            Padding(
              padding:
                  const EdgeInsets.symmetric(vertical: 30.0, horizontal: 20.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    LineIcons.heartBroken,
                    size: 50,
                    color: Color(0xFFFFCCDD),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'No favorites yet',
                    style: TextStyle(
                      color: Color(0xFFFF87B2),
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Items you like will appear here',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop();
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFF87B2),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: const Text('Explore Products'),
                  ),
                ],
              ),
            )
          else
            ConstrainedBox(
              constraints: BoxConstraints(
                maxHeight: MediaQuery.of(context).size.height * 0.5,
              ),
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: favorites.length,
                itemBuilder: (context, index) {
                  final product = favorites[index];
                  return Dismissible(
                    key: Key('favorite_${product.id}'),
                    direction: DismissDirection.endToStart,
                    background: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      alignment: Alignment.centerRight,
                      child: const Icon(Icons.delete, color: Colors.white),
                    ),
                    onDismissed: (direction) {
                      favoriteProvider.toggleFavorite(product);

                      // Show feedback
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content:
                              Text('${product.name} removed from favorites'),
                          backgroundColor: Colors.red.shade400,
                          behavior: SnackBarBehavior.floating,
                          margin: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          duration: const Duration(seconds: 2),
                          action: SnackBarAction(
                            label: 'UNDO',
                            textColor: Colors.white,
                            onPressed: () {
                              favoriteProvider.toggleFavorite(product);
                            },
                          ),
                        ),
                      );
                    },
                    child: Card(
                      margin: const EdgeInsets.symmetric(vertical: 6),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                        leading: ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.network(
                            ImageUrlHelper.buildImageUrl(product.imageUrl),
                            width: 60,
                            height: 60,
                            fit: BoxFit.cover,
                            errorBuilder: (ctx, error, _) => Container(
                              width: 60,
                              height: 60,
                              color: Colors.grey[200],
                              child: const Icon(LineIcons.imageAlt,
                                  color: Colors.grey),
                            ),
                          ),
                        ),
                        title: Text(
                          product.name,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        subtitle: Text(
                          product.isOnSale
                              ? formatPrice(product.price *
                                  (100 - product.discount) /
                                  100)
                              : formatPrice(product.price),
                          style: const TextStyle(
                            color: Color(0xFFFF87B2),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        trailing: IconButton(
                          icon: const Icon(
                            Icons.favorite,
                            color: Color(0xFFFF87B2),
                          ),
                          onPressed: () async {
                            // Show visual feedback that we're processing
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Removing from favorites...'),
                                backgroundColor: Colors.grey,
                                duration: Duration(milliseconds: 500),
                              ),
                            );

                            final success =
                                await favoriteProvider.toggleFavorite(product);

                            if (!mounted) return;

                            // Show feedback
                            ScaffoldMessenger.of(context).hideCurrentSnackBar();
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(success
                                    ? 'Added to favorites'
                                    : 'Removed from favorites'),
                                backgroundColor: success
                                    ? const Color(0xFFFF87B2)
                                    : Colors.grey.shade700,
                                behavior: SnackBarBehavior.floating,
                                margin:
                                    const EdgeInsets.fromLTRB(20, 0, 20, 20),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                duration: const Duration(seconds: 2),
                              ),
                            );
                          },
                        ),
                        onTap: () {
                          Navigator.of(context).pop();
                          Navigator.pushNamed(
                            context,
                            '/product-detail',
                            arguments: {'product': product},
                          );
                        },
                      ),
                    ),
                  );
                },
              ),
            ),
          const SizedBox(height: 10),
          if (favorites.isNotEmpty)
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.of(context).pop();
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFFF87B2),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Continue Shopping',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
