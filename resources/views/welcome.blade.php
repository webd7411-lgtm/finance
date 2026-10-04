<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FinanceDesk - Closing Sheet & Ledger Management</title>

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
        html, body, button, a {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: normal;
        }
        html, body {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
            background: #090d16;
            color: #f1f5f9;
        }
        .page-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background:
                radial-gradient(circle at 50% 15%, rgba(37, 99, 235, 0.22) 0%, transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 20% 85%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                #090d16;
        }
        /* Grid background pattern */
        .page-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .navbar-custom {
            padding: 1.25rem 2rem;
            position: relative;
            z-index: 10;
        }
        .brand-badge {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
        }
        .hero-title {
            font-size: clamp(2.1rem, 4.5vw, 3.6rem);
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -0.03em;
            color: #ffffff;
        }
        .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 50%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc {
            font-size: clamp(0.95rem, 1.8vw, 1.2rem);
            color: #94a3b8;
            max-width: 660px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .btn-glow {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 14px 38px;
            border-radius: 12px;
            border: 1px solid rgba(96, 165, 250, 0.3);
            box-shadow: 0 0 30px rgba(37, 99, 235, 0.45);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-glow:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 0 45px rgba(37, 99, 235, 0.7);
            border-color: rgba(96, 165, 250, 0.6);
        }
        .pill-tag {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(8px);
        }
        .footer-custom {
            padding: 1rem 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            position: relative;
            z-index: 10;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        @media (max-height: 650px) {
            .hero-title { font-size: 1.8rem; }
            .hero-desc { font-size: 0.85rem; margin-bottom: 1rem !important; }
            .btn-glow { padding: 10px 24px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Sleek Dark Top Navbar -->
    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <a href="{{ url('/') }}" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="d-flex align-items-center justify-content-center text-white rounded-3 shadow" style="width: 40px; height: 40px; background: linear-gradient(135deg, #2563eb, #1e40af);">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold fs-5 text-white text-uppercase" style="letter-spacing: 0.5px;">Proware Technologies</span>
                <span class="text-secondary opacity-50">&bull;</span>
                <span class="fw-bold fs-5 text-white tracking-tight">Finance<span class="text-primary">Desk</span></span>
            </div>
        </a>

        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm px-3 py-2 fw-semibold rounded-3" style="border-color: rgba(255,255,255,0.2);">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-4 py-2 fw-semibold rounded-3" style="border-color: rgba(255,255,255,0.2);">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
            @endauth
        </div>
    </header>

    <!-- Center Hero (No scroll, perfectly fitted) -->
    <main class="container text-center px-4 position-relative" style="z-index: 10;">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <!-- Brand Badge -->
                <div class="mb-3">
                    <span class="brand-badge">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i> Proware Technologies &bull; v1.0
                    </span>
                </div>

                <!-- Main Title -->
                <h1 class="hero-title mb-3">
                    Closing Sheet & <span class="gradient-text">Ledger Management</span>
                </h1>

                <!-- Brief Subtitle -->
                <p class="hero-desc mb-4">
                    Streamlined Morning & Evening shift closing, automatic cash note denomination counting, real-time difference calculation, and comprehensive party ledgers.
                </p>

                <!-- Call to Action -->
                <div class="mb-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-glow">
                            <span>Go to Dashboard</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-glow">
                            <span>Sign In to System</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    @endauth
                </div>

                <!-- Feature Pills (Inline, Compact, Modern Dark) -->
                <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                    <span class="pill-tag"><i class="bi bi-clock-history text-primary me-1"></i> Shift Closing</span>
                    <span class="pill-tag"><i class="bi bi-cash-stack text-success me-1"></i> Cash Note Counter</span>
                    <span class="pill-tag"><i class="bi bi-calendar2-check text-info me-1"></i> Day Closing</span>
                    <span class="pill-tag"><i class="bi bi-journal-bookmark text-warning me-1"></i> Party Ledgers</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Sleek Compact Dark Footer -->
    <footer class="footer-custom">
        <span>Closing Sheet & Ledger Management &bull; <strong>Proware Technologies</strong> &copy; {{ date('Y') }}. All rights reserved.</span>
    </footer>
</div>

</body>
</html>
