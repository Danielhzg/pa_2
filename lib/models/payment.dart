class Payment {
  final String id;
  final String orderId;
  final double amount;
  final String status;
  final String paymentMethod;
  final String? qrCodeUrl;
  final String? qrCodeData;
  final DateTime createdAt;

  Payment({
    required this.id,
    required this.orderId,
    required this.amount,
    required this.status,
    required this.paymentMethod,
    this.qrCodeUrl,
    this.qrCodeData,
    required this.createdAt,
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'orderId': orderId,
      'amount': amount,
      'status': status,
      'paymentMethod': paymentMethod,
      'qrCodeUrl': qrCodeUrl,
      'qrCodeData': qrCodeData,
      'createdAt': createdAt.toIso8601String(),
    };
  }

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'],
      orderId: json['orderId'],
      amount: json['amount'].toDouble(),
      status: json['status'],
      paymentMethod: json['paymentMethod'],
      qrCodeUrl: json['qrCodeUrl'],
      qrCodeData: json['qrCodeData'],
      createdAt: DateTime.parse(json['createdAt']),
    );
  }
}
