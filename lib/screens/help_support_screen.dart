import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

class HelpSupportScreen extends StatefulWidget {
  const HelpSupportScreen({Key? key}) : super(key: key);

  @override
  State<HelpSupportScreen> createState() => _HelpSupportScreenState();
}

class _HelpSupportScreenState extends State<HelpSupportScreen> {
  final List<FAQItem> _faqItems = [
    FAQItem(
      question: 'How do I place an order?',
      answer:
          'To place an order, browse our flower collection, select your desired flowers, add them to cart, and proceed to checkout. You can pay using various payment methods including QRIS, bank transfer, or e-wallet.',
    ),
    FAQItem(
      question: 'What payment methods do you accept?',
      answer:
          'We accept QRIS, bank transfer (BCA, Mandiri, BNI, BRI), e-wallets (GoPay, OVO, DANA), and credit/debit cards.',
    ),
    FAQItem(
      question: 'How long does delivery take?',
      answer:
          'Standard delivery takes 1-3 business days. Same-day delivery is available for orders placed before 2 PM in Jakarta area.',
    ),
    FAQItem(
      question: 'Can I cancel my order?',
      answer:
          'You can cancel your order within 1 hour of placing it if payment hasn\'t been processed. After payment, cancellation may incur charges.',
    ),
    FAQItem(
      question: 'How do I track my order?',
      answer:
          'Go to My Orders in your profile, select the order you want to track. You\'ll see real-time updates on your order status.',
    ),
    FAQItem(
      question: 'What if my flowers arrive damaged?',
      answer:
          'We guarantee fresh flowers. If your flowers arrive damaged, contact us within 24 hours with photos for a full refund or replacement.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Help & Support',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Quick Actions Section
            _buildSectionHeader(
              title: 'Quick Actions',
              icon: Icons.flash_on,
              color: Colors.orange,
            ),
            const SizedBox(height: 16),

            _buildQuickActionsCard(),

            const SizedBox(height: 24),

            // Contact Us Section
            _buildSectionHeader(
              title: 'Contact Us',
              icon: Icons.contact_support,
              color: Colors.blue,
            ),
            const SizedBox(height: 16),

            _buildContactCard(),

            const SizedBox(height: 24),

            // FAQ Section
            _buildSectionHeader(
              title: 'Frequently Asked Questions',
              icon: Icons.help_outline,
              color: Colors.green,
            ),
            const SizedBox(height: 16),

            _buildFAQCard(),

            const SizedBox(height: 24),

            // Resources Section
            _buildSectionHeader(
              title: 'Resources',
              icon: Icons.library_books,
              color: Colors.purple,
            ),
            const SizedBox(height: 16),

            _buildResourcesCard(),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader({
    required String title,
    required IconData icon,
    required Color color,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 20),
        ),
        const SizedBox(width: 12),
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActionsCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildActionTile(
            icon: Icons.chat_bubble_outline,
            iconColor: Colors.blue,
            title: 'Live Chat',
            subtitle: 'Chat with our support team',
            onTap: () {
              _showSnackBar('Live chat feature coming soon!');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.phone,
            iconColor: Colors.green,
            title: 'Call Support',
            subtitle: '+62 21 1234 5678',
            onTap: () {
              _makePhoneCall('+6221123456789');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.email,
            iconColor: Colors.red,
            title: 'Email Support',
            subtitle: 'support@bloomapp.com',
            onTap: () {
              _sendEmail('support@bloomapp.com');
            },
          ),
        ],
      ),
    );
  }

