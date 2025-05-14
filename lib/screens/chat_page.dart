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
  bool _isTyping = false;
  bool _isSending = false;
  Timer? _typingTimer;
  Timer? _pollingTimer;
  User? _userData;
  AnimationController? _fabAnimationController;
  Animation<double>? _fabAnimation;
  final bool _showFaq = true; // Changed to true by default
  final ChatService _chatService = ChatService();
  int _lastMessageId = 0;

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

    // Start polling for new messages
    _startPolling();

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

  void _startPolling() {
    // Poll for new messages every 3 seconds
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      if (mounted) {
        _fetchNewMessages();
      }
    });
  }

  Future<void> _fetchNewMessages() async {
    try {
      final newMessages = await _chatService.getNewMessages(_lastMessageId);
      if (newMessages.isNotEmpty) {
        setState(() {
          _messages.addAll(newMessages);
          // Update last message ID
          if (newMessages.isNotEmpty) {
            _lastMessageId = newMessages.last.id ?? _lastMessageId;
          }
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
      }
    } catch (e) {
      print('Error fetching new messages: $e');
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
      // Send message through the chat service
      final newMessage = await _chatService.sendMessage(
        message,
        productImageUrl: widget.productImageUrl,
        productName: widget.productName,
      );

      if (newMessage != null) {
        setState(() {
          _messages.add(newMessage);
          _lastMessageId = newMessage.id ?? _lastMessageId;
          _messageController.clear();
          _isSending = false;
        });

        // Save messages locally as backup
        _chatService.saveChatMessagesLocally(_messages);

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
        // If API call fails, add message locally temporarily
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
          _isSending = false;
        });

        // Save messages locally as backup
        _chatService.saveChatMessagesLocally(_messages);

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
      }
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

  // Method to handle sending a message
  void _handleSendMessage() {
    // Simply delegate to the existing _sendMessage method
    _sendMessage();
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
          isFromUser: true,
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
              isFromUser: false,
              timestamp: DateTime.now(),
              isDelivered: true,
              isRead: true,
            ),
          );
        });
      }
    });
  }

  // Simulate typing indicator
  void _simulateTyping() {
    if (mounted) {
      setState(() {
        _isTyping = true;
      });

      // Automatically turn off typing indicator after some time
      Future.delayed(const Duration(seconds: 3), () {
        if (mounted) {
          setState(() {
            _isTyping = false;
          });
        }
      });
    }
  }

  // Set user typing status and notify server
  void _setTypingStatus(bool isTyping) {
    if (mounted && _isTyping != isTyping) {
      setState(() {
        _isTyping = isTyping;
      });

      // Send typing status to server
      _chatService.updateTypingStatus(isTyping).then((success) {
        if (!success) {
          print('Failed to update typing status on server');
        }
      });

      // If typing, set a timeout to automatically set to false after inactivity
      if (isTyping) {
        if (_typingTimer != null) {
          _typingTimer!.cancel();
        }
        _typingTimer = Timer(const Duration(seconds: 5), () {
          if (mounted && _isTyping) {
            _setTypingStatus(false);
          }
        });
      }
    }
  }

  // Build the animated typing indicator dots
  Widget _buildTypingDots() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(3, (index) {
        return TweenAnimationBuilder<double>(
          tween: Tween(begin: 0.0, end: 1.0),
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOut,
          builder: (context, value, child) {
            return Container(
              margin: const EdgeInsets.symmetric(horizontal: 2),
              height: 8,
              width: 8,
              decoration: BoxDecoration(
                color: Colors.grey.withOpacity(
                  0.4 + (0.6 * sin((value * 3 + index) * 3.14)),
                ),
                shape: BoxShape.circle,
              ),
            );
          },
        );
      }),
    );
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
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        if (widget.initialMessage != null &&
            widget.initialMessage!.isNotEmpty) {
          return false;
        }
        return true;
      },
      child: Scaffold(
        appBar: AppBar(
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Customer Service',
                style: TextStyle(
                  fontFamily: 'Inter',
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                ),
              ),
              _isTyping
                  ? const Text(
                      'Typing...',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 12,
                        color: Colors.lightGreen,
                      ),
                    )
                  : const Text(
                      'Online',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 12,
                        color: Colors.lightGreen,
                      ),
                    ),
            ],
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.help_outline),
              onPressed: () {
                setState(() {
                  _expandedFaq = !_expandedFaq;
                });
              },
            ),
          ],
        ),
        body: SafeArea(
          child: Stack(
            children: [
              Column(
                children: [
                  // Messages area
                  Expanded(
                    child: _isLoading
                        ? const Center(
                            child: CircularProgressIndicator(),
                          )
                        : _messages.isEmpty
                            ? Center(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(
                                      Icons.chat_bubble_outline,
                                      size: 80,
                                      color: Colors.black12,
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      'Start a conversation',
                                      style: TextStyle(
                                        fontFamily: 'Inter',
                                        color: Colors.grey[600],
                                        fontSize: 16,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'We typically reply within a few minutes',
                                      style: TextStyle(
                                        fontFamily: 'Inter',
                                        color: Colors.grey[400],
                                        fontSize: 14,
                                      ),
                                    ),
                                  ],
                                ),
                              )
                            : ListView.builder(
                                controller: _scrollController,
                                reverse: true,
                                padding: const EdgeInsets.all(10),
                                itemCount: _messages.length,
                                itemBuilder: (context, index) {
                                  // Display messages in reverse order (newest at bottom)
                                  final message =
                                      _messages[_messages.length - 1 - index];
                                  return _buildMessageBubble(message);
                                },
                              ),
                  ),

                  // Typing indicator
                  if (_isTyping)
                    Container(
                      padding: const EdgeInsets.all(8),
                      alignment: Alignment.centerLeft,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 10),
                        decoration: BoxDecoration(
                          color: Colors.grey[200],
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            SizedBox(
                              width: 40,
                              child: _buildTypingDots(),
                            ),
                          ],
                        ),
                      ),
                    ),

                  // Input bar
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.05),
                          offset: const Offset(0, -1),
                          blurRadius: 3,
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _messageController,
                            decoration: InputDecoration(
                              hintText: 'Type a message',
                              hintStyle: TextStyle(
                                fontFamily: 'Inter',
                                color: Colors.grey[400],
                              ),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(24),
                                borderSide: BorderSide.none,
                              ),
                              filled: true,
                              fillColor: Colors.grey[100],
                              contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 8),
                            ),
                            onChanged: (text) {
                              // If the user is typing, send a typing indicator
                              if (text.isNotEmpty && !_isTyping) {
                                _setTypingStatus(true);
                              } else if (text.isEmpty && _isTyping) {
                                _setTypingStatus(false);
                              }
                            },
                            keyboardType: TextInputType.multiline,
                            textCapitalization: TextCapitalization.sentences,
                            minLines: 1,
                            maxLines: 5,
                          ),
                        ),
                        const SizedBox(width: 10),
                        InkWell(
                          onTap: _isSending ? null : _sendMessage,
                          borderRadius: BorderRadius.circular(30),
                          child: Container(
                            height: 48,
                            width: 48,
                            decoration: BoxDecoration(
                              color: Theme.of(context).primaryColor,
                              shape: BoxShape.circle,
                            ),
                            child: _isSending
                                ? const Center(
                                    child: SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                        color: Colors.white,
                                        strokeWidth: 2,
                                      ),
                                    ),
                                  )
                                : const Icon(
                                    Icons.send,
                                    color: Colors.white,
                                  ),
                          ),
                        ),
                      ],
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
                          (_faqPosition.dx + details.delta.dx).clamp(
                              16, MediaQuery.of(context).size.width - 80),
                          (_faqPosition.dy + details.delta.dy).clamp(
                              80, MediaQuery.of(context).size.height - 200),
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
                    color: Colors.black
                        .withOpacity(0.3), // Semi-transparent overlay
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
          ),
        ),
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

  Widget _buildMessageBubble(ChatMessage message) {
    return GestureDetector(
      onLongPress: () {
        // Show options
        showModalBottomSheet(
          context: context,
          builder: (context) => SafeArea(
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 16.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  ListTile(
                    leading: const Icon(Icons.copy),
                    title: const Text('Copy Text'),
                    onTap: () {
                      Navigator.pop(context);
                      // Copy message text
                    },
                  ),
                  message.isFromUser
                      ? ListTile(
                          leading: const Icon(Icons.delete_outline),
                          title: const Text('Delete'),
                          onTap: () {
                            Navigator.pop(context);
                            // Delete message
                          },
                        )
                      : const SizedBox.shrink(),
                ],
              ),
            ),
          ),
        );
      },
      child: Container(
        margin: EdgeInsets.only(
          top: 4,
          bottom: 4,
          left: message.isFromUser ? 80 : 10,
          right: message.isFromUser ? 10 : 80,
        ),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.only(
            topLeft: message.isFromUser
                ? const Radius.circular(16)
                : const Radius.circular(4),
            topRight: message.isFromUser
                ? const Radius.circular(4)
                : const Radius.circular(16),
            bottomLeft: const Radius.circular(16),
            bottomRight: const Radius.circular(16),
          ),
          color: message.isFromUser
              ? Theme.of(context).primaryColor.withOpacity(0.9)
              : Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.04),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(10.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Product Image (if available)
              if (message.productImageUrl != null &&
                  message.productImageUrl!.isNotEmpty)
                Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.05),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.network(
                      message.productImageUrl!,
                      height: 150,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      loadingBuilder: (BuildContext context, Widget child,
                          ImageChunkEvent? loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Container(
                          height: 150,
                          width: double.infinity,
                          color: Colors.grey[300],
                          child: Center(
                            child: CircularProgressIndicator(
                              value: loadingProgress.expectedTotalBytes != null
                                  ? loadingProgress.cumulativeBytesLoaded /
                                      loadingProgress.expectedTotalBytes!
                                  : null,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                  Theme.of(context).primaryColor),
                            ),
                          ),
                        );
                      },
                      errorBuilder: (context, error, stackTrace) {
                        return Container(
                          height: 150,
                          width: double.infinity,
                          color: Colors.grey[300],
                          child: Center(
                            child: Icon(
                              Icons.error_outline,
                              color: Colors.red[300],
                              size: 40,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),

              // Message Text
              Text(
                message.message,
                style: TextStyle(
                  fontFamily: 'Inter',
                  color: message.isFromUser ? Colors.white : Colors.black87,
                  fontSize: 14,
                ),
              ),

              // Timestamp and delivery status
              Row(
                mainAxisSize: MainAxisSize.min,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  const SizedBox(width: 5),
                  Text(
                    _formatDateTime(message.timestamp),
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 10,
                      color: message.isFromUser
                          ? Colors.white.withOpacity(0.7)
                          : Colors.black54,
                    ),
                  ),
                  if (message.isFromUser) ...[
                    const SizedBox(width: 4),
                    Icon(
                      message.isRead
                          ? Icons.done_all
                          : (message.isDelivered
                              ? Icons.done
                              : Icons.access_time),
                      size: 12,
                      color: message.isRead
                          ? Colors.lightBlueAccent
                          : Colors.white.withOpacity(0.7),
                    ),
                  ],
                ],
              ),
            ],
          ),
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
      return 'Yesterday, ${DateFormat('HH:mm').format(dateTime)}';
    } else if (difference.inDays < 7) {
      // Within a week: Monday, 14:30
      return DateFormat('EEEE, HH:mm').format(dateTime);
    } else {
      // Older: 2023-01-01, 14:30
      return DateFormat('yyyy-MM-dd, HH:mm').format(dateTime);
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
