import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'services/auth_service.dart';
import 'services/payment_service.dart'; // Import PaymentService
import 'services/order_service.dart'; // Import OrderService
import 'services/notification_service.dart'; // Add notification service
import 'screens/splash_screen.dart';
import 'login_page.dart';
import 'register.dart';
import 'screens/home_page.dart' as home;
import 'screens/product_detail_page.dart';
import 'screens/cart_page.dart';
import 'screens/chat_page.dart';
import 'screens/profile_page.dart';
import 'screens/favorites_page.dart'; // Import favorites page
import 'screens/my_orders_screen.dart'; // Add my orders screen
import 'screens/order_detail_screen.dart'; // Add order detail screen
import 'screens/order_tracking_screen.dart'; // Add order tracking screen
import 'screens/notifications_page.dart'; // Add notifications page
import 'screens/privacy_security_screen.dart'; // Add privacy security screen
import 'screens/help_support_screen.dart'; // Add help support screen
import 'providers/cart_provider.dart'; // Import CartProvider
import 'providers/delivery_provider.dart'; // Import DeliveryProvider
import 'providers/favorite_provider.dart'; // Import FavoriteProvider

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: Colors.white,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );

  // Initialize notification service
  NotificationService();

  // Initialize payment service
  try {
    await PaymentService().initialize();
  } catch (e) {
    debugPrint('Error initializing payment service: $e');
  }

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ProviderConfig(
      child: MaterialApp(
        title: 'Bloom Bouquet',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          primarySwatch: Colors.pink,
          colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(0xFFFF87B2),
            brightness: Brightness.light,
          ),
          useMaterial3: true,
          scaffoldBackgroundColor: Colors.white,
          appBarTheme: const AppBarTheme(
            backgroundColor: Colors.white,
            elevation: 0,
            centerTitle: true,
            iconTheme: IconThemeData(color: Color(0xFFFF87B2)),
            titleTextStyle: TextStyle(
              color: Color(0xFFFF87B2),
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
            toolbarHeight: 60, // Make header slightly taller
            shadowColor: Colors.black12, // Add subtle shadow
            surfaceTintColor: Colors.transparent,
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.symmetric(vertical: 16),
              elevation: 2, // Add slight elevation to buttons
            ),
          ),
        ),
        home: const SplashScreen(),
        routes: {
          '/login': (context) => const LoginPage(),
          '/register': (context) => const RegisterPage(),
          '/home': (context) => const home.HomePage(),
          '/cart': (context) => const CartPage(),
          '/chat': (context) => const ChatPage(showBottomNav: true),
          '/profile': (context) => const ProfilePage(),
          '/favorites': (context) =>
              const FavoritesPage(), // Add favorites route
          '/my-orders': (context) =>
              const MyOrdersScreen(), // Add my orders route
          '/notifications': (context) =>
              const NotificationsPage(), // Add notifications route
          '/privacy-security': (context) =>
              const PrivacySecurityScreen(), // Add privacy security route
          '/help-support': (context) =>
              const HelpSupportScreen(), // Add help support route
        },
        onGenerateRoute: (settings) {
          if (settings.name == '/product-detail') {
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (context) => ProductDetailPage(
                product: args?['product'],
              ),
            );
          }
          if (settings.name == '/order-detail') {
            final orderId = settings.arguments as String;
            return MaterialPageRoute(
              builder: (context) => OrderDetailScreen(
                orderId: orderId,
              ),
            );
          }
          if (settings.name == '/order-tracking') {
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (context) => OrderTrackingScreen(
                orderId: args?['orderId'] ?? '',
                initialOrder: args?['order'],
              ),
            );
          }
          return null;
        },
        onUnknownRoute: (settings) {
          return MaterialPageRoute(
            builder: (context) => Scaffold(
              appBar: AppBar(
                title: const Text('Error'),
              ),
              body: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline,
                        color: Color(0xFFFF87B2), size: 64),
                    const SizedBox(height: 16),
                    Text(
                      'Page not found: ${settings.name}',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () => Navigator.of(context)
                          .pushNamedAndRemoveUntil('/home', (route) => false),
                      child: const Text('Return Home'),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

// Provider configuration wrapper
class ProviderConfig extends StatelessWidget {
  final Widget child;

  const ProviderConfig({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthService>(
          create: (_) => AuthService(),
          lazy: false,
        ),
        ChangeNotifierProvider<CartProvider>(
          create: (_) => CartProvider(),
        ),
        ChangeNotifierProvider<DeliveryProvider>(
          create: (_) => DeliveryProvider(),
        ),
        ChangeNotifierProxyProvider<AuthService, FavoriteProvider>(
          create: (context) => FavoriteProvider(context.read<AuthService>()),
          update: (context, authService, previous) =>
              previous ?? FavoriteProvider(authService),
        ),
        ChangeNotifierProxyProvider2<AuthService, CartProvider, OrderService>(
          create: (context) => OrderService(context.read<AuthService>()),
          update: (context, authService, cartProvider, previous) {
            final orderService = previous ?? OrderService(authService);
            orderService.setCartProvider(cartProvider);
            return orderService;
          },
          lazy: false,
        ),
        ChangeNotifierProvider<NotificationService>(
          create: (_) => NotificationService(),
        ),
      ],
      child: Consumer<OrderService>(
        builder: (context, orderService, child) {
          // Initialize order expiration checker after build
          WidgetsBinding.instance.addPostFrameCallback((_) {
            orderService.initExpirationChecker();
          });
          return child!;
        },
        child: child,
      ),
    );
  }
}
