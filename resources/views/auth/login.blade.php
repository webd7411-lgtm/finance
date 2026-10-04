<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - FinanceDesk</title>
    
    <!-- Standard Clean Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body, input, button, select {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: normal;
        }
        body {
            background-color: #0b1120;
            background-image: radial-gradient(at 50% 0%, rgba(37, 99, 235, 0.2) 0px, transparent 65%),
                              radial-gradient(at 100% 100%, rgba(15, 23, 42, 1) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f1f5f9;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }
        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.4rem;
            margin: 0 auto 16px;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.35);
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }
        .form-control {
            background-color: #0f172a !important;
            border: 1px solid #334155 !important;
            color: #f8fafc !important;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25) !important;
        }
        .form-control::placeholder {
            color: #64748b !important;
            font-size: 0.88rem;
        }
        /* Fix Chrome browser autofill white background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #f8fafc !important;
            -webkit-box-shadow: 0 0 0px 1000px #0f172a inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        .input-group-text {
            background-color: #0f172a !important;
            border: 1px solid #334155 !important;
            border-right: none !important;
            color: #64748b !important;
            border-radius: 10px 0 0 10px;
            padding-left: 14px;
            padding-right: 12px;
        }
        .toggle-btn {
            background-color: #0f172a !important;
            border: 1px solid #334155 !important;
            border-left: none !important;
            color: #94a3b8;
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .toggle-btn:hover {
            color: #ffffff;
        }
        .btn-signin {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px;
            border-radius: 10px;
            border: none;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .btn-signin:hover {
            background-color: #1d4ed8;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.45);
        }
        .form-check-input {
            background-color: #0f172a;
            border-color: #334155;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        .form-check-label {
            color: #94a3b8;
            font-size: 0.82rem;
            cursor: pointer;
        }
        .back-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-link:hover {
            color: #38bdf8;
        }
        .alert-custom {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            font-size: 0.85rem;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Brand Icon & Header -->
    <div class="text-center mb-4">
        <div class="brand-icon">
            <i class="bi bi-wallet2"></i>
        </div>
        <h4 class="fw-bold text-white mb-0">Proware Technologies</h4>
        <div class="text-primary fw-semibold mb-2" style="font-size: 1.1rem; letter-spacing: 0.5px;">Finance<span class="text-white">Desk</span></div>
        <p class="text-muted small mb-0">Sign in to your account</p>
    </div>

    <!-- Error Alert -->
    @if ($errors->any())
        <div class="alert-custom d-flex align-items-center">
            <i class="bi bi-exclamation-circle me-2 fs-5"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@domain.com">
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" style="border-radius: 0;">
                <button class="toggle-btn" type="button" onclick="togglePasswordVisibility()" tabindex="-1">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
        </div>

        <button type="submit" class="btn btn-signin mb-3">
            Sign In
        </button>
    </form>

    <!-- Back to Home -->
    <div class="text-center pt-3 border-top" style="border-color: #334155 !important;">
        <a href="{{ url('/') }}" class="back-link">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Home</span>
        </a>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const pass = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pass.type === 'password') {
            pass.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            pass.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>

</body>
</html>
