import 'package:flutter/material.dart';
import 'dart:async';
import 'dart:math'; // Added for sin function
import 'dart:ui'; // Added for ImageFilter
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/chat_service.dart';
import '../models/user.dart';
import '../models/chat.dart';
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
  bool _isSending = false;
  bool _isTyping = false;
  bool _showScrollToBottomButton = false;
  User? _userData;
  AnimationController? _fabAnimationController;
  Animation<double>? _fabAnimation;
  final bool _showFaq = true;
  final ChatService _chatService = ChatService();
  int _lastMessageId = 0;

  // Variables for draggable FAQ
  Offset _faqPosition = const Offset(20, 100);
  bool _isDragging = false;
  bool _expandedFaq = false;

  // List of frequently asked questions
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

    // Monitor scroll to show/hide scroll-to-bottom button
    _scrollController.addListener(() {
      if (_scrollController.hasClients) {
        setState(() {
          _showScrollToBottomButton = _scrollController.offset > 300;
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

    // Simulate typing periodically (for demo purposes)
    Future.delayed(const Duration(seconds: 5), () {
      if (mounted) _simulateTyping();
    });
  }

  // Simulate typing indicator (for demo purposes only)
  void _simulateTyping() {
    // Don't show typing if there are no messages yet
    if (_messages.isEmpty) return;

    // Random typing simulation
    if (mounted) {
      setState(() {
        _isTyping = true;
      });

      // Hide typing after a random duration
      Future.delayed(Duration(seconds: 2 + Random().nextInt(3)), () {
        if (mounted) {
          setState(() {
            _isTyping = false;
          });

          // Schedule next typing indication
          Future.delayed(Duration(seconds: 10 + Random().nextInt(20)), () {
            if (mounted) _simulateTyping();
          });
        }
      });
    }
  }

  // Process initial message from product page if provided
  void _processInitialMessage() {
    // Process with a slight delay to ensure view is ready
    Future.delayed(const Duration(seconds: 1), () {
      // Don't proceed if widget is unmounted
      if (!mounted) return;

      if (widget.initialMessage != null && widget.initialMessage!.isNotEmpty) {
        String initialMessage = widget.initialMessage!;

        // If we have product info included, create a more detailed message
        if (widget.productName != null &&
            widget.productName!.isNotEmpty &&
            widget.requestedQuantity != null) {
          if (widget.productStock != null &&
              widget.requestedQuantity! > widget.productStock!) {
            initialMessage =
                "Saya tertarik dengan produk ${widget.productName}, tetapi saya ingin memesan ${widget.requestedQuantity} buah sedangkan stok hanya ${widget.productStock}. Apakah bisa dibantu?";
          } else {
            initialMessage =
                "Saya tertarik dengan produk ${widget.productName}. Saya ingin memesan ${widget.requestedQuantity} buah. Bisa diproses?";
          }
        }

        // Set the message text
        _messageController.text = initialMessage;

        // Send the message
        _sendMessage();
      }
    });
  }

  // Load user data from AuthService
  Future<void> _loadUserData() async {
    if (mounted) {
      try {
        final user = Provider.of<AuthService>(context, listen: false).user;
        if (user != null) {
          setState(() {
            _userData = user;
          });
        }
      } catch (e) {
        print('Error loading user data: $e');
      }
    }
  }

  // Load chat messages from chat service
  Future<void> _loadChatMessages() async {
    if (mounted) {
      setState(() {
        _isLoading = true;
      });

      try {
        // First try to load messages from the API
        final chat = await _chatService.getUserChat();
        if (chat != null && chat.messages.isNotEmpty) {
          setState(() {
            _messages.clear();
            _messages.addAll(chat.messages);

            // Update last message ID for polling
            if (chat.messages.isNotEmpty) {
              // Find the highest message ID
              int maxId = chat.messages.fold(
                  0,
                  (max, message) => message.id != null && message.id! > max
                      ? message.id!
                      : max);
              _lastMessageId = maxId;
            }

            _isLoading = false;
          });

          // Mark messages as read
          _chatService.markMessagesAsRead();

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

          return;
        }

        // Fall back to local storage if API fails or returns no messages
        final localMessages = await _chatService.getLocalChatMessages();
        if (localMessages.isNotEmpty) {
          setState(() {
            _messages.clear();
            _messages.addAll(localMessages);
            _isLoading = false;
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
        } else {
          setState(() {
            _isLoading = false;
          });

          // Show FAQ if no message history
          _showFaqPanel();
        }
      } catch (e) {
        print('Error loading chat messages: $e');
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  // Send a message
  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    setState(() {
      _isSending = true;
    });

    try {
      // Create a local message first to show immediately
      final localMessage = ChatMessage(
        message: message,
        isFromUser: true,
        timestamp: DateTime.now(),
        productImageUrl: widget.productImageUrl,
        productName: widget.productName,
      );

      setState(() {
        _messages.add(localMessage);
        _messageController.clear();
      });

      // Send message through the chat service
      final newMessage = await _chatService.sendMessage(
        message,
        productImageUrl: widget.productImageUrl,
        productName: widget.productName,
      );

      if (newMessage != null) {
        setState(() {
          // Replace local message with server message if needed
          _messages.removeLast();
          _messages.add(newMessage);
          _lastMessageId = newMessage.id ?? _lastMessageId;
          _isSending = false;
        });

        // Save messages locally as backup
        _chatService.saveLocalChatMessages(_messages);

        // Auto-response for FAQ system
        _provideFaqResponse(message);
      } else {
        // If API call fails, keep the local message
        setState(() {
          _isSending = false;
        });

        // Save messages locally as backup
        _chatService.saveLocalChatMessages(_messages);

        // Auto-response for FAQ system
        _provideFaqResponse(message);
      }

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
    } catch (e) {
      print('Error sending message: $e');
      setState(() {
        _isSending = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Failed to send message. Please try again.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  // Provide FAQ automatic response
  void _provideFaqResponse(String userMessage) {
    // Normalize user message for comparison
    final normalizedMessage = userMessage.toLowerCase();

    // Try to find a matching FAQ
    FAQ? matchedFaq;
    double highestMatch = 0;

    for (var faq in _faqs) {
      // Simple matching algorithm - can be improved
      final questionWords = faq.question.toLowerCase().split(' ');
      int matches = 0;

      for (var word in questionWords) {
        if (word.length > 3 && normalizedMessage.contains(word)) {
          matches++;
        }
      }

      final matchScore = matches / questionWords.length;
      if (matchScore > 0.3 && matchScore > highestMatch) {
        highestMatch = matchScore;
        matchedFaq = faq;
      }
    }

    // Add slight delay to simulate admin thinking
    Future.delayed(const Duration(seconds: 1), () {
      if (!mounted) return;

      // Add response message
      setState(() {
        if (matchedFaq != null) {
          // Found matching FAQ
          _messages.add(
            ChatMessage(
              message: matchedFaq.answer,
              isFromUser: false,
              timestamp: DateTime.now(),
              isDelivered: true,
              isRead: true,
            ),
          );
        } else {
          // No matching FAQ found - standard response
          _messages.add(
            ChatMessage(
              message:
                  "Terima kasih atas pertanyaan Anda. Tim admin kami akan segera membalas pesan ini. Sementara itu, Anda dapat melihat FAQ kami untuk informasi umum.",
              isFromUser: false,
              timestamp: DateTime.now(),
              isDelivered: true,
              isRead: true,
            ),
          );
        }

        // Save locally
        _chatService.saveLocalChatMessages(_messages);
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
    });
  }

  // Method to ask a FAQ
  void _askFAQ(FAQ faq) {
    // Hide the FAQ section after clicking a question
    setState(() {
      _expandedFaq = false;
    });

    // Add the question as a user message
    final userMessage = ChatMessage(
      message: faq.question,
      isFromUser: true,
      timestamp: DateTime.now(),
      isDelivered: true,
      isRead: true,
    );

    // Add the answer as a system message
    final systemMessage = ChatMessage(
      message: faq.answer,
      isFromUser: false,
      timestamp: DateTime.now().add(const Duration(milliseconds: 800)),
      isDelivered: true,
      isRead: true,
    );

    setState(() {
      _messages.add(userMessage);
    });

    // Scroll to bottom immediately after adding user message
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          0.0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });

    // Simulate typing delay for admin response
    Future.delayed(const Duration(milliseconds: 800), () {
      if (mounted) {
        setState(() {
          _messages.add(systemMessage);
        });

        // Save messages locally
        _chatService.saveLocalChatMessages(_messages);

        // Scroll to bottom again after adding system message
        Future.delayed(const Duration(milliseconds: 100), () {
          if (_scrollController.hasClients) {
            _scrollController.animateTo(
              0.0,
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeOut,
            );
          }
        });
      }
    });
  }

  // Show FAQ panel
  void _showFaqPanel() {
    if (mounted && _messages.isEmpty) {
      setState(() {
        _expandedFaq = true;
      });
    }
  }

  // Process a pending message that was set through static fields
  void _processPendingMessage(Map<String, dynamic> params) {
    final message = params['initialMessage'] as String;

    if (message.isNotEmpty) {
      // If we have product info included, use that info
      if (params['productName'] != null &&
          params['requestedQuantity'] != null &&
          params['productStock'] != null) {
        String formattedMessage;
        final productName = params['productName'] as String;
        final requestedQuantity = params['requestedQuantity'] as int;
        final productStock = params['productStock'] as int;

        if (requestedQuantity > productStock) {
          formattedMessage =
              "Saya tertarik dengan produk $productName, tetapi saya ingin memesan $requestedQuantity buah sedangkan stok hanya $productStock. Apakah bisa dibantu?";
        } else {
          formattedMessage =
              "Saya tertarik dengan produk $productName. Saya ingin memesan $requestedQuantity buah. Bisa diproses?";
        }

        // Set the message
        _messageController.text = formattedMessage;
      } else {
        // Just use the original message
        _messageController.text = message;
      }

      // Send the message
      _sendMessage();
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _fabAnimationController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFFFF87B2);
    const secondaryColor = Color(0xFFFF5A8A);

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        centerTitle: false,
        titleSpacing: 0,
        leading: Padding(
          padding: const EdgeInsets.all(8.0),
          child: Container(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                colors: [primaryColor, secondaryColor],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              boxShadow: [
                BoxShadow(
                  color: primaryColor.withOpacity(0.3),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                )
              ],
            ),
            child: const Center(
              child: Icon(
                Icons.support_agent,
                color: Colors.white,
                size: 20,
              ),
            ),
          ),
        ),
        title: Padding(
          padding: const EdgeInsets.only(left: 4.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Text(
                    'FAQ',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                      color: Color(0xFFFF5A8A),
                    ),
                  ),
                  const Text(
                    ' & Bantuan',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(width: 5),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE8F5E9),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text(
                      'New',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF2E7D32),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 2),
              Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Color(0xFF64DD17),
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 5),
                  Text(
                    'Customer Service Online',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 16),
            child: IconButton(
              icon: Icon(
                _expandedFaq ? Icons.close : Icons.question_answer,
                color: primaryColor,
              ),
              onPressed: () {
                setState(() {
                  _expandedFaq = !_expandedFaq;
                });
              },
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Stack(
          children: [
            // Messages area
            Column(
              children: [
                // Subtle divider
                Container(
                  height: 1,
                  color: Colors.grey.withOpacity(0.1),
                ),

                Expanded(
                  child: _isLoading
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const SizedBox(
                                width: 50,
                                height: 50,
                                child: CircularProgressIndicator(
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      primaryColor),
                                  strokeWidth: 3,
                                ),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Memuat pesan...',
                                style: TextStyle(
                                  color: Colors.grey[500],
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        )
                      : _messages.isEmpty
                          ? _buildEmptyState()
                          : _buildMessagesList(),
                ),

                // Input bar
                _buildInputBar(primaryColor, secondaryColor),
              ],
            ),

            // Floating FAQ button - draggable
            if (_showFaq && !_expandedFaq) _buildFloatingFaqButton(),

            // Show expanded FAQ panel
            if (_expandedFaq) _buildFaqPanel(context, primaryColor),
          ],
        ),
      ),
    );
  }

  // Building the empty state with animations
  Widget _buildEmptyState() {
    return SingleChildScrollView(
      child: Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0),
          child: AnimatedOpacity(
            opacity: _messages.isEmpty ? 1.0 : 0.0,
            duration: const Duration(milliseconds: 500),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const SizedBox(height: 30),
                // Animated logo with pulsing effect
                TweenAnimationBuilder<double>(
                  duration: const Duration(milliseconds: 2000),
                  curve: Curves.easeInOut,
                  tween: Tween<double>(begin: 0.9, end: 1.1),
                  builder: (context, value, child) {
                    return Transform.scale(
                      scale: value,
                      child: Container(
                        width: 130,
                        height: 130,
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFE5EE),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFFF87B2).withOpacity(0.3),
                              blurRadius: 20,
                              spreadRadius: 5,
                            ),
                          ],
                        ),
                        child: Center(
                          child: SizedBox(
                            width: 80,
                            height: 80,
                            child: Image.asset(
                              'assets/images/logo.png',
                              fit: BoxFit.contain,
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 30),
                const Text(
                  'Ada yang bisa kami bantu?',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Tanyakan pertanyaan seputar produk dan layanan kami. Tim customer service kami siap membantu Anda.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 15,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 20),
                // Quick access chat cards
                Container(
                  margin: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildQuickAccessCard(
                        'Produk',
                        Icons.shopping_bag_outlined,
                        Colors.blue,
                        'Saya ingin bertanya tentang produk',
                      ),
                      const SizedBox(width: 16),
                      _buildQuickAccessCard(
                        'Pesanan',
                        Icons.local_shipping_outlined,
                        Colors.orange,
                        'Bagaimana status pesanan saya?',
                      ),
                    ],
                  ),
                ),
                Container(
                  margin: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildQuickAccessCard(
                        'Pembayaran',
                        Icons.payment_outlined,
                        Colors.green,
                        'Saya ingin bertanya tentang pembayaran',
                      ),
                      const SizedBox(width: 16),
                      _buildQuickAccessCard(
                        'Bantuan',
                        Icons.help_outline_rounded,
                        Colors.purple,
                        'Saya butuh bantuan customer service',
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton.icon(
                  onPressed: () {
                    setState(() {
                      _expandedFaq = true;
                    });
                  },
                  icon: const Icon(Icons.help_outline),
                  label: const Text('Lihat Pertanyaan Umum'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF87B2),
                    foregroundColor: Colors.white,
                    elevation: 2,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 24, vertical: 12),
                  ),
                ),
                const SizedBox(height: 30),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Quick access card for common questions
  Widget _buildQuickAccessCard(
      String title, IconData icon, Color color, String message) {
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            _messageController.text = message;
            _sendMessage();
          },
          borderRadius: BorderRadius.circular(16),
          child: Ink(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: color.withOpacity(0.2),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    icon,
                    color: color,
                    size: 24,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Building message list with improved UI
  Widget _buildMessagesList() {
    return Stack(
      children: [
        // Chat background - subtle pattern
        Container(
          decoration: BoxDecoration(
            color: Colors.grey[50],
            image: const DecorationImage(
              image: AssetImage('assets/images/logo.png'),
              opacity: 0.03,
              repeat: ImageRepeat.repeat,
              scale: 10.0,
            ),
          ),
        ),

        // Message list
        ListView.builder(
          controller: _scrollController,
          reverse: true,
          padding: const EdgeInsets.all(16),
          physics: const BouncingScrollPhysics(),
          itemCount: _messages.length + 1, // +1 for the date header
          itemBuilder: (context, index) {
            // Show "Today" date header at the top
            if (index == _messages.length) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 20.0, top: 10.0),
                child: Center(
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.grey[200],
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _getCurrentDateLabel(),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[700],
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ),
              );
            }

            final messageIndex = index;
            final message = _messages[_messages.length - 1 - messageIndex];

            // Determine if we should show the avatar (first message in a group)
            bool showAvatar = messageIndex == 0;

            if (messageIndex > 0) {
              final previousMessage =
                  _messages[_messages.length - messageIndex];
              // Show avatar if this message is from a different sender than the previous one
              // or if messages are separated by more than 5 minutes
              showAvatar = previousMessage.isFromUser != message.isFromUser ||
                  previousMessage.timestamp
                          .difference(message.timestamp)
                          .inMinutes
                          .abs() >
                      5;
            }

            // Check if we need to show a date divider
            Widget? dateDivider;
            if (messageIndex < _messages.length - 1) {
              final nextMessage =
                  _messages[_messages.length - 2 - messageIndex];
              if (!_isSameDay(message.timestamp, nextMessage.timestamp)) {
                dateDivider = Padding(
                  padding: const EdgeInsets.symmetric(vertical: 16.0),
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.grey[200],
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        _formatDateHeader(nextMessage.timestamp),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[700],
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ),
                );
              }
            }

            return Column(
              children: [
                _buildEnhancedMessageBubble(message, showAvatar: showAvatar),
                if (dateDivider != null) dateDivider,
              ],
            );
          },
        ),

        // "New messages" indicator when scrolled up
        if (_messages.length > 10)
          Positioned(
            bottom: 20,
            left: 0,
            right: 0,
            child: Center(
              child: AnimatedOpacity(
                opacity: _showScrollToBottomButton ? 1.0 : 0.0,
                duration: const Duration(milliseconds: 200),
                child: GestureDetector(
                  onTap: () {
                    if (_scrollController.hasClients) {
                      _scrollController.animateTo(
                        0.0,
                        duration: const Duration(milliseconds: 300),
                        curve: Curves.easeOut,
                      );
                    }
                  },
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFF87B2),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.arrow_downward,
                            color: Colors.white, size: 18),
                        SizedBox(width: 8),
                        Text(
                          'Lihat pesan terbaru',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),

        // Optional: Typing indicator
        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          child: AnimatedOpacity(
            opacity: _isTyping ? 1.0 : 0.0,
            duration: const Duration(milliseconds: 300),
            child: Container(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    margin: const EdgeInsets.only(right: 10.0),
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
                          blurRadius: 10,
                          offset: const Offset(0, 3),
                        )
                      ],
                    ),
                    child: const Center(
                      child: Icon(
                        Icons.support_agent,
                        color: Colors.white,
                        size: 20,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.05),
                          blurRadius: 5,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        _buildTypingIndicator(),
                        const SizedBox(width: 8),
                        const Text(
                          'Customer Service sedang mengetik...',
                          style: TextStyle(
                            color: Colors.grey,
                            fontSize: 14,
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
      ],
    );
  }

  // Helper to build the typing indicator animation
  Widget _buildTypingIndicator() {
    return SizedBox(
      width: 30,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: List.generate(3, (index) {
          return TweenAnimationBuilder<double>(
            tween: Tween<double>(begin: 0, end: 1),
            duration: Duration(milliseconds: 600 + (index * 200)),
            curve: Curves.easeInOut,
            builder: (context, value, child) {
              return Transform.translate(
                offset: Offset(0, -3 * sin(value * 3.14)),
                child: Container(
                  width: 6,
                  height: 6,
                  decoration: const BoxDecoration(
                    color: Color(0xFFFF87B2),
                    shape: BoxShape.circle,
                  ),
                ),
              );
            },
          );
        }),
      ),
    );
  }

  // Get current date for the header
  String _getCurrentDateLabel() {
    final now = DateTime.now();
    return _formatDateHeader(now);
  }

  // Format date for header
  String _formatDateHeader(DateTime date) {
    final now = DateTime.now();
    final yesterday = DateTime(now.year, now.month, now.day - 1);

    if (_isSameDay(date, now)) {
      return 'Hari Ini';
    } else if (_isSameDay(date, yesterday)) {
      return 'Kemarin';
    } else {
      return DateFormat('d MMMM yyyy', 'id_ID').format(date);
    }
  }

  // Check if two dates are the same day
  bool _isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

  // Building the input bar
  Widget _buildInputBar(Color primaryColor, Color secondaryColor) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            offset: const Offset(0, -3),
            blurRadius: 5,
            color: Colors.black.withOpacity(0.05),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          IconButton(
            onPressed: () {
              // Show attachment options
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Fitur attachment akan segera tersedia'),
                  backgroundColor: Color(0xFFFF87B2),
                ),
              );
            },
            icon:
                const Icon(Icons.attach_file_rounded, color: Color(0xFFFF87B2)),
          ),
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.grey.shade300),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _messageController,
                      decoration: const InputDecoration(
                        hintText: 'Ketik pesan...',
                        hintStyle: TextStyle(color: Colors.grey),
                        border: InputBorder.none,
                      ),
                      minLines: 1,
                      maxLines: 5,
                      textCapitalization: TextCapitalization.sentences,
                      onChanged: (text) {
                        // Could add typing status update here
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          Material(
            color: _messageController.text.trim().isEmpty
                ? Colors.grey.shade300
                : primaryColor,
            borderRadius: BorderRadius.circular(50),
            child: InkWell(
              borderRadius: BorderRadius.circular(50),
              onTap:
                  _messageController.text.trim().isEmpty ? null : _sendMessage,
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(50),
                  gradient: _messageController.text.trim().isEmpty
                      ? null
                      : const LinearGradient(
                          colors: [Color(0xFFFF87B2), Color(0xFFFF5A8A)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                ),
                child: Icon(
                  _isSending ? Icons.hourglass_bottom : Icons.send_rounded,
                  color: Colors.white,
                  size: 24,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Building the floating FAQ button
  Widget _buildFloatingFaqButton() {
    return Positioned(
      left: _faqPosition.dx,
      top: _faqPosition.dy,
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
            _faqPosition = Offset(
              (_faqPosition.dx + details.delta.dx)
                  .clamp(16, MediaQuery.of(context).size.width - 80),
              (_faqPosition.dy + details.delta.dy)
                  .clamp(80, MediaQuery.of(context).size.height - 200),
            );
          });
        },
        onPanEnd: (_) {
          Future.delayed(const Duration(milliseconds: 100), () {
            if (mounted) {
              setState(() {
                _isDragging = false;
              });
            }
          });
        },
        child: AnimatedContainer(
          width: 60,
          height: 60,
          duration: const Duration(milliseconds: 300),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFFFF87B2), Color(0xFFFF5A8A)],
            ),
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: const Color(0xFFFF87B2).withOpacity(0.4),
                blurRadius: _isDragging ? 18 : 12,
                offset: const Offset(0, 4),
                spreadRadius: _isDragging ? 4 : 2,
              ),
            ],
          ),
          child: TweenAnimationBuilder<double>(
            duration: const Duration(milliseconds: 300),
            tween: Tween<double>(
              begin: 0.0,
              end: _isDragging ? 1.0 : 0.0,
            ),
            builder: (context, value, child) {
              return Transform.rotate(
                angle: value * 0.3, // Slight rotation when dragging
                child: const Center(
                  child: Icon(
                    Icons.help_outline_rounded,
                    color: Colors.white,
                    size: 28,
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  // Building the FAQ panel
  Widget _buildFaqPanel(BuildContext context, Color primaryColor) {
    return Positioned.fill(
      child: Container(
        color: Colors.black.withOpacity(0.5),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 5, sigmaY: 5),
          child: Center(
            child: TweenAnimationBuilder<double>(
              duration: const Duration(milliseconds: 300),
              tween: Tween<double>(begin: 0.9, end: 1.0),
              curve: Curves.easeOutCubic,
              builder: (context, value, child) {
                return Transform.scale(
                  scale: value,
                  child: Container(
                    width: MediaQuery.of(context).size.width * 0.9,
                    height: MediaQuery.of(context).size.height * 0.75,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.2),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                          spreadRadius: 5,
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        // Header
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [primaryColor, const Color(0xFFFF5A8A)],
                            ),
                            borderRadius: const BorderRadius.only(
                              topLeft: Radius.circular(24),
                              topRight: Radius.circular(24),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        width: 42,
                                        height: 42,
                                        decoration: BoxDecoration(
                                          color: Colors.white24,
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: const Center(
                                          child: Icon(
                                            Icons.question_answer_rounded,
                                            color: Colors.white,
                                            size: 22,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 14),
                                      Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          const Text(
                                            'Pertanyaan Populer',
                                            style: TextStyle(
                                              fontSize: 18,
                                              fontWeight: FontWeight.bold,
                                              color: Colors.white,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Row(
                                            children: [
                                              Container(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                        horizontal: 8,
                                                        vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: Colors.white30,
                                                  borderRadius:
                                                      BorderRadius.circular(20),
                                                ),
                                                child: const Row(
                                                  children: [
                                                    Icon(
                                                      Icons.trending_up,
                                                      color: Colors.white,
                                                      size: 12,
                                                    ),
                                                    SizedBox(width: 4),
                                                    Text(
                                                      'Paling Ditanyakan',
                                                      style: TextStyle(
                                                        fontSize: 10,
                                                        color: Colors.white,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  GestureDetector(
                                    onTap: () {
                                      setState(() {
                                        _expandedFaq = false;
                                      });
                                    },
                                    child: Container(
                                      width: 36,
                                      height: 36,
                                      decoration: BoxDecoration(
                                        color: Colors.white24,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: const Center(
                                        child: Icon(
                                          Icons.close,
                                          color: Colors.white,
                                          size: 18,
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),

                        // Search box (decorative)
                        Container(
                          margin: const EdgeInsets.symmetric(
                              horizontal: 20, vertical: 16),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 12),
                          decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: Colors.grey.shade200),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.05),
                                  blurRadius: 10,
                                  offset: const Offset(0, 3),
                                )
                              ]),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      primaryColor,
                                      const Color(0xFFFF5A8A)
                                    ],
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                  ),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(
                                  Icons.more_horiz_rounded,
                                  color: Colors.white,
                                  size: 20,
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Topik Populer',
                                      style: TextStyle(
                                          color: Colors.grey.shade900,
                                          fontSize: 14,
                                          fontWeight: FontWeight.w600),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Pilih dari daftar di bawah ini',
                                      style: TextStyle(
                                          color: Colors.grey.shade500,
                                          fontSize: 12),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),

                        // FAQ List
                        Expanded(
                          child: Container(
                            decoration: BoxDecoration(
                              color: Colors.grey.shade50,
                              borderRadius: const BorderRadius.only(
                                bottomLeft: Radius.circular(24),
                                bottomRight: Radius.circular(24),
                              ),
                            ),
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _faqs.length,
                              physics: const BouncingScrollPhysics(),
                              itemBuilder: (context, index) {
                                return _buildFaqItem(_faqs[index], index);
                              },
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  // Enhanced message bubble with avatar for admin
  Widget _buildEnhancedMessageBubble(ChatMessage message,
      {required bool showAvatar}) {
    const primaryColor = Color(0xFFFF87B2);
    final timeStr = _formatDateTime(message.timestamp);

    return Padding(
      padding: EdgeInsets.only(
        top: 8.0,
        bottom: 8.0,
        left: message.isFromUser ? 60 : 10,
        right: message.isFromUser ? 10 : 60,
      ),
      child: Column(
        crossAxisAlignment: message.isFromUser
            ? CrossAxisAlignment.end
            : CrossAxisAlignment.start,
        children: [
          // Render the status info at the top for time
          if (showAvatar && !message.isFromUser)
            Padding(
              padding: const EdgeInsets.only(left: 48, bottom: 4),
              child: Text(
                'Customer Service',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),

          Row(
            mainAxisAlignment: message.isFromUser
                ? MainAxisAlignment.end
                : MainAxisAlignment.start,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              // Admin avatar
              if (!message.isFromUser && showAvatar)
                Container(
                  width: 38,
                  height: 38,
                  margin: const EdgeInsets.only(right: 10.0),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [primaryColor, Color(0xFFFF5A8A)],
                    ),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: primaryColor.withOpacity(0.3),
                        blurRadius: 10,
                        offset: const Offset(0, 3),
                      )
                    ],
                  ),
                  child: const Center(
                    child: Icon(
                      Icons.support_agent,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                )
              else if (!message.isFromUser)
                const SizedBox(width: 48),

              // Message content
              Flexible(
                child: Container(
                  decoration: BoxDecoration(
                    color: message.isFromUser ? primaryColor : Colors.white,
                    borderRadius: BorderRadius.only(
                      topLeft: message.isFromUser
                          ? const Radius.circular(18)
                          : showAvatar
                              ? const Radius.circular(4)
                              : const Radius.circular(18),
                      topRight: message.isFromUser
                          ? showAvatar
                              ? const Radius.circular(4)
                              : const Radius.circular(18)
                          : const Radius.circular(18),
                      bottomLeft: const Radius.circular(18),
                      bottomRight: const Radius.circular(18),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.05),
                        blurRadius: 5,
                        offset: const Offset(0, 2),
                        spreadRadius: 1,
                      ),
                    ],
                    border: !message.isFromUser
                        ? Border.all(
                            color: Colors.grey.shade200,
                            width: 1,
                          )
                        : null,
                  ),
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Product Image (if available)
                      if (message.productImageUrl != null &&
                          message.productImageUrl!.isNotEmpty)
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.1),
                                  blurRadius: 5,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: Image.network(
                              message.productImageUrl!,
                              height: 160,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              loadingBuilder: (BuildContext context,
                                  Widget child,
                                  ImageChunkEvent? loadingProgress) {
                                if (loadingProgress == null) return child;
                                return Container(
                                  height: 160,
                                  width: double.infinity,
                                  color: Colors.grey[200],
                                  child: Center(
                                    child: CircularProgressIndicator(
                                      value:
                                          loadingProgress.expectedTotalBytes !=
                                                  null
                                              ? loadingProgress
                                                      .cumulativeBytesLoaded /
                                                  loadingProgress
                                                      .expectedTotalBytes!
                                              : null,
                                      valueColor:
                                          const AlwaysStoppedAnimation<Color>(
                                              primaryColor),
                                      strokeWidth: 2,
                                    ),
                                  ),
                                );
                              },
                              errorBuilder: (context, error, stackTrace) {
                                return Container(
                                  height: 160,
                                  width: double.infinity,
                                  color: Colors.grey[200],
                                  child: Center(
                                    child: Column(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(
                                          Icons.error_outline,
                                          color: Colors.red,
                                          size: 32,
                                        ),
                                        const SizedBox(height: 8),
                                        Text(
                                          "Gagal memuat gambar",
                                          style: TextStyle(
                                              color: Colors.grey[600]),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
                        ),

                      // Product Name (if available)
                      if (message.productName != null &&
                          message.productName!.isNotEmpty)
                        Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: message.isFromUser
                                ? Colors.white.withOpacity(0.2)
                                : primaryColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.shopping_bag_outlined,
                                size: 16,
                                color: message.isFromUser
                                    ? Colors.white
                                    : primaryColor,
                              ),
                              const SizedBox(width: 5),
                              Flexible(
                                child: Text(
                                  message.productName!,
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                    color: message.isFromUser
                                        ? Colors.white
                                        : primaryColor,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),

                      // Message Text
                      Text(
                        message.message,
                        style: TextStyle(
                          color: message.isFromUser
                              ? Colors.white
                              : Colors.black87,
                          fontSize: 15,
                          height: 1.3,
                        ),
                      ),

                      // Timestamp and delivery status
                      const SizedBox(height: 4),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          Text(
                            timeStr,
                            style: TextStyle(
                              fontSize: 10,
                              color: message.isFromUser
                                  ? Colors.white.withOpacity(0.7)
                                  : Colors.grey[400],
                            ),
                          ),
                          if (message.isFromUser) ...[
                            const SizedBox(width: 4),
                            Icon(
                              message.isRead
                                  ? Icons.done_all
                                  : (message.isDelivered
                                      ? Icons.done
                                      : Icons.schedule),
                              size: 12,
                              color: message.isRead
                                  ? Colors.blue[100]
                                  : Colors.white.withOpacity(0.7),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // User avatar (optional - shows only on first message in a group)
              if (message.isFromUser && showAvatar)
                Container(
                  width: 38,
                  height: 38,
                  margin: const EdgeInsets.only(left: 10.0),
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.1),
                        blurRadius: 5,
                        offset: const Offset(0, 2),
                      )
                    ],
                  ),
                  child: Center(
                    child: Text(
                      _userData?.name?.isNotEmpty == true
                          ? _userData!.name![0].toUpperCase()
                          : 'U',
                      style: const TextStyle(
                        color: Colors.grey,
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ),
                )
              else if (message.isFromUser)
                const SizedBox(width: 0),
            ],
          ),

          // Show user name for first message in a group
          if (showAvatar && message.isFromUser)
            Padding(
              padding: const EdgeInsets.only(right: 48, top: 4),
              child: Text(
                'Anda',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
        ],
      ),
    );
  }

  // Enhanced FAQ item with colorful designs
  Widget _buildFaqItem(FAQ faq, int index) {
    // Enhanced list of gradient colors for FAQ cards with more vibrant options
    final List<List<Color>> cardGradients = [
      [const Color(0xFFFF6B6B), const Color(0xFFFAD0C4)], // Red to Yellow
      [const Color(0xFF4E65FF), const Color(0xFF92EFFD)], // Blue to Cyan
      [const Color(0xFFA651FE), const Color(0xFFFC5CFF)], // Purple to Pink
      [
        const Color(0xFF11998E),
        const Color(0xFF38EF7D)
      ], // Deep Green to Light Green
      [const Color(0xFFFF8008), const Color(0xFFFFC837)], // Orange to Yellow
      [const Color(0xFFEC008C), const Color(0xFFFC6767)], // Magenta to Coral
      [const Color(0xFF00C6FF), const Color(0xFF0072FF)], // Light Blue to Blue
      [const Color(0xFFFF4E50), const Color(0xFFF9D423)], // Red to Yellow
    ];

    // Get gradient for this card (cycle through the list)
    final cardGradient = cardGradients[index % cardGradients.length];

    // Icon selection based on index
    final List<IconData> icons = [
      Icons.shopping_bag_outlined,
      Icons.emergency_outlined,
      Icons.credit_card,
      Icons.local_shipping_outlined,
      Icons.calendar_month_outlined,
      Icons.schedule,
      Icons.card_giftcard,
      Icons.inventory_2_outlined,
    ];

    final IconData cardIcon = icons[index % icons.length];

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Transform.translate(
        offset: Offset(0, index * 5.0), // Staggered animation effect
        child: TweenAnimationBuilder<double>(
          duration: Duration(milliseconds: 300 + (index * 50)),
          tween: Tween<double>(begin: 0.5, end: 1.0),
          curve: Curves.easeOutBack,
          builder: (context, value, child) {
            return Transform.scale(
              scale: value,
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(16),
                child: InkWell(
                  borderRadius: BorderRadius.circular(16),
                  splashColor: cardGradient[1].withOpacity(0.4),
                  highlightColor: cardGradient[0].withOpacity(0.3),
                  onTap: () => _askFAQ(faq),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          cardGradient[0].withOpacity(0.7),
                          cardGradient[1].withOpacity(0.5),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      border: Border.all(
                        color: cardGradient[0].withOpacity(0.5),
                        width: 1.5,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: cardGradient[0].withOpacity(0.3),
                          blurRadius: 8,
                          spreadRadius: 1,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        // Icon with gradient background
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                cardGradient[1],
                                cardGradient[0],
                              ],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: cardGradient[0].withOpacity(0.4),
                                blurRadius: 5,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Icon(
                            cardIcon,
                            color: Colors.white,
                            size: 24,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                faq.question,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15,
                                  color: Colors.white,
                                  shadows: [
                                    Shadow(
                                      offset: Offset(0, 1),
                                      blurRadius: 2,
                                      color: Color(0x80000000),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 5),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.3),
                                  borderRadius: BorderRadius.circular(30),
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.touch_app,
                                      color: Colors.white,
                                      size: 14,
                                    ),
                                    SizedBox(width: 4),
                                    Text(
                                      'Tap untuk lihat jawaban',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w500,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Icon(
                          Icons.arrow_forward_ios,
                          color: Colors.white,
                          size: 16,
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
    );
  }

  String _formatDateTime(DateTime dateTime) {
    final now = DateTime.now();
    final difference = now.difference(dateTime);

    if (difference.inDays == 0) {
      // Today: 14:30
      return DateFormat('HH:mm').format(dateTime);
    } else if (difference.inDays == 1) {
      // Yesterday: Yesterday, 14:30
      return 'Kemarin, ${DateFormat('HH:mm').format(dateTime)}';
    } else if (difference.inDays < 7) {
      // Within a week: Monday, 14:30
      return DateFormat('EEEE, HH:mm', 'id_ID').format(dateTime);
    } else {
      // Older: 2023-01-01, 14:30
      return DateFormat('dd/MM/yyyy, HH:mm').format(dateTime);
    }
  }
}
