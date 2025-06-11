import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class PrivacySecurityScreen extends StatefulWidget {
  const PrivacySecurityScreen({Key? key}) : super(key: key);

  @override
  State<PrivacySecurityScreen> createState() => _PrivacySecurityScreenState();
}

class _PrivacySecurityScreenState extends State<PrivacySecurityScreen> {
  bool _biometricEnabled = true;
  bool _twoFactorEnabled = false;
  bool _loginNotifications = true;
  bool _dataSharing = false;
  bool _marketingEmails = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Privacy & Security',
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
            // Security Settings Section
            _buildSectionHeader(
              title: 'Security Settings',
              icon: Icons.security,
              color: Colors.red,
            ),
            const SizedBox(height: 16),

            _buildSecurityCard(),

            const SizedBox(height: 24),

            // Privacy Settings Section
            _buildSectionHeader(
              title: 'Privacy Settings',
              icon: Icons.privacy_tip,
              color: Colors.blue,
            ),
            const SizedBox(height: 16),

            _buildPrivacyCard(),

            const SizedBox(height: 24),

            // Account Management Section
            _buildSectionHeader(
              title: 'Account Management',
              icon: Icons.manage_accounts,
              color: Colors.orange,
            ),
            const SizedBox(height: 16),

            _buildAccountManagementCard(),

            const SizedBox(height: 24),

            // Data & Privacy Section
            _buildSectionHeader(
              title: 'Data & Privacy',
              icon: Icons.data_usage,
              color: Colors.green,
            ),
            const SizedBox(height: 16),

            _buildDataPrivacyCard(),

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

