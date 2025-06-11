import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async'; // Import for TimeoutException
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:intl/date_symbol_data_local.dart'; // Add this import for locale initialization
import 'dart:math';
import '../models/user.dart'; // Import for User model
import '../models/order_status.dart'; // Import for OrderStatus
import 'package:image_picker/image_picker.dart'; // Import for image picker
import 'package:path/path.dart' as path; // Import for path manipulation
import 'chat_page.dart'; // Import for chat page
import '../services/order_service.dart'; // Import for OrderService
import '../services/notification_service.dart'; // Import for NotificationService

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage>
    with SingleTickerProviderStateMixin {
  bool _isLoading = true;
  bool _isEditing = false;
  bool _isCheckingUsername = false;
  bool _isUsernameAvailable = true;
  String? _usernameError;
  Timer? _usernameDebounce;
  final _formKey = GlobalKey<FormState>();
  final _fullNameController = TextEditingController();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _birthDateController = TextEditingController();
  DateTime? _selectedDate;
  File? _profileImage;
  final _imagePicker = ImagePicker();

  // Animation controllers
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    // Initialize date formatting
    initializeDateFormatting('id_ID');

    // Setup animations
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );

    _fadeAnimation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeIn,
    );

    // Setup username checker
    _usernameController.addListener(_checkUsernameAvailability);

    _loadUserData().then((_) {
      _animationController.forward();
    });
  }

  Future<void> _loadUserData({int retryCount = 0}) async {
    if (!mounted) return;

    setState(() => _isLoading = true);

    try {
      final authService = Provider.of<AuthService>(context, listen: false);

      // If we already have user data stored, display it as a fallback while loading
      if (authService.currentUser != null) {
        _updateFormWithUserData(authService.currentUser!);
      }

      // Try to get the latest user data from API
      final success = await authService.getUser();

      if (!mounted) return;

      if (success) {
        final userData = authService.currentUser;

        if (userData != null) {
          _updateFormWithUserData(userData);
          print('Successfully loaded user data: ${userData.full_name}');
        } else {
          _showErrorSnackbar(
              'Profile data is available, but in an unexpected format');
          print('Error: User data is null after successful API call');
        }
      } else {
        // Check if we can use locally stored data
        if (authService.currentUser != null) {
          _showWarningSnackBar('Using stored profile data');
        } else {
          _showErrorSnackbar(
              'Failed to load profile data. Check your connection.');

          // Try to retry automatically if this is not already a retry
          if (retryCount < 2) {
            print(
                'Automatically retrying data load (attempt ${retryCount + 1})');
            Future.delayed(Duration(seconds: 2 + retryCount), () {
              if (mounted) {
                _loadUserData(retryCount: retryCount + 1);
              }
            });
          }
        }
      }
    } on SocketException catch (e) {
      if (mounted) {
        _showErrorSnackbar('No internet connection. Check your connection.');
        print('Socket exception: $e');

        // Auto-retry on connection errors if not already a retry
        if (retryCount < 2) {
          print('Connection error, retrying in 3 seconds');
          Future.delayed(const Duration(seconds: 3), () {
            if (mounted) {
              _loadUserData(retryCount: retryCount + 1);
            }
          });
        }
      }
    } on TimeoutException catch (e) {
      if (mounted) {
        _showErrorSnackbar('Connection timeout. Try again later.');
        print('Timeout exception: $e');

        // Auto-retry on timeout if not already a retry
        if (retryCount < 2) {
          print('Timeout error, retrying in 3 seconds');
          Future.delayed(const Duration(seconds: 3), () {
            if (mounted) {
              _loadUserData(retryCount: retryCount + 1);
            }
          });
        }
      }
    } catch (e) {
      if (mounted) {
        _showErrorSnackbar(
            'An error occurred: ${e.toString().substring(0, min(50, e.toString().length))}...');
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
      _usernameController.text = userData.username ?? '';
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

      // Check username availability one last time before submission
      // Skip if username didn't change
      final originalUsername = authService.currentUser?.username;
      final newUsername = _usernameController.text.trim().replaceAll(' ', '');

      if (newUsername != originalUsername) {
        final isAvailable = await authService.isUsernameAvailable(newUsername);
        if (!isAvailable) {
          setState(() => _isLoading = false);
          _showErrorSnackbar(
              'Username sudah digunakan. Silakan pilih username lain.');
          return;
        }
      }

      // Format username (remove any spaces)
      final username = _usernameController.text.trim().replaceAll(' ', '');

      // Data yang akan dikirim ke API
      final Map<String, dynamic> profileData = {
        'full_name': _fullNameController.text.trim(),
        'username': username,
        'email': _emailController.text.trim(),
        'phone': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
        'birth_date': _birthDateController.text.trim(),
      };

      print('Updating profile with data: $profileData');

      // Use the enhanced updateProfile method that returns detailed information
      final result = await authService.updateProfile(profileData);

      if (!mounted) return;

      if (result['success']) {
        _showSuccessSnackbar(result['message'] ?? 'Profil berhasil diperbarui');

        // Force UI refresh with the latest data
        await _loadUserData();

        setState(() => _isEditing = false);
      } else {
        // Handle different error cases with more detailed error information
        if (result['statusCode'] == 401) {
          _showErrorSnackbar('Sesi anda telah berakhir. Silakan login ulang.');
          // Optional: Could navigate to login page here
        } else if (result['statusCode'] == 422) {
          _showErrorSnackbar('Data tidak valid. Periksa kembali input anda.');
        } else if (newUsername != originalUsername) {
          _showErrorSnackbar(
              'Gagal memperbarui username. Coba username lain atau periksa koneksi internet Anda.');
        } else {
          _showErrorSnackbar(result['message'] ??
              'Gagal memperbarui profil. Silakan coba lagi nanti.');
        }

        // Print detailed error information for debugging
        print('Update profile failed: ${result['message']}');
        if (result['error'] != null) {
          print('Error details: ${result['error']}');
        }
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
                                    child: Stack(
                                      children: [
                                        // Profile photo or avatar
                                        GestureDetector(
                                          onTap: _pickImage,
                                          child: CircleAvatar(
                                            radius: 50,
                                            backgroundColor:
                                                Colors.white.withOpacity(0.9),
                                            backgroundImage:
                                                userData.profile_photo != null
                                                    ? NetworkImage(
                                                        userData.profile_photo!)
                                                    : null,
                                            child: userData.profile_photo ==
                                                    null
                                                ? Text(
                                                    (userData.username
                                                                ?.isNotEmpty ==
                                                            true)
                                                        ? userData.username![0]
                                                            .toUpperCase()
                                                        : '?',
                                                    style: const TextStyle(
                                                      fontSize: 44,
                                                      fontWeight:
                                                          FontWeight.bold,
                                                      color: Color(0xFFFF87B2),
                                                    ),
                                                  )
                                                : null,
                                          ),
                                        ),
                                        // Camera icon for photo upload
                                        Positioned(
                                          right: 0,
                                          bottom: 0,
                                          child: GestureDetector(
                                            onTap: _pickImage,
                                            child: Container(
                                              padding: const EdgeInsets.all(4),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFFF87B2),
                                                shape: BoxShape.circle,
                                                border: Border.all(
                                                  color: Colors.white,
                                                  width: 2,
                                                ),
                                              ),
                                              child: const Icon(
                                                Icons.camera_alt,
                                                color: Colors.white,
                                                size: 20,
                                              ),
                                            ),
                                          ),
                                        ),
                                      ],
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
                                _usernameDebounce?.cancel();
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
                                    iconBgColor: const Color(0xFFF3E5F5),
                                    iconColor: Colors.purple,
                                    label: 'Birth Date',
                                    value: userData.birth_date != null
                                        ? DateFormat('dd MMMM yyyy', 'id_ID')
                                            .format(userData.birth_date!)
                                        : 'Not set',
                                  ),

                                  const SizedBox(height: 24),

                                  // Orders Section
                                  _buildSectionHeader(
                                    title: "My Orders",
                                    icon: Icons.shopping_bag_outlined,
                                  ),

                                  // Order Cards
                                  _buildOrdersCard(),

                                  // Notifications card
                                  _buildNotificationsCard(),

                                  const SizedBox(height: 24),

                                  // Account Actions section
                                  _buildSectionHeader(
                                    title: "Account Actions",
                                    icon: Icons.settings_outlined,
                                  ),

                                  // Settings options
                                  _buildSettingsItem(
                                    icon: Icons.lock_outline,
                                    iconColor: Colors.indigo,
                                    title: 'Privacy and Security',
                                    onTap: () {
                                      Navigator.pushNamed(
                                          context, '/privacy-security');
                                    },
                                  ),

                                  _buildSettingsItem(
                                    icon: Icons.help_outline,
                                    iconColor: Colors.green,
                                    title: 'Help & Support',
                                    onTap: () {
                                      Navigator.pushNamed(
                                          context, '/help-support');
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

                                  // Username field with availability check
                                  TextFormField(
                                    controller: _usernameController,
                                    decoration: InputDecoration(
                                      labelText: 'Username',
                                      hintText: 'Enter your username',
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
                                          Icons.account_circle,
                                          color: Color(0xFFFF87B2)),
                                      suffixIcon: _isCheckingUsername
                                          ? Container(
                                              width: 20,
                                              height: 20,
                                              margin: const EdgeInsets.all(12),
                                              child:
                                                  const CircularProgressIndicator(
                                                strokeWidth: 2,
                                                valueColor:
                                                    AlwaysStoppedAnimation<
                                                            Color>(
                                                        Color(0xFFFF87B2)),
                                              ),
                                            )
                                          : _usernameController.text.isNotEmpty
                                              ? Icon(
                                                  _isUsernameAvailable
                                                      ? Icons.check_circle
                                                      : Icons.error,
                                                  color: _isUsernameAvailable
                                                      ? Colors.green
                                                      : Colors.red,
                                                )
                                              : null,
                                      floatingLabelBehavior:
                                          FloatingLabelBehavior.never,
                                      enabledBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: BorderSide.none,
                                      ),
                                      focusedBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(
                                            color: Color(0xFFFF87B2),
                                            width: 1.5),
                                      ),
                                      errorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(
                                            color: Colors.red, width: 1),
                                      ),
                                      focusedErrorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(
                                            color: Colors.red, width: 1.5),
                                      ),
                                      errorText: _usernameError,
                                    ),
                                    validator: (value) {
                                      if (value?.isEmpty ?? true)
                                        return 'Username is required';
                                      if (value!.contains(' '))
                                        return 'Username cannot contain spaces';
                                      if (!_isUsernameAvailable &&
                                          _usernameController.text !=
                                              Provider.of<AuthService>(context,
                                                      listen: false)
                                                  .currentUser
                                                  ?.username)
                                        return 'This username is not available';
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

                                // My Orders button
                                ListTile(
                                  leading: Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: Theme.of(context)
                                          .primaryColor
                                          .withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Icon(
                                      Icons.shopping_bag_outlined,
                                      color: Theme.of(context).primaryColor,
                                    ),
                                  ),
                                  title: const Text('Pesanan Saya'),
                                  trailing: Icon(
                                    Icons.arrow_forward_ios,
                                    size: 16,
                                    color: Colors.grey[400],
                                  ),
                                  onTap: () {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text(
                                            'Order list feature is coming soon!'),
                                        backgroundColor: Color(0xFFFF87B2),
                                      ),
                                    );
                                  },
                                ),
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
      _usernameController.text = userData.username ?? '';
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
    _usernameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _birthDateController.dispose();
    _animationController.dispose();
    _usernameDebounce?.cancel();
    super.dispose();
  }

  // Image picker method
  Future<void> _pickImage() async {
    try {
      final XFile? pickedFile = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );

      if (pickedFile != null) {
        setState(() {
          _profileImage = File(pickedFile.path);
        });

        // Upload immediately
        await _uploadProfilePhoto();
      }
    } catch (e) {
      _showErrorSnackbar('Error picking image: $e');
    }
  }

  // Upload profile photo
  Future<void> _uploadProfilePhoto() async {
    if (_profileImage == null) return;

    try {
      setState(() => _isLoading = true);

      final authService = Provider.of<AuthService>(context, listen: false);
      final success = await authService.uploadProfilePhoto(_profileImage!);

      if (success) {
        _showSuccessSnackbar('Profile photo uploaded successfully');
      } else {
        _showErrorSnackbar('Failed to upload profile photo');
      }
    } catch (e) {
      _showErrorSnackbar('Error uploading profile photo: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _checkUsernameAvailability() {
    final username = _usernameController.text.trim();

    // Cancel any previous debounce timer
    if (_usernameDebounce?.isActive ?? false) {
      _usernameDebounce!.cancel();
    }

    // Don't check if the username is the same as the current user's
    final currentUsername =
        Provider.of<AuthService>(context, listen: false).currentUser?.username;

    // Reset validation state first
    setState(() {
      _isCheckingUsername = false;
      _usernameError = null;
    });

    // Handle empty username
    if (username.isEmpty) {
      setState(() {
        _isUsernameAvailable = false;
        _usernameError = 'Username tidak boleh kosong';
      });
      return;
    }

    // If username is unchanged, it's available to the current user
    if (username == currentUsername) {
      setState(() {
        _isUsernameAvailable = true;
        _usernameError = null;
      });
      return;
    }

    // Check for spaces and special characters immediately
    if (username.contains(' ')) {
      setState(() {
        _isUsernameAvailable = false;
        _usernameError = 'Username tidak boleh mengandung spasi';
      });
      return;
    }

    if (RegExp(r'[^\w]').hasMatch(username)) {
      setState(() {
        _isUsernameAvailable = false;
        _usernameError =
            'Username hanya boleh mengandung huruf, angka, dan underscore';
      });
      return;
    }

    // Username too short
    if (username.length < 3) {
      setState(() {
        _isUsernameAvailable = false;
        _usernameError = 'Username minimal 3 karakter';
      });
      return;
    }

    // Set a debounce timer to avoid too many API calls
    setState(() => _isCheckingUsername = true);
    _usernameDebounce = Timer(const Duration(milliseconds: 800), () async {
      // Check with the API if the username passes all local validations
      try {
        final authService = Provider.of<AuthService>(context, listen: false);
        final isAvailable = await authService.isUsernameAvailable(username);

        if (mounted) {
          setState(() {
            _isUsernameAvailable = isAvailable;
            _usernameError = isAvailable ? null : 'Username sudah digunakan';
            _isCheckingUsername = false;
          });
        }
      } catch (e) {
        if (mounted) {
          print('Error checking username availability: $e');
          setState(() {
            // Assume available on error but with a warning
            _isUsernameAvailable = true;
            _usernameError = 'Tidak dapat memeriksa ketersediaan username';
            _isCheckingUsername = false;
          });
        }
      }
    });
  }

  // Track if orders have been fetched to prevent repeated calls
  static bool _ordersInitialized = false;

  // Method to build orders card with quick access to different order statuses
  Widget _buildOrdersCard() {
    return Consumer<OrderService>(
      builder: (context, orderService, child) {
        // Only fetch orders once when first loaded and not already fetching
        if (orderService.orders.isEmpty &&
            !orderService.isLoading &&
            !_ordersInitialized &&
            mounted) {
          // Use Future.microtask to avoid build phase issues
          Future.microtask(() {
            orderService.fetchOrders();
            _ordersInitialized = true; // Prevent repeated calls
          });
        }

        // Use child parameter for static content to prevent unnecessary rebuilds
        return child!;
      },
      child: Container(
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
        child: InkWell(
          onTap: () {
            Navigator.pushNamed(context, '/my-orders');
          },
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFECB3),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.receipt_long,
                      color: Colors.amber, size: 24),
                ),
                const SizedBox(width: 16),
                const Expanded(
                  child: Text(
                    'All My Orders',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                // Use Selector to only rebuild this part when order count changes
                Selector<OrderService, String>(
                  selector: (context, orderService) => orderService.isLoading
                      ? 'loading'
                      : '${orderService.orders.length} orders',
                  builder: (context, orderInfo, child) {
                    if (orderInfo == 'loading') {
                      return const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor:
                              AlwaysStoppedAnimation<Color>(Color(0xFFFF87B2)),
                        ),
                      );
                    } else {
                      return Text(
                        orderInfo,
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 14,
                        ),
                      );
                    }
                  },
                ),
                const SizedBox(width: 8),
                const Icon(Icons.arrow_forward_ios,
                    size: 16, color: Colors.grey),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Add a new method to build notifications card
  Widget _buildNotificationsCard() {
    return Consumer<NotificationService>(
      builder: (context, notificationService, child) {
        // Use child parameter for static content
        return child!;
      },
      child: Container(
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
        child: InkWell(
          onTap: () {
            Navigator.pushNamed(context, '/notifications');
          },
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                // Use Selector for notification icon with badge
                Selector<NotificationService, bool>(
                  selector: (context, notificationService) =>
                      notificationService.hasUnread,
                  builder: (context, hasUnread, child) {
                    return Stack(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE3F2FD),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.notifications_outlined,
                              color: Colors.blue, size: 24),
                        ),
                        if (hasUnread)
                          Positioned(
                            right: 0,
                            top: 0,
                            child: Selector<NotificationService, int>(
                              selector: (context, notificationService) =>
                                  notificationService.unreadCount,
                              builder: (context, unreadCount, child) {
                                return Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFFF87B2),
                                    shape: BoxShape.circle,
                                  ),
                                  constraints: const BoxConstraints(
                                    minWidth: 16,
                                    minHeight: 16,
                                  ),
                                  child: Text(
                                    unreadCount.toString(),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                );
                              },
                            ),
                          ),
                      ],
                    );
                  },
                ),
                const SizedBox(width: 16),
                const Expanded(
                  child: Text(
                    'Notifications',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                // Use Selector for notification count
                Selector<NotificationService, int>(
                  selector: (context, notificationService) =>
                      notificationService.notifications.length,
                  builder: (context, notificationCount, child) {
                    return Text(
                      '$notificationCount items',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 14,
                      ),
                    );
                  },
                ),
                const SizedBox(width: 8),
                const Icon(Icons.arrow_forward_ios,
                    size: 16, color: Colors.grey),
              ],
            ),
          ),
        ),
      ),
    );
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
