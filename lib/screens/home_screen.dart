import 'package:flutter/material.dart';
import '../widgets/banner_available.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Home'),
      ),
      body: const SingleChildScrollView(
        child: Column(
          children: [
            BannerAvailable(),
          ],
        ),
      ),
    );
  }
}
