@extends('layouts.app')

@section('title', 'Commission Portal - Caltex Rao Filling Station')
@section('page_title', 'Commission Earnings & Settlements')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-percent me-1 text-primary"></i> Sales Commission
    </span>
@endsection

@section('page_actions')
    @if($employees->count() > 0)
    <form method="GET" action="{{ route('hr.payroll.my-commission') }}" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 fw-bold small text-nowrap">Employee:</label>
        <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 220px;">
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ optional($employee)->id == $emp->id ? 'selected' : '' }}>
                    {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code ?? 'EMP-'.$emp->id }})
                </option>
            @endforeach
        </select>
    </form>
    @endif
@endsection

@section('content')
<style>
    .comm-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease-in-out;
    }
    .comm-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.08);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .badge-status {
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 20px;
    }
</style>

<div class="container-fluid px-0 py-1">

    @if($employee)
    <!-- Employee Info Bar -->
    <div class="alert alert-light border d-flex align-items-center justify-content-between mb-4 shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon bg-primary text-white">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ $employee->first_name }} {{ $employee->last_name }}</h6>
                <small class="text-muted">
                    {{ $employee->designation->name ?? 'Sales Officer' }} | 
                    Department: {{ $employee->department->name ?? 'Sales' }}
                </small>
            </div>
        </div>
        <div>
            <span class="badge bg-soft-info text-info border border-info px-3 py-2">
                <i class="bi bi-info-circle me-1"></i>
                Commission calculated after deducting 18% GST & 5% Sale Tax
            </span>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="comm-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Total Sales Net</small>
                        <h4 class="fw-bold mb-0 text-dark">Rs. {{ number_format($summary['total_sales_net'], 2) }}</h4>
                        <small class="text-muted">{{ $summary['total_sales_count'] }} Sale Invoice(s)</small>
                    </div>
                    <div class="stat-icon bg-light text-primary border">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="comm-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Total Commission Earned</small>
                        <h4 class="fw-bold mb-0 text-primary">Rs. {{ number_format($summary['total_commission_earned'], 2) }}</h4>
                        <small class="text-muted">Tax-Exclusive Base</small>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary border border-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="comm-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Salary Mein Add Ho Gaya</small>
                        <h4 class="fw-bold mb-0 text-success">Rs. {{ number_format($summary['total_commission_paid'], 2) }}</h4>
                        <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Paid in Payroll</small>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success border border-success">
                        <i class="bi bi-check-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="comm-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Baki (Pending) Commission</small>
                        <h4 class="fw-bold mb-0 text-warning">Rs. {{ number_format($summary['total_commission_pending'], 2) }}</h4>
                        <small class="text-muted">Awaiting Customer Payment</small>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning border border-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-list-stars me-2 text-primary"></i>Sales Commission Breakdown
            </h6>
            <span class="badge bg-light text-dark border">
                Showing {{ $sales->count() }} sales
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center" style="font-size: 13px;">
                    <thead class="table-light text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                        <tr>
                            <th># Invoice</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total Net (Rs.)</th>
                            <th>Tax Base (Rs.)</th>
                            <th>Comm %</th>
                            <th>Commission (Rs.)</th>
                            <th>Cust. Payment</th>
                            <th>Salary Paid</th>
                            <th>Pending (Rs.)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $item)
                        <tr>
                            <td class="fw-bold text-primary">
                                <a href="{{ route('sales.invoice', $item['sale']->id) }}" target="_blank" class="text-decoration-none">
                                    {{ $item['invoice_no'] }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $item['date'] }}</td>
                            <td class="fw-semibold text-start ps-3">{{ $item['customer'] }}</td>
                            <td class="fw-bold">Rs. {{ number_format($item['total_net'], 2) }}</td>
                            <td class="text-dark fw-semibold">
                                Rs. {{ number_format($item['tax_base'], 2) }}
                                <div class="text-muted" style="font-size: 9px;">(-18% GST, -5% Tax)</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $item['comm_pct'] }}%
                                </span>
                            </td>
                            <td class="fw-bold text-primary">Rs. {{ number_format($item['max_comm'], 2) }}</td>
                            <td>
                                @if($item['is_fully_paid'])
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <i class="bi bi-check-all me-1"></i>Full Paid
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">
                                        Rs. {{ number_format($item['received'], 2) }} / {{ number_format($item['total_net'], 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="fw-bold text-success">
                                Rs. {{ number_format($item['comm_paid'], 2) }}
                            </td>
                            <td class="fw-bold text-warning">
                                Rs. {{ number_format($item['comm_pending'], 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $item['status_badge'] }} badge-status">
                                    {{ $item['status_label'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Koi sales commission record nahi mila.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning border text-center py-5">
        <i class="bi bi-exclamation-triangle fs-1 text-warning d-block mb-2"></i>
        <h5>Aapka Employee Profile Link Nahi Hai</h5>
        <p class="text-muted mb-0">Apne HR Manager se rabta karein taake aapke User account ko Employee profile se link kiya jaye.</p>
    </div>
    @endif
</div>
@endsection
