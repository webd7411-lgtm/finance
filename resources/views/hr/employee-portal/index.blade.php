<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Portal - Caltex Rao Filling Station</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ═══════════════════════════════════════════
           VARIABLES & RESET
        ═══════════════════════════════════════════ */
        :root {
            --bg-deep:    #0d0d1a;
            --bg-card:    rgba(255,255,255,0.05);
            --bg-card-hv: rgba(255,255,255,0.08);
            --border:     rgba(255,255,255,0.1);
            --border-glow:rgba(108,99,255,0.4);
            --purple:     #6c63ff;
            --purple-light:#8b84ff;
            --green:      #10b981;
            --green-light:#34d399;
            --amber:      #f59e0b;
            --red:        #ef4444;
            --blue:       #3b82f6;
            --text:       #f1f5f9;
            --muted:      rgba(241,245,249,0.55);
            --radius-lg:  20px;
            --radius-md:  14px;
            --radius-sm:  10px;
            --shadow:     0 8px 32px rgba(0,0,0,0.4);
            --shadow-glow:0 0 30px rgba(108,99,255,0.15);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-deep);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ═══════════════════════════════════════════
           ANIMATED BACKGROUND
        ═══════════════════════════════════════════ */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: -1;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(108,99,255,0.18) 0%, transparent 50%),
                radial-gradient(ellipse at 85% 80%, rgba(16,185,129,0.12) 0%, transparent 50%),
                linear-gradient(160deg, #0d0d1a 0%, #101028 50%, #0d1a1a 100%);
        }

        /* ═══════════════════════════════════════════
           TOP HEADER
        ═══════════════════════════════════════════ */
        .portal-header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(13,13,26,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 14px 20px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .portal-header .logo {
            font-size: 16px; font-weight: 700;
            background: linear-gradient(135deg, var(--purple-light), var(--green-light));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .portal-header .emp-info {
            display: flex; align-items: center; gap: 10px;
        }
        .emp-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
            flex-shrink: 0;
        }
        .emp-name-sm { font-size: 13px; font-weight: 600; }
        .emp-dept-sm { font-size: 11px; color: var(--muted); }

        /* ═══════════════════════════════════════════
           MAIN CONTAINER
        ═══════════════════════════════════════════ */
        .portal-body { padding: 20px 16px 100px; max-width: 480px; margin: 0 auto; }

        /* ═══════════════════════════════════════════
           GREETING STRIP
        ═══════════════════════════════════════════ */
        .greeting-strip {
            padding: 20px 0 10px;
        }
        .greeting-strip .greeting { font-size: 22px; font-weight: 700; }
        .greeting-strip .subline { font-size: 13px; color: var(--muted); margin-top: 4px; }
        .greeting-strip .date-chip {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px; color: var(--muted);
        }
        .greeting-strip .date-chip i { color: var(--purple-light); }

        /* ═══════════════════════════════════════════
           GLASS CARD
        ═══════════════════════════════════════════ */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            transition: border-color 0.3s;
        }
        .glass-card:hover { border-color: var(--border-glow); }

        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 15px; font-weight: 700;
        }
        .card-icon {
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .icon-purple { background: rgba(108,99,255,0.2); color: var(--purple-light); }
        .icon-green  { background: rgba(16,185,129,0.2);  color: var(--green-light); }
        .icon-amber  { background: rgba(245,158,11,0.2);  color: var(--amber); }
        .icon-blue   { background: rgba(59,130,246,0.2);  color: var(--blue); }
        .icon-red    { background: rgba(239,68,68,0.2);   color: var(--red); }

        /* ═══════════════════════════════════════════
           ATTENDANCE CARD
        ═══════════════════════════════════════════ */
        .att-time-row {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-bottom: 16px;
        }
        .att-time-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 14px;
            text-align: center;
        }
        .att-time-box .label { font-size: 11px; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .att-time-box .time { font-size: 20px; font-weight: 700; }
        .att-time-box .loc { font-size: 10px; color: var(--muted); margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 4px; }
        .att-time-box.done-in  { border-color: rgba(16,185,129,0.35); }
        .att-time-box.done-in .time { color: var(--green-light); }
        .att-time-box.done-out { border-color: rgba(239,68,68,0.35); }
        .att-time-box.done-out .time { color: #fc8181; }
        .att-time-box .empty { color: var(--muted); font-size: 22px; }

        .att-btn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .att-btn {
            padding: 14px 10px;
            border: none; border-radius: var(--radius-md);
            font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .att-btn:active { transform: scale(0.97); }
        .btn-checkin  { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
        .btn-checkout { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .att-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .att-btn.loading { opacity: 0.7; pointer-events: none; }

        .att-status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-present { background: rgba(16,185,129,0.15); color: var(--green-light); border: 1px solid rgba(16,185,129,0.3); }
        .badge-absent  { background: rgba(239,68,68,0.15);  color: #fc8181; border: 1px solid rgba(239,68,68,0.3); }
        .badge-late    { background: rgba(245,158,11,0.15); color: var(--amber); border: 1px solid rgba(245,158,11,0.3); }

        /* Location loader */
        .loc-loader {
            display: none; align-items: center; gap: 8px;
            font-size: 12px; color: var(--muted);
            margin-top: 10px; padding: 10px;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-sm);
        }
        .loc-loader.active { display: flex; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ═══════════════════════════════════════════
           SALARY CARD
        ═══════════════════════════════════════════ */
        .salary-big {
            text-align: center; padding: 10px 0 16px;
        }
        .salary-big .label { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
        .salary-big .amount {
            font-size: 36px; font-weight: 800;
            background: linear-gradient(135deg, #34d399, #6c63ff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .salary-big .status-row { margin-top: 8px; }

        .salary-breakdown { border-top: 1px solid var(--border); padding-top: 14px; }
        .sal-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 13px;
        }
        .sal-row:last-child { border-bottom: none; }
        .sal-row .sal-label { color: var(--muted); display: flex; align-items: center; gap: 8px; }
        .sal-row .sal-val { font-weight: 600; }
        .sal-row.total-row { padding-top: 12px; font-size: 15px; font-weight: 700; }
        .sal-row.total-row .sal-label { color: var(--text); }
        .deduction-val { color: #fc8181 !important; }
        .earning-val   { color: var(--green-light) !important; }
        .net-val       { color: var(--green-light) !important; font-size: 17px !important; }

        .month-nav {
            display: flex; align-items: center; gap: 8px;
        }
        .month-nav button {
            width: 28px; height: 28px;
            background: var(--bg-card-hv);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .month-nav span { font-size: 13px; font-weight: 600; min-width: 80px; text-align: center; }
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.09) 50%, rgba(255,255,255,0.05) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 6px;
            height: 14px; margin: 6px 0;
        }
        @keyframes shimmer { to { background-position: -200% 0; } }

        /* ═══════════════════════════════════════════
           COMMISSION CARD
        ═══════════════════════════════════════════ */
        .comm-summary {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 8px; margin-bottom: 16px;
        }
        .comm-kpi {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 8px;
            text-align: center;
        }
        .comm-kpi .kpi-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .comm-kpi .kpi-val   { font-size: 16px; font-weight: 700; margin-top: 4px; }
        .comm-kpi.earned .kpi-val { color: var(--blue); }
        .comm-kpi.paid   .kpi-val { color: var(--green-light); }
        .comm-kpi.pending .kpi-val { color: var(--amber); }

        .comm-list { display: flex; flex-direction: column; gap: 8px; }
        .comm-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }
        .comm-item-top {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 8px;
        }
        .comm-inv { font-size: 13px; font-weight: 700; }
        .comm-date { font-size: 11px; color: var(--muted); }
        .comm-badge {
            font-size: 11px; font-weight: 600; padding: 3px 10px;
            border-radius: 12px;
        }
        .badge-paid    { background: rgba(16,185,129,0.15); color: var(--green-light); }
        .badge-partial { background: rgba(245,158,11,0.15); color: var(--amber); }
        .badge-pending { background: rgba(239,68,68,0.15);  color: #fc8181; }
        .comm-amounts {
            display: flex; justify-content: space-between;
            font-size: 12px; color: var(--muted);
        }
        .comm-amounts span { display: flex; flex-direction: column; }
        .comm-amounts .val { font-size: 14px; font-weight: 600; color: var(--text); margin-top: 2px; }

        /* ═══════════════════════════════════════════
           LOAN CARD
        ═══════════════════════════════════════════ */
        .loan-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 10px;
        }
        .loan-item:last-child { margin-bottom: 0; }
        .loan-top {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 12px;
        }
        .loan-title { font-size: 14px; font-weight: 700; }
        .loan-type-chip {
            font-size: 11px; padding: 3px 10px;
            border-radius: 12px;
            background: rgba(59,130,246,0.15); color: var(--blue);
        }
        .loan-amounts {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 8px; margin-bottom: 14px;
        }
        .loan-kpi { text-align: center; }
        .loan-kpi .lk-label { font-size: 10px; color: var(--muted); text-transform: uppercase; }
        .loan-kpi .lk-val   { font-size: 15px; font-weight: 700; margin-top: 3px; }
        .lk-borrowed { color: var(--blue); }
        .lk-paid     { color: var(--green-light); }
        .lk-remaining{ color: var(--amber); }

        .loan-progress-wrap { margin-bottom: 12px; }
        .loan-progress-label {
            display: flex; justify-content: space-between;
            font-size: 11px; color: var(--muted); margin-bottom: 6px;
        }
        .loan-progress-bar {
            height: 8px; background: rgba(255,255,255,0.08);
            border-radius: 10px; overflow: hidden;
        }
        .loan-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6c63ff, #10b981);
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .loan-meta {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .loan-meta-item {
            font-size: 11px; color: var(--muted);
            display: flex; align-items: center; gap: 4px;
        }
        .loan-meta-item i { color: var(--purple-light); font-size: 10px; }

        .loan-installments {
            display: flex; justify-content: space-between;
            background: rgba(255,255,255,0.04);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            margin-top: 10px;
        }
        .inst-item { text-align: center; }
        .inst-item .il { font-size: 10px; color: var(--muted); }
        .inst-item .iv { font-size: 14px; font-weight: 700; margin-top: 2px; }

        /* ═══════════════════════════════════════════
           ATTENDANCE HISTORY
        ═══════════════════════════════════════════ */
        .att-hist-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .att-hist-item:last-child { border-bottom: none; }
        .att-day-dot {
            width: 10px; height: 10px;
            border-radius: 50%; flex-shrink: 0;
        }
        .dot-present { background: var(--green); }
        .dot-late    { background: var(--amber); }
        .dot-absent  { background: var(--red); }
        .dot-off     { background: var(--muted); }
        .att-day-info { flex: 1; }
        .att-day-name { font-size: 13px; font-weight: 600; }
        .att-day-times { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .att-day-hours { font-size: 13px; font-weight: 700; color: var(--purple-light); }

        /* ═══════════════════════════════════════════
           BOTTOM NAV
        ═══════════════════════════════════════════ */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
            background: rgba(13,13,26,0.92);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            display: flex; padding: 8px 0 safe-area-inset-bottom;
        }
        .nav-btn {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 4px; padding: 8px 4px;
            font-size: 10px; color: var(--muted);
            background: none; border: none; cursor: pointer;
            transition: all 0.2s;
        }
        .nav-btn i { font-size: 20px; transition: all 0.2s; }
        .nav-btn.active { color: var(--purple-light); }
        .nav-btn.active i { transform: scale(1.15); }
        .nav-btn:not(.active):hover { color: var(--text); }

        /* ═══════════════════════════════════════════
           SECTION TOGGLE
        ═══════════════════════════════════════════ */
        .portal-section { display: none; }
        .portal-section.active { display: block; }

        /* ═══════════════════════════════════════════
           EMPTY STATES
        ═══════════════════════════════════════════ */
        .empty-state {
            text-align: center; padding: 30px 20px;
            color: var(--muted);
        }
        .empty-state i { font-size: 40px; margin-bottom: 12px; display: block; opacity: 0.4; }
        .empty-state p { font-size: 13px; }

        /* ═══════════════════════════════════════════
           MISC
        ═══════════════════════════════════════════ */
        .section-label {
            font-size: 11px; text-transform: uppercase;
            letter-spacing: 1px; color: var(--muted);
            margin-bottom: 12px; padding-left: 4px;
        }
        .divider { height: 1px; background: var(--border); margin: 12px 0; }

        /* scroll sub-tab */
        .tab-bar {
            display: flex; gap: 8px; overflow-x: auto;
            padding-bottom: 4px; margin-bottom: 12px;
            scrollbar-width: none;
        }
        .tab-bar::-webkit-scrollbar { display: none; }
        .tab-pill {
            padding: 6px 16px; border-radius: 20px; white-space: nowrap;
            font-size: 12px; font-weight: 600; cursor: pointer;
            background: var(--bg-card); border: 1px solid var(--border);
            color: var(--muted); transition: all 0.2s;
        }
        .tab-pill.active { background: var(--purple); border-color: var(--purple); color: #fff; }

        /* pulse ring for check-in */
        .pulse-ring {
            position: relative;
        }
        .pulse-ring::before {
            content: '';
            position: absolute; inset: -4px;
            border-radius: inherit;
            background: rgba(16,185,129,0.25);
            animation: pulse-anim 2s ease-out infinite;
        }
        @keyframes pulse-anim { 0%,100% { opacity: 1; transform: scale(1); } 60% { opacity: 0; transform: scale(1.15); } }

        /* Loading overlay */
        .loading-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(13,13,26,0.7);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .loading-overlay.active { display: flex; }
        .loading-spinner {
            width: 48px; height: 48px;
            border: 3px solid rgba(108,99,255,0.3);
            border-top-color: var(--purple);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* ═══════════════════════════════════════════
           HEADER ACTIONS & PROFILE DROPDOWN
        ═══════════════════════════════════════════ */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .emp-info-wrap {
            position: relative;
        }
        .portal-header .emp-info {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 24px;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .portal-header .emp-info:hover {
            background: rgba(255, 255, 255, 0.09);
            border-color: var(--border-glow);
        }
        .emp-dropdown-icon {
            font-size: 10px;
            color: var(--muted);
            transition: transform 0.2s ease;
            margin-left: 2px;
        }
        .emp-info-wrap.open .emp-dropdown-icon {
            transform: rotate(180deg);
        }

        .btn-logout-header {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-logout-header:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
        }
        .btn-logout-header i {
            font-size: 12px;
            color: #ef4444;
            transition: color 0.2s;
        }
        .btn-logout-header:hover i {
            color: #fff;
        }
        @media (max-width: 440px) {
            .btn-logout-header .logout-text { display: none; }
            .btn-logout-header { padding: 8px 10px; }
            .portal-header .emp-dept-sm { display: none; }
        }

        /* Profile Dropdown */
        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 250px;
            background: rgba(18, 18, 36, 0.98);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
            padding: 14px;
            z-index: 150;
            display: none;
        }
        .profile-dropdown-menu.show {
            display: block;
            animation: dropDownSlide 0.2s ease;
        }
        @keyframes dropDownSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 8px;
        }
        .profile-dropdown-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        .profile-dropdown-name {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }
        .profile-dropdown-role {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }
        .profile-dropdown-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            color: var(--text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
        }
        .profile-dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        .profile-dropdown-item.item-logout {
            color: #fca5a5;
            margin-top: 4px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 10px;
        }
        .profile-dropdown-item.item-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .profile-dropdown-backdrop {
            position: fixed;
            inset: 0;
            z-index: 140;
            display: none;
        }
        .profile-dropdown-backdrop.show {
            display: block;
        }

        /* Profile Tab Section */
        .profile-hero-card {
            text-align: center;
            padding: 24px 20px;
        }
        .profile-hero-avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 24px;
            margin: 0 auto 12px;
            box-shadow: 0 8px 24px rgba(108, 99, 255, 0.35);
            border: 2px solid rgba(255,255,255,0.2);
        }
        .profile-hero-name {
            font-size: 18px;
            font-weight: 700;
        }
        .profile-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(108,99,255,0.15);
            border: 1px solid rgba(108,99,255,0.35);
            color: var(--purple-light);
        }
        .profile-info-list {
            display: flex;
            flex-direction: column;
        }
        .profile-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 13px;
        }
        .profile-info-row:last-child {
            border-bottom: none;
        }
        .profile-info-label {
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-info-label i {
            width: 16px;
            text-align: center;
            color: var(--purple-light);
        }
        .profile-info-val {
            font-weight: 600;
            color: var(--text);
            text-align: right;
        }
        .btn-erp-switch {
            width: 100%;
            padding: 12px 18px;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #93c5fd;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-erp-switch:hover {
            background: rgba(59, 130, 246, 0.25);
            border-color: #3b82f6;
            color: #fff;
        }
        .btn-logout-full {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(220,38,38,0.35));
            border: 1px solid rgba(239,68,68,0.5);
            color: #fff;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(239,68,68,0.2);
        }
        .btn-logout-full:hover {
            background: linear-gradient(135deg, rgba(239,68,68,0.35), rgba(220,38,38,0.55));
            border-color: #ef4444;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(239,68,68,0.35);
        }

        /* SweetAlert2 Custom Dark Theme */
        div:where(.swal2-container).swal2-backdrop-show {
            background: rgba(13, 13, 26, 0.78) !important;
            backdrop-filter: blur(8px) !important;
        }
        .swal2-popup.swal2-dark-card {
            background: #151528 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 22px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.75) !important;
            padding: 24px !important;
        }
        .swal2-popup.swal2-dark-card .swal2-title {
            color: #fff !important;
            font-size: 20px !important;
            font-weight: 700 !important;
        }
        .swal2-popup.swal2-dark-card .swal2-html-container {
            color: var(--muted) !important;
            font-size: 13px !important;
            line-height: 1.5 !important;
        }
        .swal2-popup.swal2-dark-card .swal2-actions {
            gap: 10px !important;
            margin-top: 20px !important;
        }
        .swal2-popup.swal2-dark-card .swal2-styled {
            border-radius: 12px !important;
            padding: 10px 22px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>

<!-- LOADING OVERLAY -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- TOP HEADER -->
<header class="portal-header">
    <div class="logo"><i class="fas fa-gas-pump"></i> Caltex Rao Filling Station</div>
    <div class="header-actions">
        <div class="emp-info-wrap" id="empInfoWrap">
            <div class="emp-info" onclick="toggleProfileDropdown()" title="Account Menu">
                <div>
                    <div class="emp-name-sm">{{ $employee->full_name ?? $employee->first_name.' '.$employee->last_name }}</div>
                    <div class="emp-dept-sm">{{ $employee->designation->name ?? 'Employee' }}</div>
                </div>
                <div class="emp-avatar">
                    {{ strtoupper(substr($employee->first_name ?? 'E', 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}
                </div>
                <i class="fas fa-chevron-down emp-dropdown-icon"></i>
            </div>

            <!-- Profile Dropdown Menu -->
            <div class="profile-dropdown-menu" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <div class="profile-dropdown-avatar">
                        {{ strtoupper(substr($employee->first_name ?? 'E', 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <div class="profile-dropdown-name">{{ $employee->full_name ?? $employee->first_name.' '.$employee->last_name }}</div>
                        <div class="profile-dropdown-role">{{ $employee->designation->name ?? 'Employee' }}</div>
                    </div>
                </div>
                <div class="profile-dropdown-list">
                    <button type="button" class="profile-dropdown-item" onclick="switchTab('profile'); closeProfileDropdown();">
                        <i class="fas fa-user-circle" style="color:var(--purple-light);"></i>
                        <span>My Profile</span>
                    </button>
                    @if(auth()->check() && (auth()->user()->isOwner() || auth()->user()->isIncharge()))
                    <a href="{{ route('dashboard') }}" class="profile-dropdown-item">
                        <i class="fas fa-desktop" style="color:var(--blue);"></i>
                        <span>Main Dashboard</span>
                    </a>
                    @endif
                    <button type="button" class="profile-dropdown-item item-logout" onclick="closeProfileDropdown(); confirmLogout();">
                        <i class="fas fa-arrow-right-from-bracket" style="color:#ef4444;"></i>
                        <span>Log Out</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Header Logout Button -->
        <button type="button" class="btn-logout-header" onclick="confirmLogout()" title="Log Out">
            <i class="fas fa-arrow-right-from-bracket"></i>
            <span class="logout-text">Logout</span>
        </button>
    </div>
</header>
<div class="profile-dropdown-backdrop" id="dropdownBackdrop" onclick="closeProfileDropdown()"></div>

<!-- MAIN BODY -->
<div class="portal-body">

    <!-- ══════════════ SECTION: HOME (Attendance) ══════════════ -->
    <div class="portal-section active" id="section-home">

        <!-- Greeting -->
        <div class="greeting-strip">
            <div class="greeting">Hello, {{ $employee->first_name }} 👋</div>
            <div class="subline">{{ $employee->designation->name ?? '' }} · {{ $employee->department->name ?? '' }}</div>
            <div class="date-chip">
                <i class="fas fa-calendar-day"></i>
                <span id="currentDateLabel"></span>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="glass-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon icon-green"><i class="fas fa-fingerprint"></i></div>
                    <div>
                        <div>Today's Attendance</div>
                        @if($todayAttendance)
                            <div style="margin-top:4px;">
                                @if($todayAttendance->status === 'present')
                                    <span class="att-status-badge badge-present"><i class="fas fa-check-circle"></i> Present</span>
                                @elseif($todayAttendance->status === 'late')
                                    <span class="att-status-badge badge-late"><i class="fas fa-clock"></i> Late ({{ $todayAttendance->late_minutes }}m)</span>
                                @elseif($todayAttendance->status === 'absent')
                                    <span class="att-status-badge badge-absent"><i class="fas fa-times-circle"></i> Absent</span>
                                @endif
                            </div>
                        @else
                            <span style="font-size:12px;color:var(--muted);">Not marked yet</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Time Display -->
            <div class="att-time-row">
                <div class="att-time-box {{ $todayAttendance && $todayAttendance->check_in_time ? 'done-in' : '' }}">
                    <div class="label"><i class="fas fa-sign-in-alt"></i> Check In</div>
                    @if($todayAttendance && $todayAttendance->check_in_time)
                        <div class="time">{{ \Carbon\Carbon::parse($todayAttendance->check_in_time)->format('h:i A') }}</div>
                        @if($todayAttendance->check_in_location)
                            <div class="loc"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($todayAttendance->check_in_location, 20) }}</div>
                        @endif
                    @else
                        <div class="empty"><i class="fas fa-minus"></i></div>
                        <div class="loc">-- : --</div>
                    @endif
                </div>
                <div class="att-time-box {{ $todayAttendance && $todayAttendance->check_out_time ? 'done-out' : '' }}">
                    <div class="label"><i class="fas fa-sign-out-alt"></i> Check Out</div>
                    @if($todayAttendance && $todayAttendance->check_out_time)
                        <div class="time">{{ \Carbon\Carbon::parse($todayAttendance->check_out_time)->format('h:i A') }}</div>
                        @if($todayAttendance->check_out_location)
                            <div class="loc"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($todayAttendance->check_out_location, 20) }}</div>
                        @endif
                        <div style="margin-top:4px;font-size:11px;color:var(--muted);">{{ $todayAttendance->total_hours }}h total</div>
                    @else
                        <div class="empty"><i class="fas fa-minus"></i></div>
                        <div class="loc">-- : --</div>
                    @endif
                </div>
            </div>

            <!-- Location Status -->
            <div class="loc-loader" id="locLoader">
                <i class="fas fa-circle-notch spin"></i>
                <span id="locLoaderText">Getting your location...</span>
            </div>

            <!-- Buttons -->
            <div class="att-btn-row">
                <button class="att-btn btn-checkin {{ $todayAttendance && $todayAttendance->check_in_time ? '' : ($todayAttendance && $todayAttendance->check_out_time ? '' : 'pulse-ring') }}"
                    id="btnCheckIn"
                    {{ ($todayAttendance && $todayAttendance->check_in_time) ? 'disabled' : '' }}>
                    <i class="fas fa-sign-in-alt"></i>
                    {{ ($todayAttendance && $todayAttendance->check_in_time) ? 'Checked In ✓' : 'Check In' }}
                </button>
                <button class="att-btn btn-checkout"
                    id="btnCheckOut"
                    {{ (!$todayAttendance || !$todayAttendance->check_in_time || $todayAttendance->check_out_time) ? 'disabled' : '' }}>
                    <i class="fas fa-sign-out-alt"></i>
                    {{ ($todayAttendance && $todayAttendance->check_out_time) ? 'Checked Out ✓' : 'Check Out' }}
                </button>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div class="glass-card" style="margin-bottom:0;cursor:pointer;" onclick="switchTab('salary')">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="card-icon icon-purple"><i class="fas fa-money-check-alt"></i></div>
                    <div>
                        <div style="font-size:11px;color:var(--muted);">This Month</div>
                        <div style="font-size:14px;font-weight:700;margin-top:2px;">
                            @if($currentPayroll)
                                Rs. {{ number_format($currentPayroll->net_salary, 0) }}
                            @else
                                <span style="color:var(--muted);font-size:12px;">Not Generated</span>
                            @endif
                        </div>
                        <div style="font-size:11px;color:var(--muted);margin-top:2px;">Net Salary</div>
                    </div>
                </div>
            </div>
            <div class="glass-card" style="margin-bottom:0;cursor:pointer;" onclick="switchTab('loans')">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="card-icon icon-amber"><i class="fas fa-hand-holding-usd"></i></div>
                    <div>
                        <div style="font-size:11px;color:var(--muted);">Active Loans</div>
                        <div style="font-size:14px;font-weight:700;margin-top:2px;color:var(--amber);">
                            {{ $activeLoans->count() }}
                        </div>
                        <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                            @if($activeLoans->count() > 0)
                                Rs. {{ number_format($activeLoans->sum(fn($l) => $l->amount - $l->paid_amount), 0) }} left
                            @else
                                No Active Loans
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="glass-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
                    Last 7 Days
                </div>
            </div>
            <div id="attHistList">
                @forelse($attendanceHistory->take(7) as $att)
                    <div class="att-hist-item">
                        <div class="att-day-dot
                            @if($att->status === 'present') dot-present
                            @elseif($att->status === 'late') dot-late
                            @elseif($att->status === 'absent') dot-absent
                            @else dot-off @endif">
                        </div>
                        <div class="att-day-info">
                            <div class="att-day-name">{{ \Carbon\Carbon::parse($att->date)->format('D, d M') }}</div>
                            <div class="att-day-times">
                                @if($att->check_in_time)
                                    In: {{ \Carbon\Carbon::parse($att->check_in_time)->format('h:i A') }}
                                    @if($att->check_out_time)
                                        &nbsp;·&nbsp;Out: {{ \Carbon\Carbon::parse($att->check_out_time)->format('h:i A') }}
                                    @endif
                                @else
                                    {{ ucfirst($att->status ?? 'N/A') }}
                                @endif
                                @if($att->is_late && $att->late_minutes)
                                    &nbsp;· <span style="color:var(--amber);">Late {{ $att->late_minutes }}m</span>
                                @endif
                            </div>
                        </div>
                        <div class="att-day-hours">{{ $att->total_hours ? $att->total_hours.'h' : '' }}</div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No attendance records this month.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ══════════════ SECTION: SALARY ══════════════ -->
    <div class="portal-section" id="section-salary">
        <div class="greeting-strip">
            <div class="greeting">My Salary</div>
            <div class="subline">Monthly payroll details</div>
        </div>

        <!-- Month Navigator -->
        <div class="glass-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="card-title">
                    <div class="card-icon icon-purple"><i class="fas fa-money-check-alt"></i></div>
                    Payroll Details
                </div>
                <div class="month-nav">
                    <button onclick="changeSalaryMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <span id="salMonthLabel">--</span>
                    <button onclick="changeSalaryMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            <div id="salaryContent">
                <div class="salary-big">
                    <div class="skeleton" style="height:40px;width:60%;margin:0 auto 10px;"></div>
                    <div class="skeleton" style="height:14px;width:40%;margin:0 auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════ SECTION: COMMISSION ══════════════ -->
    <div class="portal-section" id="section-commission">
        <div class="greeting-strip">
            <div class="greeting">My Commission</div>
            <div class="subline">Sales commission breakdown</div>
        </div>

        <div class="glass-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon icon-amber"><i class="fas fa-chart-line"></i></div>
                    Commission Summary
                </div>
            </div>
            <div id="commissionContent">
                <div class="comm-summary">
                    <div class="comm-kpi earned">
                        <div class="kpi-label">Earned</div>
                        <div class="kpi-val skeleton" style="height:20px;"></div>
                    </div>
                    <div class="comm-kpi paid">
                        <div class="kpi-label">Paid</div>
                        <div class="kpi-val skeleton" style="height:20px;"></div>
                    </div>
                    <div class="comm-kpi pending">
                        <div class="kpi-label">Pending</div>
                        <div class="kpi-val skeleton" style="height:20px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════ SECTION: LOANS ══════════════ -->
    <div class="portal-section" id="section-loans">
        <div class="greeting-strip">
            <div class="greeting">My Loans</div>
            <div class="subline">Loan & advance details</div>
        </div>
        <div id="loansContent">
            <div class="glass-card">
                <div class="skeleton" style="height:20px;margin-bottom:8px;"></div>
                <div class="skeleton" style="height:14px;margin-bottom:8px;"></div>
                <div class="skeleton" style="height:14px;width:70%;"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════ SECTION: PROFILE ══════════════ -->
    <div class="portal-section" id="section-profile">
        <div class="greeting-strip">
            <div class="greeting">My Profile</div>
            <div class="subline">Account & employment details</div>
        </div>

        <!-- Profile Hero Card -->
        <div class="glass-card profile-hero-card">
            <div class="profile-hero-avatar">
                {{ strtoupper(substr($employee->first_name ?? 'E', 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}
            </div>
            <div class="profile-hero-name">{{ $employee->full_name ?? $employee->first_name.' '.$employee->last_name }}</div>
            <div class="profile-hero-badge">
                <i class="fas fa-shield-alt"></i> {{ $employee->designation->name ?? 'Employee' }}
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                {{ $employee->department->name ?? 'General Department' }}
            </div>
        </div>

        <!-- Employee Info Card -->
        <div class="glass-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon icon-purple"><i class="fas fa-id-card"></i></div>
                    Employment Info
                </div>
            </div>
            <div class="profile-info-list">
                @if($employee->id)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-hashtag"></i> Employee ID</span>
                    <span class="profile-info-val">#EMP-{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                @endif
                @if($employee->phone)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-phone-alt"></i> Phone</span>
                    <span class="profile-info-val">{{ $employee->phone }}</span>
                </div>
                @endif
                @if($employee->email)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="profile-info-val">{{ $employee->email }}</span>
                </div>
                @endif
                @if($employee->joining_date)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-calendar-check"></i> Joining Date</span>
                    <span class="profile-info-val">{{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</span>
                </div>
                @endif
                @if($employee->custom_start_time && $employee->custom_end_time)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-clock"></i> Shift Timings</span>
                    <span class="profile-info-val">{{ \Carbon\Carbon::parse($employee->custom_start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($employee->custom_end_time)->format('h:i A') }}</span>
                </div>
                @endif
                @if($employee->status)
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-user-check"></i> Status</span>
                    <span class="profile-info-val" style="color:var(--green-light);text-transform:capitalize;">
                        <i class="fas fa-circle" style="font-size:8px;vertical-align:middle;margin-right:4px;"></i>{{ $employee->status }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Access & Logout Card -->
        <div class="glass-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon icon-red"><i class="fas fa-cog"></i></div>
                    Account Actions
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
                @if(auth()->check() && (auth()->user()->isOwner() || auth()->user()->isIncharge()))
                <a href="{{ route('dashboard') }}" class="btn-erp-switch">
                    <i class="fas fa-desktop"></i>
                    <span>Switch to Main Dashboard</span>
                </a>
                @endif

                <button type="button" class="btn-logout-full" onclick="confirmLogout()">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Log Out from Account</span>
                </button>
            </div>
        </div>
    </div>

</div><!-- end portal-body -->

<!-- HIDDEN LOGOUT FORM -->
<form id="employeeLogoutForm" method="POST" action="{{ route('employee.logout') }}" style="display:none;">
    @csrf
</form>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <button class="nav-btn active" id="nav-home" onclick="switchTab('home')">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </button>
    <button class="nav-btn" id="nav-salary" onclick="switchTab('salary')">
        <i class="fas fa-wallet"></i>
        <span>Salary</span>
    </button>
    <button class="nav-btn" id="nav-commission" onclick="switchTab('commission')">
        <i class="fas fa-chart-bar"></i>
        <span>Commission</span>
    </button>
    <button class="nav-btn" id="nav-loans" onclick="switchTab('loans')">
        <i class="fas fa-hand-holding-usd"></i>
        <span>Loans</span>
    </button>
    <button class="nav-btn" id="nav-profile" onclick="switchTab('profile')">
        <i class="fas fa-user-circle"></i>
        <span>Profile</span>
    </button>
</nav>

<!-- JS SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
// ═══════════════════════════════════════════
//   GLOBALS
// ═══════════════════════════════════════════
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const requiresLocation = {{ $requiresLocation ? 'true' : 'false' }};
const markUrl = "{{ route('my-attendance.mark') }}";
const portalBase = "{{ url('employee/portal') }}";

let salaryMonth = "{{ $currentMonth }}";
let commissionLoaded = false;
let loansLoaded = false;
let salaryLoaded = false;

// ═══════════════════════════════════════════
//   DATE DISPLAY
// ═══════════════════════════════════════════
function updateDateLabel() {
    const now = new Date();
    const days  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('currentDateLabel').textContent =
        `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
updateDateLabel();

// ═══════════════════════════════════════════
//   TAB SWITCHING
// ═══════════════════════════════════════════
function switchTab(tab) {
    document.querySelectorAll('.portal-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('section-' + tab).classList.add('active');
    document.getElementById('nav-' + tab).classList.add('active');

    // Lazy load data
    if (tab === 'salary' && !salaryLoaded) { loadSalary(); salaryLoaded = true; }
    if (tab === 'commission' && !commissionLoaded) { loadCommission(); commissionLoaded = true; }
    if (tab === 'loans' && !loansLoaded) { loadLoans(); loansLoaded = true; }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ═══════════════════════════════════════════
//   ATTENDANCE — GPS + MARK
// ═══════════════════════════════════════════
function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            resolve({ lat: null, lng: null });
            return;
        }
        const loader = document.getElementById('locLoader');
        const loaderText = document.getElementById('locLoaderText');
        loader.classList.add('active');
        loaderText.textContent = 'Getting your GPS location...';

        navigator.geolocation.getCurrentPosition(
            pos => {
                loader.classList.remove('active');
                resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
            },
            err => {
                loader.classList.remove('active');
                if (requiresLocation) {
                    reject('Location permission denied. Please enable GPS to mark attendance.');
                } else {
                    resolve({ lat: null, lng: null });
                }
            },
            { timeout: 12000, enableHighAccuracy: true, maximumAge: 0 }
        );
    });
}

async function markAttendance(type) {
    const btn = document.getElementById(type === 'check_in' ? 'btnCheckIn' : 'btnCheckOut');
    const originalHtml = btn.innerHTML;

    // Confirmation
    const label = type === 'check_in' ? 'Check In' : 'Check Out';
    const result = await Swal.fire({
        title: `Confirm ${label}`,
        text: `Are you sure you want to mark ${label} now?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: type === 'check_in' ? '#10b981' : '#ef4444',
        cancelButtonColor: 'rgba(255,255,255,0.1)',
        confirmButtonText: `Yes, ${label}`,
        background: '#1a1a2e',
        color: '#f1f5f9',
    });

    if (!result.isConfirmed) return;

    btn.disabled = true;
    btn.classList.add('loading');
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';

    try {
        // Get location
        let lat = null, lng = null;
        try {
            const loc = await getLocation();
            lat = loc.lat;
            lng = loc.lng;
        } catch (locErr) {
            Swal.fire({ title: 'Location Required', text: locErr, icon: 'error', background: '#1a1a2e', color: '#f1f5f9' });
            btn.disabled = false;
            btn.classList.remove('loading');
            btn.innerHTML = originalHtml;
            return;
        }

        // POST to mark attendance
        const body = new FormData();
        body.append('_token', CSRF);
        body.append('type', type);
        if (lat) body.append('latitude', lat);
        if (lng) body.append('longitude', lng);

        const resp = await fetch(markUrl, { method: 'POST', body });
        const data = await resp.json();

        if (data.success || data.message) {
            await Swal.fire({
                title: 'Marked!',
                text: data.message || `${label} recorded successfully.`,
                icon: 'success',
                confirmButtonColor: '#6c63ff',
                background: '#1a1a2e', color: '#f1f5f9',
                timer: 2000, timerProgressBar: true,
            });
            location.reload();
        } else {
            Swal.fire({ title: 'Error', text: data.error || 'Could not mark attendance.', icon: 'error', background: '#1a1a2e', color: '#f1f5f9' });
            btn.disabled = false;
            btn.classList.remove('loading');
            btn.innerHTML = originalHtml;
        }
    } catch (e) {
        Swal.fire({ title: 'Error', text: 'Server error. Please try again.', icon: 'error', background: '#1a1a2e', color: '#f1f5f9' });
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.innerHTML = originalHtml;
    }
}

document.getElementById('btnCheckIn').addEventListener('click', () => markAttendance('check_in'));
document.getElementById('btnCheckOut').addEventListener('click', () => markAttendance('check_out'));

// ═══════════════════════════════════════════
//   SALARY
// ═══════════════════════════════════════════
function formatMonth(ym) {
    const [y, m] = ym.split('-');
    const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${names[parseInt(m)-1]} ${y}`;
}

function changeSalaryMonth(dir) {
    const [y, m] = salaryMonth.split('-').map(Number);
    const d = new Date(y, m - 1 + dir);
    salaryMonth = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
    salaryLoaded = false;
    loadSalary();
}

async function loadSalary() {
    document.getElementById('salMonthLabel').textContent = formatMonth(salaryMonth);
    document.getElementById('salaryContent').innerHTML = `
        <div class="salary-big">
            <div class="skeleton" style="height:40px;width:60%;margin:0 auto 10px;"></div>
            <div class="skeleton" style="height:14px;width:40%;margin:0 auto;"></div>
        </div>`;

    try {
        const resp = await fetch(`${portalBase}/salary?month=${salaryMonth}`);
        const data = await resp.json();

        if (!data.found) {
            document.getElementById('salaryContent').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <p>No payroll generated for <strong>${formatMonth(salaryMonth)}</strong> yet.</p>
                </div>`;
            return;
        }

        const p = data.payroll;
        const statusColors = { generated: 'var(--amber)', reviewed: 'var(--blue)', paid: 'var(--green-light)' };
        const statusColor = statusColors[p.status] || 'var(--muted)';

        let html = `
            <div class="salary-big">
                <div class="label">Net Payable Amount</div>
                <div class="amount">Rs. ${p.net_salary}</div>
                <div class="status-row">
                    <span style="font-size:12px;padding:3px 12px;border-radius:12px;background:rgba(255,255,255,0.07);color:${statusColor};border:1px solid ${statusColor}30;">
                        ${p.status.toUpperCase()}
                    </span>
                </div>
            </div>
            <div class="salary-breakdown">
                <div class="sal-row">
                    <span class="sal-label"><i class="fas fa-money-bill-wave" style="color:var(--blue)"></i> Basic Salary</span>
                    <span class="sal-val earning-val">Rs. ${p.basic_salary}</span>
                </div>`;

        if (data.allowances && data.allowances.length) {
            data.allowances.forEach(a => {
                html += `<div class="sal-row"><span class="sal-label"><i class="fas fa-plus-circle" style="color:var(--green)"></i> ${a.name}</span><span class="sal-val earning-val">Rs. ${parseFloat(a.amount).toLocaleString('en-PK', {minimumFractionDigits:2})}</span></div>`;
            });
        }

        if (data.commissions && data.commissions.length) {
            data.commissions.forEach(c => {
                html += `<div class="sal-row"><span class="sal-label"><i class="fas fa-chart-line" style="color:var(--amber)"></i> ${c.name}</span><span class="sal-val" style="color:var(--amber)">Rs. ${parseFloat(c.amount).toLocaleString('en-PK', {minimumFractionDigits:2})}</span></div>`;
            });
        }

        html += `<div class="divider"></div>`;
        html += `<div class="sal-row"><span class="sal-label"><i class="fas fa-coins" style="color:var(--green)"></i> Gross Salary</span><span class="sal-val earning-val">Rs. ${p.gross_salary}</span></div>`;

        if (data.deductions && data.deductions.length) {
            data.deductions.forEach(d => {
                html += `<div class="sal-row"><span class="sal-label"><i class="fas fa-minus-circle" style="color:var(--red)"></i> ${d.name}</span><span class="sal-val deduction-val">- Rs. ${parseFloat(d.amount).toLocaleString('en-PK', {minimumFractionDigits:2})}</span></div>`;
            });
        }

        if (parseFloat(p.attendance_deductions.replace(/,/g,'')) > 0) {
            html += `<div class="sal-row"><span class="sal-label"><i class="fas fa-user-clock" style="color:var(--red)"></i> Attendance Deductions</span><span class="sal-val deduction-val">- Rs. ${p.attendance_deductions}</span></div>`;
        }

        html += `<div class="divider"></div>
            <div class="sal-row total-row">
                <span class="sal-label"><i class="fas fa-hand-holding-usd" style="color:var(--green)"></i> Net Payable</span>
                <span class="sal-val net-val">Rs. ${p.net_salary}</span>
            </div>
        </div>`;

        document.getElementById('salaryContent').innerHTML = html;
        salaryLoaded = true;
    } catch (e) {
        document.getElementById('salaryContent').innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Could not load salary data.</p></div>`;
    }
}

// ═══════════════════════════════════════════
//   COMMISSION
// ═══════════════════════════════════════════
async function loadCommission() {
    document.getElementById('commissionContent').innerHTML = `
        <div class="comm-summary">
            <div class="comm-kpi earned"><div class="kpi-label">Earned</div><div class="skeleton" style="height:22px;margin-top:6px;"></div></div>
            <div class="comm-kpi paid"><div class="kpi-label">Paid</div><div class="skeleton" style="height:22px;margin-top:6px;"></div></div>
            <div class="comm-kpi pending"><div class="kpi-label">Pending</div><div class="skeleton" style="height:22px;margin-top:6px;"></div></div>
        </div>`;

    try {
        const resp = await fetch(`${portalBase}/commission`);
        const data = await resp.json();

        let html = `
            <div class="comm-summary">
                <div class="comm-kpi earned">
                    <div class="kpi-label">Total Earned</div>
                    <div class="kpi-val">Rs. ${data.total_earned}</div>
                </div>
                <div class="comm-kpi paid">
                    <div class="kpi-label">Paid</div>
                    <div class="kpi-val">Rs. ${data.total_paid}</div>
                </div>
                <div class="comm-kpi pending">
                    <div class="kpi-label">Pending</div>
                    <div class="kpi-val">Rs. ${data.total_pending}</div>
                </div>
            </div>`;

        if (!data.sales || data.sales.length === 0) {
            html += `<div class="empty-state"><i class="fas fa-chart-bar"></i><p>No commission sales found.</p></div>`;
        } else {
            html += `<div style="font-size:11px;color:var(--muted);margin-bottom:10px;">${data.count} sale(s) with commission</div>`;
            html += `<div class="comm-list">`;
            data.sales.forEach(s => {
                const badgeClass = s.status === 'paid' ? 'badge-paid' : s.status === 'partial' ? 'badge-partial' : 'badge-pending';
                const badgeLabel = s.status === 'paid' ? '✓ Paid' : s.status === 'partial' ? '⏳ Partial' : '🔴 Pending';
                html += `
                    <div class="comm-item">
                        <div class="comm-item-top">
                            <div>
                                <div class="comm-inv">${s.invoice_no}</div>
                                <div class="comm-date">${s.sale_date}</div>
                            </div>
                            <span class="comm-badge ${badgeClass}">${badgeLabel}</span>
                        </div>
                        <div class="comm-amounts">
                            <span>Sale<span class="val">Rs. ${s.sale_amount}</span></span>
                            <span>Commission<span class="val" style="color:var(--amber)">Rs. ${s.commission}</span></span>
                            <span>Pending<span class="val" style="color:${s.status === 'paid' ? 'var(--green-light)' : 'var(--red)'}">Rs. ${s.pending}</span></span>
                        </div>
                    </div>`;
            });
            html += `</div>`;
        }

        document.getElementById('commissionContent').innerHTML = html;
        commissionLoaded = true;
    } catch (e) {
        document.getElementById('commissionContent').innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Could not load commission data.</p></div>`;
    }
}

// ═══════════════════════════════════════════
//   LOANS
// ═══════════════════════════════════════════
async function loadLoans() {
    document.getElementById('loansContent').innerHTML = `
        <div class="glass-card">
            <div class="skeleton" style="height:20px;margin-bottom:10px;"></div>
            <div class="skeleton" style="height:80px;margin-bottom:10px;"></div>
            <div class="skeleton" style="height:14px;width:70%;"></div>
        </div>`;

    try {
        const resp = await fetch(`${portalBase}/loans`);
        const data = await resp.json();

        if (data.count === 0) {
            document.getElementById('loansContent').innerHTML = `
                <div class="glass-card">
                    <div class="empty-state">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>No loans found on your account.</p>
                    </div>
                </div>`;
            return;
        }

        // Summary card
        let html = `
            <div class="glass-card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-icon icon-amber"><i class="fas fa-hand-holding-usd"></i></div>
                        Loan Summary
                    </div>
                </div>
                <div class="loan-amounts">
                    <div class="loan-kpi">
                        <div class="lk-label">Total Borrowed</div>
                        <div class="lk-val lk-borrowed">Rs. ${data.total_borrowed}</div>
                    </div>
                    <div class="loan-kpi">
                        <div class="lk-label">Total Paid</div>
                        <div class="lk-val lk-paid">Rs. ${data.total_paid}</div>
                    </div>
                    <div class="loan-kpi">
                        <div class="lk-label">Remaining</div>
                        <div class="lk-val lk-remaining">Rs. ${data.total_remaining}</div>
                    </div>
                </div>
            </div>`;

        // Individual loans
        data.loans.forEach(loan => {
            const statusColor = loan.status === 'paid' ? 'var(--green-light)' : loan.status === 'approved' || loan.status === 'active' ? 'var(--blue)' : loan.status === 'pending' ? 'var(--amber)' : 'var(--muted)';
            html += `
                <div class="glass-card">
                    <div class="loan-item" style="margin:0;border:none;padding:0;">
                        <div class="loan-top">
                            <div>
                                <div class="loan-title">Loan #${loan.id} ${loan.reason ? '· '+loan.reason : ''}</div>
                                <div style="font-size:11px;color:${statusColor};margin-top:3px;">${loan.status_label}</div>
                            </div>
                            <span class="loan-type-chip">${loan.loan_type_label}</span>
                        </div>

                        <div class="loan-amounts">
                            <div class="loan-kpi">
                                <div class="lk-label">Borrowed</div>
                                <div class="lk-val lk-borrowed">Rs. ${loan.amount}</div>
                            </div>
                            <div class="loan-kpi">
                                <div class="lk-label">Paid</div>
                                <div class="lk-val lk-paid">Rs. ${loan.paid}</div>
                            </div>
                            <div class="loan-kpi">
                                <div class="lk-label">Remaining</div>
                                <div class="lk-val lk-remaining">Rs. ${loan.remaining}</div>
                            </div>
                        </div>

                        <div class="loan-progress-wrap">
                            <div class="loan-progress-label">
                                <span>Repayment Progress</span>
                                <span>${loan.progress_pct}%</span>
                            </div>
                            <div class="loan-progress-bar">
                                <div class="loan-progress-fill" style="width:${loan.progress_pct}%"></div>
                            </div>
                        </div>

                        ${loan.loan_type === 'salary_deduction' ? `
                        <div class="loan-installments">
                            <div class="inst-item">
                                <div class="il">Monthly</div>
                                <div class="iv">Rs. ${loan.installment_amount}</div>
                            </div>
                            <div class="inst-item">
                                <div class="il">Total</div>
                                <div class="iv">${loan.total_installments}</div>
                            </div>
                            <div class="inst-item">
                                <div class="il">Paid</div>
                                <div class="iv" style="color:var(--green-light)">${loan.installments_paid}</div>
                            </div>
                            <div class="inst-item">
                                <div class="il">Left</div>
                                <div class="iv" style="color:var(--amber)">${loan.installments_left}</div>
                            </div>
                        </div>` : ''}

                        <div class="loan-meta" style="margin-top:12px;">
                            ${loan.start_month ? `<span class="loan-meta-item"><i class="fas fa-calendar-day"></i> Start: ${loan.start_month}</span>` : ''}
                            ${loan.expected_end_month ? `<span class="loan-meta-item"><i class="fas fa-calendar-check"></i> End: ${loan.expected_end_month}</span>` : ''}
                            ${loan.approved_at ? `<span class="loan-meta-item"><i class="fas fa-check-circle"></i> Approved: ${loan.approved_at}</span>` : ''}
                        </div>
                    </div>
                </div>`;
        });

        document.getElementById('loansContent').innerHTML = html;
        loansLoaded = true;
    } catch (e) {
        document.getElementById('loansContent').innerHTML = `
            <div class="glass-card">
                <div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Could not load loan data.</p></div>
            </div>`;
    }
}

// ═══════════════════════════════════════════
//   PROFILE DROPDOWN & LOGOUT
// ═══════════════════════════════════════════
function toggleProfileDropdown() {
    const wrap = document.getElementById('empInfoWrap');
    const dropdown = document.getElementById('profileDropdown');
    const backdrop = document.getElementById('dropdownBackdrop');
    const isOpen = dropdown.classList.contains('show');

    if (isOpen) {
        closeProfileDropdown();
    } else {
        dropdown.classList.add('show');
        backdrop.classList.add('show');
        wrap.classList.add('open');
    }
}

function closeProfileDropdown() {
    const wrap = document.getElementById('empInfoWrap');
    const dropdown = document.getElementById('profileDropdown');
    const backdrop = document.getElementById('dropdownBackdrop');
    if (dropdown) dropdown.classList.remove('show');
    if (backdrop) backdrop.classList.remove('show');
    if (wrap) wrap.classList.remove('open');
}

function confirmLogout() {
    Swal.fire({
        title: 'Log Out?',
        text: 'Are you sure you want to log out from your account?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#334155',
        confirmButtonText: '<i class="fas fa-arrow-right-from-bracket"></i> Yes, Log Out',
        cancelButtonText: 'Cancel',
        background: '#151528',
        color: '#f1f5f9',
        iconColor: '#ef4444',
        reverseButtons: true,
        customClass: {
            popup: 'swal2-dark-card',
            confirmButton: 'swal2-danger-btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('employeeLogoutForm').submit();
        }
    });
}

// ═══════════════════════════════════════════
//   INIT
// ═══════════════════════════════════════════
// Pre-load salary month label
document.getElementById('salMonthLabel').textContent = formatMonth(salaryMonth);
</script>
</body>
</html>
