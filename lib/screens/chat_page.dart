import 'package:flutter/material.dart';
import 'dart:async';
import 'dart:math'; // Added for sin function
import 'dart:ui'; // Added for ImageFilter
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../models/user.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

// Custom painter to draw connecting lines between center and FAQs
class LinePainter extends CustomPainter {
  final double startX;
  final double startY;
  final double endX;
  final double endY;
  final double animationValue;
  final Color color;

  LinePainter({
    required this.startX,
    required this.startY,
    required this.endX,
    required this.endY,
    required this.animationValue,
    required this.color,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1.5
      ..style = PaintingStyle.stroke;

    // Calculate the animated endpoint based on animation value
    final double currentEndX =
        startX + (endX - startX) * min(1.0, animationValue);
    final double currentEndY =
        startY + (endY - startY) * min(1.0, animationValue);

    final path = Path();
    path.moveTo(startX, startY);

    // Create a slightly curved line
    final midX = (startX + currentEndX) / 2;
    final midY = (startY + currentEndY) / 2;
    const offset = 10.0; // Curve offset

    path.quadraticBezierTo(
        midX + offset, midY - offset, currentEndX, currentEndY);

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant LinePainter oldDelegate) =>
      oldDelegate.animationValue != animationValue;
}

class FAQ {
  final String question;
  final String answer;

  FAQ({required this.question, required this.answer});
}

class ChatMessage {
  final String message;
  final bool isSentByMe;
  final DateTime timestamp;
  final bool isDelivered;
  final bool isRead;
  final String? productImageUrl; // Optional product image URL

  ChatMessage({
    required this.message,
    required this.isSentByMe,
    required this.timestamp,
    this.isDelivered = false,
    this.isRead = false,
    this.productImageUrl,
  });

  // Convert ChatMessage to JSON for storage
  Map<String, dynamic> toJson() {
    return {
      'message': message,
      'isSentByMe': isSentByMe,
      'timestamp': timestamp.toIso8601String(),
      'isDelivered': isDelivered,
      'isRead': isRead,
      'productImageUrl': productImageUrl,
    };
  }

  // Create ChatMessage from JSON
  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      message: json['message'],
      isSentByMe: json['isSentByMe'],
      timestamp: DateTime.parse(json['timestamp']),
      isDelivered: json['isDelivered'],
      isRead: json['isRead'],
      productImageUrl: json['productImageUrl'],
    );
  }
}

class ChatPage extends StatefulWidget {
  final String? initialMessage;
  final String? productImageUrl;
  final String? productName;
  final int? productStock;
  final int? requestedQuantity;
  final bool showBottomNav;

  // Static fields to store pending chat info between screens
  static String? pendingInitialMessage;
  static String? pendingProductName;
  static String? pendingProductImageUrl;
  static int? pendingProductStock;
  static int? pendingRequestedQuantity;

