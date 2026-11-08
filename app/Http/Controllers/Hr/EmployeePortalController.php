<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Loan;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayrollDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePortalController extends Controller
{
    /**
     * Get authenticated employee or abort
     */
    private function getEmployee()
    {
        $user = auth()->user();
        return Employee::where('user_id', $user->id)
            ->with(['designation', 'department', 'activeSalaryStructure'])
            ->first();
    }

    /**
     * Main employee portal dashboard
     */
    public function index()
    {
        $employee = $this->getEmployee();

        if (! $employee) {
            return view('hr.employee-portal.no-employee');
        }

        $requiresLocation = $employee->designation && $employee->designation->requires_location;

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $currentMonth = Carbon::now()->format('Y-m');

        // Latest payroll for current month
        $currentPayroll = Payroll::where('employee_id', $employee->id)
            ->where('month', $currentMonth)
            ->where('payroll_type', 'monthly')
            ->orderByDesc('id')
            ->first();

        // Active loans summary
        $activeLoans = Loan::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'active'])
            ->whereRaw('paid_amount < amount')
            ->get();

        // Commission summary for current month
        $commissionTotal = 0;
        $commissionPaid  = 0;
        if ($currentPayroll) {
            $commissionTotal = PayrollDetail::where('payroll_id', $currentPayroll->id)
                ->where('type', 'commission')
                ->sum('amount');
            $commissionPaid = $commissionTotal; // All in payroll are considered paid
        }

        // Sales commission data (from sales table linked to employee)
        $salesWithCommission = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('sales') && $employee->user_id) {
            $salesWithCommission = DB::table('sales')
                ->where('employee_id', $employee->id)
                ->whereNotNull('total_commission')
                ->where('total_commission', '>', 0)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        // Attendance history (last 30 days)
        $attendanceHistory = Attendance::where('employee_id', $employee->id)
            ->where('date', '>=', Carbon::now()->subDays(30)->toDateString())
            ->orderByDesc('date')
            ->get();

        return view('hr.employee-portal.index', compact(
            'employee',
            'requiresLocation',
            'todayAttendance',
            'currentPayroll',
            'activeLoans',
            'commissionTotal',
            'commissionPaid',
            'salesWithCommission',
            'attendanceHistory',
            'currentMonth'
        ));
    }

    /**
     * AJAX: Get detailed salary data for a specific month
     */
    public function getSalaryData(Request $request)
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $month = $request->input('month', Carbon::now()->format('Y-m'));

        $payroll = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('payroll_type', 'monthly')
            ->orderByDesc('id')
            ->first();

        if (! $payroll) {
            return response()->json([
                'found'  => false,
                'month'  => $month,
                'message' => 'No payroll generated for this month yet.',
            ]);
        }

        $details = PayrollDetail::where('payroll_id', $payroll->id)->get();

        $allowances  = $details->where('type', 'allowance')->values();
        $deductions  = $details->where('type', 'deduction')->values();
        $commissions = $details->where('type', 'commission')->values();

        return response()->json([
            'found'   => true,
            'month'   => $month,
            'payroll' => [
                'id'                   => $payroll->id,
                'month'                => $payroll->month,
                'basic_salary'         => number_format($payroll->basic_salary, 2),
                'allowances'           => number_format($payroll->allowances, 2),
                'commission'           => number_format($payroll->commission, 2),
                'manual_allowances'    => number_format($payroll->manual_allowances, 2),
                'deductions'           => number_format($payroll->deductions, 2),
                'attendance_deductions'=> number_format($payroll->attendance_deductions, 2),
                'manual_deductions'    => number_format($payroll->manual_deductions, 2),
                'gross_salary'         => number_format($payroll->gross_salary, 2),
                'net_salary'           => number_format($payroll->net_salary, 2),
                'status'               => $payroll->status,
                'payroll_type'         => $payroll->payroll_type,
            ],
            'allowances'  => $allowances,
            'deductions'  => $deductions,
            'commissions' => $commissions,
        ]);
    }

    /**
     * AJAX: Get commission details
     */
    public function getCommissionData(Request $request)
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Get all sales for this employee
        $sales = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            $sales = DB::table('sales')
                ->where('employee_id', $employee->id)
                ->where(function ($q) {
                    $q->where('total_commission', '>', 0)
                      ->orWhereNotNull('commission_percentage');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $result = [];
        $totalEarned  = 0;
        $totalPaid    = 0;
        $totalPending = 0;

        foreach ($sales as $sale) {
            $saleTotal       = floatval($sale->total_net ?? 0);
            $gst             = floatval($sale->total_gst ?? 0);
            $advTax          = floatval($sale->total_adv_tax ?? 0);
            $taxBase         = max(0, $saleTotal - $gst - $advTax);
            $commPct         = floatval($sale->commission_percentage ?? 0);
            $maxComm         = $commPct > 0 ? round($taxBase * ($commPct / 100), 2) : floatval($sale->total_commission ?? 0);
            $commPaid        = floatval($sale->commission_paid ?? 0);
            $commPending     = max(0, $maxComm - $commPaid);

            $status = 'pending';
            if ($commPaid >= $maxComm && $maxComm > 0) {
                $status = 'paid';
            } elseif ($commPaid > 0) {
                $status = 'partial';
            }

            $totalEarned  += $maxComm;
            $totalPaid    += $commPaid;
            $totalPending += $commPending;

            $result[] = [
                'invoice_no'   => $sale->invoice_no ?? 'N/A',
                'sale_date'    => $sale->sale_date ?? ($sale->created_at ? substr($sale->created_at, 0, 10) : 'N/A'),
                'sale_amount'  => number_format($saleTotal, 2),
                'tax_base'     => number_format($taxBase, 2),
                'comm_pct'     => $commPct,
                'commission'   => number_format($maxComm, 2),
                'paid'         => number_format($commPaid, 2),
                'pending'      => number_format($commPending, 2),
                'status'       => $status,
            ];
        }

        return response()->json([
            'sales'         => $result,
            'total_earned'  => number_format($totalEarned, 2),
            'total_paid'    => number_format($totalPaid, 2),
            'total_pending' => number_format($totalPending, 2),
            'count'         => count($result),
        ]);
    }

    /**
     * AJAX: Get loan details
     */
    public function getLoanData()
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $loans = Loan::where('employee_id', $employee->id)
            ->with('payments')
            ->orderByDesc('id')
            ->get();

        $result = $loans->map(function ($loan) {
            $paidAmount     = floatval($loan->paid_amount ?? 0);
            $totalAmount    = floatval($loan->amount ?? 0);
            $remaining      = max(0, $totalAmount - $paidAmount);
            $progressPct    = $totalAmount > 0 ? min(100, round(($paidAmount / $totalAmount) * 100)) : 0;

            $installmentsPaid = intval($loan->installments_paid ?? 0);
            $totalInstallments= intval($loan->total_installments ?? 1);
            $installmentsLeft = max(0, $totalInstallments - $installmentsPaid);

            return [
                'id'                => $loan->id,
                'loan_type'         => $loan->loan_type,
                'loan_type_label'   => $loan->loan_type === 'salary_deduction' ? 'Salary Deduction' : 'Self Paid',
                'amount'            => number_format($totalAmount, 2),
                'paid'              => number_format($paidAmount, 2),
                'remaining'         => number_format($remaining, 2),
                'installment_amount'=> number_format(floatval($loan->installment_amount ?? 0), 2),
                'total_installments'=> $totalInstallments,
                'installments_paid' => $installmentsPaid,
                'installments_left' => $installmentsLeft,
                'start_month'       => $loan->start_month,
                'expected_end_month'=> $loan->expected_end_month,
                'status'            => $loan->status,
                'status_label'      => ucfirst($loan->status),
                'progress_pct'      => $progressPct,
                'reason'            => $loan->reason,
                'approved_at'       => $loan->approved_at ? $loan->approved_at->format('d M Y') : null,
                'disbursed_at'      => $loan->disbursed_at ? $loan->disbursed_at->format('d M Y') : null,
            ];
        });

        $totalBorrowed = $loans->sum('amount');
        $totalPaid     = $loans->sum('paid_amount');
        $totalLeft     = max(0, $totalBorrowed - $totalPaid);

        return response()->json([
            'loans'          => $result,
            'total_borrowed' => number_format($totalBorrowed, 2),
            'total_paid'     => number_format($totalPaid, 2),
            'total_remaining'=> number_format($totalLeft, 2),
            'count'          => $loans->count(),
        ]);
    }

    /**
     * AJAX: Get attendance history for a month
     */
    public function getAttendanceHistory(Request $request)
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $records = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->orderByDesc('date')
            ->get()
            ->map(function ($att) {
                return [
                    'date'            => $att->date,
                    'date_label'      => Carbon::parse($att->date)->format('D, d M'),
                    'status'          => $att->status,
                    'check_in'        => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('h:i A') : null,
                    'check_out'       => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('h:i A') : null,
                    'total_hours'     => $att->total_hours,
                    'is_late'         => $att->is_late,
                    'late_minutes'    => $att->late_minutes,
                    'is_early_leave'  => $att->is_early_leave,
                    'check_in_location'  => $att->check_in_location,
                    'check_out_location' => $att->check_out_location,
                ];
            });

        $present = $records->whereIn('status', ['present', 'late'])->count();
        $absent  = $records->where('status', 'absent')->count();
        $late    = $records->where('is_late', true)->count();

        return response()->json([
            'month'   => $month,
            'records' => $records->values(),
            'summary' => [
                'present' => $present,
                'absent'  => $absent,
                'late'    => $late,
                'total'   => $records->count(),
            ],
        ]);
    }
}
