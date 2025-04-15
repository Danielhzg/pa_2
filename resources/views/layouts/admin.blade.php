<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffe6f2; /* Light pink background */
            color: #333;
        }
        .navbar {
            background-color: var(--pink-primary); /* Mengubah warna navbar menjadi pink */
        }
        .navbar .nav-link {
            color: white; /* Warna teks navbar */
        }
        .navbar .nav-link:hover {
            background-color: var(--pink-dark); /* Warna hover link navbar */
            color: white;
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #ff66b2;
            border-color: #ff66b2;
        }
        .btn-primary:hover {
            background-color: #ff4da6;
            border-color: #ff4da6;
        }
        .alert {
            animation: fadeIn 0.5s ease-in-out, fadeOut 0.5s ease-in-out 3s forwards;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        .notification {
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ff66b2;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            transition: top 0.5s ease-in-out;
        }
        .notification.show {
            top: 20px;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .toast {
            background-color: #ff66b2;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-in-out, fadeOut 0.5s ease-in-out 3s forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">Admin Panel</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.categories.index') }}">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.products.index') }}">
                        <i class="fas fa-seedling"></i> Products
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    @if (session('success'))
        <div id="notification" class="notification">
            {{ session('success') }}
        </div>
    @endif
    <div class="toast-container">
        @if (session('success'))
            <div class="toast">
                {{ session('success') }}
            </div>
        @endif
    </div>
    <div class="container mt-4">
        @yield('content')
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000); // Notification disappears after 3 seconds
            }
        });
    </script>
</body>
</html>
