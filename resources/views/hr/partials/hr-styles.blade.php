{{-- HR Module Modern ERP Styles - Aligned with Caltex Petrol UI --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    body {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
    }

    body, body *, input, select, textarea, button, .form-control, .form-select, .btn, .card, .alert, .badge, table, th, td {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }

    /* Preserve Icon Fonts */
    .fa, .fas, .fa-solid, .fa-regular, .fa-brands, [class^="fa-"], [class*=" fa-"] {
        font-family: "Font Awesome 6 Free", "FontAwesome" !important;
    }
    .fab, [class*="fa-brands"] {
        font-family: "Font Awesome 6 Brands", "FontAwesome" !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }

    :root {
        --hr-primary: #2563eb;
        --hr-primary-hover: #1d4ed8;
        --hr-success: #16a34a;
        --hr-warning: #d97706;
        --hr-danger: #dc2626;
        --hr-info: #0284c7;
        --hr-bg: #f8fafc;
        --hr-card: #ffffff;
        --hr-border: #e2e8f0;
        --hr-text: #0f172a;
        --hr-muted: #64748b;
    }

    /* Container resets - Eliminate artificial narrowing & horizontal scroll */
    .main-content {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .main-content-inner {
        width: 100%;
        max-width: 100%;
    }

    .main-content .container,
    .main-content-inner .container {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    /* Executive HR Sub-Header Strip (Distinct Visual Bar) */
    .sub-header {
        background: #e2e8f0 !important;
        background: linear-gradient(90deg, #dbe4ee 0%, #edf2f7 50%, #dbe4ee 100%) !important;
        border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
        border-bottom: 2px solid #cbd5e1 !important;
        padding: 0.55rem 0 !important;
        margin-bottom: 0.75rem !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05) !important;
    }
    .sub-header h5 {
        color: #0f172a !important;
        font-size: 1.05rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.2px;
        display: inline-flex !important;
        align-items: center !important;
    }
    .sub-header h5::before {
        content: "";
        display: inline-block;
        width: 4px;
        height: 1.15rem;
        background: #2563eb;
        border-radius: 2px;
        margin-right: 0.55rem;
        vertical-align: middle;
    }
    .sub-header .badge {
        background: #ffffff !important;
        color: #0f172a !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    }
    .sub-header .btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-dark) {
        background: #ffffff !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    }
    .sub-header .btn-primary {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25) !important;
    }
    .sub-header .btn-primary:hover {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        color: #ffffff !important;
    }
    .sub-header .btn:hover {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
    }

    /* Page Header inside content (when not in layout sub-header) */
    .page-header {
        margin-bottom: 0.85rem;
    }

    .page-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--hr-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .page-title i {
        color: var(--hr-primary);
    }

    .page-subtitle {
        color: var(--hr-muted);
        font-size: 0.82rem;
        margin-top: 2px;
        margin-bottom: 0;
    }

    /* Stats Cards - Sleek Compact ERP KPI row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        margin-bottom: 14px;
        width: 100%;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 10px 14px;
        border: 1px solid var(--hr-border);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        min-height: 58px;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .stat-card .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .stat-card.primary {
        border-left: 4px solid var(--hr-primary);
    }
    .stat-card.primary .stat-icon {
        background: #eff6ff;
        color: var(--hr-primary);
    }

    .stat-card.success {
        border-left: 4px solid var(--hr-success);
    }
    .stat-card.success .stat-icon {
        background: #f0fdf4;
        color: var(--hr-success);
    }

    .stat-card.warning {
        border-left: 4px solid var(--hr-warning);
    }
    .stat-card.warning .stat-icon {
        background: #fffbeb;
        color: var(--hr-warning);
    }

    .stat-card.info {
        border-left: 4px solid var(--hr-info);
    }
    .stat-card.info .stat-icon {
        background: #f0f9ff;
        color: var(--hr-info);
    }

    .stat-card.danger {
        border-left: 4px solid var(--hr-danger);
    }
    .stat-card.danger .stat-icon {
        background: #fef2f2;
        color: var(--hr-danger);
    }

    .stat-card.secondary {
        border-left: 4px solid #64748b;
    }
    .stat-card.secondary .stat-icon {
        background: #f8fafc;
        color: #64748b;
    }

    .stat-card-body {
        flex: 1;
        min-width: 0;
    }

    .stat-card .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--hr-text);
        line-height: 1.1;
    }

    .stat-card .stat-label {
        font-size: 0.7rem;
        color: var(--hr-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
        margin-top: 1px;
    }

    /* Main Card Container - Matching app .card-custom */
    .hr-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--hr-border);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 16px;
    }

    .hr-header {
        padding: 10px 14px;
        border-bottom: 1px solid var(--hr-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        background: #ffffff;
    }

    .hr-header > div {
        flex-wrap: wrap !important;
        max-width: 100%;
    }

    .search-box {
        position: relative;
        min-width: 180px;
        max-width: 280px;
        flex: 1;
    }

    .search-box input {
        width: 100%;
        height: 34px;
        padding: 6px 12px 6px 34px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.82rem;
        transition: all 0.2s;
        background: #f8fafc;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--hr-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        background: #ffffff;
    }

    .search-box i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.82rem;
    }

    /* Standard App Action Buttons */
    .btn-create, .btn-save {
        background: var(--hr-primary) !important;
        border: 1px solid var(--hr-primary) !important;
        color: #ffffff !important;
        padding: 6px 14px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.82rem !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.15s ease !important;
        text-decoration: none !important;
    }

    .btn-create:hover, .btn-save:hover {
        background: var(--hr-primary-hover) !important;
        border-color: var(--hr-primary-hover) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
        transform: translateY(-1px) !important;
    }

    .btn-cancel {
        background: #f1f5f9 !important;
        border: 1px solid #cbd5e1 !important;
        color: #334155 !important;
        padding: 6px 14px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.82rem !important;
        transition: all 0.15s ease !important;
    }

    .btn-cancel:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
    }

    /* Compact Cards Grid */
    .hr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
        padding: 14px;
    }

    @media (max-width: 575.98px) {
        .hr-grid {
            grid-template-columns: 1fr;
            padding: 10px;
            gap: 10px;
        }
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
    }

    .hr-item-card {
        background: #ffffff;
        border: 1px solid var(--hr-border);
        border-radius: 10px;
        padding: 12px 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .hr-item-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        transform: translateY(-2px);
    }

    .hr-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 8px;
    }

    .hr-avatar {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        flex-shrink: 0;
    }

    .hr-item-info {
        flex: 1;
        margin-left: 10px;
        min-width: 0;
    }

    .hr-item-name {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--hr-text);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hr-item-subtitle {
        font-size: 0.78rem;
        color: var(--hr-muted);
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hr-item-meta {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    .hr-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    .hr-actions .btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        font-size: 0.78rem;
        transition: all 0.15s ease;
    }

    .hr-actions .btn-view {
        background: #f0f9ff;
        color: var(--hr-info);
        border: 1px solid #bae6fd;
    }

    .hr-actions .btn-view:hover {
        background: var(--hr-info);
        color: #ffffff;
    }

    .hr-actions .btn-edit {
        background: #fffbeb;
        color: var(--hr-warning);
        border: 1px solid #fde68a;
    }

    .hr-actions .btn-edit:hover {
        background: var(--hr-warning);
        color: #ffffff;
    }

    .hr-actions .btn-delete {
        background: #fef2f2;
        color: var(--hr-danger);
        border: 1px solid #fecaca;
    }

    .hr-actions .btn-delete:hover {
        background: var(--hr-danger);
        color: #ffffff;
    }

    /* Tags - Matching Bootstrap 5 subtle colors */
    .hr-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 8px;
    }

    .hr-tag {
        font-size: 0.68rem;
        padding: 2px 7px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    .hr-tag.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .hr-tag.danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .hr-tag.warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .hr-tag.info {
        background: #e0f2fe;
        color: #075985;
        border: 1px solid #bae6fd;
    }

    .hr-tag.primary {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .hr-tag.default {
        background: #f1f5f9;
        color: var(--hr-text);
        border: 1px solid var(--hr-border);
    }

    /* Modals - Aligned with Executive Navy & Blue UI */
    .modal-content {
        border: 1px solid var(--hr-border);
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        max-width: 100%;
        overflow-x: hidden;
    }

    .modal-header.gradient,
    .modal-header {
        background: #090e17 !important;
        color: #ffffff !important;
        padding: 12px 18px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .modal-header.gradient .modal-title,
    .modal-header .modal-title {
        font-weight: 700 !important;
        font-size: 0.98rem !important;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-header.gradient .btn-close,
    .modal-header .btn-close {
        filter: brightness(0) invert(1) !important;
        opacity: 0.8 !important;
    }

    .modal-body {
        padding: 16px 18px !important;
        background: #ffffff !important;
        overflow-x: hidden;
    }

    .form-group-modern {
        margin-bottom: 14px;
    }

    .form-group-modern .form-label {
        font-weight: 600;
        color: var(--hr-text);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
    }

    .form-group-modern .form-label i {
        color: var(--hr-primary);
        font-size: 0.82rem;
    }

    .form-group-modern .form-control,
    .form-group-modern .form-select {
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 6px 10px !important;
        font-size: 0.85rem !important;
        transition: all 0.2s ease;
        background: #f8fafc !important;
        box-shadow: none !important;
        height: 36px !important;
    }

    .form-group-modern .form-control:focus,
    .form-group-modern .form-select:focus {
        border-color: var(--hr-primary) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        background: #ffffff !important;
    }

    .modal-footer-modern,
    .modal-footer {
        padding: 10px 18px !important;
        background: #f8fafc !important;
        border-top: 1px solid var(--hr-border) !important;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    /* Assign Table Styles */
    .assign-table-container {
        border-radius: 10px;
        border: 1px solid var(--hr-border);
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .assign-emp-table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .assign-emp-table thead th {
        background: #f8fafc !important;
        color: #475569;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 9px 12px;
        border-bottom: 1px solid var(--hr-border);
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .assign-emp-table tbody tr.employee-row {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border-bottom: 1px solid #f1f5f9;
    }

    .assign-emp-table tbody tr.employee-row:hover {
        background-color: #f8fafc !important;
    }

    .assign-emp-table tbody tr.employee-row.selected-row {
        background-color: #eff6ff !important;
        border-left: 3px solid var(--hr-primary) !important;
    }

    .assign-emp-table tbody td {
        padding: 9px 12px;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .custom-chk-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        user-select: none;
    }

    .custom-chk-input {
        width: 18px !important;
        height: 18px !important;
        cursor: pointer !important;
        margin: 0 !important;
        accent-color: var(--hr-primary);
        border-radius: 4px;
    }

    .emp-avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .badge-soft-info {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        font-weight: 600 !important;
        padding: 3px 8px !important;
        border-radius: 6px !important;
        font-size: 0.72rem !important;
    }

    .badge-soft-secondary {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600 !important;
        padding: 3px 8px !important;
        border-radius: 6px !important;
        font-size: 0.72rem !important;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 16px;
        color: var(--hr-muted);
    }

    .empty-state i {
        font-size: 2.2rem;
        margin-bottom: 10px;
        color: #cbd5e1;
    }

    /* Floating Save Bar */
    .save-bar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: #090e17;
        color: #ffffff;
        padding: 10px 22px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        z-index: 1050;
        display: none;
        border: 1px solid rgba(255, 255, 255, 0.15);
        align-items: center;
        gap: 14px;
    }

    .save-bar.visible {
        display: flex;
        animation: hrSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes hrSlideUp {
        from {
            transform: translate(-50%, 100%);
            opacity: 0;
        }
        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
</style>
