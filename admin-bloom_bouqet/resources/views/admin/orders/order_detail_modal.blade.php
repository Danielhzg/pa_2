<!-- Order Detail Modal -->
<style>
    /* Custom styles for order detail modal */
    #orderDetailModal .modal-header {
        background: linear-gradient(135deg, #2962FF 0%, #1E88E5 100%);
        border-bottom: none;
        padding: 15px 20px;
    }
    
    #orderDetailModal .modal-title {
        font-weight: 600;
        font-size: 18px;
    }
    
    #orderDetailModal .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    
    #orderDetailModal .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
    }
    
    #orderDetailModal .card-header h6 {
        color: #333;
        font-size: 16px;
    }
    
    #orderDetailModal .card-body {
        padding: 20px;
    }
    
    #orderDetailModal .table th {
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
    }
    
    #orderDetailModal .badge {
        padding: 6px 10px;
        font-weight: 500;
        font-size: 12px;
    }
    
    #orderDetailModal .btn-group {
        width: 100%;
    }
    
    #orderDetailModal .dropdown-menu {
        padding: 8px 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: none;
        border-radius: 8px;
    }
    
    #orderDetailModal .dropdown-item {
        padding: 8px 15px;
        font-size: 14px;
    }
    
    #orderDetailModal .dropdown-item i {
        margin-right: 8px;
        width: 18px;
        text-align: center;
    }
    
    #orderDetailModal .toast {
        z-index: 10000;
    }
    
    /* Status colors */
    .status-badge.waiting_for_payment {
        background-color: #FF9800;
    }
    
    .status-badge.processing {
        background-color: #2196F3;
    }
    
    .status-badge.shipping {
        background-color: #3F51B5;
    }
    
    .status-badge.delivered {
        background-color: #4CAF50;
    }
    
    .status-badge.cancelled {
        background-color: #F44336;
    }
    
    .status-badge.pending {
        background-color: #FF9800;
    }
    
    .status-badge.paid {
        background-color: #4CAF50;
    }
    
    .status-badge.failed {
        background-color: #F44336;
    }
    
    .status-badge.expired {
        background-color: #9E9E9E;
    }
    
    .status-badge.refunded {
        background-color: #2196F3;
    }
    
    /* Customer information card */
    .customer-info-card .card-header {
        background-color: #FFF0F5 !important;
        border-bottom: 1px solid #FFD6E7 !important;
    }
    
    .customer-info-card .card-header h6 {
        color: #D81B60 !important;
    }
    
    /* Order information card */
    .order-info-card .card-header {
        background-color: #F5F9FF !important;
        border-bottom: 1px solid #D6E9FF !important;
    }
    
    .order-info-card .card-header h6 {
        color: #1976D2 !important;
    }
    
    /* Actions card */
    .actions-card .card-header {
        background-color: #F5F5F5 !important;
        border-bottom: 1px solid #E0E0E0 !important;
    }
    
    /* Table styling */
    #orderDetailModal .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    #orderDetailModal .table th:first-child {
        border-top-left-radius: 8px;
    }
    
    #orderDetailModal .table th:last-child {
        border-top-right-radius: 8px;
    }
    
    #orderDetailModal .table-bordered td, 
    #orderDetailModal .table-bordered th {
        border-color: #eaeaea;
    }
    
    /* Modal footer */
    #orderDetailModal .modal-footer {
        border-top: 1px solid #eaeaea;
        padding: 15px 20px;
    }
    
    /* Button styling */
    #orderDetailModal .btn-primary {
        background-color: #1976D2;
        border-color: #1976D2;
    }
    
    #orderDetailModal .btn-success {
        background-color: #2E7D32;
        border-color: #2E7D32;
    }
    
    #orderDetailModal .btn-secondary {
        background-color: #616161;
        border-color: #616161;
    }
    
    #orderDetailModal .btn-pink {
        background-color: #FF4081;
        border-color: #FF4081;
        color: white;
    }
    
    #orderDetailModal .btn-pink:hover {
        background-color: #F50057;
        border-color: #F50057;
        color: white;
    }