  Widget _buildContactCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildContactTile(
            icon: Icons.location_on,
            iconColor: Colors.red,
            title: 'Visit Our Store',
            subtitle: 'Jl. Bunga Raya No. 123\nJakarta Selatan, 12345',
            onTap: () {
              _openMaps();
            },
          ),
          const Divider(height: 1),
          _buildContactTile(
            icon: Icons.access_time,
            iconColor: Colors.orange,
            title: 'Business Hours',
            subtitle:
                'Monday - Friday: 9:00 AM - 6:00 PM\nSaturday - Sunday: 10:00 AM - 4:00 PM',
            onTap: null,
          ),
          const Divider(height: 1),
          _buildContactTile(
            icon: Icons.language,
            iconColor: Colors.blue,
            title: 'Website',
            subtitle: 'www.bloomapp.com',
            onTap: () {
              _openWebsite('https://www.bloomapp.com');
            },
          ),
        ],
      ),
    );
  }

  Widget _buildFAQCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: _faqItems.asMap().entries.map((entry) {
          final index = entry.key;
          final faq = entry.value;
          return Column(
            children: [
              _buildFAQTile(faq),
              if (index < _faqItems.length - 1) const Divider(height: 1),
            ],
          );
        }).toList(),
      ),
    );
  }

  Widget _buildResourcesCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildActionTile(
            icon: Icons.book,
            iconColor: Colors.purple,
            title: 'User Guide',
            subtitle: 'Learn how to use the app',
            onTap: () {
              _showUserGuideDialog();
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.video_library,
            iconColor: Colors.red,
            title: 'Video Tutorials',
            subtitle: 'Watch step-by-step tutorials',
            onTap: () {
              _showSnackBar('Video tutorials coming soon!');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.feedback,
            iconColor: Colors.orange,
            title: 'Send Feedback',
            subtitle: 'Help us improve the app',
            onTap: () {
              _showFeedbackDialog();
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.star_rate,
            iconColor: Colors.amber,
            title: 'Rate Our App',
            subtitle: 'Rate us on the app store',
            onTap: () {
              _showSnackBar('Thank you for your interest in rating our app!');
            },
          ),
        ],
      ),
    );
  }

  Widget _buildActionTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: iconColor.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 16,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          color: Colors.grey[600],
          fontSize: 14,
        ),
      ),
      trailing: Icon(
        Icons.arrow_forward_ios,
        size: 16,
        color: Colors.grey[400],
      ),
      onTap: onTap,
    );
  }

  Widget _buildContactTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback? onTap,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: iconColor.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 16,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          color: Colors.grey[600],
          fontSize: 14,
          height: 1.4,
        ),
      ),
      trailing: onTap != null
          ? Icon(
              Icons.arrow_forward_ios,
              size: 16,
              color: Colors.grey[400],
            )
          : null,
      onTap: onTap,
    );
  }

  Widget _buildFAQTile(FAQItem faq) {
    return ExpansionTile(
      tilePadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      childrenPadding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
      leading: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: Colors.green.withOpacity(0.1),
          borderRadius: BorderRadius.circular(6),
        ),
        child: const Icon(
          Icons.help_outline,
          color: Colors.green,
          size: 16,
        ),
      ),
      title: Text(
        faq.question,
        style: const TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 15,
        ),
      ),
      children: [
        Text(
          faq.answer,
          style: TextStyle(
            color: Colors.grey[700],
            fontSize: 14,
            height: 1.5,
          ),
        ),
      ],
    );
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFFFF87B2),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }

  void _makePhoneCall(String phoneNumber) async {
    final Uri launchUri = Uri(
      scheme: 'tel',
      path: phoneNumber,
    );
    if (await canLaunchUrl(launchUri)) {
      await launchUrl(launchUri);
    } else {
      _showSnackBar('Could not launch phone dialer');
    }
  }

  void _sendEmail(String email) async {
    final Uri launchUri = Uri(
      scheme: 'mailto',
      path: email,
      query: 'subject=Support Request&body=Hello, I need help with...',
    );
    if (await canLaunchUrl(launchUri)) {
      await launchUrl(launchUri);
    } else {
      _showSnackBar('Could not launch email client');
    }
  }

  void _openMaps() async {
    const String address = 'Jl. Bunga Raya No. 123, Jakarta Selatan, 12345';
    final Uri launchUri = Uri(
      scheme: 'https',
      host: 'maps.google.com',
      path: '/search/',
      query: 'api=1&query=${Uri.encodeComponent(address)}',
    );
    if (await canLaunchUrl(launchUri)) {
      await launchUrl(launchUri);
    } else {
      _showSnackBar('Could not open maps');
    }
  }

  void _openWebsite(String url) async {
    final Uri launchUri = Uri.parse(url);
    if (await canLaunchUrl(launchUri)) {
      await launchUrl(launchUri);
    } else {
      _showSnackBar('Could not open website');
    }
  }

  void _showUserGuideDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('User Guide'),
        content: const SingleChildScrollView(
          child: Text(
            'Welcome to Bloom Bouquet App!\n\n'
            '1. Browse Flowers\n'
            'Explore our beautiful collection of fresh flowers on the home screen.\n\n'
            '2. Add to Cart\n'
            'Tap on any flower to view details and add to your cart.\n\n'
            '3. Checkout\n'
            'Review your cart and proceed to checkout with your preferred payment method.\n\n'
            '4. Track Orders\n'
            'Monitor your order status in the My Orders section.\n\n'
            '5. Profile Management\n'
            'Update your profile information and delivery addresses.\n\n'
            'Need more help? Contact our support team!',
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Got it!'),
          ),
        ],
      ),
    );
  }

  void _showFeedbackDialog() {
    final TextEditingController feedbackController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Send Feedback'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'We value your feedback! Please let us know how we can improve.',
            ),
            const SizedBox(height: 16),
            TextField(
              controller: feedbackController,
              maxLines: 4,
              decoration: const InputDecoration(
                hintText: 'Enter your feedback here...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _showSnackBar('Thank you for your feedback!');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Send'),
          ),
        ],
      ),
    );
  }
}

class FAQItem {
  final String question;
  final String answer;

  FAQItem({
    required this.question,
    required this.answer,
  });
}