  const ChatPage({
    super.key,
    this.initialMessage,
    this.productImageUrl,
    this.productName,
    this.productStock,
    this.requestedQuantity,
    this.showBottomNav = false,
  });

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> with TickerProviderStateMixin {
  final _messageController = TextEditingController();
  final List<ChatMessage> _messages = [];
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;
  bool _isTyping = false;
  bool _isSending = false;
  Timer? _typingTimer;
  User? _userData;
  AnimationController? _fabAnimationController;
  Animation<double>? _fabAnimation;
  final bool _showFaq = true; // Changed to true by default

  // Variables for draggable FAQ
  Offset _faqPosition = const Offset(20, 100);
  bool _isDragging = false; // Changed to non-final to allow updating
  bool _expandedFaq = false;

  // List of frequently asked questions - updated with the new content
  final List<FAQ> _faqs = [
    FAQ(
      question: "Berapa lama estimasi pengisian stok?",
      answer:
          "Waktu pengisian ulang stok biasanya memakan waktu antara 1 hingga 3 hari kerja. Kami selalu berusaha untuk memastikan ketersediaan produk secara optimal dan akan memberikan pemberitahuan apabila terjadi keterlambatan.",
    ),
    FAQ(
      question: "Apakah saya bisa custom rangkaian bunga sesuai permintaan?",
      answer:
          "Tentu, kami menyediakan layanan kustomisasi rangkaian bunga agar dapat disesuaikan dengan kebutuhan dan preferensi Anda. Anda dapat menentukan jenis bunga, warna, ukuran, serta gaya rangkaian atau mengirimkan foto bouquet bunga yang anda inginkan.",
    ),
    FAQ(
      question: "Layanan apa saja yang tersedia untuk pembayaran?",
      answer:
          "Saat ini, metode pembayaran yang tersedia adalah melalui virtual account dari berbagai bank seperti virtual account bank BRI,BCA,BNI",
    ),
    FAQ(
      question:
          "Bagaimana jika bunga yang saya terima rusak atau tidak sesuai?",
      answer:
          "Apabila bunga yang diterima dalam kondisi rusak atau tidak sesuai dengan pesanan, mohon segera menghubungi kami dalam waktu 24 jam setelah produk diterima dan segera kirim bukti foto/video. Kami akan memverifikasi laporan Anda.",
    ),
    FAQ(
      question: "Apakah kalian buka di hari libur atau di hari Minggu?",
      answer:
          "Ya, kami tetap melayani pelanggan pada hari libur nasional dan hari Minggu.",
    ),
    FAQ(
      question: "Berapa lama waktu pengiriman bunga?",
      answer:
          "Waktu pengiriman bunga biasanya memakan waktu 1-2 hari kerja tergantung lokasi pengiriman. Untuk pengiriman di hari yang sama, pesanan harus masuk sebelum jam 12 siang.",
    ),
    FAQ(
      question: "Apakah bisa membuat kartu ucapan dengan pesan pribadi?",
      answer:
          "Ya, kami menyediakan layanan kartu ucapan dengan pesan pribadi. Anda dapat menambahkan pesan khusus saat melakukan checkout pesanan Anda.",
    ),
  ];

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _loadChatMessages();

    // Initialize FAQ position to top right corner
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final size = MediaQuery.of(context).size;
      setState(() {
        _faqPosition =
            Offset(size.width - 80, size.height * 0.15); // Top right corner
      });
    });

    // Setup animations
    _fabAnimationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _fabAnimation = CurvedAnimation(
      parent: _fabAnimationController!,
      curve: Curves.easeOut,
    );
    _fabAnimationController!.forward();

    // Automatically show FAQ for first time or when chat is empty
    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted && _messages.isEmpty) {
        setState(() {
          _expandedFaq = true;
        });

        // Auto-hide FAQ panel after some time if user doesn't interact with it
        Future.delayed(const Duration(seconds: 8), () {
          if (mounted && _expandedFaq && _messages.isEmpty) {
            setState(() {
              _expandedFaq = false;
            });
          }
        });
      }
    });

    // Check for any pending messages set through static fields
    if (ChatPage.pendingInitialMessage != null) {
      // Get values from static fields
      final pendingMessage = ChatPage.pendingInitialMessage!;
      final pendingProductName = ChatPage.pendingProductName;
      final pendingProductImageUrl = ChatPage.pendingProductImageUrl;
      final pendingProductStock = ChatPage.pendingProductStock;
      final pendingRequestedQuantity = ChatPage.pendingRequestedQuantity;

      // Clear static fields to prevent duplicates
      ChatPage.pendingInitialMessage = null;
      ChatPage.pendingProductName = null;
      ChatPage.pendingProductImageUrl = null;
      ChatPage.pendingProductStock = null;
      ChatPage.pendingRequestedQuantity = null;

      // Process the pending message immediately
      Future.delayed(Duration.zero, () {
        if (mounted) {
          // Create parameters object that matches what we use elsewhere
          final processParams = {
            'initialMessage': pendingMessage,
            'productName': pendingProductName,
            'productImageUrl': pendingProductImageUrl,
            'productStock': pendingProductStock,
            'requestedQuantity': pendingRequestedQuantity,
          };

          // Process this message
          _processPendingMessage(processParams);
        }
      });
    }
    // Process any initial message provided directly
    else if (widget.initialMessage != null &&
        widget.initialMessage!.isNotEmpty) {
      // Process immediately without delay
      _processInitialMessage();
    }
  }

  // New method to process initial messages immediately
  void _processInitialMessage() {
    // Get username if available
    String userName = '';
    if (_userData != null &&
        _userData?.username != null &&
        _userData!.username!.isNotEmpty) {
      userName = _userData!.username!;
    } else {
      try {
        final authService = Provider.of<AuthService>(context, listen: false);
        userName = authService.currentUser?.username ?? '';
      } catch (_) {}
    }

    // Check if this is a product stock inquiry
    if (widget.productName != null && widget.productStock != null) {
      final String message = widget.initialMessage!;

      // Add user message immediately
      setState(() {
        _messages.add(
          ChatMessage(
            message: message,
            isSentByMe: true,
            timestamp: DateTime.now(),
            isDelivered: true,
            productImageUrl: widget.productImageUrl,
          ),
        );

        // Also add immediate response from admin
        _isTyping = true;
      });

      // Show admin response after a short typing animation
      Future.delayed(const Duration(milliseconds: 1500), () {
        if (mounted) {
          setState(() {
            _isTyping = false;

            // Create custom stock response based on the product details
            String responseMessage = '';
            if (widget.productStock == 0) {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "${widget.productName}". Produk ini sedang kosong dan kami sedang melakukan pengisian stok. Kami perkirakan akan tersedia dalam 2-3 hari kerja. Kami akan segera memberi tahu Anda saat produk tersedia kembali.';
            } else if (widget.requestedQuantity != null &&
                widget.productStock! < widget.requestedQuantity!) {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "${widget.productName}". Saat ini stok tersedia hanya ${widget.productStock} buah, sedangkan Anda membutuhkan ${widget.requestedQuantity} buah. Kami akan segera melakukan pengisian stok dan memberi tahu Anda saat jumlah yang Anda inginkan tersedia.';
            } else {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "${widget.productName}". Kami akan segera mengisi kembali stok produk yang anda inginkan dan akan segera mengabari anda. Terima kasih sudah tertarik pada produk kami.';
            }

            _messages.add(
              ChatMessage(
                message: responseMessage,
                isSentByMe: false,
                timestamp: DateTime.now(),
                isDelivered: true,
                isRead: true,
              ),
            );
          });
        }
      });
    } else if (widget.initialMessage!.contains("out of stock")) {
      // Extract product name using regex
      final RegExp regex = RegExp(r'"([^"]*)"');
      final match = regex.firstMatch(widget.initialMessage!);

      if (match != null && match.groupCount > 0) {
        final productName = match.group(1);

        // Add user message immediately
        setState(() {
          _messages.add(
            ChatMessage(
              message: widget.initialMessage!,
              isSentByMe: true,
              timestamp: DateTime.now(),
              isDelivered: true,
              productImageUrl: widget.productImageUrl,
            ),
          );

          _isTyping = true;
        });

        // Show response after short typing animation
        Future.delayed(const Duration(milliseconds: 1500), () {
          if (mounted) {
            setState(() {
              _isTyping = false;
              _messages.add(
                ChatMessage(
                  message:
                      "Terima kasih telah menghubungi kami mengenai produk \"$productName\" yang sedang kosong. Kami perkirakan produk tersebut akan tersedia kembali dalam 2-3 hari kerja. Kami sudah mencatat ketertarikan Anda dan akan segera memberitahu begitu stok tersedia. Apakah ada yang bisa kami bantu lainnya?",
                  isSentByMe: false,
                  timestamp: DateTime.now(),
                  isDelivered: true,
                  isRead: true,
                ),
              );
            });
          }
        });
      } else {
        // Regular message - send immediately
        _messageController.text = widget.initialMessage!;
        _handleSendMessage();
      }
    } else {
      // Regular message
      _messageController.text = widget.initialMessage!;
      _handleSendMessage();
    }
  }

  Future<void> _loadUserData() async {
    try {
      final authService = Provider.of<AuthService>(context, listen: false);
      if (authService.currentUser != null) {
        setState(() {
          _userData = authService.currentUser;
        });
      } else {
        await authService.getUser();
        if (mounted) {
          setState(() {
            _userData = authService.currentUser;
          });
        }
      }
    } catch (e) {
      print('Error loading user data: $e');
    }
  }

  Future<void> _loadChatMessages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final messagesJson = prefs.getString('chat_messages');

      if (messagesJson != null && messagesJson.isNotEmpty) {
        final List<dynamic> decodedMessages = json.decode(messagesJson);
        final List<ChatMessage> loadedMessages =
            decodedMessages.map((msg) => ChatMessage.fromJson(msg)).toList();

        if (loadedMessages.isNotEmpty) {
          setState(() {
            _messages.addAll(loadedMessages);
          });
          print('Loaded ${loadedMessages.length} messages from storage');
          return;
        }
      }

      // If no messages were loaded, continue with loading dummy messages
      print('No saved messages found, loading welcome message');
      _loadDummyMessages();
    } catch (e) {
      print('Error loading chat messages: $e');
      _loadDummyMessages();
    }
  }

  Future<void> _loadDummyMessages() async {
    setState(() => _isLoading = true);

    // Shorter loading delay for better UX
    await Future.delayed(const Duration(milliseconds: 300));

    if (mounted) {
      setState(() {
        // Only add the welcome message if there are no other messages
        // This ensures the product inquiry appears first
        if (_messages.isEmpty) {
          _messages.add(
            ChatMessage(
              message:
                  "Selamat datang di Customer Service Bloom Bouquet! Saya siap membantu Anda dengan pertanyaan dan kebutuhan Anda. Silakan pilih pertanyaan dari menu FAQ atau tulis pesan langsung kepada kami.",
              isSentByMe: false,
              timestamp: DateTime.now().subtract(const Duration(minutes: 5)),
              isDelivered: true,
              isRead: true,
            ),
          );
        }
        _isLoading = false;
      });
    }
  }

  void _simulateTyping() {
    setState(() => _isTyping = true);

    // Cancel any existing typing timer
    _typingTimer?.cancel();

    // Set a new timer for typing indicator
    _typingTimer = Timer(const Duration(seconds: 3), () {
      if (mounted) {
        setState(() => _isTyping = false);
      }
    });
  }

  void _handleSendMessage() {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    setState(() {
      _isSending = true;
      _messages.add(
        ChatMessage(
          message: message,
          isSentByMe: true,
          timestamp: DateTime.now(),
        ),
      );
      _messageController.clear();
    });

    // Scroll to bottom
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          0.0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });

    // Simulate sending delay
    Future.delayed(const Duration(milliseconds: 800), () {
      if (mounted) {
        setState(() {
          // Update last message to show as delivered
          _messages[_messages.length - 1] = ChatMessage(
            message: _messages[_messages.length - 1].message,
            isSentByMe: true,
            timestamp: _messages[_messages.length - 1].timestamp,
            isDelivered: true,
          );
          _isSending = false;
        });
      }
    });

    // Simulate typing
    Future.delayed(const Duration(seconds: 1), () {
      if (mounted) {
        _simulateTyping();
      }
    });

    // Simulate response after typing
    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) {
        setState(() {
          _isTyping = false;
          _messages.add(
            ChatMessage(
              message: _generateResponse(message),
              isSentByMe: false,
              timestamp: DateTime.now(),
              isDelivered: true,
              isRead: true,
            ),
          );

          // Save chat messages after adding response
          _saveChatMessages();
        });
      }
    });
  }

  // Method to ask a FAQ
  void _askFAQ(FAQ faq) {
    // Hide the FAQ section after clicking a question
    setState(() {
      _expandedFaq = false;
    });

    // Set the question to the input field
    _messageController.text = faq.question;

    // Send the message
    _handleSendMessage();

    // The response will be handled by the existing message system
  }

  // Method to handle product inquiry with product details
  void handleProductInquiry(String productName, String productImageUrl) {
    // Create a message with product details
    final message =
        'Hi Admin, I\'m interested in the product "$productName" but it\'s currently out of stock. When will it be available again?';

    setState(() {
      _messages.add(
        ChatMessage(
          message: message,
          isSentByMe: true,
          timestamp: DateTime.now(),
          isDelivered: true,
        ),
      );
    });

    // Simulate typing
    Future.delayed(const Duration(seconds: 1), () {
      if (mounted) {
        _simulateTyping();
      }
    });

    // Simulate response with specific information about restocking
    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) {
        setState(() {
          _isTyping = false;
          _messages.add(
            ChatMessage(
              message:
                  "Terima kasih telah menghubungi kami mengenai produk \"$productName\" yang sedang kosong. Kami perkirakan produk tersebut akan tersedia kembali dalam 2-3 hari kerja. Kami sudah mencatat ketertarikan Anda dan akan segera memberitahu begitu stok tersedia. Apakah ada yang bisa kami bantu lainnya?",
              isSentByMe: false,
              timestamp: DateTime.now(),
              isDelivered: true,
              isRead: true,
            ),
          );
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final screenSize = MediaQuery.of(context).size;
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    final bottomNavHeight = widget.showBottomNav ? 60.0 : 0.0;
    final totalBottomPadding = bottomNavHeight + bottomPadding;

    // Ensure the FAQ is within the screen bounds, accounting for bottom navigation
    final safeBottomOffset = screenSize.height - 200 - totalBottomPadding;
    _faqPosition = Offset(_faqPosition.dx.clamp(16, screenSize.width - 80),
        _faqPosition.dy.clamp(80, safeBottomOffset));

    // The main content of the chat page
    final chatContent = Stack(
      children: [
        Column(
          children: [
            // Customer service info at the top
            _buildCustomerServiceHeader(),

            // Messages section
            Expanded(
              child: _messages.isEmpty
                  ? _buildEmptyChat()
                  : ListView.builder(
                      reverse: true,
                      controller: _scrollController,
                      padding: EdgeInsets.only(
                        top: 20,
                        bottom: 100 +
                            totalBottomPadding, // Increase padding to avoid bottom nav and ensure visibility
                        left: 16,
                        right: 16,
                      ),
                      itemCount: _messages.length + (_isTyping ? 1 : 0),
                      itemBuilder: (context, index) {
                        // Reverse the index to display messages in reverse chronological order
                        final actualIndex = _messages.length - 1 - index;

                        // Show typing indicator at the very bottom (most recent)
                        if (_isTyping && index == 0) {
                          return _buildTypingIndicator();
                        }

                        // Adjust index if typing indicator is showing
                        final messageIndex =
                            _isTyping ? actualIndex + 1 : actualIndex;
                        if (messageIndex < 0 ||
                            messageIndex >= _messages.length) {
                          return const SizedBox.shrink();
                        }

                        final message = _messages[messageIndex];
                        return _buildMessageBubble(message);
                      },
                    ),
            ),

            // Message input section with SafeArea to ensure it's not covered by system UI
            SafeArea(
              bottom: true,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8.0, vertical: 8.0),
                color: Colors.white,
                child: Row(
                  children: [
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(30),
                        ),
                        child: TextField(
                          controller: _messageController,
                          decoration: const InputDecoration(
                            hintText: 'Tulis pesan...',
                            border: InputBorder.none,
                          ),
                          textCapitalization: TextCapitalization.sentences,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    InkWell(
                      onTap: _isSending ? null : _handleSendMessage,
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFF87B2),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFFF87B2).withOpacity(0.3),
                              blurRadius: 10,
                              offset: const Offset(0, 5),
                            ),
                          ],
                        ),
                        child: _isSending
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white),
                                ),
                              )
                            : const Icon(
                                Icons.send_rounded,
                                color: Colors.white,
                                size: 24,
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),

        // Floating FAQ button - draggable
        if (_showFaq && !_expandedFaq)
          Positioned(
            left: _faqPosition
                .dx, // Use X position from state for free positioning
            top: _faqPosition.dy, // Use Y position from state
            child: GestureDetector(
              onTap: _isDragging
                  ? null
                  : () {
                      setState(() {
                        _expandedFaq = true;
                      });
                    },
              onPanStart: (_) {
                setState(() {
                  _isDragging = true;
                });
              },
              onPanUpdate: (details) {
                setState(() {
                  // Allow free movement in both X and Y directions
                  _faqPosition = Offset(
                    (_faqPosition.dx + details.delta.dx)
                        .clamp(16, screenSize.width - 80),
                    (_faqPosition.dy + details.delta.dy)
                        .clamp(80, safeBottomOffset),
                  );
                });
              },
              onPanEnd: (_) {
                // Small delay to prevent accidental tap when dragging ends
                Future.delayed(const Duration(milliseconds: 100), () {
                  if (mounted) {
                    setState(() {
                      _isDragging = false;
                    });
                  }
                });
              },
              child: _buildFaqIcon(),
            ),
          ),

        // Show expanded FAQ panel when needed - centered on screen
        if (_expandedFaq)
          Positioned.fill(
            child: Container(
              color: Colors.black.withOpacity(0.3), // Semi-transparent overlay
              child: Center(
                child: TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0.8, end: 1.0),
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeOutBack,
                  builder: (context, scale, child) {
                    return Transform.scale(
                      scale: scale,
                      child: _buildVerticalFaqPanel(),
                    );
                  },
                ),
              ),
            ),
          ),
      ],
    );

    // Return either a full Scaffold or just the content based on showBottomNav
    if (widget.showBottomNav) {
      return Scaffold(
        body: chatContent,
      );
    } else {
      // Just return the content without Scaffold when used in a bottom sheet
      return chatContent;
    }
  }

  // New method to build customer service header
  Widget _buildCustomerServiceHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 50, 16, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Profile image with online indicator - replaced with icon
          Container(
            margin: const EdgeInsets.only(right: 12),
            child: Stack(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFFFF87B2), Color(0xFFFF5A8A)],
                    ),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFFF87B2).withOpacity(0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      // Inner circle for design
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.15),
                          shape: BoxShape.circle,
                        ),
                      ),
                      // Customer service icon
                      const Icon(
                        Icons.support_agent,
                        color: Colors.white,
                        size: 28,
                      ),
                    ],
                  ),
                ),
                // Online indicator
                Positioned(
                  right: 0,
                  bottom: 0,
                  child: Container(
                    width: 14,
                    height: 14,
                    decoration: BoxDecoration(
                      color: Colors.green,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: Colors.white,
                        width: 2,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Title and status
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Customer Service',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      margin: const EdgeInsets.only(right: 5),
                      decoration: const BoxDecoration(
                        color: Colors.green,
                        shape: BoxShape.circle,
                      ),
                    ),
                    Text(
                      _isTyping ? 'mengetik...' : 'online',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          // More options
          IconButton(
            icon: Icon(Icons.more_vert, color: Colors.grey[700]),
            onPressed: () {
              // Show options menu
              showModalBottomSheet(
                context: context,
                shape: const RoundedRectangleBorder(
                  borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                ),
                builder: (context) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 20),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      ListTile(
                        leading: const Icon(Icons.delete),
                        title: const Text('Clear chat'),
                        onTap: () {
                          Navigator.pop(context);
                          _clearChat();
                        },
                      ),
                      ListTile(
                        leading: const Icon(Icons.help_outline),
                        title: const Text('Show FAQ'),
                        onTap: () {
                          Navigator.pop(context);
                          setState(() {
                            _expandedFaq = true;
                          });
                        },
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildFaqIcon() {
    return AnimatedBuilder(
      animation: _fabAnimationController!,
      builder: (context, child) {
        return Transform.scale(
          scale: _fabAnimation!.value,
          child: Stack(
            children: [
              // Pulse animation for visibility
              TweenAnimationBuilder<double>(
                tween: Tween(begin: 0.0, end: 1.0),
                duration: const Duration(milliseconds: 2000),
                curve: Curves.easeInOut,
                builder: (context, value, child) {
                  return Opacity(
                    opacity: (1.0 - value) * 0.3,
                    child: Transform.scale(
                      scale: 1.0 + (value * 0.1),
                      child: Container(
                        width: 56,
                        height: 56,
                        decoration: const BoxDecoration(
                          color: Color(0xFFFF87B2),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                  );
                },
              ),

              // Main button
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFFFF87B2), Color(0xFFFF5A8A)],
                  ),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFFF87B2).withOpacity(0.3),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    // Subtle design elements
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.15),
                        shape: BoxShape.circle,
                      ),
                    ),
                    // Icon for FAQ
                    const Icon(
                      Icons.help_outline,
                      color: Colors.white,
                      size: 28,
                    ),
                    // Hint animation to indicate it's clickable
                    TweenAnimationBuilder<double>(
                      tween: Tween(begin: 0.9, end: 1.0),
                      duration: const Duration(milliseconds: 1500),
                      curve: Curves.easeInOut,
                      builder: (context, value, child) {
                        return Transform.scale(
                          scale: value,
                          child: Container(
                            width: 16,
                            height: 16,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              shape: BoxShape.circle,
                              border: Border.all(
                                color: const Color(0xFFFF87B2),
                                width: 2,
                              ),
                            ),
                            child: const Center(
                              child: Text(
                                '?',
                                style: TextStyle(
                                  color: Color(0xFFFF87B2),
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),

              // Show drag icon only when dragging for better UX
              if (_isDragging)
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.5),
                    shape: BoxShape.circle,
                  ),
                  child: const Center(
                    child: Icon(
                      Icons.open_with,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildVerticalFaqPanel() {
    final screenSize = MediaQuery.of(context).size;
    // Use a centered panel with appropriate width
    final double maxPanelWidth = min(screenSize.width * 0.9, 400.0);
    final double maxPanelHeight = min(screenSize.height * 0.8, 600.0);

    return Container(
      width: maxPanelWidth,
      constraints: BoxConstraints(
        maxHeight: maxPanelHeight,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header with close button
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFFF87B2), Color(0xFFFF5A8A)],
                ),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.question_answer,
                    size: 22,
                    color: Colors.white,
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      "Pertanyaan Umum",
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  // Close button
                  InkWell(
                    onTap: () {
                      setState(() {
                        _expandedFaq = false;
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.3),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.close,
                        size: 20,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // FAQ list
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: _faqs.length,
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                itemBuilder: (context, index) {
                  // Create beautiful gradient color based on index
                  final List<List<Color>> gradients = [
                    [const Color(0xFFFF87B2), const Color(0xFFFF5A8A)], // Pink
                    [const Color(0xFF90CAF9), const Color(0xFF42A5F5)], // Blue
                    [const Color(0xFFA5D6A7), const Color(0xFF66BB6A)], // Green
                    [
                      const Color(0xFFFFCC80),
                      const Color(0xFFFF9800)
                    ], // Orange
                    [
                      const Color(0xFFCE93D8),
                      const Color(0xFF9C27B0)
                    ], // Purple
                    [
                      const Color(0xFFFFAB91),
                      const Color(0xFFFF5722)
                    ], // Deep Orange
                    [
                      const Color(0xFFB39DDB),
                      const Color(0xFF673AB7)
                    ], // Indigo
                  ];

                  final colorIndex = index % gradients.length;

                  return AnimatedContainer(
                    duration: Duration(milliseconds: 300 + (index * 30)),
                    curve: Curves.easeOutQuad,
                    transform: Matrix4.translationValues(0, 0, 0),
                    margin: const EdgeInsets.only(bottom: 10),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => _askFAQ(_faqs[index]),
                        borderRadius: BorderRadius.circular(15),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 14),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: gradients[colorIndex],
                            ),
                            borderRadius: BorderRadius.circular(15),
                            boxShadow: [
                              BoxShadow(
                                color:
                                    gradients[colorIndex][0].withOpacity(0.2),
                                blurRadius: 4,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    width: 30,
                                    height: 30,
                                    decoration: BoxDecoration(
                                        color: Colors.white,
                                        shape: BoxShape.circle,
                                        boxShadow: [
                                          BoxShadow(
                                            color:
                                                Colors.black.withOpacity(0.1),
                                            blurRadius: 2,
                                            offset: const Offset(0, 1),
                                          )
                                        ]),
                                    child: Center(
                                      child: Text(
                                        '${index + 1}',
                                        style: TextStyle(
                                          color: gradients[colorIndex][1],
                                          fontSize: 14,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      _faqs[index].question,
                                      style: const TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 6,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(
                                      Icons.touch_app,
                                      size: 14,
                                      color: Colors.white,
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      "Tap untuk melihat jawaban",
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.white.withOpacity(0.9),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              blurRadius: 3,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildDot(1),
            _buildDot(2),
            _buildDot(3),
          ],
        ),
      ),
    );
  }

  Widget _buildDot(int index) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 2),
      child: TweenAnimationBuilder<double>(
        tween: Tween(begin: 0.0, end: 1.0),
        duration: Duration(milliseconds: 400 + (index * 100)),
        curve: Curves.easeInOut,
        builder: (context, value, child) {
          return Transform.translate(
            offset: Offset(0, -3 * sin(value * 3.14)),
            child: Container(
              width: 7,
              height: 7,
              decoration: BoxDecoration(
                color: const Color(0xFFFF87B2).withOpacity(0.7),
                shape: BoxShape.circle,
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildMessageBubble(ChatMessage message) {
    return Container(
      margin: EdgeInsets.only(
        bottom: 12,
        left: message.isSentByMe ? 50 : 0,
        right: message.isSentByMe ? 0 : 50,
      ),
      child: AnimatedOpacity(
        opacity: 1.0,
        duration: const Duration(milliseconds: 300),
        child: Column(
          crossAxisAlignment: message.isSentByMe
              ? CrossAxisAlignment.end
              : CrossAxisAlignment.start,
          children: [
            // Product image if available
            if (message.productImageUrl != null &&
                message.productImageUrl!.isNotEmpty)
              Container(
                margin: EdgeInsets.only(
                  bottom: 8,
                  left: message.isSentByMe ? 50 : 0,
                  right: message.isSentByMe ? 0 : 50,
                ),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: const Color(0xFFFF87B2).withOpacity(0.5),
                    width: 2,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.2),
                      blurRadius: 5,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Image.network(
                    message.productImageUrl!,
                    height: 150,
                    width: 200,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        height: 150,
                        width: 200,
                        color: Colors.grey[200],
                        child: const Center(
                          child: Icon(Icons.image_not_supported,
                              size: 40, color: Colors.grey),
                        ),
                      );
                    },
                    loadingBuilder: (context, child, loadingProgress) {
                      if (loadingProgress == null) return child;
                      return Container(
                        height: 150,
                        width: 200,
                        color: Colors.grey[100],
                        child: Center(
                          child: CircularProgressIndicator(
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded /
                                    loadingProgress.expectedTotalBytes!
                                : null,
                            valueColor: const AlwaysStoppedAnimation<Color>(
                                Color(0xFFFF87B2)),
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),

            // Message bubble
            Row(
              mainAxisAlignment: message.isSentByMe
                  ? MainAxisAlignment.end
                  : MainAxisAlignment.start,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (!message.isSentByMe)
                  Container(
                    margin: const EdgeInsets.only(right: 8),
                    child: const CircleAvatar(
                      radius: 16,
                      backgroundColor: Color(0xFFFF87B2),
                      child: Icon(
                        Icons.support_agent,
                        size: 18,
                        color: Colors.white,
                      ),
                    ),
                  ),
                Flexible(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: message.isSentByMe
                          ? const Color(0xFFFF87B2)
                          : Colors.white,
                      borderRadius: BorderRadius.circular(20).copyWith(
                        bottomRight: message.isSentByMe
                            ? const Radius.circular(5)
                            : const Radius.circular(20),
                        bottomLeft: message.isSentByMe
                            ? const Radius.circular(20)
                            : const Radius.circular(5),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 3,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          message.message,
                          style: TextStyle(
                            color: message.isSentByMe
                                ? Colors.white
                                : Colors.black87,
                            fontSize: 15,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              _formatTime(message.timestamp),
                              style: TextStyle(
                                fontSize: 11,
                                color: message.isSentByMe
                                    ? Colors.white.withOpacity(0.7)
                                    : Colors.black54,
                              ),
                            ),
                            if (message.isSentByMe) ...[
                              const SizedBox(width: 4),
                              Icon(
                                message.isRead
                                    ? Icons.done_all
                                    : message.isDelivered
                                        ? Icons.done
                                        : Icons.access_time,
                                size: 14,
                                color: message.isRead
                                    ? Colors.white
                                    : Colors.white.withOpacity(0.7),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                if (message.isSentByMe)
                  Container(
                    margin: const EdgeInsets.only(left: 8),
                    child: CircleAvatar(
                      radius: 16,
                      backgroundColor: const Color(0xFFFF87B2),
                      child: _userData?.profile_photo != null
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(16),
                              child: Image.network(
                                _userData!.profile_photo!,
                                width: 32,
                                height: 32,
                                fit: BoxFit.cover,
                              ),
                            )
                          : Text(
                              _userData?.username?.isNotEmpty == true
                                  ? _userData!.username![0].toUpperCase()
                                  : 'U',
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(DateTime time) {
    return DateFormat('HH:mm').format(time);
  }

  String _generateResponse(String message) {
    message = message.toLowerCase();

    // Check if the message matches any FAQ
    for (var faq in _faqs) {
      if (message.toLowerCase() == faq.question.toLowerCase()) {
        return faq.answer;
      }
    }

    // Check if this is a product inquiry about availability or stock
    if ((message.contains('stok') ||
            message.contains('ketersediaan') ||
            message.contains('stock') ||
            message.contains('availability')) &&
        (message.contains('produk') || message.contains('product'))) {
      // Try to extract product name if it's in quotes
      final RegExp regex = RegExp(r'"([^"]*)"');
      final match = regex.firstMatch(message);

      if (match != null && match.groupCount > 0) {
        final productName = match.group(1);
        return "Terima kasih telah menghubungi kami tentang ketersediaan produk \"$productName\". Kami sedang memeriksa stok produk tersebut dan akan memberikan informasi sesegera mungkin. Kami akan berusaha memenuhi permintaan Anda dan akan melakukan pengisian stok jika diperlukan.";
      } else {
        return "Terima kasih telah menghubungi kami tentang ketersediaan produk. Kami sedang memeriksa stok produk tersebut dan akan memberikan informasi sesegera mungkin. Mohon sebutkan nama spesifik produk yang Anda cari agar kami bisa memberikan informasi yang lebih akurat.";
      }
    }

    // Check if this is a product inquiry (contains product name and out of stock)
    if (message.contains('out of stock') &&
        message.contains('interested in the product')) {
      // Extract product name from the message using RegExp
      final RegExp regex = RegExp(r'"([^"]*)"');
      final match = regex.firstMatch(message);
      if (match != null && match.groupCount > 0) {
        final productName = match.group(1);
        return "Terima kasih telah menghubungi kami mengenai produk \"$productName\" yang sedang kosong. Kami perkirakan produk tersebut akan tersedia kembali dalam 2-3 hari kerja. Kami sudah mencatat ketertarikan Anda dan akan segera memberitahu begitu stok tersedia. Apakah ada yang bisa kami bantu lainnya?";
      }
    }

    // Default responses for common queries
    if (message.contains('stok') ||
        message.contains('stock') ||
        message.contains('tersedia')) {
      return "Terima kasih atas pertanyaannya. Untuk pengecekan stok produk, mohon berikan nama produk yang ingin Anda cek ketersediaannya.";
    } else if (message.contains('harga') || message.contains('price')) {
      return "Untuk informasi harga produk, Anda dapat melihatnya di halaman detail produk. Jika ada pertanyaan spesifik tentang harga, mohon sebutkan nama produknya.";
    } else if (message.contains('pengiriman') ||
        message.contains('delivery') ||
        message.contains('kirim')) {
      return "Untuk pengiriman, kami menyediakan beberapa metode yang dapat Anda pilih saat checkout. Pengiriman biasanya membutuhkan waktu 1-3 hari kerja tergantung lokasi Anda.";
    } else if (message.contains('bayar') ||
        message.contains('payment') ||
        message.contains('pembayaran')) {
      return "Kami menerima pembayaran melalui transfer bank, QRIS, dan virtual account. Semua metode pembayaran dapat Anda pilih saat proses checkout.";
    } else if (message.contains('hello') ||
        message.contains('hi') ||
        message.contains('halo')) {
      return "Halo! Selamat datang di Bloom Bouquet. Ada yang bisa kami bantu hari ini?";
    } else if (message.contains('custom') ||
        message.contains('kustom') ||
        message.contains('sesuai permintaan')) {
      return "Tentu, kami menyediakan layanan kustomisasi rangkaian bunga agar dapat disesuaikan dengan kebutuhan dan preferensi Anda. Anda dapat menentukan jenis bunga, warna, ukuran, serta gaya rangkaian atau mengirimkan foto bouquet bunga yang anda inginkan.";
    } else if (message.contains('rusak') ||
        message.contains('tidak sesuai') ||
        message.contains('komplain')) {
      return "Apabila bunga yang diterima dalam kondisi rusak atau tidak sesuai dengan pesanan, mohon segera menghubungi kami dalam waktu 24 jam setelah produk diterima dan segera kirim bukti foto/video. Kami akan memverifikasi laporan Anda.";
    } else if (message.contains('buka') ||
        message.contains('hari libur') ||
        message.contains('minggu')) {
      return "Ya, kami tetap melayani pelanggan pada hari libur nasional dan hari Minggu.";
    } else {
      return "Terima kasih atas pesan Anda. Customer service kami akan segera menghubungi Anda. Jika ada pertanyaan lain, silakan sampaikan kepada kami.";
    }
  }

  // Helper method to create a product stock inquiry message
  String _createProductStockMessage(
      String productName, int stock, int requestedQuantity) {
    if (stock == 0) {
      return 'Halo Admin, saya tertarik dengan produk "$productName" tetapi saat ini stoknya kosong. Saya ingin membeli $requestedQuantity buah, kapan kira-kira akan tersedia kembali?';
    } else if (stock < requestedQuantity) {
      return 'Halo Admin, saya tertarik dengan produk "$productName" tetapi stoknya hanya tersisa $stock buah. Saya sebenarnya membutuhkan $requestedQuantity buah. Apakah bisa dipesan lebih dulu atau kapan akan ada tambahan stok?';
    } else {
      return 'Halo Admin, saya tertarik dengan produk "$productName" dan ingin menanyakan beberapa hal tentang produk ini sebelum melakukan pembelian.';
    }
  }

  // Helper method to generate response for stock inquiry
  String _generateStockInquiryResponse(
      String productName, int stock, int requestedQuantity) {
    if (stock == 0) {
      return 'Terima kasih telah menghubungi kami mengenai produk "$productName". Mohon maaf produk ini sedang tidak tersedia. Kami perkirakan akan tersedia kembali dalam 3-5 hari kerja. Kami sudah mencatat permintaan Anda untuk $requestedQuantity buah dan akan segera memberitahu begitu stok tersedia. Apakah Anda ingin kami menyisihkan produk ini untuk Anda saat sudah tersedia?';
    } else if (stock < requestedQuantity) {
      return 'Terima kasih telah menghubungi kami mengenai produk "$productName". Benar, saat ini stok kami hanya tersisa $stock buah, sedangkan Anda membutuhkan $requestedQuantity buah. Kami bisa membantu memesan lebih dulu dan akan mendapatkan tambahan stok dalam 2-3 hari kerja. Apakah Anda berkenan untuk membeli $stock buah terlebih dahulu, dan kami akan menyisihkan sisanya ($requestedQuantity - $stock) saat stok sudah tersedia?';
    } else {
      return 'Terima kasih telah menghubungi kami mengenai produk "$productName". Dengan senang hati kami akan membantu menjawab pertanyaan Anda. Silakan sampaikan pertanyaan spesifik yang ingin Anda ketahui tentang produk ini. Stok saat ini tersedia $stock buah, cukup untuk memenuhi permintaan Anda sebanyak $requestedQuantity buah.';
    }
  }

  // Method to clear chat history
  void _clearChat() {
    setState(() {
      _messages.clear();
      _loadDummyMessages();
    });

    // Save the cleared state
    _saveChatMessages();
  }

  // New method to process pending message from static fields
  void _processPendingMessage(Map<String, dynamic> params) {
    final initialMessage = params['initialMessage'] as String;
    final productName = params['productName'] as String?;
    final productImageUrl = params['productImageUrl'] as String?;
    final productStock = params['productStock'] as int?;
    final requestedQuantity = params['requestedQuantity'] as int?;

    // Get username if available
    String userName = '';
    if (_userData != null &&
        _userData?.username != null &&
        _userData!.username!.isNotEmpty) {
      userName = _userData!.username!;
    } else {
      try {
        final authService = Provider.of<AuthService>(context, listen: false);
        userName = authService.currentUser?.username ?? '';
      } catch (_) {}
    }

    // Check if this is a product stock inquiry
    if (productName != null && productStock != null) {
      // Add user message immediately
      setState(() {
        _messages.add(
          ChatMessage(
            message: initialMessage,
            isSentByMe: true,
            timestamp: DateTime.now(),
            isDelivered: true,
            productImageUrl: productImageUrl,
          ),
        );

        // Show typing indicator
        _isTyping = true;
      });

      // Show admin response after a short typing animation
      Future.delayed(const Duration(milliseconds: 1500), () {
        if (mounted) {
          setState(() {
            _isTyping = false;

            // Create custom stock response based on the product details
            String responseMessage = '';
            if (productStock == 0) {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "$productName". Produk ini sedang kosong dan kami sedang melakukan pengisian stok. Kami perkirakan akan tersedia dalam 2-3 hari kerja. Kami akan segera memberi tahu Anda saat produk tersedia kembali.';
            } else if (requestedQuantity != null &&
                productStock < requestedQuantity) {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "$productName". Saat ini stok tersedia hanya $productStock buah, sedangkan Anda membutuhkan $requestedQuantity buah. Kami akan segera melakukan pengisian stok dan memberi tahu Anda saat jumlah yang Anda inginkan tersedia.';
            } else {
              responseMessage =
                  'Halo${userName.isNotEmpty ? ' $userName' : ''}, terima kasih telah menghubungi kami tentang produk "$productName". Kami akan segera mengisi kembali stok produk yang anda inginkan dan akan segera mengabari anda. Terima kasih sudah tertarik pada produk kami.';
            }

            _messages.add(
              ChatMessage(
                message: responseMessage,
                isSentByMe: false,
                timestamp: DateTime.now(),
                isDelivered: true,
                isRead: true,
              ),
            );
          });
        }
      });
    } else {
      // Regular message - send immediately
      _messageController.text = initialMessage;
      _handleSendMessage();
    }
  }

  // Static method to navigate to chat tab with a pre-set message
  static void navigateToChat(
    BuildContext context, {
    required String initialMessage,
    String? productName,
    String? productImageUrl,
    int? productStock,
    int? requestedQuantity,
  }) {
    // Store the message parameters to be picked up when ChatPage loads
    ChatPage.pendingInitialMessage = initialMessage;
    ChatPage.pendingProductName = productName;
    ChatPage.pendingProductImageUrl = productImageUrl;
    ChatPage.pendingProductStock = productStock;
    ChatPage.pendingRequestedQuantity = requestedQuantity;

    // Navigate to home page
    Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);

    // Show a toast guiding the user to the chat tab
    Future.delayed(const Duration(milliseconds: 300), () {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Silakan lihat percakapan di tab Chat'),
          duration: Duration(seconds: 2),
          backgroundColor: Color(0xFFFF87B2),
        ),
      );
    });
  }

  // New method to build empty chat state with FAQ suggestions
  Widget _buildEmptyChat() {
    return SingleChildScrollView(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const SizedBox(height: 40),
          Container(
            width: 100,
            height: 100,
            decoration: const BoxDecoration(
              color: Color(0xFFFFF0F5),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.chat_bubble_outline,
              size: 50,
              color: Color(0xFFFF87B2),
            ),
          ),
          const SizedBox(height: 20),
          Text(
            'Selamat Datang di Chat Bloom Bouquet',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 10),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 40),
            child: Text(
              'Kami siap membantu dengan semua pertanyaan Anda tentang bunga dan layanan kami',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey[600],
              ),
            ),
          ),
          const SizedBox(height: 30),
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 20),
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  const Color(0xFFFF87B2).withOpacity(0.8),
                  const Color(0xFFFF5A8A).withOpacity(0.9)
                ],
              ),
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.3),
                  spreadRadius: 2,
                  blurRadius: 5,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Column(
              children: [
                const Row(
                  children: [
                    Icon(Icons.lightbulb, color: Colors.white, size: 22),
                    SizedBox(width: 8),
                    Text(
                      'Apa yang bisa kami bantu?',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                const Text(
                  'Bloom Bouquet menyediakan berbagai pilihan rangkaian bunga untuk segala kebutuhan. Tanyakan apa saja kepada kami, dari pengiriman, ketersediaan bunga, hingga kustomisasi pesanan.',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 15),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.9),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Coba tanyakan salah satu dari ini:',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFFFF5A8A),
                        ),
                      ),
                      const SizedBox(height: 8),
                      ...[
                        'Berapa lama estimasi pengiriman?',
                        'Apakah bisa custom rangkaian bunga?',
                        'Metode pembayaran apa saja yang tersedia?',
                        'Bagaimana prosedur pengembalian?'
                      ].map((item) {
                        return InkWell(
                          onTap: () {
                            // Set text and send message
                            _messageController.text = item;
                            _handleSendMessage();
                          },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.chat_bubble_outline,
                                  color: Color(0xFFFF87B2),
                                  size: 16,
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    item,
                                    style: const TextStyle(
                                      fontSize: 13,
                                      color: Colors.black87,
                                    ),
                                  ),
                                ),
                                const Icon(
                                  Icons.arrow_forward_ios,
                                  size: 12,
                                  color: Color(0xFFFF87B2),
                                ),
                              ],
                            ),
                          ),
                        );
                      }).toList(),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 30),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                Icon(
                  Icons.question_answer,
                  size: 20,
                  color: Color(0xFFFF5A8A),
                ),
                SizedBox(width: 8),
                Text(
                  "Pertanyaan Populer",
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFFFF5A8A),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _buildFaqCarousel(),
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  // New method to build a horizontal carousel of FAQ items
  Widget _buildFaqCarousel() {
    final screenWidth = MediaQuery.of(context).size.width;
    final itemWidth = min(240.0, screenWidth * 0.75);

    return Container(
      height: 190,
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _faqs.length,
        physics: const BouncingScrollPhysics(),
        itemBuilder: (context, index) {
          final gradientColors = [
            [const Color(0xFFFF87B2), const Color(0xFFFF5A8A)], // Pink
            [const Color(0xFF90CAF9), const Color(0xFF42A5F5)], // Blue
            [const Color(0xFFA5D6A7), const Color(0xFF66BB6A)], // Green
            [const Color(0xFFFFCC80), const Color(0xFFFF9800)], // Orange
            [const Color(0xFFCE93D8), const Color(0xFF9C27B0)], // Purple
            [const Color(0xFFFFAB91), const Color(0xFFFF5722)], // Deep Orange
            [const Color(0xFFB39DDB), const Color(0xFF673AB7)], // Indigo
          ];

          final colorIndex = index % gradientColors.length;

          return Container(
            width: itemWidth,
            margin: const EdgeInsets.only(right: 16),
            child: GestureDetector(
              onTap: () => _askFAQ(_faqs[index]),
              child: Hero(
                tag: 'faq_card_$index',
                child: Material(
                  type: MaterialType.transparency,
                  child: Card(
                    elevation: 5,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: gradientColors[colorIndex],
                        ),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.question_answer,
                                  color: Colors.white,
                                  size: 22,
                                ),
                              ),
                              const Spacer(),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 5),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  'FAQ #${index + 1}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          Expanded(
                            child: Text(
                              _faqs[index].question,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                              ),
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 6),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(
                                      'Tanyakan',
                                      style: TextStyle(
                                        color: gradientColors[colorIndex][1],
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                    const SizedBox(width: 5),
                                    Icon(
                                      Icons.arrow_forward,
                                      size: 14,
                                      color: gradientColors[colorIndex][1],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  // Method to add a new FAQ from the customer (for future implementation)
  void _saveFrequentlyAskedQuestion(String question, String answer) {
    // This would ideally save to a database or shared preferences
    // For now, we'll just add it to the current session
    setState(() {
      _faqs.add(FAQ(question: question, answer: answer));
    });

    // Show a confirmation toast
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Pertanyaan berhasil disimpan'),
        backgroundColor: Color(0xFFFF87B2),
        duration: Duration(seconds: 2),
      ),
    );
  }

  // Save chat messages to SharedPreferences
  Future<void> _saveChatMessages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final messagesJson = _messages.map((msg) => msg.toJson()).toList();
      await prefs.setString('chat_messages', json.encode(messagesJson));
      print('Saved ${_messages.length} messages to storage');
    } catch (e) {
      print('Error saving chat messages: $e');
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _typingTimer?.cancel();
    _fabAnimationController?.dispose();
    super.dispose();
  }
}
