<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>FinanceDesk - Closing Sheet & Ledger Management | Proware Technologies</title>

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
            --card-bg: rgba(15, 23, 42, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
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
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        .page-wrapper {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(37, 99, 235, 0.25), transparent 70%),
                radial-gradient(ellipse 60% 40% at 85% 90%, rgba(14, 165, 233, 0.15), transparent 60%),
                radial-gradient(ellipse 50% 30% at 15% 85%, rgba(99, 102, 241, 0.12), transparent 50%),
                var(--bg-dark);
        }

        /* Subtle modern grid pattern */
        .page-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 1;
        }

        /* Ambient glowing decorative circles */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 1;
        }
        .glow-orb-1 {
            width: 320px;
            height: 320px;
            background: rgba(37, 99, 235, 0.18);
            top: 5%;
            left: 50%;
            transform: translateX(-50%);
        }
        .glow-orb-2 {
            width: 260px;
            height: 260px;
            background: rgba(56, 189, 248, 0.12);
            bottom: 10%;
            right: 5%;
        }

        /* Header Navbar */
        .navbar-custom {
            padding: 1rem 1.25rem;
            position: relative;
            z-index: 10;
        }

        @media (min-width: 768px) {
            .navbar-custom {
                padding: 1.5rem 2.5rem;
            }
        }

        .brand-logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .brand-text-group {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand-company {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 600;
        }

        .brand-product {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .brand-product span {
            color: #38bdf8;
        }

        /* Nav Action Button */
        .btn-nav-action {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #f1f5f9;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.5rem 1.1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.25s ease;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            min-height: 40px;
        }

        .btn-nav-action:hover, .btn-nav-action:focus {
            background: rgba(37, 99, 235, 0.2);
            border-color: rgba(96, 165, 250, 0.5);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Hero Content */
        .hero-section {
            position: relative;
            z-index: 10;
            padding: 2.5rem 1rem;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        @media (min-width: 768px) {
            .hero-section {
                padding: 3.5rem 1.5rem;
            }
        }

        /* Live Status Pill */
        .live-status-pill {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(56, 189, 248, 0.25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Typography */
        .hero-title {
            font-size: clamp(2rem, 5.5vw, 3.75rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.035em;
            color: #ffffff;
            margin-bottom: 1.25rem;
        }

        .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 50%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .hero-desc {
            font-size: clamp(0.95rem, 2.2vw, 1.18rem);
            color: #94a3b8;
            max-width: 680px;
            margin: 0 auto 2rem auto;
            line-height: 1.65;
            font-weight: 400;
        }

        /* CTA Glow Button */
        .btn-glow-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 0.9rem 2.25rem;
            border-radius: 14px;
            border: 1px solid rgba(147, 197, 253, 0.35);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.45);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            min-height: 52px;
            width: 100%;
            max-width: 290px;
        }

        .btn-glow-primary:hover, .btn-glow-primary:focus {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.65);
            border-color: rgba(147, 197, 253, 0.6);
        }

        .btn-glow-primary:active {
            transform: translateY(0);
        }

        /* Feature Badges / Cards Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            max-width: 780px;
            margin: 2.25rem auto 0 auto;
        }

        @media (min-width: 768px) {
            .features-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 1rem 0.85rem;
            border-radius: 14px;
            text-align: center;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .feature-card:hover {
            border-color: rgba(56, 189, 248, 0.3);
            background: rgba(30, 41, 59, 0.85);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .feature-icon-wrap {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 0.2rem;
        }

        .feature-title {
            font-size: 0.84rem;
            font-weight: 600;
            color: #f1f5f9;
            margin: 0;
            line-height: 1.25;
        }

        .feature-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            margin: 0;
            line-height: 1.2;
        }

        /* Footer */
        .footer-custom {
            padding: 1.25rem 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            position: relative;
            z-index: 10;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(7, 11, 20, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
        }

        @media (min-width: 768px) {
            .footer-content {
                flex-direction: row;
                justify-content: space-between;
                max-width: 1200px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <!-- Ambient Glow Orbs -->
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <!-- Header Navbar -->
    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <a href="{{ url('/') }}" class="brand-logo-container">
            <div class="brand-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="brand-text-group">
                <span class="brand-company">Proware Technologies</span>
                <span class="brand-product">Finance<span>Desk</span></span>
            </div>
        </a>

        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-nav-action">
                    <i class="bi bi-speedometer2 text-primary"></i>
                    <span>Dashboard</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-nav-action">
                    <i class="bi bi-box-arrow-in-right text-primary"></i>
                    <span>Sign In</span>
                </a>
            @endauth
        </div>
    </header>

    <!-- Main Hero Section -->
    <main class="hero-section">
        <div class="container text-center px-3 px-md-4">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-9 col-xl-8">
                    
                    <!-- Live Status Badge -->
                    <div class="d-flex justify-content-center">
                        <div class="live-status-pill">
                            <span class="pulse-dot"></span>
                            <span>Enterprise Closing & Ledger System &bull; v1.0</span>
                        </div>
                    </div>

                    <!-- Main Hero Headline -->
                    <h1 class="hero-title">
                        Shift Closing & <span class="gradient-text">Financial Ledgers</span>
                    </h1>

                    <!-- Description -->
                    <p class="hero-desc">
                        Effortless morning & evening shift reconciliations, automatic cash note denomination counter, real-time variance detection, and comprehensive multi-party ledgers.
                    </p>

                    <!-- Main Action Button -->
                    <div class="d-flex justify-content-center mb-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-glow-primary">
                                <span>Go to Dashboard</span>
                                <i class="bi bi-arrow-right fs-5"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-glow-primary">
                                <span>Sign In to System</span>
                                <i class="bi bi-arrow-right fs-5"></i>
                            </a>
                        @endauth
                    </div>

                    <!-- Features Showcase Grid -->
                    <div class="features-grid">
                        <!-- Feature 1 -->
                        <div class="feature-card">
                            <div class="feature-icon-wrap" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3 class="feature-title">Shift Closing</h3>
                            <p class="feature-subtitle">Morning & Evening</p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="feature-card">
                            <div class="feature-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <h3 class="feature-title">Cash Counter</h3>
                            <p class="feature-subtitle">Auto Denomination</p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="feature-card">
                            <div class="feature-icon-wrap" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8;">
                                <i class="bi bi-calendar2-check"></i>
                            </div>
                            <h3 class="feature-title">Day Closings</h3>
                            <p class="feature-subtitle">Audit & Approval</p>
                        </div>

                        <!-- Feature 4 -->
                        <div class="feature-card">
                            <div class="feature-icon-wrap" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                                <i class="bi bi-journal-bookmark"></i>
                            </div>
                            <h3 class="feature-title">Party Ledgers</h3>
                            <p class="feature-subtitle">Bank & Cash Books</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content">
            <div>
                <span>FinanceDesk &bull; <strong>Proware Technologies</strong> &copy; {{ date('Y') }}. All rights reserved.</span>
            </div>
            <div class="d-flex align-items-center gap-2 justify-content-center text-muted">
                <i class="bi bi-shield-check text-success"></i>
                <span>Enterprise Grade Security</span>
            </div>
        </div>
    </footer>
</div>

</body>
</html>
