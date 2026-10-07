<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Sign In - FinanceDesk | Proware Technologies</title>
    
    <!-- Standard Clean Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-glow: #2563eb;
            --primary-accent: #38bdf8;
            --bg-dark: #070b14;
            --card-bg: rgba(15, 23, 42, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --input-bg: #090e1a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 1.25rem 1rem;
            position: relative;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(37, 99, 235, 0.25), transparent 70%),
                radial-gradient(ellipse 60% 40% at 90% 90%, rgba(14, 165, 233, 0.15), transparent 60%),
                radial-gradient(ellipse 50% 30% at 10% 85%, rgba(99, 102, 241, 0.12), transparent 50%),
                var(--bg-dark);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Subtle grid overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        /* Ambient glowing circles */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
        .glow-orb-top {
            width: 320px;
            height: 320px;
            background: rgba(37, 99, 235, 0.2);
            top: -10%;
            left: 50%;
            transform: translateX(-50%);
        }
        .glow-orb-bottom {
            width: 250px;
            height: 250px;
            background: rgba(56, 189, 248, 0.12);
            bottom: -5%;
            right: 10%;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            margin: auto;
        }

        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.65), 
                0 0 0 1px rgba(255, 255, 255, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        @media (min-width: 576px) {
            .login-card {
                padding: 2.75rem 2.25rem;
            }
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.35rem;
            margin: 0 auto 14px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .brand-subtitle-company {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #ffffff;
        }

        .brand-title span {
            color: #38bdf8;
        }

        .brand-desc {
            font-size: 0.88rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 7px;
        }

        .input-group {
            background-color: var(--input-bg);
            border: 1px solid #273549;
            border-radius: 12px;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .input-group:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25) !important;
        }

        .input-group-text {
            background-color: transparent !important;
            border: none !important;
            color: #64748b !important;
            padding-left: 14px;
            padding-right: 8px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
        }

        .form-control {
            background-color: transparent !important;
            border: none !important;
            color: #f8fafc !important;
            padding: 13px 14px 13px 4px;
            font-size: 1rem; /* 16px to prevent iOS Safari auto-zoom */
            line-height: 1.5;
            box-shadow: none !important;
            min-height: 48px;
        }

        .form-control:focus {
            background-color: transparent !important;
            color: #f8fafc !important;
            box-shadow: none !important;
        }

        .form-control::placeholder {
            color: #475569 !important;
            font-size: 0.92rem;
        }

        /* Fix Chrome / Edge autofill background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #f8fafc !important;
            -webkit-box-shadow: 0 0 0px 1000px #090e1a inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .toggle-btn {
            background-color: transparent !important;
            border: none !important;
            color: #94a3b8;
            padding: 0 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
            min-height: 48px;
        }

        .toggle-btn:hover {
            color: #f8fafc;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 24px;
        }

        .form-check-input {
            background-color: var(--input-bg);
            border: 1px solid #334155;
            cursor: pointer;
            width: 17px;
            height: 17px;
            margin-top: 0;
            border-radius: 5px;
        }

        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .form-check-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
        }

        .form-check-label {
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }

        .btn-signin {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 13px;
            border-radius: 12px;
            border: 1px solid rgba(147, 197, 253, 0.35);
            width: 100%;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 50px;
        }

        .btn-signin:hover, .btn-signin:focus {
            color: #ffffff;
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.55);
            border-color: rgba(147, 197, 253, 0.6);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        .back-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .back-link:hover {
            color: #38bdf8;
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(56, 189, 248, 0.3);
        }

        .alert-custom {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            font-size: 0.85rem;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            backdrop-filter: blur(8px);
        }

        .security-badge {
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

    <!-- Glowing background accents -->
    <div class="glow-orb glow-orb-top"></div>
    <div class="glow-orb glow-orb-bottom"></div>

    <div class="login-wrapper">
        <div class="login-card">
            
            <!-- Brand Header -->
            <div class="text-center mb-4">
                <div class="brand-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="brand-subtitle-company">Proware Technologies</div>
                <h1 class="brand-title">Finance<span>Desk</span></h1>
                <p class="brand-desc">Sign in to your financial portal</p>
            </div>

            <!-- Error Notification -->
            @if ($errors->any())
                <div class="alert-custom d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0 mt-0.5"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" autocomplete="on">
                @csrf

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus 
                            placeholder="name@domain.com"
                            autocomplete="email"
                            inputmode="email"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required 
                            placeholder="••••••••"
                            autocomplete="current-password"
                        >
                        <button class="toggle-btn" type="button" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility" tabindex="-1">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="d-flex justify-content-between align-items-center mb-4 pt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Remember this device</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-signin mb-3">
                    <span>Sign In to Account</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Back to Home -->
            <div class="text-center pt-3 mt-2 border-top" style="border-color: rgba(255, 255, 255, 0.08) !important;">
                <a href="{{ url('/') }}" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
                
                <div class="security-badge">
                    <i class="bi bi-shield-lock-fill text-success"></i>
                    <span>256-Bit SSL Encrypted &bull; Audit Monitored</span>
                </div>
            </div>

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
