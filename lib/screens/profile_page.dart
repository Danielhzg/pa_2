import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../utils/database_helper.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  bool _isLoading = true;
  bool _isEditing = false;
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    try {
      setState(() => _isLoading = true);

      final authService = Provider.of<AuthService>(context, listen: false);
      print(
          'Profile: Current user data before refresh: ${authService.currentUser}');

      await authService.getUser();

      final userData = authService.currentUser;
      print('Profile: Updated user data: $userData');

      if (userData != null) {
        setState(() {
          _emailController.text = userData.email ?? '';
          _phoneController.text = userData.phone ?? '';
        });
      } else {
        print('Profile: No user data available');
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Unable to load user data')),
          );
        }
      }
    } catch (e) {
      print('Error loading user data: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading profile: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _handleUpdateProfile() async {
    if (!_formKey.currentState!.validate()) return;

    try {
      setState(() => _isLoading = true);

      final authService = Provider.of<AuthService>(context, listen: false);
      final token = authService.token;

      if (token == null) {
        throw Exception('Not authenticated');
      }

      try {
        final response = await http.post(
          Uri.parse('http://10.0.2.2:8000/api/v1/update-profile'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
          body: json.encode({
            'email': _emailController.text.trim(),
            'phone': _phoneController.text.trim(),
          }),
        );

        print(
            'Update profile response: ${response.statusCode} - ${response.body}');

        if (!mounted) return;

        // Try to parse the response as JSON
        Map<String, dynamic> result;
        try {
          result = json.decode(response.body);
        } catch (e) {
          // Handle HTML responses or other non-JSON responses
          print('Failed to parse response: $e');
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Server error. Please try again later.')),
          );
          return;
        }

        if (response.statusCode == 200) {
          await authService.getUser();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Profile updated successfully')),
          );
          setState(() => _isEditing = false);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(result['message'] ?? 'Update failed')),
          );
        }
      } catch (e) {
        print('Network error: $e');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Network error: $e')),
        );
      }
    } catch (e) {
      print('Error updating profile: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error updating profile: $e')),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: Icon(_isEditing ? Icons.close : Icons.edit),
            onPressed: () {
              if (_isEditing) {
                setState(() {
                  _isEditing = false;
                  _resetForm();
                });
              } else {
                setState(() => _isEditing = true);
              }
            },
          ),
        ],
      ),
      body: Consumer<AuthService>(
        builder: (context, auth, _) {
          final userData = auth.currentUser;
          print('Building profile with userData: $userData'); // Debug print

          if (_isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (userData == null) {
            return const Center(
              child: Text('Unable to load profile data'),
            );
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  const CircleAvatar(
                    radius: 50,
                    backgroundColor: Colors.pink,
                    child: Icon(Icons.person, size: 50, color: Colors.white),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    userData.name ?? 'N/A',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 32),
                  if (_isEditing) ...[
                    _buildEditableField(
                      controller: _emailController,
                      label: 'Email',
                      icon: Icons.email,
                      validator: (value) {
                        if (value?.isEmpty ?? true) return 'Email is required';
                        if (!value!.contains('@'))
                          return 'Invalid email format';
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    _buildEditableField(
                      controller: _phoneController,
                      label: 'Phone',
                      icon: Icons.phone,
                      validator: (value) {
                        if (value?.isEmpty ?? true) return 'Phone is required';
                        if (value!.length < 10) return 'Invalid phone number';
                        return null;
                      },
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: _handleUpdateProfile,
                      child: const Text('Save Changes'),
                    ),
                  ] else ...[
                    _buildInfoCard(
                      icon: Icons.email,
                      title: 'Email',
                      value: userData.email ?? 'N/A',
                    ),
                    _buildInfoCard(
                      icon: Icons.phone,
                      title: 'Phone',
                      value: userData.phone ?? 'N/A',
                    ),
                  ],
                  const SizedBox(height: 32),
                  ElevatedButton.icon(
                    onPressed: () => _handleLogout(context),
                    icon: const Icon(Icons.logout),
                    label: const Text('Logout'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.all(16),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _handleLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Logout'),
          ),
        ],
      ),
    );

    if (confirmed ?? false) {
      if (!mounted) return;

      final authService = Provider.of<AuthService>(context, listen: false);
      await authService.logout();

      if (!mounted) return;
      Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
    }
  }

  Widget _buildInfoCard({
    required IconData icon,
    required String title,
    required String value,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: ListTile(
        leading: Icon(icon, color: Colors.pink),
        title: Text(title),
        subtitle: Text(value),
      ),
    );
  }

  Widget _buildEditableField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    required String? Function(String?) validator,
  }) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      validator: validator,
    );
  }

  void _resetForm() {
    final userData =
        Provider.of<AuthService>(context, listen: false).currentUser;
    _emailController.text = userData?.email ?? '';
    _phoneController.text = userData?.phone ?? '';
  }

  @override
  void dispose() {
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }
}
