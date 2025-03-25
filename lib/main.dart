import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'services/auth_service.dart';
import 'screens/splash_screen.dart';
import 'login_page.dart';
import 'register.dart';
import 'screens/home_page.dart';
import 'screens/product_detail_page.dart';
import 'screens/cart_page.dart';
import 'screens/chat_page.dart';
import 'screens/profile_page.dart';

void main() {
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
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
          ),
        ),
        home: const AppLoading(),
        routes: {
          '/login': (context) => const LoginPage(),
          '/register': (context) => const RegisterPage(),
          '/home': (context) => const HomePage(),
          '/cart': (context) => const CartPage(),
          '/chat': (context) => const ChatPage(),
          '/profile': (context) => const ProfilePage(),
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
    return ChangeNotifierProvider<AuthService>(
      create: (_) => AuthService(),
      lazy: false,
      child: child,
    );
  }
}

// Loading screen waiting for auth initialization
class AppLoading extends StatelessWidget {
  const AppLoading({super.key});

  @override
  Widget build(BuildContext context) {
    // Access the auth service without listening for changes
    final authService = Provider.of<AuthService>(context, listen: false);

    return FutureBuilder(
      future: authService.initializationFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return Scaffold(
            backgroundColor: Colors.white,
            body: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Image.asset(
                    'assets/images/logo.png',
                    width: 150,
                    height: 150,
                  ),
                  const SizedBox(height: 30),
                  const CircularProgressIndicator(
                    color: Color(0xFFFF87B2),
                  ),
                ],
              ),
            ),
          );
        }

        // When initialization completes, show the SplashScreen
        return const SplashScreen();
      },
    );
  }
}
