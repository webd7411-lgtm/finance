<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Caltex Rao Filling Station</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #090e17 0%, #0f172a 50%, #1e293b 100%); display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; margin: 0; padding: 20px; box-sizing: border-box; }
        .card { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.12); border-radius: 20px; padding: 40px; text-align: center; color: #fff; max-width: 440px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); backdrop-filter: blur(10px); }
        .icon { font-size: 56px; margin-bottom: 20px; color: #60a5fa; }
        h2 { margin-bottom: 10px; font-weight: 700; font-size: 1.5rem; }
        p { opacity: 0.75; font-size: 0.95rem; line-height: 1.5; color: #94a3b8; }
        .actions { display: flex; gap: 12px; justify-content: center; margin-top: 28px; flex-wrap: wrap; }
        .btn-dash { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: #2563eb; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-dash:hover { background: #1d4ed8; }
        .btn-logout { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #ef4444; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-logout:hover { background: rgba(239,68,68,0.25); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fa fa-user-clock"></i></div>
        <h2>No Employee Profile Linked</h2>
        <p>Logged in as <strong>{{ auth()->user()->name }}</strong>. Your user account is not currently linked to an active employee record. Please contact HR or link your profile in HR Employees.</p>
        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn-dash">
                <i class="fa fa-home"></i> Back to Dashboard
            </a>
            @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
            <a href="{{ route('hr.employees.index') }}" class="btn-dash" style="background:#059669;">
                <i class="fa fa-users"></i> Employees Directory
            </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="fa fa-sign-out-alt"></i> Sign Out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
