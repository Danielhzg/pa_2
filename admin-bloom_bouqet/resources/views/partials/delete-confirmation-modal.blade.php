<!-- 
Delete Confirmation Modal 
This is a reusable component for delete confirmations across the admin interface.

Usage instructions:
1. Include this partial in your page
2. To trigger: call openDeleteModal(id, name, type, route) with:
   - id: the ID of the item to delete
   - name: display name of the item
   - type: type of item (e.g., 'product', 'category', 'carousel')
   - route: base route name, e.g., 'admin.products'
-->

<div class="modal fade delete-confirmation-modal" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Konfirmasi Hapus <span id="itemType"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="delete-warning-icon text-center mb-3">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x"></i>
                </div>
                <p class="text-center">Apakah Anda yakin ingin menghapus <span id="itemType2"></span> <strong id="itemName"></strong>?</p>
                <p class="text-center text-danger"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Batal
                </button>
                <form id="deleteItemForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .delete-warning-icon {
        margin-bottom: 1rem;
    }
    /* Fix for modal display */
    .delete-confirmation-modal {
        z-index: 9999 !important;
    }
    .delete-confirmation-modal .modal-dialog {
        z-index: 10000 !important;
        pointer-events: auto !important;
    }
    .modal-backdrop {
        z-index: 9990 !important;
    }
    body.modal-open {
        overflow: hidden;
        padding-right: 0px !important;
    }
    
    /* Fix for specific modals in categories */
    #deleteWithProductsModal {
        z-index: 9999 !important;
    }
    #deleteWithProductsModal .modal-dialog {
        z-index: 10000 !important;
        pointer-events: auto !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the delete modal
        const modalElement = document.getElementById('deleteConfirmationModal');
        if (modalElement) {
            window.deleteModal = new bootstrap.Modal(modalElement);
            
            // Fix any existing z-index or overflow issues
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.style.zIndex = '9990';
            });
        }
    });
    
    // Function to open delete modal
    function openDeleteModal(id, name, type, route) {
        // Set the item details in the modal
        document.getElementById('itemName').textContent = name;
        document.getElementById('itemType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('itemType2').textContent = type.toLowerCase();
        
        // Set the form action
        document.getElementById('deleteItemForm').action = `{{ url('') }}/${route}/${id}`;
        
        // Fix any existing modal backdrops
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.style.zIndex = '9990';
        });
        
        // Show the modal
        const modalElement = document.getElementById('deleteConfirmationModal');
        modalElement.style.zIndex = '9999';
        const modalDialog = modalElement.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.zIndex = '10000';
            modalDialog.style.pointerEvents = 'auto';
        }
        
        window.deleteModal.show();
    }
</script> 