</style>

<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="orderDetailModalLabel">Detail Pesanan #<span id="order-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Order Information -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm order-info-card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold">Informasi Pesanan</h6>
                                <div>
                                    <span class="badge bg-warning me-2" id="order-status-badge">Menunggu Pembayaran</span>
                                    <span class="badge bg-warning" id="payment-status-badge">Menunggu Pembayaran</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Tanggal Pesanan:</strong></p>
                                        <p id="order-date">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Metode Pembayaran:</strong></p>
                                        <p id="payment-method">-</p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>ID Order:</strong></p>
                                        <p id="order-id-text">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Batas Pembayaran:</strong></p>
                                        <p id="payment-deadline">-</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive mt-4">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="15%">Gambar</th>
                                                <th>Produk</th>
                                                <th>Harga</th>
                                                <th>Jumlah</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="order-items">
                                            <!-- Items will be loaded here -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                                <td id="subtotal-amount"><strong>-</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Biaya Pengiriman:</strong></td>
                                                <td id="shipping-amount"><strong>-</strong></td>
                                            </tr>
                                            <tr class="table-light">
                                                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                <td id="total-amount"><strong>-</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm customer-info-card">
                            <div class="card-header bg-light">
                                <h6 class="m-0 fw-bold">Informasi Pelanggan</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="avatar avatar-lg mb-3">
                                        <img src="" id="customer-avatar" alt="Customer Avatar" class="rounded-circle" width="80" height="80" style="display: none;">
                                        <i class="fas fa-user-circle fa-4x text-secondary" id="customer-avatar-placeholder"></i>
                                    </div>
                                    <h5 id="customer-name" class="mb-2">-</h5>
                                    <a href="#" id="customer-profile-link" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> Lihat Profil
                                    </a>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="fw-bold mb-2">Email</h6>
                                    <p id="customer-email" class="mb-0">-</p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="fw-bold mb-2">Nomor Telepon</h6>
                                    <p id="customer-phone" class="mb-0">-</p>
                                </div>
                                
                                <div class="mb-0">
                                    <h6 class="fw-bold mb-2">Alamat Pengiriman</h6>
                                    <div id="shipping-address" class="mb-0">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Actions -->
                        <div class="card shadow-sm mt-3 actions-card">
                            <div class="card-header bg-light">
                                <h6 class="m-0 fw-bold">Tindakan</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success update-payment-btn" data-status="paid">
                                        <i class="fas fa-check-circle"></i> Tandai Sudah Dibayar
                                    </button>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-exchange-alt"></i> Ubah Status Pesanan
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            <li><button class="dropdown-item update-status-btn" data-status="processing">
                                                <i class="fas fa-box text-info"></i> Proses Pesanan
                                            </button></li>
                                            <li><button class="dropdown-item update-status-btn" data-status="shipping">
                                                <i class="fas fa-shipping-fast text-primary"></i> Kirim Pesanan
                                            </button></li>
                                            <li><button class="dropdown-item update-status-btn" data-status="delivered">
                                                <i class="fas fa-check text-success"></i> Tandai Selesai
                                            </button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button class="dropdown-item update-status-btn text-danger" data-status="cancelled">
                                                <i class="fas fa-times-circle"></i> Batalkan Pesanan
                                            </button></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
                <a href="#" id="view-full-detail-btn" class="btn btn-pink">
                    <i class="fas fa-external-link-alt me-1"></i> Lihat Detail Lengkap
                </a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Order Detail Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    // Function to format address
    function formatAddress(address) {
        if (typeof address === 'string') {
            try {
                const addressObj = JSON.parse(address);
                if (addressObj && typeof addressObj === 'object') {
                    let formattedAddress = '';
                    if (addressObj.name) formattedAddress += `<strong>${addressObj.name}</strong><br>`;
                    if (addressObj.phone) formattedAddress += `${addressObj.phone}<br>`;
                    if (addressObj.address) formattedAddress += `${addressObj.address}`;
                    if (addressObj.city) formattedAddress += `, ${addressObj.city}`;
                    if (addressObj.postal_code) formattedAddress += `, ${addressObj.postal_code}`;
                    return formattedAddress || address;
                }
                return address;
            } catch (e) {
                return address;
            }
        } else if (address && typeof address === 'object') {
            let formattedAddress = '';
            if (address.name) formattedAddress += `<strong>${address.name}</strong><br>`;
            if (address.phone) formattedAddress += `${address.phone}<br>`;
            if (address.address) formattedAddress += `${address.address}`;
            if (address.city) formattedAddress += `, ${address.city}`;
            if (address.postal_code) formattedAddress += `, ${address.postal_code}`;
            return formattedAddress || JSON.stringify(address);
        }
        return address || '-';
    }
    
    // Function to load order details
    window.loadOrderDetail = function(orderId) {
        // Create backdrop if it doesn't exist
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.zIndex = '9998';
            document.body.appendChild(backdrop);
        }
        
        // Add modal-open class to body
        document.body.classList.add('modal-open');
        
        fetch(`/admin/orders/${orderId}/api`)
            .then(response => response.json())
            .then(data => {
                // Set basic order information
                document.getElementById('order-id').textContent = data.id;
                document.getElementById('order-id-text').textContent = data.order_id || `ORDER-${data.id}`;
                document.getElementById('order-date').textContent = data.created_at;
                document.getElementById('payment-method').textContent = data.payment_method;
                document.getElementById('payment-deadline').textContent = data.payment_deadline || '-';
                
                // Set status badges
                const statusBadge = document.getElementById('order-status-badge');
                statusBadge.textContent = data.status_label;
                statusBadge.className = 'badge me-2 status-badge ' + data.status;
                
                const paymentBadge = document.getElementById('payment-status-badge');
                paymentBadge.textContent = data.payment_status_label;
                paymentBadge.className = 'badge status-badge ' + data.payment_status;
                
                // Set customer information
                const customerName = data.user?.name || data.user?.full_name || (data.shipping_address?.name || 'Guest User');
                document.getElementById('customer-name').textContent = customerName;
                
                // Set customer avatar
                const customerAvatar = document.getElementById('customer-avatar');
                const customerAvatarPlaceholder = document.getElementById('customer-avatar-placeholder');
                
                if (data.user && data.user.id) {
                    customerAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(customerName)}&background=FF87B2&color=fff&size=80`;
                    customerAvatar.style.display = 'inline-block';
                    customerAvatarPlaceholder.style.display = 'none';
                } else {
                    customerAvatar.style.display = 'none';
                    customerAvatarPlaceholder.style.display = 'inline-block';
                }
                
                document.getElementById('customer-email').textContent = data.user?.email && data.user.email !== 'guest@example.com' ? data.user.email : (data.shipping_address?.email || '-');
                document.getElementById('customer-phone').textContent = data.user?.phone || data.phone_number || data.shipping_address?.phone || '-';
                document.getElementById('shipping-address').innerHTML = formatAddress(data.shipping_address);
                
                // Set customer profile link
                const profileLink = document.getElementById('customer-profile-link');
                if (data.user && data.user.id) {
                    profileLink.href = `/admin/customers/${data.user.id}`;
                    profileLink.style.display = 'inline-block';
                } else {
                    profileLink.style.display = 'none';
                }
                
                // Set full detail link
                document.getElementById('view-full-detail-btn').href = `/admin/orders/${data.id}`;
                
                // Set order items
                const orderItemsContainer = document.getElementById('order-items');
                orderItemsContainer.innerHTML = '';
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        const row = document.createElement('tr');
                        
                        // Image cell
                        const imageCell = document.createElement('td');
                        if (item.image) {
                            const img = document.createElement('img');
                            img.src = `/storage/${item.image}`;
                            img.alt = item.name;
                            img.className = 'img-thumbnail';
                            img.style.maxHeight = '50px';
                            imageCell.appendChild(img);
                        } else {
                            const placeholder = document.createElement('div');
                            placeholder.className = 'bg-light text-center p-2 rounded';
                            placeholder.innerHTML = '<i class="fas fa-image text-muted"></i>';
                            imageCell.appendChild(placeholder);
                        }
                        row.appendChild(imageCell);
                        
                        // Name cell
                        const nameCell = document.createElement('td');
                        nameCell.textContent = item.name;
                        row.appendChild(nameCell);
                        
                        // Price cell
                        const priceCell = document.createElement('td');
                        priceCell.textContent = formatCurrency(item.price);
                        row.appendChild(priceCell);
                        
                        // Quantity cell
                        const qtyCell = document.createElement('td');
                        qtyCell.textContent = item.quantity;
                        row.appendChild(qtyCell);
                        
                        // Subtotal cell
                        const subtotalCell = document.createElement('td');
                        subtotalCell.textContent = formatCurrency(item.price * item.quantity);
                        row.appendChild(subtotalCell);
                        
                        orderItemsContainer.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 5;
                    cell.textContent = 'Tidak ada item pesanan';
                    cell.className = 'text-center';
                    row.appendChild(cell);
                    orderItemsContainer.appendChild(row);
                }
                
                // Set totals
                document.getElementById('subtotal-amount').textContent = formatCurrency(data.subtotal);
                document.getElementById('shipping-amount').textContent = formatCurrency(data.shipping_cost);
                document.getElementById('total-amount').textContent = formatCurrency(data.total_amount);
                
                // Show or hide buttons based on order status
                const updatePaymentBtn = document.querySelector('.update-payment-btn');
                updatePaymentBtn.style.display = data.payment_status === 'paid' ? 'none' : 'block';
                
                // Show the modal
                const orderDetailModal = document.getElementById('orderDetailModal');
                orderDetailModal.classList.add('show');
                orderDetailModal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                alert('Terjadi kesalahan saat memuat detail pesanan.');
            });
    };
    
    // Handle status update buttons
    document.querySelectorAll('.update-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = document.getElementById('order-id').textContent;
            const status = this.dataset.status;
            
            if (confirm(`Apakah Anda yakin ingin mengubah status pesanan menjadi "${this.textContent.trim()}"?`)) {
                fetch(`/admin/orders/${orderId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Use toast notification instead of alert
                        const toast = `
                            <div class="toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-check-circle me-2"></i> Status pesanan berhasil diperbarui.
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', toast);
                        const toastElement = document.body.lastElementChild;
                        const bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
                        bsToast.show();
                        
                        // Reload order details
                        loadOrderDetail(orderId);
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat memperbarui status pesanan.');
                    }
                })
                .catch(error => {
                    console.error('Error updating order status:', error);
                    alert('Terjadi kesalahan saat memperbarui status pesanan.');
                });
            }
        });
    });
    
    // Handle payment status update button
    document.querySelectorAll('.update-payment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = document.getElementById('order-id').textContent;
            const status = this.dataset.status;
            
            if (confirm('Apakah Anda yakin ingin menandai pesanan ini sebagai sudah dibayar?')) {
                fetch(`/admin/orders/${orderId}/payment-status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ payment_status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Use toast notification instead of alert
                        const toast = `
                            <div class="toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-check-circle me-2"></i> Status pembayaran berhasil diperbarui.
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', toast);
                        const toastElement = document.body.lastElementChild;
                        const bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
                        bsToast.show();
                        
                        // Reload order details
                        loadOrderDetail(orderId);
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat memperbarui status pembayaran.');
                    }
                })
                .catch(error => {
                    console.error('Error updating payment status:', error);
                    alert('Terjadi kesalahan saat memperbarui status pembayaran.');
                });
            }
        });
    });
    
    // Close button functionality
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            // Hide modal
            const modal = document.getElementById('orderDetailModal');
            modal.classList.remove('show');
            modal.style.display = 'none';
            
            // Remove backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
        });
    });
});
</script> 