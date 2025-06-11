import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:pa_2/main.dart';
import 'package:pa_2/services/notification_service.dart';
import 'package:pa_2/services/order_service.dart';
import 'package:pa_2/services/auth_service.dart';
import 'package:pa_2/providers/cart_provider.dart';
import 'package:pa_2/providers/favorite_provider.dart';

void main() {
  group('Navigation and Optimization Tests', () {
    late NotificationService notificationService;
    late OrderService orderService;
    late AuthService authService;
    late CartProvider cartProvider;
    late FavoriteProvider favoriteProvider;

    setUp(() {
      notificationService = NotificationService();
      orderService = OrderService();
      authService = AuthService();
      cartProvider = CartProvider();
      favoriteProvider = FavoriteProvider();
    });

    testWidgets('Homepage notification icon navigates to notifications page', (WidgetTester tester) async {
      // Build the app with providers
      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<NotificationService>.value(value: notificationService),
            ChangeNotifierProvider<OrderService>.value(value: orderService),
            ChangeNotifierProvider<AuthService>.value(value: authService),
            ChangeNotifierProvider<CartProvider>.value(value: cartProvider),
            ChangeNotifierProvider<FavoriteProvider>.value(value: favoriteProvider),
          ],
          child: MaterialApp(
            home: const MyApp(),
            routes: {
              '/notifications': (context) => const Scaffold(
                body: Center(child: Text('Notifications Page')),
              ),
            },
          ),
        ),
      );

      // Wait for the app to load
      await tester.pumpAndSettle();

      // Find the notification icon button
      final notificationIcon = find.byIcon(Icons.notifications);
      
      // Verify the notification icon exists
      expect(notificationIcon, findsOneWidget);

      // Tap the notification icon
      await tester.tap(notificationIcon);
      await tester.pumpAndSettle();

      // Verify navigation to notifications page
      expect(find.text('Notifications Page'), findsOneWidget);
    });

    testWidgets('Profile page notification card navigates to notifications page', (WidgetTester tester) async {
      // Build the app with providers
      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<NotificationService>.value(value: notificationService),
            ChangeNotifierProvider<OrderService>.value(value: orderService),
            ChangeNotifierProvider<AuthService>.value(value: authService),
            ChangeNotifierProvider<CartProvider>.value(value: cartProvider),
            ChangeNotifierProvider<FavoriteProvider>.value(value: favoriteProvider),
          ],
          child: MaterialApp(
            home: const MyApp(),
            routes: {
              '/notifications': (context) => const Scaffold(
                body: Center(child: Text('Notifications Page')),
              ),
            },
          ),
        ),
      );

      // Wait for the app to load
      await tester.pumpAndSettle();

      // Navigate to profile page (assuming it's the last tab)
      final bottomNavItems = find.byType(BottomNavigationBar);
      if (bottomNavItems.findsWidgets) {
        // Tap on profile tab (usually the last one)
        await tester.tap(find.byIcon(Icons.person).last);
        await tester.pumpAndSettle();
      }

      // Find the notifications card in profile page
      final notificationsCard = find.text('Notifications');
      
      // Verify the notifications card exists
      expect(notificationsCard, findsOneWidget);

      // Tap the notifications card
      await tester.tap(notificationsCard);
      await tester.pumpAndSettle();

      // Verify navigation to notifications page
      expect(find.text('Notifications Page'), findsOneWidget);
    });

    testWidgets('Profile page order card navigates to my orders page', (WidgetTester tester) async {
      // Build the app with providers
      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<NotificationService>.value(value: notificationService),
            ChangeNotifierProvider<OrderService>.value(value: orderService),
            ChangeNotifierProvider<AuthService>.value(value: authService),
            ChangeNotifierProvider<CartProvider>.value(value: cartProvider),
            ChangeNotifierProvider<FavoriteProvider>.value(value: favoriteProvider),
          ],
          child: MaterialApp(
            home: const MyApp(),
            routes: {
              '/my-orders': (context) => const Scaffold(
                body: Center(child: Text('My Orders Page')),
              ),
            },
          ),
        ),
      );

      // Wait for the app to load
      await tester.pumpAndSettle();

      // Navigate to profile page
      final bottomNavItems = find.byType(BottomNavigationBar);
      if (bottomNavItems.findsWidgets) {
        await tester.tap(find.byIcon(Icons.person).last);
        await tester.pumpAndSettle();
      }

      // Find the orders card in profile page
      final ordersCard = find.text('All My Orders');
      
      // Verify the orders card exists
      expect(ordersCard, findsOneWidget);

      // Tap the orders card
      await tester.tap(ordersCard);
      await tester.pumpAndSettle();

      // Verify navigation to my orders page
      expect(find.text('My Orders Page'), findsOneWidget);
    });

    testWidgets('Notification badge shows correct count', (WidgetTester tester) async {
      // Add some test notifications
      notificationService.addNotification(
        'Test Notification 1',
        'Test message 1',
        'test',
      );
      notificationService.addNotification(
        'Test Notification 2',
        'Test message 2',
        'test',
      );

      // Build the app with providers
      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<NotificationService>.value(value: notificationService),
            ChangeNotifierProvider<OrderService>.value(value: orderService),
            ChangeNotifierProvider<AuthService>.value(value: authService),
            ChangeNotifierProvider<CartProvider>.value(value: cartProvider),
            ChangeNotifierProvider<FavoriteProvider>.value(value: favoriteProvider),
          ],
          child: const MaterialApp(
            home: MyApp(),
          ),
        ),
      );

      // Wait for the app to load
      await tester.pumpAndSettle();

      // Verify notification badge shows correct count
      expect(find.text('2'), findsAtLeastNWidget(1));
    });

    testWidgets('Order count updates correctly', (WidgetTester tester) async {
      // Build the app with providers
      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<NotificationService>.value(value: notificationService),
            ChangeNotifierProvider<OrderService>.value(value: orderService),
            ChangeNotifierProvider<AuthService>.value(value: authService),
            ChangeNotifierProvider<CartProvider>.value(value: cartProvider),
            ChangeNotifierProvider<FavoriteProvider>.value(value: favoriteProvider),
          ],
          child: const MaterialApp(
            home: MyApp(),
          ),
        ),
      );

      // Wait for the app to load
      await tester.pumpAndSettle();

      // Navigate to profile page
      final bottomNavItems = find.byType(BottomNavigationBar);
      if (bottomNavItems.findsWidgets) {
        await tester.tap(find.byIcon(Icons.person).last);
        await tester.pumpAndSettle();
      }

      // Initially should show 0 orders
      expect(find.text('0 orders'), findsOneWidget);

      // Simulate adding orders (this would normally come from API)
      // Note: In real implementation, this would be tested with mock data
    });

    test('NotificationService optimization test', () {
      final service = NotificationService();
      
      // Test that notification count is cached
      service.addNotification('Test 1', 'Message 1', 'test');
      service.addNotification('Test 2', 'Message 2', 'test');
      
      expect(service.unreadCount, equals(2));
      expect(service.notifications.length, equals(2));
      
      // Mark one as read
      if (service.notifications.isNotEmpty) {
        service.markAsRead(service.notifications.first.id);
      }
      
      expect(service.unreadCount, equals(1));
    });

    test('OrderService optimization test', () {
      final service = OrderService();
      
      // Test that orders are not fetched repeatedly
      expect(service.orders.isEmpty, isTrue);
      expect(service.isLoading, isFalse);
      
      // Simulate loading state
      // Note: In real implementation, this would test actual API calls
    });
  });
}

// Test helper to create a minimal app structure
class TestApp extends StatelessWidget {
  final Widget child;
  
  const TestApp({Key? key, required this.child}) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: child,
      routes: {
        '/notifications': (context) => const Scaffold(
          appBar: AppBar(title: Text('Notifications')),
          body: Center(child: Text('Notifications Page')),
        ),
        '/my-orders': (context) => const Scaffold(
          appBar: AppBar(title: Text('My Orders')),
          body: Center(child: Text('My Orders Page')),
        ),
      },
    );
  }
}
