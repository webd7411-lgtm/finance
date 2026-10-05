<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - FinanceDesk</title>

    <!-- Standard Clean Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --nav-bg: #090e17;
            --nav-border: #1e293b;
            --primary-accent: #2563eb;
            --page-bg: #f8fafc;
            --bs-font-sans-serif: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --bs-font-monospace: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            --bs-body-font-family: var(--bs-font-sans-serif);
        }
        *, *::before, *::after, body, input, button, select, textarea, .font-monospace, code, kbd, samp, pre {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            letter-spacing: normal !important;
        }
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100%;
        }
        body {
            background-color: var(--page-bg);
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* VIP Top Navbar */
        .vip-navbar {
            background: #090e17;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.45rem 0;
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            width: 100%;
        }
        .vip-brand {
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
        }
        .vip-brand:hover {
            opacity: 0.95;
            color: #ffffff;
        }
        .vip-brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
            flex-shrink: 0;
        }
        .vip-brand-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.12;
        }
        .vip-company-name {
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #ffffff;
            text-transform: uppercase;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            white-space: nowrap;
        }
        .vip-brand-date {
            font-size: 0.68rem;
            font-weight: 600;
            color: #38bdf8;
            letter-spacing: 0.2px;
            margin: 1px 0;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }
        .vip-product-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .vip-product-name .text-accent {
            color: #60a5fa;
        }
        @media (max-width: 576px) {
            .vip-brand {
                gap: 6px;
            }
            .vip-brand-icon {
                width: 34px;
                height: 34px;
                font-size: 1.05rem;
                border-radius: 8px;
            }
            .vip-company-name {
                font-size: 0.88rem;
            }
            .vip-brand-date {
                font-size: 0.64rem;
            }
            .vip-product-name {
                font-size: 0.78rem;
            }
        }
        .vip-nav-link {
            color: #cbd5e1;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.35rem 0.52rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .vip-nav-link i {
            font-size: 0.85rem;
        }
        @media (min-width: 1440px) {
            .vip-nav-link {
                font-size: 0.84rem;
                padding: 0.42rem 0.68rem;
                gap: 5px;
            }
        }
        .vip-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }
        .vip-nav-link.active {
            color: #ffffff;
            background: #2563eb;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .vip-nav-disabled {
            color: #64748b !important;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 0.55rem 0.85rem;
            cursor: not-allowed;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            opacity: 0.8;
        }
        /* VIP Dropdown Menu Styling */
        .vip-dropdown-menu {
            background-color: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 10px !important;
            padding: 6px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
        }
        .vip-dropdown-menu .dropdown-item {
            color: #cbd5e1 !important;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 8px 12px !important;
            border-radius: 6px !important;
            transition: all 0.15s ease-in-out;
            background-color: transparent !important;
        }
        .vip-dropdown-menu .dropdown-item:hover,
        .vip-dropdown-menu .dropdown-item:focus {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .vip-dropdown-menu .dropdown-item.active {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .vip-dropdown-menu .dropdown-item-danger:hover,
        .vip-dropdown-menu .dropdown-item-danger:focus {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #ef4444 !important;
        }
        .vip-dropdown-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1) !important;
            margin: 5px 0 !important;
        }
        /* Modal Scroll Fix */
        .modal {
            overflow-y: auto !important;
        }
        .modal-dialog {
            margin: 1.75rem auto !important;
        }
        .modal-body {
            max-height: calc(82vh - 120px);
            overflow-y: auto !important;
        }
        /* Subbar with Breadcrumb & Context */
        .sub-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 0;
            margin-bottom: 1.5rem;
        }
        .card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .table-custom {
            margin-bottom: 0;
        }
        .table-custom thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 11px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-custom tbody td {
            padding: 13px 16px;
            font-size: 0.88rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-role-owner {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.25);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .badge-role-incharge {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
            border: 1px solid rgba(37, 99, 235, 0.25);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .badge-role-cashier {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.25);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 6px;
        }
        @media print {
            .vip-navbar, .sub-header, .no-print, .btn {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- VIP Executive Top Navbar -->
    <nav class="vip-navbar">
        <div class="container-fluid px-2 px-md-3 px-xl-4">
            <div class="d-flex align-items-center justify-content-between w-100 flex-nowrap">
                <!-- Brand & Navigation -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="vip-brand">
                        <div class="vip-brand-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="vip-brand-content">
                            <span class="vip-company-name">Proware Technologies</span>
                            <div class="vip-brand-date">
                                <i class="bi bi-calendar3 me-1"></i>{{ date('D, d M Y') }}
                            </div>
                            <span class="vip-product-name">Finance<span class="text-accent">Desk</span></span>
                        </div>
                    </a>

                    <!-- Desktop Nav Links -->
                    <div class="d-none d-xl-flex align-items-center gap-1">
                        <a href="{{ route('dashboard') }}" class="vip-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>

                        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                            <a href="{{ route('users.index') }}" class="vip-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="bi bi-people"></i> Users & Roles
                            </a>
                        @endif

                        <!-- Step 2: Master Setup Dropdown -->
                        <div class="dropdown">
                            <button class="vip-nav-link border-0 bg-transparent dropdown-toggle {{ request()->routeIs('parties.*') || request()->routeIs('accounts.*') || request()->routeIs('expense-categories.*') ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sliders"></i> Master Setup
                            </button>
                            <ul class="dropdown-menu vip-dropdown-menu" style="min-width: 220px;">
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('parties.index') }}">
                                        <i class="bi bi-person-lines-fill text-primary"></i> Parties & Ledgers
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('accounts.index') }}">
                                        <i class="bi bi-bank text-success"></i> Payment Accounts
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('expense-categories.index') }}">
                                        <i class="bi bi-tags text-warning"></i> Expense Categories
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('shift-closings.index') }}" class="vip-nav-link {{ request()->routeIs('shift-closings.*') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i> Shift Closing
                        </a>
                        <a href="{{ route('transactions.index') }}" class="vip-nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-left-right"></i> Payments In / Out
                        </a>
                        <a href="{{ route('day-closings.index') }}" class="vip-nav-link {{ request()->routeIs('day-closings.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-check"></i> Day Closing
                        </a>

                        <!-- Step 6: Ledgers & Reports Dropdown -->
                        <div class="dropdown">
                            <button class="vip-nav-link border-0 bg-transparent dropdown-toggle {{ request()->routeIs('ledgers.*') || request()->routeIs('reports.*') ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-journal-text"></i> Ledgers & Reports
                            </button>
                            <ul class="dropdown-menu vip-dropdown-menu" style="min-width: 240px;">
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('ledgers.party') }}">
                                        <i class="bi bi-person-lines-fill text-primary"></i> Party Ledger
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('ledgers.cash-book') }}">
                                        <i class="bi bi-cash-stack text-success"></i> Cash Book Register
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('ledgers.bank-book') }}">
                                        <i class="bi bi-bank text-info"></i> Bank & Wallet Register
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('reports.daily-closing') }}">
                                        <i class="bi bi-calendar2-range text-warning"></i> Daily Closing Summary
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('reports.variance') }}">
                                        <i class="bi bi-shield-exclamation text-danger"></i> Discrepancy Audit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 {{ request()->routeIs('reports.expenses') ? 'active' : '' }}" href="{{ route('reports.expenses') }}">
                                        <i class="bi bi-receipt-cutoff text-danger"></i> Expense Reports
                                    </a>
                                </li>
                            </ul>
                        </div>

                        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                            <a href="{{ route('audit-logs.index') }}" class="vip-nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                                <i class="bi bi-shield-check"></i> Audit Trail
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Right Side Actions & User Profile -->
                <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
                    <!-- User Profile Dropdown -->
                    <div class="dropdown flex-shrink-0">
                        <button class="btn btn-sm btn-dark d-flex align-items-center gap-1.5 border border-secondary border-opacity-25 rounded-3 px-2 py-1 flex-shrink-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #1e293b;">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 24px; height: 24px; font-size: 0.72rem;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="text-white small fw-semibold d-none d-sm-inline">{{ auth()->user()->name }}</span>
                            <span class="d-none d-xxl-inline">
                                @if(auth()->user()->isOwner())
                                    <span class="badge-role-owner">Owner</span>
                                @elseif(auth()->user()->isIncharge())
                                    <span class="badge-role-incharge">Incharge</span>
                                @else
                                    <span class="badge-role-cashier">Cashier</span>
                                @endif
                            </span>
                            <i class="bi bi-chevron-down text-secondary" style="font-size: 0.65rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end vip-dropdown-menu" style="min-width: 210px;">
                            <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1">
                                <div class="fw-bold text-white small">{{ auth()->user()->name }}</div>
                                <div class="text-secondary small" style="font-size: 0.75rem;">{{ auth()->user()->email }}</div>
                            </li>
                            @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('users.index') }}">
                                        <i class="bi bi-people text-primary"></i> Manage Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('audit-logs.index') }}">
                                        <i class="bi bi-shield-check text-info"></i> Audit Trail & Logs
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item dropdown-item-danger text-danger py-2 d-flex align-items-center gap-2">
                                        <i class="bi bi-box-arrow-right"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>

                    <!-- Mobile Menu Drawer Toggle Button -->
                    <button class="btn btn-sm btn-dark text-white d-xl-none flex-shrink-0 px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" id="mobileNavToggleBtn" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Offcanvas Drawer -->
    <div class="offcanvas offcanvas-start bg-dark text-white border-0 shadow-lg" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel" style="width: 295px; background-color: #0b1120 !important; z-index: 1060;">
        <div class="offcanvas-header border-bottom border-secondary border-opacity-25 pb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="vip-brand-icon" style="width: 36px; height: 36px; font-size: 1.1rem;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="d-flex flex-column lh-1">
                    <span class="fw-bold text-white text-uppercase" style="font-size: 0.95rem; letter-spacing: 0.4px;">Proware Technologies</span>
                    <span class="text-info mt-1" style="font-size: 0.72rem;"><i class="bi bi-calendar3 me-1"></i>{{ date('D, d M Y') }}</span>
                    <span class="fw-semibold text-white mt-1" style="font-size: 0.85rem;">Finance<span class="text-primary">Desk</span></span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close" id="mobileNavCloseBtn"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column justify-content-between p-3" style="overflow-y: auto;">
            <div class="d-flex flex-column gap-1">
                <!-- User Profile Pill -->
                <div class="p-2 mb-2 rounded-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="lh-1 text-truncate">
                        <div class="fw-bold text-white small text-truncate">{{ auth()->user()->name }}</div>
                        <div class="text-secondary text-truncate" style="font-size: 0.72rem;">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="ms-auto flex-shrink-0">
                        @if(auth()->user()->isOwner())
                            <span class="badge-role-owner">Owner</span>
                        @elseif(auth()->user()->isIncharge())
                            <span class="badge-role-incharge">Incharge</span>
                        @else
                            <span class="badge-role-cashier">Cashier</span>
                        @endif
                    </div>
                </div>

                <!-- Navigation Links -->
                <a href="{{ route('dashboard') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                </a>

                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                    <a href="{{ route('users.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people text-info"></i> Users & Roles
                    </a>
                @endif

                <div class="text-secondary small fw-bold text-uppercase px-2 pt-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Master Setup</div>
                <a href="{{ route('parties.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('parties.*') ? 'active' : '' }}">
                    <i class="bi bi-person-lines-fill text-primary"></i> Parties & Ledgers
                </a>
                <a href="{{ route('accounts.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <i class="bi bi-bank text-success"></i> Payment Accounts
                </a>
                <a href="{{ route('expense-categories.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags text-warning"></i> Expense Categories
                </a>

                <div class="text-secondary small fw-bold text-uppercase px-2 pt-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Daily Operations</div>
                <a href="{{ route('shift-closings.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('shift-closings.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history text-info"></i> Shift Closing
                </a>
                <a href="{{ route('transactions.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right text-success"></i> Payments In / Out
                </a>
                <a href="{{ route('day-closings.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('day-closings.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check text-primary"></i> Day Closing
                </a>

                <div class="text-secondary small fw-bold text-uppercase px-2 pt-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Ledgers & Reports</div>
                <a href="{{ route('ledgers.party') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('ledgers.party') ? 'active' : '' }}">
                    <i class="bi bi-journal-text text-primary"></i> Party Ledger
                </a>
                <a href="{{ route('ledgers.cash-book') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('ledgers.cash-book') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack text-success"></i> Cash Book Register
                </a>
                <a href="{{ route('ledgers.bank-book') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('ledgers.bank-book') ? 'active' : '' }}">
                    <i class="bi bi-wallet2 text-info"></i> Bank & Wallet Register
                </a>
                <a href="{{ route('reports.daily-closing') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('reports.daily-closing') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-range text-warning"></i> Daily Closing Summary
                </a>
                <a href="{{ route('reports.variance') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('reports.variance') ? 'active' : '' }}">
                    <i class="bi bi-shield-exclamation text-danger"></i> Discrepancy Audit
                </a>
                <a href="{{ route('reports.expenses') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('reports.expenses') ? 'active' : '' }}">
                    <i class="bi bi-receipt-cutoff text-danger"></i> Expense Reports
                </a>

                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                    <div class="text-secondary small fw-bold text-uppercase px-2 pt-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Security & Audit</div>
                    <a href="{{ route('audit-logs.index') }}" class="vip-nav-link w-100 py-2 px-3 rounded-3 {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check text-info"></i> Audit Trail & Logs
                    </a>
                @endif
            </div>

            <div class="pt-3 mt-3 border-top border-secondary border-opacity-25">
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sub-header Bar (Title & Breadcrumb) -->
    <div class="sub-header">
        <div class="container-fluid px-3 px-lg-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold m-0 text-dark">@yield('page_title', 'Dashboard')</h5>
                    @yield('page_badge')
                </div>
                <div>
                    @yield('page_actions')
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <main class="container-fluid px-3 px-lg-4 flex-grow-1 mb-4">
        {{-- Flash Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center border-0 shadow-sm rounded-3 py-2 px-3 mb-3 small" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-6 text-success"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center border-0 shadow-sm rounded-3 py-2 px-3 mb-3 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6 text-danger"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-3 small" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-6 text-danger"></i>
                    <div class="fw-bold">Form Submission Error:</div>
                </div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container-fluid px-3 px-lg-4 d-flex justify-content-center align-items-center">
            <span>Closing Sheet & Ledger Management &bull; <strong>Proware Technologies</strong> &copy; {{ date('Y') }}</span>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bulletproof Mobile Drawer Handler
        document.addEventListener('DOMContentLoaded', function() {
            var toggleBtn = document.getElementById('mobileNavToggleBtn');
            var drawer = document.getElementById('mobileNavDrawer');
            var closeBtn = document.getElementById('mobileNavCloseBtn');
            if (!toggleBtn || !drawer) return;

            var backdrop = null;

            function openDrawer() {
                if (window.bootstrap && bootstrap.Offcanvas) {
                    var inst = bootstrap.Offcanvas.getOrCreateInstance(drawer);
                    inst.show();
                } else {
                    drawer.classList.add('show');
                    drawer.style.visibility = 'visible';
                    drawer.style.transform = 'none';
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'offcanvas-backdrop fade show';
                        backdrop.addEventListener('click', closeDrawer);
                        document.body.appendChild(backdrop);
                    }
                }
            }

            function closeDrawer() {
                if (window.bootstrap && bootstrap.Offcanvas) {
                    var inst = bootstrap.Offcanvas.getOrCreateInstance(drawer);
                    inst.hide();
                } else {
                    drawer.classList.remove('show');
                    drawer.style.visibility = 'hidden';
                    drawer.style.transform = 'translateX(-100%)';
                    if (backdrop) {
                        backdrop.remove();
                        backdrop = null;
                    }
                }
            }

            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openDrawer();
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeDrawer();
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