  Widget _buildSecurityCard() {
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
          _buildSwitchTile(
            icon: Icons.fingerprint,
            iconColor: Colors.green,
            title: 'Biometric Login',
            subtitle: 'Use fingerprint or face ID to login',
            value: _biometricEnabled,
            onChanged: (value) {
              setState(() {
                _biometricEnabled = value;
              });
              _showSnackBar(
                  'Biometric login ${value ? 'enabled' : 'disabled'}');
            },
          ),
          const Divider(height: 1),
          _buildSwitchTile(
            icon: Icons.security,
            iconColor: Colors.orange,
            title: 'Two-Factor Authentication',
            subtitle: 'Add extra security to your account',
            value: _twoFactorEnabled,
            onChanged: (value) {
              setState(() {
                _twoFactorEnabled = value;
              });
              _showSnackBar(
                  'Two-factor authentication ${value ? 'enabled' : 'disabled'}');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.lock_reset,
            iconColor: Colors.red,
            title: 'Change Password',
            subtitle: 'Update your account password',
            onTap: () {
              _showChangePasswordDialog();
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.devices,
            iconColor: Colors.blue,
            title: 'Active Sessions',
            subtitle: 'Manage your logged-in devices',
            onTap: () {
              _showActiveSessionsDialog();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildPrivacyCard() {
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
          _buildSwitchTile(
            icon: Icons.notifications_active,
            iconColor: Colors.purple,
            title: 'Login Notifications',
            subtitle: 'Get notified of new logins',
            value: _loginNotifications,
            onChanged: (value) {
              setState(() {
                _loginNotifications = value;
              });
              _showSnackBar(
                  'Login notifications ${value ? 'enabled' : 'disabled'}');
            },
          ),
          const Divider(height: 1),
          _buildSwitchTile(
            icon: Icons.share,
            iconColor: Colors.indigo,
            title: 'Data Sharing',
            subtitle: 'Share data for better experience',
            value: _dataSharing,
            onChanged: (value) {
              setState(() {
                _dataSharing = value;
              });
              _showSnackBar('Data sharing ${value ? 'enabled' : 'disabled'}');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.visibility,
            iconColor: Colors.teal,
            title: 'Profile Visibility',
            subtitle: 'Control who can see your profile',
            onTap: () {
              _showProfileVisibilityDialog();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildAccountManagementCard() {
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
            icon: Icons.download,
            iconColor: Colors.green,
            title: 'Download My Data',
            subtitle: 'Get a copy of your data',
            onTap: () {
              _showDownloadDataDialog();
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.delete_forever,
            iconColor: Colors.red,
            title: 'Delete Account',
            subtitle: 'Permanently delete your account',
            onTap: () {
              _showDeleteAccountDialog();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildDataPrivacyCard() {
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
          _buildSwitchTile(
            icon: Icons.email,
            iconColor: Colors.pink,
            title: 'Marketing Emails',
            subtitle: 'Receive promotional emails',
            value: _marketingEmails,
            onChanged: (value) {
              setState(() {
                _marketingEmails = value;
              });
              _showSnackBar(
                  'Marketing emails ${value ? 'enabled' : 'disabled'}');
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.policy,
            iconColor: Colors.blue,
            title: 'Privacy Policy',
            subtitle: 'Read our privacy policy',
            onTap: () {
              _showPrivacyPolicyDialog();
            },
          ),
          const Divider(height: 1),
          _buildActionTile(
            icon: Icons.description,
            iconColor: Colors.orange,
            title: 'Terms of Service',
            subtitle: 'Read our terms of service',
            onTap: () {
              _showTermsOfServiceDialog();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
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
      trailing: Switch(
        value: value,
        onChanged: onChanged,
        activeColor: const Color(0xFFFF87B2),
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

  void _showChangePasswordDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Change Password'),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Current Password',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'New Password',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Confirm New Password',
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
              _showSnackBar('Password changed successfully');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Change'),
          ),
        ],
      ),
    );
  }

  void _showActiveSessionsDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Active Sessions'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildSessionItem('iPhone 13', 'Current device', true),
            const SizedBox(height: 8),
            _buildSessionItem('MacBook Pro', 'Last active 2 hours ago', false),
            const SizedBox(height: 8),
            _buildSessionItem('Chrome Browser', 'Last active 1 day ago', false),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _showSnackBar('All other sessions terminated');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Terminate All Others'),
          ),
        ],
      ),
    );
  }

  Widget _buildSessionItem(String device, String status, bool isCurrent) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isCurrent
            ? const Color(0xFFFF87B2).withOpacity(0.1)
            : Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(
            Icons.devices,
            color: isCurrent ? const Color(0xFFFF87B2) : Colors.grey,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  device,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                Text(
                  status,
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          if (isCurrent)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFFFF87B2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text(
                'Current',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _showProfileVisibilityDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Profile Visibility'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Public'),
              subtitle: const Text('Anyone can see your profile'),
              value: 'public',
              groupValue: 'private',
              onChanged: (value) {},
              activeColor: const Color(0xFFFF87B2),
            ),
            RadioListTile<String>(
              title: const Text('Friends Only'),
              subtitle: const Text('Only your friends can see your profile'),
              value: 'friends',
              groupValue: 'private',
              onChanged: (value) {},
              activeColor: const Color(0xFFFF87B2),
            ),
            RadioListTile<String>(
              title: const Text('Private'),
              subtitle: const Text('Only you can see your profile'),
              value: 'private',
              groupValue: 'private',
              onChanged: (value) {},
              activeColor: const Color(0xFFFF87B2),
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
              _showSnackBar('Profile visibility updated');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  void _showDownloadDataDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Download My Data'),
        content: const Text(
          'We will prepare a copy of your data and send it to your email address. This may take up to 24 hours.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _showSnackBar('Data download request submitted');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Request Download'),
          ),
        ],
      ),
    );
  }

  void _showDeleteAccountDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Delete Account'),
        content: const Text(
          'Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _showSnackBar('Account deletion cancelled');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Delete Account'),
          ),
        ],
      ),
    );
  }

  void _showPrivacyPolicyDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Privacy Policy'),
        content: const SingleChildScrollView(
          child: Text(
            'Privacy Policy\n\n'
            '1. Information We Collect\n'
            'We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us.\n\n'
            '2. How We Use Your Information\n'
            'We use the information we collect to provide, maintain, and improve our services.\n\n'
            '3. Information Sharing\n'
            'We do not sell, trade, or otherwise transfer your personal information to third parties.\n\n'
            '4. Data Security\n'
            'We implement appropriate security measures to protect your personal information.\n\n'
            '5. Contact Us\n'
            'If you have any questions about this Privacy Policy, please contact us.',
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  void _showTermsOfServiceDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Text('Terms of Service'),
        content: const SingleChildScrollView(
          child: Text(
            'Terms of Service\n\n'
            '1. Acceptance of Terms\n'
            'By using our service, you agree to these terms.\n\n'
            '2. Use of Service\n'
            'You may use our service only for lawful purposes.\n\n'
            '3. User Accounts\n'
            'You are responsible for maintaining the security of your account.\n\n'
            '4. Prohibited Uses\n'
            'You may not use our service for any illegal or unauthorized purpose.\n\n'
            '5. Termination\n'
            'We may terminate your account at any time for violation of these terms.\n\n'
            '6. Changes to Terms\n'
            'We reserve the right to modify these terms at any time.',
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF87B2),
            ),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }
}
