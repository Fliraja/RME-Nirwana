<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - RME RSU Nirwana Banjarbaru</title>
    
    <link rel="icon" type="image/x-icon" href="/img/icon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
     <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #059669 0%, #10b981 30%, #34d399 60%, #6ee7b7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(5, 150, 105, 0.4);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -50px;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            bottom: -75px;
            left: -40px;
        }
        
        .login-logo {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .login-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .login-logo i {
            font-size: 42px;
            color: #10b981;
        }
        
        .login-header h4 {
            font-weight: 700;
            font-size: 26px;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-text h5 {
            font-weight: 600;
            color: #064e3b;
            margin-bottom: 8px;
            font-size: 20px;
        }
        
        .welcome-text p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #065f46;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            font-size: 18px;
            z-index: 10;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #d1fae5;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f0fdf4;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
            background: white;
        }
        
        .form-control.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .invalid-feedback {
            font-size: 13px;
            margin-top: 6px;
            color: #dc2626;
            display: block;
        }
        
        .btn-toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #10b981;
            cursor: pointer;
            z-index: 10;
            padding: 15px;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .btn-toggle-password:hover {
            color: #059669;
            transform: translateY(-50%) scale(1.1);
        }
        
        .form-check {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            border: 2px solid #d1fae5;
        }
        
        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }
        
        .form-check-label {
            font-size: 14px;
            color: #6b7280;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.5);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 24px 30px;
            background: linear-gradient(to bottom, #f0fdf4, #ecfdf5);
            text-align: center;
            border-top: 1px solid #d1fae5;
        }
        
        .login-footer p {
            margin: 0;
            font-size: 13px;
            color: #065f46;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 12px 16px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .login-header h4 {
                font-size: 22px;
            }
            
            .login-logo {
                width: 80px;
                height: 80px;
            }
            
            .login-logo img,
            .login-logo i {
                width: 60px;
                height: 60px;
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="{{ asset('img/icon.png') }}" alt="" style="height: 70px; width: 70px">
                </div>
                <h4>RSU Nirwana</h4>
                <p>Banjarbaru, Kalimantan Selatan</p>
            </div>
            
            <div class="login-body">
                <div class="welcome-text">
                    <h5>Selamat Datang Kembali</h5>
                    <p>Silakan masuk ke Sistem Rekam Medis Elektronik</p>
                </div>
                
                @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    {{ $errors->first() }}
                </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group-custom">
                            <i class="bi bi-person"></i>
                            <input 
                                type="text" 
                                class="form-control @error('username') is-invalid @enderror" 
                                id="username" 
                                name="username" 
                                placeholder="Masukkan username Anda"
                                value="{{ old('username') }}"
                                required
                                autofocus
                            >
                        </div>
                        @error('username')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock"></i>
                            <input 
                                type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                placeholder="Masukkan password Anda"
                                required
                            >
                            <button type="button" class="btn-toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Masuk
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>&copy; 2026 RSU Nirwana Banjarbaru. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
    
</body>
</html>