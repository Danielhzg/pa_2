<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - Bloom Bouquet</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    @include('admin.layouts.navbar')

    <!-- Main Content -->
    <div class="container-fluid py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Chat Notification Script -->
    <script>
        $(document).ready(function() {
            // Function to check for unread messages
            function checkUnreadMessages() {
                $.ajax({
                    url: "{{ route('admin.chats.unread') }}",
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        if (data.success && data.unread_count > 0) {
                            // Update the unread counter
                            var badge = $('.nav-link .badge[data-count-for="chat"]');
                            if (badge.length === 0) {
                                // Create badge if it doesn't exist
                                $('.nav-link[href="{{ route('admin.chats.index') }}"]').append(
                                    '<span class="badge bg-danger ms-1" data-count-for="chat">' + data.unread_count + '</span>'
                                );
                            } else {
                                // Update existing badge
                                badge.text(data.unread_count);
                                badge.show();
                            }
                        } else {
                            // Hide badge if no unread messages
                            $('.nav-link .badge[data-count-for="chat"]').hide();
                        }
                    },
                    error: function() {
                        console.error("Failed to check for unread messages");
                    }
                });
            }

            // Check for unread messages every 30 seconds
            checkUnreadMessages(); // Check on page load
            setInterval(checkUnreadMessages, 30000); // Check every 30 seconds
        });
    </script>
    
    @stack('scripts')
</body>
</html> 