import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

class OtpVerificationPage extends StatefulWidget {
  final String email;
  final bool skipVerification;

  const OtpVerificationPage(
      {super.key, required this.email, this.skipVerification = false});

  @override
  State<OtpVerificationPage> createState() => _OtpVerificationPageState();
}

class _OtpVerificationPageState extends State<OtpVerificationPage> {
  final List<TextEditingController> _controllers = List.generate(
    6,
    (index) => TextEditingController(),
  );
  final List<FocusNode> _focusNodes = List.generate(
    6,
    (index) => FocusNode(),
  );

  bool _isLoading = false;
  bool _canResend = false;
  int _timeLeft = 180; // 3 minutes in seconds
  Timer? _timer;
  StreamSubscription<ConnectivityResult>? _connectivitySubscription;
  bool _isOffline = false;

  @override
  void initState() {
    super.initState();

    if (widget.skipVerification) {
      _showSkipVerificationNotice();
    }

    _startTimer();
    _setupConnectivityListener();
    _setupClipboardListener();
  }

  void _showSkipVerificationNotice() {
    Future.delayed(const Duration(milliseconds: 300), () {
      if (mounted) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => AlertDialog(
            title: const Text('Email Verification Skipped',
                style: TextStyle(color: Color(0xFFFF87B2))),
            content: const Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Email verification has been temporarily disabled.'),
                SizedBox(height: 10),
                Text(
                    'Your account has been created, but email services are unavailable.'),
                SizedBox(height: 10),
                Text('This would normally require email verification.')
              ],
            ),
            actions: [
              TextButton(
                child: const Text('Continue',
                    style: TextStyle(color: Color(0xFFFF87B2))),
                onPressed: () {
                  Navigator.of(context).pop();
                  _continueToHomePage();
                },
              ),
            ],
          ),
        );
      }
    });
  }

  void _continueToHomePage() {
    Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
  }

  @override
  void dispose() {
    _timer?.cancel();
    _connectivitySubscription?.cancel();
    for (var controller in _controllers) {
      controller.dispose();
    }
    for (var node in _focusNodes) {
      node.dispose();
    }
    super.dispose();
  }

  void _setupConnectivityListener() async {
    final connectivity = Connectivity();
    _isOffline =
        await connectivity.checkConnectivity() == ConnectivityResult.none;

    _connectivitySubscription =
        connectivity.onConnectivityChanged.listen((result) {
      setState(() {
        _isOffline = result == ConnectivityResult.none;
      });
    });
  }

  void _setupClipboardListener() {
    for (var node in _focusNodes) {
      node.addListener(() {
        if (node.hasFocus) {
          _checkClipboard();
        }
      });
    }
  }

  Future<void> _checkClipboard() async {
    try {
      final ClipboardData? data = await Clipboard.getData(Clipboard.kTextPlain);
      final String? text = data?.text;

      if (text != null &&
          text.length == 6 &&
          RegExp(r'^\d{6}$').hasMatch(text)) {
        if (!mounted) return;
        final shouldAutofill = await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Auto-fill OTP'),
            content: const Text('Would you like to use the copied OTP code?'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('No'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Yes'),
              ),
            ],
          ),
        );

        if (shouldAutofill == true) {
          _handlePastedOtp(text);
        }
      }
    } catch (e) {
      print('Error checking clipboard: $e');
    }
  }

  void _handlePastedOtp(String otp) {
    for (int i = 0; i < 6; i++) {
      _controllers[i].text = otp[i];
    }
    _focusNodes.last.unfocus();
    _verifyOtp();
  }

  void _startTimer() {
    setState(() {
      _timeLeft = 180;
      _canResend = false;
    });

    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_timeLeft > 0) {
        setState(() {
          _timeLeft--;
        });
      } else {
        setState(() {
          _canResend = true;
        });
        timer.cancel();
      }
    });
  }

  String get _formattedTime {
    int minutes = _timeLeft ~/ 60;
    int seconds = _timeLeft % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  Future<void> _resendOtp() async {
    if (!_canResend || _isLoading) return;

    if (_isOffline) {
      _showErrorSnackBar('Tidak ada koneksi internet');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final authService = Provider.of<AuthService>(context, listen: false);
      final result = await authService.resendOtp(email: widget.email);

      if (!mounted) return;

      if (result['success']) {
        _startTimer();
        _showSuccessSnackBar('OTP baru telah dikirim ke email Anda');
      } else {
        if (result['isRateLimited'] == true) {
          _showErrorSnackBar('Tunggu beberapa saat sebelum meminta OTP baru');
        } else {
          _showErrorSnackBar(result['message'] ?? 'Gagal mengirim ulang OTP');
        }
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _verifyOtp() async {
    if (_isLoading) return;

    if (_isOffline) {
      _showErrorSnackBar('Tidak ada koneksi internet');
      return;
    }

    String otp = _controllers.map((c) => c.text).join();

    if (otp.length != 6) {
      _showErrorSnackBar('Masukkan 6 digit kode OTP');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final authService = Provider.of<AuthService>(context, listen: false);
      final result = await authService.verifyOtp(widget.email, otp);

      if (!mounted) return;

      if (result['success']) {
        Navigator.pushReplacementNamed(context, '/login');
      } else {
        if (result['expired'] == true) {
          setState(() => _canResend = true);
        }
        _showErrorSnackBar(result['message'] ?? 'Verifikasi gagal');
      }
    } catch (e) {
      print('Error during OTP verification: $e');
      _showErrorSnackBar(
          'Terjadi kesalahan saat memverifikasi. Silakan coba lagi.');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _onDigitInput(String value, int index) {
    if (value.length == 1) {
      if (index < 5) {
        _focusNodes[index + 1].requestFocus();
      } else {
        _focusNodes[index].unfocus();
        _verifyOtp();
      }
    } else if (value.isEmpty && index > 0) {
      _focusNodes[index - 1].requestFocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Verifikasi OTP'),
        backgroundColor: const Color(0xFFFF87B2),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_isOffline)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(8),
                color: Colors.orange.shade100,
                child: const Row(
                  children: [
                    Icon(Icons.wifi_off, color: Colors.orange),
                    SizedBox(width: 8),
                    Text('Tidak ada koneksi internet',
                        style: TextStyle(color: Colors.orange)),
                  ],
                ),
              ),
            const SizedBox(height: 20),
            Text(
              'Kode OTP telah dikirim ke:',
              style: Theme.of(context).textTheme.bodyLarge,
            ),
            Text(
              widget.email,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 30),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: List.generate(
                6,
                (index) => SizedBox(
                  width: 45,
                  child: TextField(
                    controller: _controllers[index],
                    focusNode: _focusNodes[index],
                    keyboardType: TextInputType.number,
                    textAlign: TextAlign.center,
                    maxLength: 1,
                    style: const TextStyle(fontSize: 24),
                    decoration: InputDecoration(
                      counterText: '',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                        borderSide: const BorderSide(color: Color(0xFFFF87B2)),
                      ),
                    ),
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                    ],
                    onChanged: (value) => _onDigitInput(value, index),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 30),
            Center(
              child: Text(
                'Kode berlaku selama: $_formattedTime',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _verifyOtp,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFFF87B2),
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: _isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor:
                              AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      )
                    : const Text(
                        'Verifikasi',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
              ),
            ),
            const SizedBox(height: 20),
            Center(
              child: TextButton(
                onPressed: _canResend && !_isLoading ? _resendOtp : null,
                child: Text(
                  _canResend
                      ? 'Kirim Ulang OTP'
                      : 'Kirim Ulang OTP dalam $_formattedTime',
                  style: TextStyle(
                    color: _canResend ? const Color(0xFFFF87B2) : Colors.grey,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
