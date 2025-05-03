import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/cart_item.dart';
import '../utils/image_url_helper.dart';
import '../providers/cart_provider.dart';
import 'package:line_icons/line_icons.dart';
import '../models/product.dart';
import './checkout_page.dart';

class CartPage extends StatefulWidget {
  const CartPage({super.key});

  @override
  State<CartPage> createState() => _CartPageState();
}

class _CartPageState extends State<CartPage>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  // Colors
  static const Color primaryColor = Color.fromRGBO(255, 135, 178, 1);
  static const Color accentColor = Color(0xFFFFE5EE);
  static const Color darkTextColor = Color(0xFF333333);

  // Format price to match product detail format
  String formatPrice(double price) {
    return 'Rp${price.toInt().toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (match) => '${match[1]}.')}';
  }

  // Helper method to get full image URL
  String getFullImageUrl(String imageUrl) {
    return ImageUrlHelper.buildImageUrl(imageUrl);
  }

  @override
  void initState() {
    super.initState();
    _loadCartItems();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _loadCartItems() async {
    try {
      // Memuat data cart dari CartProvider
      await Provider.of<CartProvider>(context, listen: false)
          .loadCartFromStorage();
    } catch (e) {
      print('Error loading cart items: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading cart: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Shopping Cart',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: darkTextColor,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: darkTextColor),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: Consumer<CartProvider>(
        builder: (context, cartProvider, child) {
          if (cartProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (cartProvider.items.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    LineIcons.shoppingCart,
                    size: 80,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Your cart is empty',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: darkTextColor,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Explore our collections and add some items',
                    style: TextStyle(
                      color: Colors.grey,
                    ),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 24,
                        vertical: 12,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    onPressed: () {
                      Navigator.pushReplacementNamed(context, '/home');
                    },
                    child: const Text(
                      'Continue Shopping',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            );
          }

          return Column(
            children: [
              // Select All Row
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: [
                    Checkbox(
                      value: cartProvider.allItemsSelected,
                      onChanged: (value) {
                        cartProvider.selectAll(value ?? false);
                      },
                      activeColor: primaryColor,
                    ),
                    const Text(
                      'Select All',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                      ),
                    ),
                    const Spacer(),
                    TextButton.icon(
                      onPressed: cartProvider.items.isNotEmpty
                          ? () => _showClearCartDialog(context, cartProvider)
                          : null,
                      icon: const Icon(LineIcons.alternateTrash,
                          color: Colors.red),
                      label: const Text(
                        'Clear Cart',
                        style: TextStyle(color: Colors.red),
                      ),
                    )
                  ],
                ),
              ),

              const Divider(),

              // Cart Items List
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: cartProvider.items.length,
                  itemBuilder: (context, index) {
                    final item = cartProvider.items[index];
                    return _buildCartItemCard(item, cartProvider);
                  },
                ),
              ),

              // Bottom Checkout Bar
              _buildCheckoutBar(context, cartProvider),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCheckoutBar(BuildContext context, CartProvider cartProvider) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.2),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, -1),
          ),
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      const Text(
                        'Subtotal: ',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey,
                        ),
                      ),
                      Text(
                        formatPrice(cartProvider.totalAmount),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: primaryColor,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${cartProvider.selectedItemCount} items selected',
                    style: const TextStyle(
                      color: Colors.grey,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
            ElevatedButton(
              onPressed: cartProvider.selectedItemCount > 0
                  ? () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const CheckoutPage(),
                        ),
                      )
                  : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 12,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                disabledBackgroundColor: Colors.grey.shade300,
              ),
              child: Text(
                cartProvider.selectedItemCount > 0
                    ? 'Checkout (${cartProvider.selectedItemCount})'
                    : 'Select Items',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyCart() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Image.asset(
            'assets/images/empty_cart.png',
            height: 150,
            width: 150,
            errorBuilder: (context, error, stackTrace) {
              return Icon(
                LineIcons.shoppingCart,
                size: 100,
                color: Colors.grey[300],
              );
            },
          ),
          const SizedBox(height: 20),
          const Text(
            'Your Cart is Empty',
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: darkTextColor,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Add beautiful bouquets to your cart',
            style: TextStyle(
              fontSize: 16,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 30),
          ElevatedButton(
            onPressed: () => Navigator.pushReplacementNamed(context, '/home'),
            style: ElevatedButton.styleFrom(
              backgroundColor: primaryColor,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 15),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(30),
              ),
              elevation: 2,
            ),
            child: const Text(
              'Start Shopping',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  void _showClearCartDialog(BuildContext context, CartProvider cartProvider) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        title: const Text('Clear Cart'),
        content: const Text(
            'Are you sure you want to remove all items from your cart?'),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
            },
            child: const Text(
              'Cancel',
              style: TextStyle(color: Colors.grey),
            ),
          ),
          ElevatedButton(
            onPressed: () {
              cartProvider.clear();
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Cart cleared successfully'),
                  backgroundColor: Colors.green,
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
            ),
            child: const Text('Clear'),
          ),
        ],
      ),
    );
  }

  Widget _buildCartList(CartProvider cartProvider) {
    final cartItems = cartProvider.items;

    return RefreshIndicator(
      onRefresh: _loadCartItems,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: cartItems.length + 1, // +1 for summary card
        itemBuilder: (context, index) {
          if (index == cartItems.length) {
            return _buildSummaryCard(cartItems);
          }
          final item = cartItems[index];
          return _buildCartItemCard(item, cartProvider);
        },
      ),
    );
  }

  Widget _buildSummaryCard(List<CartItem> cartItems) {
    int totalItems = 0;
    for (var item in cartItems) {
      totalItems += item.quantity;
    }

    return Card(
      margin: const EdgeInsets.only(top: 16, bottom: 80),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(15),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Cart Summary',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: darkTextColor,
              ),
            ),
            const SizedBox(height: 16),
            _buildSummaryRow('Total Items', '$totalItems items'),
            const Divider(),
            _buildSummaryRow(
                'Subtotal', formatPrice(_calculateSubtotal(cartItems))),
            _buildSummaryRow('Shipping', 'Free'),
            const Divider(),
            _buildSummaryRow(
              'Total',
              formatPrice(_calculateTotal(cartItems)),
              isTotal: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, {bool isTotal = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: isTotal ? 16 : 14,
              fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
              color: isTotal ? primaryColor : darkTextColor,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: isTotal ? 18 : 14,
              fontWeight: isTotal ? FontWeight.bold : FontWeight.w600,
              color: isTotal ? primaryColor : darkTextColor,
            ),
          ),
        ],
      ),
    );
  }

  double _calculateSubtotal(List<CartItem> items) {
    double subtotal = 0;
    for (var item in items) {
      subtotal += item.total;
    }
    return subtotal;
  }

  double _calculateTotal(List<CartItem> items) {
    // For now, total is same as subtotal since shipping is free
    return _calculateSubtotal(items);
  }

  Widget _buildCartItemCard(CartItem item, CartProvider cartProvider) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(15),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            // Selection Checkbox
            Checkbox(
              value: item.isSelected,
              onChanged: (bool? value) {
                cartProvider.toggleItemSelection(int.parse(item.productId));
              },
              activeColor: primaryColor,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            // Product Image
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.network(
                getFullImageUrl(item.imageUrl),
                width: 80,
                height: 80,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) {
                  return Container(
                    width: 80,
                    height: 80,
                    color: Colors.grey[200],
                    child: Icon(
                      LineIcons.image,
                      color: Colors.grey[400],
                    ),
                  );
                },
              ),
            ),
            const SizedBox(width: 12),
            // Product Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: darkTextColor,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    formatPrice(item.price),
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: primaryColor,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Quantity controls
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            _buildQuantityButton(
                              icon: Icons.remove,
                              onTap: () {
                                if (item.quantity > 1) {
                                  cartProvider.updateQuantity(
                                    int.parse(item.productId),
                                    item.quantity - 1,
                                  );
                                }
                              },
                            ),
                            SizedBox(
                              width: 40,
                              child: Text(
                                '${item.quantity}',
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                            _buildQuantityButton(
                              icon: Icons.add,
                              onTap: () {
                                cartProvider.updateQuantity(
                                  int.parse(item.productId),
                                  item.quantity + 1,
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                      // Delete button
                      IconButton(
                        icon: const Icon(
                          LineIcons.trash,
                          color: Colors.red,
                          size: 22,
                        ),
                        onPressed: () {
                          _showRemoveItemDialog(cartProvider, item);
                        },
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

  Widget _buildQuantityButton({
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: accentColor,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, size: 18, color: primaryColor),
      ),
    );
  }

  void _showRemoveItemDialog(CartProvider cartProvider, CartItem item) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          title: const Text('Remove Item'),
          content: Text(
              'Are you sure you want to remove ${item.name} from your cart?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text(
                'Cancel',
                style: TextStyle(color: Colors.grey),
              ),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                cartProvider.removeItem(item.productId);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('${item.name} removed from cart'),
                    backgroundColor: Colors.green,
                    behavior: SnackBarBehavior.floating,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    action: SnackBarAction(
                      label: 'UNDO',
                      textColor: Colors.white,
                      onPressed: () {
                        final product = Product(
                          id: int.parse(item.productId),
                          name: item.name,
                          price: item.price,
                          imageUrl: item.imageUrl,
                          description: '',
                          stock: 1,
                          categoryName: '',
                          isOnSale: false,
                          discount: 0,
                          categoryId: 0,
                          rating: 0.0,
                          isFeatured: false,
                        );
                        cartProvider.addToCart(product, item.quantity);
                      },
                    ),
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
              ),
              child: const Text('Remove'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _checkout(CartProvider cartProvider) async {
    if (cartProvider.items.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Your cart is empty'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    // Check if any items are selected
    if (!cartProvider.items.any((item) => item.isSelected)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select at least one item to checkout'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    // Navigate to enhanced checkout page
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const CheckoutPage(),
      ),
    );
  }
}
