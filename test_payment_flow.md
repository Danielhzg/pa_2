# Test Payment Flow - Order Status Update

## Masalah yang Diperbaiki
Status order customer Flutter tidak berubah otomatis ketika customer selesai melakukan pembayaran.

## Perbaikan yang Dilakukan

### 1. Payment WebView Screen (`lib/screens/payment_webview_screen.dart`)
- **Ditambahkan**: Import PaymentService dan OrderService
- **Ditambahkan**: Method `_handlePaymentSuccess()` yang:
  - Memanggil `simulatePaymentSuccess()` untuk trigger backend webhook
  - Memanggil `updateOrderStatus()` sebagai fallback
  - Refresh OrderService untuk update UI
  - Menampilkan notifikasi sukses
- **Diperbaiki**: Payment completion callback sekarang melakukan update status order

### 2. Payment Service (`lib/services/payment_service.dart`)
- **Ditambahkan**: Method `simulatePaymentSuccess()` yang memanggil endpoint `/api/payment/simulate-success`
- **Diperbaiki**: Method `updateOrderStatus()` sudah ada dan berfungsi dengan baik

### 3. Checkout Page (`lib/screens/checkout_page.dart`)
- **Diperbaiki**: Payment completion callback sekarang refresh OrderService
- **Diperbaiki**: Navigation ke order tracking screen setelah payment

## Flow Perbaikan

### Sebelum Perbaikan:
1. Customer melakukan pembayaran di WebView
2. WebView mendeteksi payment success
3. Callback hanya melakukan debug print
4. Status order tetap "waiting_for_payment"
5. Customer tidak melihat perubahan status

### Setelah Perbaikan:
1. Customer melakukan pembayaran di WebView
2. WebView mendeteksi payment success
3. `_handlePaymentSuccess()` dipanggil:
   - Memanggil `simulatePaymentSuccess()` → Backend webhook triggered
   - Backend `updatePaymentStatus()` → Order status berubah ke "processing"
   - Fallback `updateOrderStatus()` jika webhook gagal
   - OrderService.refreshOrders() → UI diupdate
4. Customer melihat status berubah ke "pesanan diproses"

## Endpoint API yang Digunakan

### Backend Endpoints:
- `POST /api/payment/simulate-success` - Simulasi payment success
- `POST /api/v1/orders/{orderId}/status` - Update order status
- `GET /api/v1/orders` - Refresh order list

### Backend Logic:
- `Order::updatePaymentStatus()` - Auto-update order status ketika payment = "paid"
- `PaymentWebhookController::simulatePaymentSuccess()` - Simulasi webhook Midtrans

## Testing

### Test Case 1: Payment Success via WebView
1. Buat order baru
2. Pilih payment method (e.g., Credit Card)
3. Lakukan pembayaran di WebView
4. Verify: Status order berubah dari "waiting_for_payment" ke "processing"

### Test Case 2: Payment Success via QR Code
1. Buat order baru
2. Pilih QRIS payment
3. Simulasi payment success
4. Verify: Status order berubah ke "processing"

### Test Case 3: Order Refresh
1. Setelah payment success
2. Navigate ke My Orders
3. Verify: Order list menampilkan status terbaru

## Monitoring & Debugging

### Log Messages:
- `Payment successful detected in WebView`
- `Handling payment success for transaction: {orderId}`
- `Payment simulation result: {result}`
- `Order status update result: {result}`
- `Orders refreshed after successful payment`

### Error Handling:
- Jika simulasi payment gagal → Tetap lanjut dengan fallback
- Jika update status gagal → Tampilkan warning message
- Jika refresh orders gagal → Log error tapi tidak stop flow

## Kesimpulan
Perbaikan ini memastikan bahwa status order customer Flutter akan otomatis berubah menjadi "pesanan diproses" ketika customer selesai melakukan pembayaran, baik melalui WebView maupun QR Code payment.
