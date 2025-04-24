import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'services/auth_service.dart';
import 'screens/otp_verification_page.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _cityController = TextEditingController();
  final _birthDateController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  DateTime? _selectedDate;

  Future<void> _handleRegister() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);

      try {
        final authService = Provider.of<AuthService>(context, listen: false);
        await authService.initializationFuture;

        // Format the address in a structured way for easier parsing
        final formattedAddress = "${_addressController.text.trim()}, "
            "${_cityController.text.trim()}";

        print('Register button tapped');
        print('Username: ${_usernameController.text.trim()}');
        print('Full Name: ${_fullNameController.text.trim()}');
        print('Email: ${_emailController.text.trim()}');
        print('Phone: ${_phoneController.text.trim()}');
        print('Address: $formattedAddress');
        print('Birth Date: ${_birthDateController.text.trim()}');

        final result = await authService.register(
          username: _usernameController.text.trim(),
          fullName: _fullNameController.text.trim(),
          email: _emailController.text.trim(),
          phone: _phoneController.text.trim(),
          address: formattedAddress,
          birthDate: _birthDateController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _confirmPasswordController.text,
        );

        if (!mounted) return;

        if (result['success']) {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => OtpVerificationPage(
                email: _emailController.text.trim(),
              ),
            ),
          );
        } else {
          // Enhanced error handling
          String errorMessage = 'Registration failed';

          if (result.containsKey('message') && result['message'] != null) {
            errorMessage = result['message'];
          }

          // Check for database connection errors
          if (result.containsKey('debug') && result['debug'] != null) {
            var debugMsg = result['debug'].toString();

            // Check for SMTP/Email errors
            if (debugMsg.contains('Failed to authenticate on SMTP server') ||
                debugMsg.contains('Username and Password not accepted') ||
                debugMsg.contains('BadCredentials')) {
              // SMTP authentication error
              errorMessage =
                  'Email verification service is currently unavailable. Your account has been created but email verification is not working.';

              // Show dialog with detailed error message and instructions
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Email Service Error',
                      style: TextStyle(color: Color(0xFFFF87B2))),
                  content: const SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                            'Your account was created successfully, but we could not send a verification email.'),
                        SizedBox(height: 8),
                        Text(
                            'You can still use the app, but you may not receive email notifications.'),
                        SizedBox(height: 16),
                        Text('For administrators:',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        Text('• Check SMTP credentials in .env file'),
                        Text('• Check if 2-factor authentication is enabled'),
                        Text('• Generate an app password for Gmail'),
                        Text('• Or use Mailtrap for development testing'),
                        SizedBox(height: 12),
                        Text(
                            'For more details, see admin-bloom_bouqet/smtp_setup.txt'),
                      ],
                    ),
                  ),
                  actions: [
                    TextButton(
                      child: const Text('Continue to App',
                          style: TextStyle(color: Color(0xFFFF87B2))),
                      onPressed: () {
                        Navigator.of(context).pop();
                        // Navigate to main page after registration instead of OTP verification
                        Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(
                            builder: (context) => OtpVerificationPage(
                              email: _emailController.text.trim(),
                              skipVerification:
                                  true, // Add this parameter to OtpVerificationPage
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              );
              return; // Skip showing the snackbar and other error processing
            } else if (debugMsg.contains('SQLSTATE[HY000] [2002]') ||
                debugMsg.contains('target machine actively refused it') ||
                debugMsg.contains('No connection could be made')) {
              // Database connection error
              errorMessage =
                  'Cannot connect to the database server. Please check if MySQL is running on the correct port or ask an administrator for help.';

              // Show dialog with detailed error message and instructions
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Database Connection Error',
                      style: TextStyle(color: Color(0xFFFF87B2))),
                  content: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                            'Cannot connect to the database. Please check:'),
                        const SizedBox(height: 8),
                        const Text('• If MySQL server is running'),
                        const Text(
                            '• If MySQL is using the correct port (3307)'),
                        const Text(
                            '• If the database "admin_bloom_bouqet" exists'),
                        const SizedBox(height: 12),
                        const Text('To fix this issue:',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        const Text('Option 1: Run the database setup script'),
                        const Text('1. Open command prompt'),
                        const Text(
                            '2. Navigate to the admin-bloom_bouqet folder'),
                        const Text('3. Run: php database_setup.php'),
                        const Text(
                            '4. The script will create the database for you'),
                        const SizedBox(height: 8),
                        const Text('Option 2: Manual setup'),
                        const Text('1. Open XAMPP Control Panel'),
                        const Text('2. Make sure MySQL is running'),
                        const Text(
                            '3. Open phpMyAdmin (http://localhost/phpmyadmin)'),
                        const Text(
                            '4. Click "New" in the sidebar to create a new database'),
                        const Text(
                            '5. Enter "admin_bloom_bouqet" as the database name'),
                        const Text('6. Click "Create"'),
                        const Text('7. Then restart the Laravel server with:'),
                        const Text('   php artisan serve'),
                        const SizedBox(height: 12),
                        const Text('Technical details:',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        Text(debugMsg, style: const TextStyle(fontSize: 12)),
                      ],
                    ),
                  ),
                  actions: [
                    TextButton(
                      child: const Text('OK',
                          style: TextStyle(color: Color(0xFFFF87B2))),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
              );
            }
          }

          if (result.containsKey('details') && result['details'] is Map) {
            final details = result['details'] as Map;

            if (details.containsKey('errors') && details['errors'] is Map) {
              final errors = details['errors'] as Map;
              if (errors.isNotEmpty) {
                final firstErrorField = errors.keys.first;
                final firstErrorMessage = errors[firstErrorField]?.first;

                if (firstErrorMessage != null) {
                  errorMessage = firstErrorMessage;
                }
              }
            }
          }

          print('Registration error: $errorMessage');
          print('Full error details: $result');

          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(errorMessage),
              backgroundColor: Colors.red,
              duration: const Duration(seconds: 5),
            ),
          );
        }
      } catch (e) {
        print('Exception in register handler: $e');

        // Check if this is a database connection error
        String errorMessage = 'Error: $e';
        if (e.toString().contains('target machine actively refused it') ||
            e.toString().contains('Connection refused') ||
            e.toString().contains('No connection could be made')) {
          errorMessage =
              'Cannot connect to the database server. Please check if MySQL is running on the correct port.';
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 5),
          ),
        );
      } finally {
        if (mounted) {
          setState(() => _isLoading = false);
        }
      }
    }
  }

  // Fungsi untuk memilih tanggal lahir
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

    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
        _birthDateController.text =
            "${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}";
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFFFF87B2)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Create Account',
                  style: TextStyle(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFFFF87B2),
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Please fill in the form to continue',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 30),

                // Username field
                TextFormField(
                  controller: _usernameController,
                  decoration: InputDecoration(
                    labelText: 'Username',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon:
                        const Icon(Icons.person, color: Color(0xFFFF87B2)),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your username';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Full Name field
                TextFormField(
                  controller: _fullNameController,
                  decoration: InputDecoration(
                    labelText: 'Full Name',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon: const Icon(Icons.person_outline,
                        color: Color(0xFFFF87B2)),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your full name';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Email field
                TextFormField(
                  controller: _emailController,
                  decoration: InputDecoration(
                    labelText: 'Email',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon:
                        const Icon(Icons.email, color: Color(0xFFFF87B2)),
                  ),
                  keyboardType: TextInputType.emailAddress,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your email';
                    }
                    if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$')
                        .hasMatch(value)) {
                      return 'Please enter a valid email';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Phone field
                TextFormField(
                  controller: _phoneController,
                  decoration: InputDecoration(
                    labelText: 'Phone Number',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon:
                        const Icon(Icons.phone, color: Color(0xFFFF87B2)),
                  ),
                  keyboardType: TextInputType.phone,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your phone number';
                    }
                    if (value.length < 10 || value.length > 13) {
                      return 'Phone number must be between 10 and 13 digits';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Address heading
                const Text(
                  'Address Information',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFFFF87B2),
                  ),
                ),
                const SizedBox(height: 12),

                // Street Address field
                TextFormField(
                  controller: _addressController,
                  decoration: InputDecoration(
                    labelText: 'Street Address',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon:
                        const Icon(Icons.home, color: Color(0xFFFF87B2)),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your street address';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // City field
                TextFormField(
                  controller: _cityController,
                  decoration: InputDecoration(
                    labelText: 'City',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon: const Icon(Icons.location_city,
                        color: Color(0xFFFF87B2)),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter your city';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Birth Date field
                TextFormField(
                  controller: _birthDateController,
                  decoration: InputDecoration(
                    labelText: 'Birth Date',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon: const Icon(Icons.calendar_today,
                        color: Color(0xFFFF87B2)),
                    suffixIcon: IconButton(
                      icon: const Icon(Icons.date_range,
                          color: Color(0xFFFF87B2)),
                      onPressed: () => _selectDate(context),
                    ),
                  ),
                  readOnly: true,
                  onTap: () => _selectDate(context),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please select your birth date';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Password field
                TextFormField(
                  controller: _passwordController,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon:
                        const Icon(Icons.lock, color: Color(0xFFFF87B2)),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility_off
                            : Icons.visibility,
                        color: const Color(0xFFFF87B2),
                      ),
                      onPressed: () {
                        setState(() {
                          _obscurePassword = !_obscurePassword;
                        });
                      },
                    ),
                  ),
                  obscureText: _obscurePassword,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a password';
                    }
                    if (value.length < 6) {
                      return 'Password must be at least 6 characters';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Confirm password field
                TextFormField(
                  controller: _confirmPasswordController,
                  decoration: InputDecoration(
                    labelText: 'Confirm Password',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          const BorderSide(color: Color(0xFFFF87B2), width: 2),
                    ),
                    prefixIcon: const Icon(Icons.lock_outline,
                        color: Color(0xFFFF87B2)),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscureConfirmPassword
                            ? Icons.visibility_off
                            : Icons.visibility,
                        color: const Color(0xFFFF87B2),
                      ),
                      onPressed: () {
                        setState(() {
                          _obscureConfirmPassword = !_obscureConfirmPassword;
                        });
                      },
                    ),
                  ),
                  obscureText: _obscureConfirmPassword,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please confirm your password';
                    }
                    if (value != _passwordController.text) {
                      return 'Passwords do not match';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 30),

                // Register button
                SizedBox(
                  width: double.infinity,
                  height: 55,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _handleRegister,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFF87B2),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 2,
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor:
                                  AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          )
                        : const Text(
                            'Create Account',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 20),

                // Login option
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text(
                      'Already have an account? ',
                      style: TextStyle(color: Colors.grey),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text(
                        'Login',
                        style: TextStyle(
                          color: Color(0xFFFF87B2),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _fullNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _birthDateController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }
}
