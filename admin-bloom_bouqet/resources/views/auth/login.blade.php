<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bloom Bouquet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF87B2;
            --primary-light: #FFB6C1;
            --primary-dark: #D46A9F;
            --secondary-color: #FFC0D9;
            --accent-color: #FFE5EE;
            --dark-text: #333333;
            --light-text: #717171;
            --success: #4CAF50;
            --danger: #F44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: linear-gradient(135deg, var(--accent-color) 0%, #ffffff 100%);
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
        }
        
        .shape-1 {
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
            opacity: 0.5;
        }
        
        .shape-2 {
            background: linear-gradient(to right, var(--primary-light), var(--secondary-color));
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -50px;
            opacity: 0.4;
        }
        
        .shape-3 {
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            width: 150px;
            height: 150px;
            bottom: 50px;
            right: 10%;
            opacity: 0.3;
        }
        
        .login-container {
            display: flex;
            width: 900px;
            max-width: 95%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.8s ease-out;
            position: relative;
            z-index: 1;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .flower-side {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .flower-side::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .flower-side::after {
            content: '';
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 250px;
            height: 250px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .flower-decoration {
            position: absolute;
            font-size: 20px;
            color: rgba(255, 255, 255, 0.3);
            z-index: 1;
        }
        
        .flower-1 { top: 10%; left: 10%; animation: float 6s infinite ease-in-out; }
        .flower-2 { top: 20%; right: 20%; animation: float 7s infinite ease-in-out; }
        .flower-3 { bottom: 15%; left: 15%; animation: float 5s infinite ease-in-out; }
        .flower-4 { bottom: 25%; right: 10%; animation: float 8s infinite ease-in-out; }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }
        
        .logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            letter-spacing: 1px;
        }
        
        .tagline {
            font-size: 16px;
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
            line-height: 1.8;
            max-width: 80%;
            opacity: 0.9;
        }
        
        .flower-icon {
            font-size: 100px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            animation: pulse 4s infinite alternate;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        
        .login-form {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
        }
        
        .form-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--dark-text);
            font-size: 14px;
            font-weight: 500;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #eee;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .input-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(255, 135, 178, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: var(--primary-color);
        }
        
        button {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(255, 135, 178, 0.3);
        }
        
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 135, 178, 0.4);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 14px;
            background-color: #FFEBEE;
            color: #C62828;
            border: none;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(198, 40, 40, 0.1);
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--light-text);
            font-size: 14px;
        }
        
        .remember-me input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
        }
        
        .login-help {
            margin-top: 25px;
            padding: 20px;
            background-color: #FAFAFA;
            border-radius: 12px;
            font-size: 13px;
            color: var(--light-text);
            border-left: 3px solid var(--primary-light);
        }
        
        .login-help p {
            margin-bottom: 6px;
        }
        
        .login-help p:last-child {
            margin-bottom: 0;
        }
        
        .login-help strong {
            color: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 90%;
                margin: 20px;
            }
            
            .flower-side {
                padding: 40px 20px;
            }
            
            .login-form {
                padding: 40px 20px;
            }
            
            .form-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .flower-icon {
                font-size: 70px;
            }
            
            .logo {
                font-size: 26px;
            }
            
            .shape-1, .shape-2, .shape-3 {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <div class="login-container">
        <div class="flower-side">
            <div class="flower-decoration flower-1"><i class="fas fa-spa"></i></div>
            <div class="flower-decoration flower-2"><i class="fas fa-fan"></i></div>
            <div class="flower-decoration flower-3"><i class="fas fa-leaf"></i></div>
            <div class="flower-decoration flower-4"><i class="fas fa-seedling"></i></div>
            
            <div class="flower-icon">
                <i class="fas fa-spa"></i>
            </div>
            <div class="logo">Bloom Bouquet</div>
            <div class="tagline">
                Admin Dashboard for managing your beautiful flower bouquet business. Control products, orders, and more from one place.
            </div>
        </div>
        <div class="login-form">
            <h2 class="form-title">Admin Login</h2>
            
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" name="email" id="email" autocomplete="off" required autofocus>
                    @error('email')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                    @error('password')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember Me</label>
                </div>
                
                <button type="submit">Sign In <i class="fas fa-arrow-right ms-1"></i></button>
            </form>
            
        </div>
    </div>
</body>
</html> 