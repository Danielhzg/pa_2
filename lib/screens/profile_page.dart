import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async'; // Import for TimeoutException
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'dart:math';
import '../models/user.dart'; // Import for User model

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage>
    with SingleTickerProviderStateMixin {
  bool _isLoading = true;
  bool _isEditing = false;
  final _formKey = GlobalKey<FormState>();
  final _fullNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _birthDateController = TextEditingController();
  DateTime? _selectedDate;

  // Animation controllers
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    // Setup animations
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );

    _fadeAnimation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeIn,
    );

    _loadUserData().then((_) {
      _animationController.forward();
    });
  }

  Future<void> _loadUserData() async {
    if (!mounted) return;

    setState(() => _isLoading = true);

    try {
      final authService = Provider.of<AuthService>(context, listen: false);

      // Jika sudah ada data user yang tersimpan, tampilkan sebagai fallback sementara loading
      if (authService.currentUser != null) {
        _updateFormWithUserData(authService.currentUser!);
      }

      // Mencoba mengambil data user terbaru dari API
      final success = await authService.getUser();

      if (!mounted) return;

      if (success) {
        final userData = authService.currentUser;

        if (userData != null) {
          _updateFormWithUserData(userData);
          print('Sukses memuat data user: ${userData.full_name}');
        } else {
          _showErrorSnackbar(
              'Data profil tersedia, tetapi format tidak sesuai');
          print('Error: User data is null after successful API call');
        }
      } else {
        // Cek jika ada data lokal yang bisa digunakan
        if (authService.currentUser != null) {
          _showWarningSnackBar('Menggunakan data profil yang tersimpan');
        } else {
          _showErrorSnackbar('Gagal memuat data profil. Periksa koneksi anda.');
        }
      }
    } on SocketException catch (e) {
      if (mounted) {
        _showErrorSnackbar('Tidak ada koneksi internet. Periksa koneksi anda.');
        print('Socket exception: $e');
      }
    } on TimeoutException catch (e) {
      if (mounted) {
        _showErrorSnackbar('Koneksi timeout. Coba lagi nanti.');
        print('Timeout exception: $e');
      }
    } catch (e) {
      if (mounted) {
        _showErrorSnackbar(
            'Terjadi kesalahan: ${e.toString().substring(0, min(50, e.toString().length))}...');
        print('Error in _loadUserData: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  // Helper untuk memperbarui form fields
  void _updateFormWithUserData(User userData) {
    setState(() {
      _fullNameController.text = userData.full_name ?? userData.name ?? '';
      _emailController.text = userData.email ?? '';
      _phoneController.text = userData.phone ?? '';
      _addressController.text = userData.address ?? '';

      if (userData.birth_date != null) {
        _selectedDate = userData.birth_date;
        _birthDateController.text =
            DateFormat('yyyy-MM-dd').format(userData.birth_date!);
      } else {
        _birthDateController.text = '';
        _selectedDate = null;
      }
    });
  }

  void _showWarningSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.orange,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showErrorSnackbar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showSuccessSnackbar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? DateTime.now(),
      firstDate: DateTime(1950),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFFFF87B2),
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
            textButtonTheme: TextButtonThemeData(
              style: TextButton.styleFrom(
                foregroundColor: const Color(0xFFFF87B2),
              ),
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
        _birthDateController.text = DateFormat('yyyy-MM-dd').format(picked);
      });
    }
  }

  Future<void> _handleUpdateProfile() async {
    if (!_formKey.currentState!.validate()) return;

    try {
      setState(() => _isLoading = true);

      final authService = Provider.of<AuthService>(context, listen: false);
      final token = authService.token;

      if (token == null) {
        throw Exception('Tidak terautentikasi');
      }

      // Data yang akan dikirim ke API
      final Map<String, dynamic> profileData = {
        'full_name': _fullNameController.text.trim(),
        'email': _emailController.text.trim(),
        'phone': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
        'birth_date': _birthDateController.text.trim(),
      };

      print('Mengirim data profil: $profileData');

      // Daftar endpoint yang akan dicoba
      List<String> endpoints = [
        'http://10.0.2.2:8000/api/v1/update-profile',
        'http://10.0.2.2:8000/api/v1/profile', // Alternatif endpoint
        'http://10.0.2.2:8000/api/user/profile' // Endpoint fallback
      ];

      bool updateSuccess = false;
      String errorMessage =
          'Gagal memperbarui profil. Silakan coba lagi nanti.';

      // Mencoba endpoint satu per satu sampai berhasil
      for (String endpoint in endpoints) {
        try {
          print('Mencoba memperbarui profil via endpoint: $endpoint');

          final response = await http
              .post(
                Uri.parse(endpoint),
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'Authorization': 'Bearer $token',
                },
                body: json.encode(profileData),
              )
              .timeout(const Duration(seconds: 15));

          print(
              'Respons update profil: ${response.statusCode} - ${response.body}');

          // Coba parse response jika ada
          Map<String, dynamic> result = {};
          try {
            result = json.decode(response.body);
            print('Response decoded: $result');
          } catch (e) {
            print('Error parsing response: $e');
            continue; // Coba endpoint berikutnya
          }

          // Periksa response code
          if (response.statusCode == 200 || response.statusCode == 201) {
            updateSuccess = true;
            break; // Berhasil, keluar dari loop
          } else if (response.statusCode == 401) {
            // Token tidak valid
            errorMessage = 'Sesi anda telah berakhir. Silakan login kembali.';
            break;
          } else if (result['message'] != null) {
            // Gunakan pesan error dari API jika ada
            errorMessage = result['message'];
          }
        } on TimeoutException {
          print('Connection timeout on endpoint: $endpoint');
          errorMessage = 'Koneksi timeout. Periksa koneksi internet anda.';
          continue;
        } on SocketException {
          print('Network error on endpoint: $endpoint');
          errorMessage = 'Tidak ada koneksi internet.';
          continue;
        } catch (e) {
          print('Error calling endpoint $endpoint: $e');
          continue;
        }
      }

      if (!mounted) return;

      if (updateSuccess) {
        // Ambil kembali data user yang sudah diupdate
        await authService.getUser();
        _showSuccessSnackbar('Profil berhasil diperbarui');
        setState(() => _isEditing = false);
      } else {
        _showErrorSnackbar(errorMessage);
      }
    } catch (e) {
      if (mounted) {
        _showErrorSnackbar('Error: ${e.toString()}');
        print('Error in _handleUpdateProfile: $e');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[100],
      body: RefreshIndicator(
        onRefresh: _loadUserData,
        color: const Color(0xFFFF87B2),
        child: Consumer<AuthService>(
          builder: (context, auth, _) {
            final userData = auth.currentUser;

            // Tampilkan loading state saat pertama kali memuat
            if (_isLoading && userData == null) {
              return const Center(
                  child: CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(Color(0xFFFF87B2)),
              ));
            }

            // Tampilkan pesan error jika tidak ada data user
            if (userData == null) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline,
                        size: 80, color: Color(0xFFFF87B2)),
                    const SizedBox(height: 16),
                    const Text(
                      'Tidak dapat memuat data profil',
                      style: TextStyle(fontSize: 18),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: _loadUserData,
                      child: const Text('Coba Lagi'),
                    ),
                  ],
                ),
              );
            }

            return FadeTransition(
              opacity: _fadeAnimation,
              child: CustomScrollView(
                physics:
                    const AlwaysScrollableScrollPhysics(), // Memastikan refresh bekerja
                slivers: [
                  // App Bar with profile header
                  SliverAppBar(
                    expandedHeight: 220.0,
                    floating: false,
                    pinned: true,
                    backgroundColor: Colors.white,
                    elevation: 0,
                    flexibleSpace: FlexibleSpaceBar(
                      titlePadding: EdgeInsets.zero,
                      title: AnimatedOpacity(
                        duration: const Duration(milliseconds: 300),
                        opacity: 1.0,
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: Text(
                            _isEditing ? 'Edit Profile' : '',
                            style: const TextStyle(
                              color: Color(0xFFFF87B2),
                              fontWeight: FontWeight.bold,
                              fontSize: 20,
                            ),
                          ),
                        ),
                      ),
                      background: Container(
                        decoration: const BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [Color(0xFFFF87B2), Color(0xFFFF5E8A)],
                          ),
                        ),
                        child: Stack(
                          children: [
                            // Background pattern
                            Positioned.fill(
                              child: Opacity(
                                opacity: 0.1,
                                child: CustomPaint(
                                  painter: PatternPainter(),
                                ),
                              ),
                            ),
                            // Profile avatar and name
                            Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const SizedBox(height: 20),
                                  Hero(
                                    tag: 'profile-avatar',
                                    child: CircleAvatar(
                                      radius: 50,
                                      backgroundColor:
                                          Colors.white.withOpacity(0.9),
                                      child: Text(
                                        (userData.username?.isNotEmpty == true)
                                            ? userData.username![0]
                                                .toUpperCase()
                                            : '?',
                                        style: const TextStyle(
                                          fontSize: 44,
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFFFF87B2),
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  Text(
                                    userData.full_name ??
                                        userData.name ??
                                        'Your Name',
                                    style: const TextStyle(
                                      fontSize: 22,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    actions: [
                      // Tombol refresh data
                      if (!_isEditing)
                        IconButton(
                          icon: Icon(
                            Icons.refresh,
                            color: const Color(0xFFFF87B2).withOpacity(0.8),
                          ),
                          onPressed: _isLoading ? null : _loadUserData,
                          tooltip: 'Refresh data',
                        ),
                      Container(
                        margin: const EdgeInsets.only(right: 8),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _isEditing
                              ? Colors.red.withOpacity(0.2)
                              : const Color(0xFFFF87B2).withOpacity(0.2),
                        ),
                        child: IconButton(
                          icon: Icon(
                            _isEditing ? Icons.close : Icons.edit,
                            color: _isEditing
                                ? Colors.red
                                : const Color(0xFFFF87B2),
                          ),
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
                      ),
                    ],
                  ),

                  // Content below the app bar
                  SliverToBoxAdapter(
                    child: Stack(
                      children: [
                        // Main content
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16.0),
                          child: Form(
                            key: _formKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (!_isEditing) ...[
                                  const SizedBox(height: 20),

                                  // Account information section
                                  _buildSectionHeader(
                                    title: "Account Information",
                                    icon: Icons.person_outline,
                                  ),

                                  // Username card
                                  _buildProfileInfoCard(
                                    icon: Icons.account_circle,
                                    iconBgColor: const Color(0xFFE3F2FD),
                                    iconColor: Colors.blue,
                                    label: 'Username',
                                    value: userData.username ?? 'Not set',
                                    showBadge: true,
                                  ),

                                  // Email card
                                  _buildProfileInfoCard(
                                    icon: Icons.email_outlined,
                                    iconBgColor: const Color(0xFFE8F5E9),
                                    iconColor: Colors.green,
                                    label: 'Email',
                                    value: userData.email ?? 'Not set',
                                    showBadge: userData.email != null,
                                  ),

                                  // Phone card
                                  _buildProfileInfoCard(
                                    icon: Icons.phone_android,
                                    iconBgColor: const Color(0xFFFFECB3),
                                    iconColor: Colors.amber[800]!,
                                    label: 'Phone',
                                    value: userData.phone ?? 'Not set',
                                  ),

                                  const SizedBox(height: 24),

                                  // Personal information section
                                  _buildSectionHeader(
                                    title: "Personal Information",
                                    icon: Icons.info_outline,
                                  ),

                                  // Full Name card
                                  _buildProfileInfoCard(
                                    icon: Icons.person,
                                    iconBgColor: const Color(0xFFFCE4EC),
                                    iconColor: const Color(0xFFFF87B2),
                                    label: 'Full Name',
                                    value: userData.full_name ??
                                        userData.name ??
                                        'Not set',
                                  ),

                                  // Address card
                                  _buildProfileInfoCard(
                                    icon: Icons.location_on_outlined,
                                    iconBgColor: const Color(0xFFFFEBEE),
                                    iconColor: Colors.red,
                                    label: 'Address',
                                    value: userData.address ?? 'Not set',
                                    maxLines: 2,
                                  ),

                                  // Birth Date card
                                  _buildProfileInfoCard(
                                    icon: Icons.cake_outlined,
                                    iconBgColor: const Color(0xFFE0F2F1),
                                    iconColor: Colors.teal,
                                    label: 'Birth Date',
                                    value: userData.birth_date != null
                                        ? DateFormat('dd MMMM yyyy')
                                            .format(userData.birth_date!)
                                        : 'Not set',
                                  ),

                                  const SizedBox(height: 24),

                                  // App settings section
                                  _buildSectionHeader(
                                    title: "App Settings",
                                    icon: Icons.settings_outlined,
                                  ),

                                  // Settings options
                                  _buildSettingsItem(
                                    icon: Icons.notifications_none,
                                    iconColor: Colors.orange,
                                    title: 'Notifications',
                                    onTap: () {
                                      ScaffoldMessenger.of(context)
                                          .showSnackBar(
                                        const SnackBar(
                                            content: Text(
                                                'Notification settings coming soon!')),
                                      );
                                    },
                                  ),

                                  _buildSettingsItem(
                                    icon: Icons.lock_outline,
                                    iconColor: Colors.indigo,
                                    title: 'Privacy and Security',
                                    onTap: () {
                                      ScaffoldMessenger.of(context)
                                          .showSnackBar(
                                        const SnackBar(
                                            content: Text(
                                                'Privacy settings coming soon!')),
                                      );
                                    },
                                  ),

                                  _buildSettingsItem(
                                    icon: Icons.help_outline,
                                    iconColor: Colors.green,
                                    title: 'Help & Support',
                                    onTap: () {
                                      ScaffoldMessenger.of(context)
                                          .showSnackBar(
                                        const SnackBar(
                                            content:
                                                Text('Support coming soon!')),
                                      );
                                    },
                                  ),

                                  const SizedBox(height: 24),
                                ] else ...[
                                  // EDIT MODE
                                  const SizedBox(height: 20),

                                  _buildSectionHeader(
                                    title: "Edit Your Profile",
                                    icon: Icons.edit_note,
                                  ),

                                  const SizedBox(height: 16),

                                  // Full Name field
                                  _buildEditableField(
                                    controller: _fullNameController,
                                    label: 'Full Name',
                                    icon: Icons.person,
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Full name is required';
                                      return null;
                                    },
                                  ),

                                  const SizedBox(height: 16),

                                  // Email field
                                  _buildEditableField(
                                    controller: _emailController,
                                    label: 'Email',
                                    icon: Icons.email,
                                    keyboardType: TextInputType.emailAddress,
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Email is required';
                                      if (!value!.contains('@'))
                                        return 'Invalid email format';
                                      return null;
                                    },
                                  ),

                                  const SizedBox(height: 16),

                                  // Phone field
                                  _buildEditableField(
                                    controller: _phoneController,
                                    label: 'Phone',
                                    icon: Icons.phone,
                                    keyboardType: TextInputType.phone,
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Phone is required';
                                      if (value!.length < 10)
                                        return 'Invalid phone number';
                                      return null;
                                    },
                                  ),

                                  const SizedBox(height: 16),

                                  // Address field
                                  _buildEditableField(
                                    controller: _addressController,
                                    label: 'Address',
                                    icon: Icons.location_on,
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Address is required';
                                      return null;
                                    },
                                    maxLines: 3,
                                  ),

                                  const SizedBox(height: 16),

                                  // Birth Date field
                                  TextFormField(
                                    controller: _birthDateController,
                                    decoration: InputDecoration(
                                      labelText: 'Birth Date',
                                      filled: true,
                                      fillColor: Colors.white,
                                      contentPadding:
                                          const EdgeInsets.symmetric(
                                              horizontal: 16, vertical: 16),
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: BorderSide.none,
                                      ),
                                      prefixIcon: const Icon(
                                          Icons.cake_outlined,
                                          color: Color(0xFFFF87B2)),
                                      suffixIcon: IconButton(
                                        icon: const Icon(Icons.calendar_today,
                                            color: Color(0xFFFF87B2)),
                                        onPressed: () => _selectDate(context),
                                      ),
                                      floatingLabelBehavior:
                                          FloatingLabelBehavior.never,
                                      hintText: 'Select your birth date',
                                    ),
                                    readOnly: true,
                                    onTap: () => _selectDate(context),
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Birth date is required';
                                      return null;
                                    },
                                  ),

                                  const SizedBox(height: 24),

                                  // Save button
                                  SizedBox(
                                    width: double.infinity,
                                    height: 55,
                                    child: ElevatedButton.icon(
                                      onPressed: _isLoading
                                          ? null
                                          : _handleUpdateProfile,
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor:
                                            const Color(0xFFFF87B2),
                                        foregroundColor: Colors.white,
                                        elevation: 2,
                                        shadowColor: const Color(0xFFFF87B2)
                                            .withOpacity(0.4),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                      ),
                                      icon: const Icon(Icons.save_outlined),
                                      label: _isLoading
                                          ? const SizedBox(
                                              width: 24,
                                              height: 24,
                                              child: CircularProgressIndicator(
                                                strokeWidth: 2,
                                                valueColor:
                                                    AlwaysStoppedAnimation<
                                                        Color>(Colors.white),
                                              ),
                                            )
                                          : const Text(
                                              'Save Changes',
                                              style: TextStyle(
                                                fontSize: 16,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                    ),
                                  ),

                                  const SizedBox(height: 8),

                                  // Cancel button
                                  SizedBox(
                                    width: double.infinity,
                                    height: 50,
                                    child: TextButton(
                                      onPressed: () {
                                        setState(() {
                                          _isEditing = false;
                                          _resetForm();
                                        });
                                      },
                                      style: TextButton.styleFrom(
                                        foregroundColor: Colors.grey[700],
                                      ),
                                      child: const Text('Cancel'),
                                    ),
                                  ),
                                ],

                                const SizedBox(height: 24),

                                // Logout button (shown in both edit and view mode)
                                _buildLogoutButton(),

                                // Version info
                                const Center(
                                  child: Padding(
                                    padding:
                                        EdgeInsets.symmetric(vertical: 16.0),
                                    child: Text(
                                      'Bloom Bouquet v1.0.0',
                                      style: TextStyle(
                                        color: Colors.grey,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ),

                                const SizedBox(height: 30),
                              ],
                            ),
                          ),
                        ),

                        // Overlay loading indicator
                        if (_isLoading)
                          Positioned.fill(
                            child: Container(
                              color: Colors.black.withOpacity(0.1),
                              child: const Center(
                                child: CircularProgressIndicator(
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      Color(0xFFFF87B2)),
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildSectionHeader({required String title, required IconData icon}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0, left: 4.0),
      child: Row(
        children: [
          Icon(icon, size: 20, color: const Color(0xFFFF87B2)),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileInfoCard({
    required IconData icon,
    required Color iconBgColor,
    required Color iconColor,
    required String label,
    required String value,
    int maxLines = 1,
    bool showBadge = false,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.08),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: iconBgColor,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: iconColor, size: 24),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: maxLines,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            if (showBadge)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.green.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text(
                  'Verified',
                  style: TextStyle(
                    color: Colors.green,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildSettingsItem(
      {required IconData icon,
      required Color iconColor,
      required String title,
      required VoidCallback onTap}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.08),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                Icon(icon, color: iconColor, size: 24),
                const SizedBox(width: 16),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const Spacer(),
                Icon(Icons.arrow_forward_ios,
                    size: 16, color: Colors.grey[400]),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEditableField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    required String? Function(String?) validator,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(
        labelText: label,
        hintText: 'Enter your $label',
        filled: true,
        fillColor: Colors.white,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        prefixIcon: Icon(icon, color: const Color(0xFFFF87B2)),
        floatingLabelBehavior: FloatingLabelBehavior.never,
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFFF87B2), width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Colors.red, width: 1),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Colors.red, width: 1.5),
        ),
      ),
      validator: validator,
      maxLines: maxLines,
      keyboardType: keyboardType,
    );
  }

  Widget _buildLogoutButton() {
    return Container(
      margin: const EdgeInsets.only(top: 8),
      width: double.infinity,
      decoration: BoxDecoration(
        boxShadow: [
          BoxShadow(
            color: Colors.red.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ElevatedButton.icon(
        onPressed: () => _handleLogout(context),
        icon: const Icon(Icons.logout),
        label: const Text(
          'Logout',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.red,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 0,
        ),
      ),
    );
  }

  Future<void> _handleLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm Logout'),
        content: const Text('Are you sure you want to logout?'),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(
              foregroundColor: Colors.red,
              textStyle: const TextStyle(fontWeight: FontWeight.bold),
            ),
            child: const Text('Logout'),
          ),
        ],
      ),
    );

    if (confirmed ?? false) {
      if (!mounted) return;

      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => const Center(
          child: CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(Color(0xFFFF87B2)),
          ),
        ),
      );

      try {
        final authService = Provider.of<AuthService>(context, listen: false);
        await authService.logout();

        if (!mounted) return;

        // Close loading dialog
        Navigator.of(context).pop();

        // Navigate to login
        Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
      } catch (e) {
        // Close loading dialog
        Navigator.of(context).pop();

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error during logout: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _resetForm() {
    final userData =
        Provider.of<AuthService>(context, listen: false).currentUser;
    if (userData != null) {
      _fullNameController.text = userData.full_name ?? userData.name ?? '';
      _emailController.text = userData.email ?? '';
      _phoneController.text = userData.phone ?? '';
      _addressController.text = userData.address ?? '';
      if (userData.birth_date != null) {
        _selectedDate = userData.birth_date;
        _birthDateController.text =
            DateFormat('yyyy-MM-dd').format(userData.birth_date!);
      } else {
        _selectedDate = null;
        _birthDateController.text = '';
      }
    }
  }

  @override
  void dispose() {
    _fullNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _birthDateController.dispose();
    _animationController.dispose();
    super.dispose();
  }
}

// Pattern Painter for header background
class PatternPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    Paint paint = Paint()
      ..color = Colors.white.withOpacity(0.1)
      ..style = PaintingStyle.fill;

    // Draw some abstract shapes
    for (int i = 0; i < 10; i++) {
      double x = size.width * (i / 10);
      double y = size.height * 0.2 + (i % 2) * 20;
      double radius = 10 + (i % 3) * 5.0;
      canvas.drawCircle(Offset(x, y), radius, paint);
    }

    for (int i = 0; i < 8; i++) {
      double x = size.width * (i / 8 + 0.1);
      double y = size.height * 0.6 + (i % 3) * 15;
      double radius = 8 + (i % 4) * 4.0;
      canvas.drawCircle(Offset(x, y), radius, paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
