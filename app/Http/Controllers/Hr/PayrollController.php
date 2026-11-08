<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
// use App\Jobs\Hr\GenerateMonthlyPayrollJob;
// use App\Jobs\Hr\GenerateDailyPayrollJob;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Loan;
use App\Models\Hr\Payroll;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display paginated payrolls with filters
     */
    public function index(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $availableMonths = Payroll::select('month')->distinct()->orderBy('month', 'desc')->pluck('month');
        $selectedMonth = $request->input('month', '');

        $query = Payroll::with(['employee.designation', 'employee.department']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('payroll_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $payrolls = $query->latest()->paginate(50);
        $employees = Employee::all();
        
        $activeTab = $request->type ?? 'all';

        return view('hr.payroll.index', compact('payrolls', 'employees', 'availableMonths', 'selectedMonth'))->with('activeTab', $activeTab);
    }

    /**
     * Show monthly payrolls only
     */
    public function monthly(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $availableMonths = Payroll::monthly()->select('month')->distinct()->orderBy('month', 'desc')->pluck('month');
        $selectedMonth = $request->input('month', '');

        $query = Payroll::with(['employee.designation', 'employee.department'])
            ->monthly();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $payrolls = $query->latest()->paginate(50);

        // Show employees eligible for monthly payroll using scope
        $employees = Employee::forMonthlyPayroll()
            ->with('activeSalaryStructure', 'designation', 'department')
            ->get();

        return view('hr.payroll.index', compact('payrolls', 'employees', 'availableMonths', 'selectedMonth'))->with('activeTab', 'monthly');
    }

    /**
     * Show daily payrolls only
     */
    public function daily(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $availableMonths = Payroll::daily()->select('month')->distinct()->orderBy('month', 'desc')->pluck('month');
        $selectedMonth = $request->input('month', '');

        $query = Payroll::with(['employee.designation', 'employee.department'])
            ->daily();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $payrolls = $query->latest()->paginate(50);

        // Show employees eligible for daily payroll using scope
        $employees = Employee::forDailyPayroll()
            ->with('activeSalaryStructure', 'designation', 'department')
            ->get();

        return view('hr.payroll.index', compact('payrolls', 'employees', 'availableMonths', 'selectedMonth'))->with('activeTab', 'daily');
    }

    /**
     * Get detailed payroll breakdown
     */
    public function details($id)
    {
        try {
            if (! auth()->user()->can('hr.payroll.view')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }

            $payroll = Payroll::with(['employee.designation', 'employee.activeSalaryStructure', 'details', 'reviewer', 'sale.customer_relation'])->findOrFail($id);

            $employee = $payroll->employee;
            if (!$employee) {
                return response()->json(['error' => 'Employee details not found for this payroll record.'], 404);
            }

            // Get salary structure information
            $salaryStructure = $employee->activeSalaryStructure ?? $employee->salaryStructure;
            $structureInfo = [
                'salary_type' => $salaryStructure->salary_type ?? 'salary',
                'use_daily_wages' => $salaryStructure->use_daily_wages ?? false,
                'daily_wages' => $salaryStructure->daily_wages ?? 0,
                'base_salary' => $salaryStructure->base_salary ?? 0,
                'commission_percentage' => $salaryStructure->commission_percentage ?? 0,
            ];

            // Format payroll period based on type
            $payrollPeriod = $this->formatPayrollPeriod($payroll);

            // Get allowance and arrear details
            $allowanceDetails = $payroll->details()->whereIn('type', ['allowance', 'arrear'])->get()->map(function ($detail) {
                return [
                    'name' => $detail->name,
                    'amount' => $detail->amount,
                    'description' => $detail->description,
                    'type' => $detail->type,
                    'calculation_type' => $detail->description ? 'fixed' : 'fixed',
                ];
            });

            // Get deduction details (non-attendance)
            $deductionDetails = $payroll->details()->where('type', 'deduction')->get()->map(function ($detail) {
                return [
                    'name' => $detail->name,
                    'amount' => $detail->amount,
                    'description' => $detail->description,
                ];
            });

            // Get commission details
            $commissionDetails = $payroll->details()->where('type', 'commission')->get()->map(function ($detail) {
                $isJson = is_string($detail->description) && is_array(json_decode($detail->description, true)) && (json_last_error() === JSON_ERROR_NONE);
                $meta = $isJson ? json_decode($detail->description, true) : ['text_desc' => $detail->description];
                
                return [
                    'name' => $detail->name,
                    'amount' => $detail->amount,
                    'description' => $detail->description,
                    'meta' => $meta,
                ];
            });

            // Get attendance breakdown for the payroll period
            $attendanceBreakdown = $this->getAttendanceBreakdown($payroll);

            // Calculate assigned and worked hours
            $assignedHours = 0;
            $workedHours = 0;
            $lateEarlyMinutes = 0;
            $extraHours = 0;

            if ($payroll->payroll_type === 'monthly' && $attendanceBreakdown['has_data']) {
                $startDate = Carbon::parse($payroll->month.'-01')->startOfMonth();
                $endDate = Carbon::parse($payroll->month.'-01')->endOfMonth();
                
                // Total working days * shift hours (default to 8 or get from shift)
                $shift = $payroll->employee->shift;
                $shiftHoursPerDay = 8; // default
                if ($shift && $shift->start_time && $shift->end_time) {
                    $start = Carbon::parse($shift->start_time);
                    $end = Carbon::parse($shift->end_time);
                    $shiftHoursPerDay = $start->diffInHours($end);
                }
                
                $assignedHours = $attendanceBreakdown['total_working_days'] * $shiftHoursPerDay;
                
                // Sum worked hours from attendance records
                $workedHours = DB::table('hr_attendances')
                    ->where('employee_id', $payroll->employee_id)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->sum('total_hours');
                    
                $lateEarlyMinutes = ($attendanceBreakdown['late_minutes_total'] ?? 0) + ($attendanceBreakdown['early_minutes_total'] ?? 0);
                $extraHours = max(0, $workedHours - $assignedHours);
            } elseif ($payroll->payroll_type === 'daily' && $attendanceBreakdown['has_data']) {
                $shift = $payroll->employee->shift;
                $shiftHoursPerDay = 8;
                if ($shift && $shift->start_time && $shift->end_time) {
                    $start = Carbon::parse($shift->start_time);
                    $end = Carbon::parse($shift->end_time);
                    $shiftHoursPerDay = $start->diffInHours($end);
                }
                $assignedHours = $shiftHoursPerDay;
                
                $attendance = Attendance::where('employee_id', $payroll->employee_id)
                    ->where('date', $payroll->month)
                    ->first();
                $workedHours = $attendance->total_hours ?? 0;
                $lateEarlyMinutes = ($attendance->late_minutes ?? 0) + ($attendance->early_leave_minutes ?? 0);
                $extraHours = max(0, $workedHours - $assignedHours);
            }

            // Patterns for ERP Modal
            $lateMinutes = $attendanceBreakdown['late_minutes_total'] ?? 0;
            if ($payroll->payroll_type === 'daily' && isset($attendance)) {
                $lateMinutes = $attendance->late_minutes ?? 0;
            }

            $attendanceTimePattern = sprintf("%02d | %02d | %02d", $assignedHours, $workedHours, $lateMinutes);
            $attendanceSummaryPattern = sprintf("%02d | %02d | %02d", $assignedHours, $workedHours, $lateEarlyMinutes);
            $extraTimePattern = sprintf("%02d | %02d | %02d", $assignedHours + $extraHours, $workedHours, $extraHours);

            // Commission metrics — always compute live for monthly commission payrolls
            $commissionMetrics = null;
            $liveCommissionDetails = [];

            if ($payroll->sale_id && $payroll->sale) {
                // Single-sale commission (commission-type payroll)
                $sale = $payroll->sale;
                $totalCommission = floatval($sale->total_commission);
                $paidSoFar = floatval($sale->commission_paid);
                $currentCommission = floatval($payroll->commission);
                $remaining = max(0, $totalCommission - ($paidSoFar + $currentCommission));

                $customerPaid = DB::table('customer_payments')->where('sale_id', $sale->id)->sum('amount');
                $saleTotal = floatval($sale->total_net);
                $paymentRatio = $saleTotal > 0 ? min(100, ($customerPaid / $saleTotal) * 100) : 0;

                $commissionMetrics = [
                    'total_commission'   => $totalCommission,
                    'paid_so_far'        => $paidSoFar,
                    'current_commission' => $currentCommission,
                    'remaining_commission' => $remaining,
                    'customer_paid_total' => $customerPaid,
                    'sale_total'         => $saleTotal,
                    'payment_ratio'      => round($paymentRatio, 1) . '%',
                    'customer_name'      => $sale->customer_relation->name ?? 'N/A',
                ];
            } elseif ($payroll->payroll_type === 'monthly' && in_array($salaryStructure?->salary_type ?? '', ['commission', 'both'])) {
                // Monthly payroll — manually compute live commission for ALL sales to show metrics
                $allSales = class_exists(\App\Models\Sale::class)
                    ? \App\Models\Sale::where('employee_id', $employee->id)->where('total_net', '>', 0)->get()
                    : collect();

                $aggSaleTotal  = 0;
                $aggMaxComm    = 0;
                $aggCustPaid   = 0;
                $aggRemaining  = 0;
                $liveTotalComm = 0;

                foreach ($allSales as $sale) {
                    $saleTotal            = floatval($sale->total_net);
                    $maxCommission        = floatval($sale->total_commission);
                    $alreadyPaid          = floatval($sale->commission_paid);

                    if ($maxCommission <= 0) {
                        if ($salaryStructure && $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers) > 0) {
                            $maxCommission = $salaryStructure->calculateTieredCommission($saleTotal);
                        } elseif ($salaryStructure && $salaryStructure->commission_percentage > 0) {
                            $maxCommission = ($saleTotal * $salaryStructure->commission_percentage) / 100;
                        }
                    }

                    if ($maxCommission <= 0) {
                        continue;
                    }

                    // Compute payments for this sale up to the end of payroll month
                    $endOfMonth = \Carbon\Carbon::parse($payroll->month . '-01')->endOfMonth()->format('Y-m-d');
                    
                    $posPaid = max(0, floatval($sale->cash) + floatval($sale->card) - max(0, floatval($sale->change)));
                    
                    $totalPaymentsOnSale = $posPaid + DB::table('customer_payments')
                        ->where('sale_id', $sale->id)
                        ->where('payment_date', '<=', $endOfMonth)
                        ->sum('amount');

                    $paymentRatio = $saleTotal > 0 ? min(1, $totalPaymentsOnSale / $saleTotal) : 0;
                    $earnedSoFar = round($paymentRatio * $maxCommission, 2);
                    $newCommission = max(0, $earnedSoFar - $alreadyPaid);
                    $remaining = max(0, $maxCommission - ($alreadyPaid + $newCommission));

                    $aggSaleTotal += $saleTotal;
                    $aggMaxComm   += $maxCommission;
                    $aggCustPaid  += $totalPaymentsOnSale;
                    $aggRemaining += $remaining;
                    $liveTotalComm += $newCommission;

                    $liveCommissionDetails[] = [
                        'name'        => "Sale #{$sale->invoice_no}",
                        'amount'      => $newCommission,
                        'description' => "Sale {$sale->invoice_no}: " . number_format($saleTotal, 2) . " total",
                        'meta'        => [
                            'sale_total'           => $saleTotal,
                            'max_commission'       => $maxCommission,
                            'customer_paid_total'  => $totalPaymentsOnSale,
                            'paid_so_far'          => $alreadyPaid,
                            'current_commission'   => $newCommission,
                            'remaining_commission' => $remaining,
                            'text_desc'            => "Sale {$sale->invoice_no}: " . number_format($saleTotal, 2) . " total, " . round($paymentRatio * 100, 1) . "% paid",
                        ],
                    ];
                }

                $commissionMetrics = [
                    'is_aggregated'      => true,
                    'total_commission'   => $aggMaxComm,
                    'paid_so_far'        => max(0, $aggMaxComm - $aggRemaining - $liveTotalComm),
                    'current_commission' => floatval($payroll->commission) + $liveTotalComm, // Use saved database value + any new unpaid live commission
                    'remaining_commission' => $aggRemaining,
                    'customer_paid_total' => $aggCustPaid,
                    'sale_total'         => $aggSaleTotal,
                    'payment_ratio'      => $aggSaleTotal > 0 ? round(($aggCustPaid / $aggSaleTotal) * 100, 1) . '%' : 'N/A',
                    'customer_name'      => 'Multiple Sales (' . count($liveCommissionDetails) . ')',
                ];

            } elseif ($payroll->commission > 0) {
                $commissionMetrics = [
                    'is_aggregated'      => true,
                    'total_commission'   => $payroll->commission,
                    'paid_so_far'        => 'N/A',
                    'current_commission' => $payroll->commission,
                    'remaining_commission' => 'Check Ledger',
                    'customer_paid_total' => 'N/A',
                    'sale_total'         => 'N/A',
                    'payment_ratio'      => 'Multiple Sales',
                    'customer_name'      => 'Aggregated Sales Commission',
                ];
            }

            // Loan information & calculation
            $loanPayment = \App\Models\Hr\LoanPayment::where('payroll_id', $payroll->id)->first();
            $activeLoan = null;
            $loanDeductionAmount = 0;
            $installmentNumber = 1;
            $totalInstallments = null;
            $installmentLabel = 'Loan Installment';

            if ($loanPayment && $loanPayment->loan) {
                $activeLoan = $loanPayment->loan;
                $loanDeductionAmount = floatval($loanPayment->amount);
                
                $paidCount = \App\Models\Hr\LoanPayment::where('loan_id', $activeLoan->id)
                    ->where('id', '<=', $loanPayment->id)
                    ->count();
                $installmentNumber = max(1, $paidCount);
                $totalInstallments = $activeLoan->total_installments;
                $installmentLabel = "Installment #{$installmentNumber}" . ($totalInstallments ? " of {$totalInstallments}" : "");
            } else {
                $implicitDeduction = max(0, floatval($payroll->gross_salary) - (floatval($payroll->net_salary) + floatval($payroll->deductions) + floatval($payroll->attendance_deductions) + floatval($payroll->manual_deductions) + floatval($payroll->carried_forward_deduction)));
                
                $activeLoan = \App\Models\Hr\Loan::where('employee_id', $payroll->employee_id)
                    ->where('loan_type', 'salary_deduction')
                    ->whereIn('status', ['approved', 'active', 'paid'])
                    ->first();
                    
                if ($implicitDeduction > 0) {
                    $loanDeductionAmount = $implicitDeduction;
                } elseif ($activeLoan) {
                    $loanDeductionAmount = floatval($activeLoan->monthly_installment);
                }
                
                if ($activeLoan) {
                    $installmentNumber = max(1, $activeLoan->installments_paid + ($loanDeductionAmount > 0 ? 1 : 0));
                    $totalInstallments = $activeLoan->total_installments;
                    $installmentLabel = "Installment #{$installmentNumber}" . ($totalInstallments ? " of {$totalInstallments}" : "");
                }
            }

            $loanInfo = null;
            if ($activeLoan || $loanDeductionAmount > 0) {
                $loanInfo = [
                    'id'                     => $activeLoan?->id,
                    'loan_type'              => $activeLoan?->loan_type ?? 'salary_deduction',
                    'type_label'             => $activeLoan?->type_label ?? 'Salary Deduction',
                    'amount'                 => $loanDeductionAmount,
                    'loan_total_amount'      => $activeLoan?->amount ?? 0,
                    'paid_amount'            => $activeLoan?->paid_amount ?? 0,
                    'remaining_amount'       => $activeLoan?->remaining_amount ?? 0,
                    'monthly_installment'    => $activeLoan?->monthly_installment ?? $loanDeductionAmount,
                    'total_installments'     => $totalInstallments,
                    'installment_number'     => $installmentNumber,
                    'installment_label'      => $installmentLabel,
                    'installments_paid'      => $activeLoan?->installments_paid ?? 0,
                    'remaining_installments' => $activeLoan?->remaining_installments ?? 0,
                    'progress_percentage'    => $activeLoan?->progress_percentage ?? 0,
                    'expected_end_month'     => $activeLoan?->expected_end_month,
                    'is_overdue'             => $activeLoan?->is_overdue ?? false,
                ];
            }

            return response()->json([
                'payroll' => $payroll,
                'payroll_period' => $payrollPeriod,
                'structure_info' => $structureInfo,
                'commission_metrics' => $commissionMetrics,
                'breakdown' => [
                    'earnings' => [
                        'basic_salary' => $payroll->basic_salary,
                        'allowances' => $payroll->allowances,
                        'manual_allowances' => $payroll->manual_allowances,
                        'commission' => $payroll->commission,
                        'total' => $payroll->gross_salary,
                    ],
                    'deductions' => [
                        'fixed_deductions' => $payroll->deductions,
                        'attendance_deductions' => $payroll->attendance_deductions,
                        'carried_forward' => $payroll->carried_forward_deduction,
                        'carried_forward_to_next' => $payroll->carried_forward_to_next,
                        'manual_deductions' => $payroll->manual_deductions,
                        'loan_deduction' => $loanDeductionAmount,
                        'total' => floatval($payroll->gross_salary) - floatval($payroll->net_salary),
                    ],
                    'net_payable' => $payroll->net_salary,
                ],
                'stats' => [
                    'assigned_hours' => $assignedHours,
                    'worked_hours' => $workedHours,
                    'extra_hours' => $extraHours,
                    'late_early_minutes' => $lateEarlyMinutes,
                    'attendance_time_pattern' => $attendanceTimePattern,
                    'attendance_summary_pattern' => $attendanceSummaryPattern,
                    'extra_time_pattern' => $extraTimePattern,
                    'loan_installment' => $loanDeductionAmount,
                ],
                'allowance_details' => $allowanceDetails,
                'commission_details' => count($commissionDetails) === 0 ? $liveCommissionDetails : $commissionDetails,
                'deduction_details' => $deductionDetails,
                'attendance_breakdown' => $attendanceBreakdown,
                'loan_info' => $loanInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Format payroll period based on payroll type
     */
    private function formatPayrollPeriod($payroll): array
    {
        if ($payroll->payroll_type === 'daily') {
            // For daily: Display Date, Month, and Year (e.g., "15 March 2026")
            $date = \Carbon\Carbon::parse($payroll->month);

            return [
                'type' => 'daily',
                'formatted' => $date->format('d F Y'),
                'day' => $date->format('d'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
            ];
        } else {
            // For monthly: Display Month and Year only (e.g., "March 2026")
            $date = \Carbon\Carbon::parse($payroll->month.'-01');

            return [
                'type' => 'monthly',
                'formatted' => $date->format('F Y'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
            ];
        }
    }

    /**
     * Get attendance breakdown for payroll period
     */
    private function getAttendanceBreakdown($payroll): array
    {
        $employee = $payroll->employee;
        $structure = $this->payrollService->getEffectiveSalaryStructure($employee);
        
        if ($payroll->payroll_type === 'monthly') {
            if (!$structure) {
                return [
                    'has_data' => false,
                    'data_message' => 'Salary structure not found',
                    'total_deduction' => 0,
                    'absent_records' => [],
                    'late_records' => [],
                    'early_records' => []
                ];
            }

            $serviceData = $this->payrollService->calculateMonthlyAttendanceDeductions(
                $employee, 
                $payroll->month, 
                $structure
            );

            $breakdown = $serviceData['breakdown'];
            
            return [
                'has_data' => $breakdown['has_data'],
                'data_message' => $breakdown['has_data'] ? null : 'Attendance data incomplete for this period',
                'has_attendance_deductions' => $payroll->attendance_deductions > 0,
                'total_working_days' => $breakdown['total_working_days'],
                'days_present' => $breakdown['days_present'],
                'days_absent' => $breakdown['days_absent'],
                'late_check_ins' => $breakdown['late_check_ins'],
                'early_check_outs' => $breakdown['early_check_outs'],
                'late_minutes_total' => $breakdown['total_late_minutes'],
                'early_minutes_total' => $breakdown['total_early_minutes'],
                'total_deduction' => $payroll->attendance_deductions,
                'deduction_details' => [
                    'absence_deduction' => $breakdown['absence_deduction'],
                    'late_deduction' => $breakdown['late_deduction'],
                    'early_deduction' => $breakdown['early_deduction'],
                ],
                'absent_records' => $breakdown['absent_records'],
                'late_records' => $breakdown['late_records'] ?? [],
                'early_records' => $breakdown['early_records'] ?? [],
            ];
        } else {
            // For daily payroll
            $date = \Carbon\Carbon::parse($payroll->month);

            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            if ($attendance) {
                // Get specific deduction amounts from saved details
                $lateDeductionAmount = $payroll->details
                    ->filter(fn ($d) => str_contains(strtolower($d->name), 'late check-in'))
                    ->sum('amount');

                $earlyDeductionAmount = $payroll->details
                    ->filter(fn ($d) => str_contains(strtolower($d->name), 'early leave') || str_contains(strtolower($d->name), 'early check-out'))
                    ->sum('amount');

                return [
                    'has_data' => true,
                    'has_attendance_deductions' => $payroll->attendance_deductions > 0,
                    'date' => $date->format('Y-m-d'),
                    'formatted_date' => $date->format('d M Y'),
                    'day' => $date->format('l'),
                    'status' => $attendance->status,
                    'check_in' => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : 'N/A',
                    'check_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : 'N/A',
                    'is_late' => $attendance->is_late,
                    'is_early_out' => $attendance->is_early_leave,
                    'late_minutes' => $attendance->late_minutes ?? 0,
                    'early_checkout_minutes' => $attendance->early_leave_minutes ?? 0,
                    'total_deduction' => $payroll->attendance_deductions,
                    'late_deduction_amount' => $lateDeductionAmount,
                    'early_deduction_amount' => $earlyDeductionAmount,
                ];
            }

            return [
                'has_data' => false,
                'data_message' => 'Attendance data incomplete for this period',
                'has_attendance_deductions' => false,
                'date' => $date->format('Y-m-d'),
                'status' => 'No attendance record',
            ];
        }
    }

    /**
     * Calculate working days in a date range (excluding weekends)
     */
    private function getWorkingDaysInRange($startDate, $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Exclude Saturdays (6) and Sundays (0)
            if (! in_array($current->dayOfWeek, [0, 6])) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Generate payroll (manual or single employee)
     */
    public function generate(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'payroll_type' => 'required|in:monthly,daily',
            'month' => 'nullable|required_if:payroll_type,monthly',
            'date' => 'nullable|required_if:payroll_type,daily|date',
        ], [
            'month.required_if' => 'Please select a month for monthly payroll calculation.',
            'date.required_if' => 'Please select a specific date for daily wage calculation.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $employee = Employee::with('salaryStructure')->findOrFail($request->employee_id);

            // Determine effective period and validation criteria
            $effectivePeriod = ($request->payroll_type === 'monthly') 
                ? trim($request->month) 
                : trim($request->date);

            // Re-check for existing payroll inside the transaction
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('month', $effectivePeriod)
                ->where('payroll_type', $request->payroll_type)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === 'paid') {
                DB::rollBack();
                $typeLabel = ucfirst($request->payroll_type);
                return response()->json([
                    'error' => "🔒 <b>Paid Payroll Protected:</b> A paid {$typeLabel} payroll record (Ref ID: #{$existing->id}) already exists for <b>{$employee->full_name}</b> for the period <b>{$effectivePeriod}</b>. Paid payrolls cannot be overwritten.",
                ], 422);
            }

            if ($request->payroll_type === 'monthly') {
                $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $request->month);
                $payrollData['auto_generated'] = false; // Mark manual generation
            } else { // daily
                $attendance = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $request->date)
                    ->first();

                if (! $attendance || ! $attendance->clock_out) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "🕒 <b>Attendance Missing:</b> No completed attendance record (with clock-out) was found for <b>{$employee->full_name}</b> on {$request->date}.",
                    ], 422);
                }

                $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);
                $payrollData['auto_generated'] = false; // Mark manual generation
            }

            if ($existing) {
                // Remove old details before re-saving freshly calculated ones
                $existing->details()->delete();

                $existing->update(array_merge(
                    ['auto_generated' => false],
                    \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions', 'auto_generated'])
                ));
                $payroll = $existing;
            } else {
                $payroll = Payroll::create(array_merge(
                    ['employee_id' => $employee->id, 'auto_generated' => false],
                    \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions', 'auto_generated'])
                ));
            }

            // Save detailed breakdown
            $this->payrollService->savePayrollDetails(
                $payroll,
                $payrollData['allowance_details'] ?? [],
                $payrollData['deduction_details'] ?? [],
                $payrollData['commission_details'] ?? []
            );

            // Update pending deductions for daily payroll
            if ($request->payroll_type === 'daily') {
                $this->payrollService->updatePendingDeductions(
                    $employee,
                    $payrollData['new_pending_deductions'] ?? 0
                );
            }

            // Commit any pending commission updates for this employee
            $this->payrollService->commitCommissionUpdates($employee->id);

            DB::commit();

            return response()->json([
                'success' => "✨ <b>Success!</b> Payroll for <b>{$employee->full_name}</b> has been correctly generated and documented. ID: #{$payroll->id}.",
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => '❌ <b>System Error:</b> ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview monthly payrolls for all salaried employees before generating
     */
    public function previewMonthly(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $employees = Employee::forMonthlyPayroll()
                ->with(['activeSalaryStructure', 'designation', 'department'])
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'error' => 'No eligible employees found for monthly payroll. Employees must be active and have either: (1) a salary structure without daily wages, OR (2) commission enabled.',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error while fetching eligible employees: ' . $e->getMessage()
            ], 500);
        }

        $previews = [];
        
        /** @var Employee $employee */
        foreach ($employees as $employee) {
            $existsPaid = Payroll::where('employee_id', $employee->id)
                ->where('month', $request->month)
                ->where('payroll_type', 'monthly')
                ->where('status', 'paid')
                ->exists();

            if ($existsPaid) {
                continue;
            }

            try {
                $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $request->month);
                $previews[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'designation' => $employee->designation->name ?? 'N/A',
                    'base_salary' => $payrollData['basic_salary'],
                    'allowances' => $payrollData['allowances'],
                    'commission' => $payrollData['commission'] ?? 0,
                    'fixed_deductions' => $payrollData['deductions'],
                    'attendance_deductions' => $payrollData['attendance_deductions'],
                    'days_absent' => $payrollData['attendance_breakdown']['days_absent'] ?? 0,
                    'net_salary' => $payrollData['net_salary']
                ];
            } catch (\Exception $e) {
                // Skip employees with calculation errors in preview
            }
        }

        return response()->json(['previews' => $previews]);
    }

    /**
     * Generate monthly payrolls for all salaried employees
     */
    public function generateMonthly(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
            'custom_amounts' => 'nullable|array',
            'custom_amounts.*' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get active employees eligible for monthly payroll using scope
            $employees = Employee::forMonthlyPayroll()
                ->with(['activeSalaryStructure', 'designation', 'department'])
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'error' => 'No eligible employees found for monthly payroll. Employees must be active and have either: (1) a salary structure without daily wages, OR (2) commission enabled.',
                ], 400);
            }

            $generated = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            /** @var Employee $employee */
            foreach ($employees as $employee) {
                try {
                    $customAmount = $request->custom_amounts[$employee->id] ?? null;
                    
                    if (isset($request->custom_amounts) && !array_key_exists($employee->id, $request->custom_amounts)) {
                        continue; // If a preview was loaded and this employee wasn't in the list
                    }
                    
                    $existing = Payroll::where('employee_id', $employee->id)
                        ->where('month', trim($request->month))
                        ->where('payroll_type', 'monthly')
                        ->first();

                    if ($existing) {
                        if ($existing->status === 'paid') {
                            $skipped++;
                            continue;
                        }
                        // Delete old breakdown details before recalculating fresh
                        $existing->details()->where('type', '!=', 'commission')->delete();
                    }

                    $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $request->month);
                    
                    // Apply custom amount logic
                    if ($customAmount !== null && $customAmount !== '') {
                        $targetNet = floatval($customAmount);
                        $calculatedNet = $payrollData['net_salary'];
                        $difference = $targetNet - $calculatedNet;
                        
                        if ($difference > 0) {
                            $payrollData['manual_allowances'] = $difference;
                            $payrollData['gross_salary'] += $difference;
                        } elseif ($difference < 0) {
                            // Ensure we don't deduct more than we can
                            $possibleDeduction = min(abs($difference), $payrollData['gross_salary'] - $payrollData['deductions'] - $payrollData['attendance_deductions']);
                            $payrollData['manual_deductions'] = $possibleDeduction;
                        }
                        
                        $payrollData['net_salary'] = max(0, $payrollData['gross_salary'] - $payrollData['deductions'] - $payrollData['attendance_deductions'] - $payrollData['manual_deductions']);
                    }

                    if ($existing) {
                        // Remove old details before re-saving freshly calculated ones
                        $existing->details()->delete();

                        $existing->update(array_merge(
                            ['auto_generated' => true],
                            \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'breakdown', 'attendance_breakdown'])
                        ));
                        $payroll = $existing;
                        $updated++;
                    } else {
                        // Create Payroll Record
                        $payroll = Payroll::create(array_merge(
                            ['employee_id' => $employee->id],
                            \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'breakdown', 'attendance_breakdown'])
                        ));
                        $generated++;
                    }

                    // Save Details
                    $this->payrollService->savePayrollDetails(
                        $payroll,
                        $payrollData['allowance_details'] ?? [],
                        $payrollData['deduction_details'] ?? [],
                        $payrollData['commission_details'] ?? []
                    );

                    // Consolidate standalone commission payrolls into single monthly payroll card
                    $this->payrollService->consolidateEmployeeMonthPayrolls($employee->id, $request->month);

                    // Commit any commission updates for this employee
                    $this->payrollService->commitCommissionUpdates($employee->id);

                } catch (\Exception $e) {
                    $errors[] = $employee->full_name.': '.$e->getMessage();
                }
            }

            DB::commit();

            $msgParts = [];
            if ($generated > 0) $msgParts[] = "{$generated} new generated";
            if ($updated > 0) $msgParts[] = "{$updated} updated/re-calculated";
            $msg = implode(', ', $msgParts);
            if (empty($msg)) $msg = "No payrolls modified";

            return response()->json([
                'success' => "✅ Success! Monthly payroll processed: {$msg}. " . ($skipped > 0 ? "({$skipped} paid payrolls protected)" : ""),
                'errors' => $errors,
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => '❌ Payroll Generation Failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate monthly payroll in background
     */
    public function generateMonthlyBackground(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // GenerateMonthlyPayrollJob::dispatch($request->month, auth()->id());

        return response()->json([
            'success' => 'Payroll generation started in background. You will be notified when completed.',
            'reload' => true,
        ]);
    }

    /**
     * Generate daily payrolls for all daily wage employees for a specific date
     */
    public function generateDaily(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Fetch active employees eligible for daily payroll using scope
            $employees = Employee::forDailyPayroll()
                ->with(['activeSalaryStructure', 'designation', 'department'])
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'error' => 'No eligible employees found for daily payroll. Employees must be active, have daily wages enabled, and NOT have commission (commission employees are in monthly payroll).',
                ], 400);
            }

            $generated = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            /** @var Employee $employee */
            foreach ($employees as $employee) {
                // Check if payroll already exists for this attendance date
                $existing = Payroll::where('employee_id', $employee->id)
                    ->where('payroll_type', 'daily')
                    ->where('month', $request->date)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'paid') {
                        $skipped++;
                        continue;
                    }
                    $existing->details()->where('type', '!=', 'commission')->delete();
                }

                // Get attendance for the date
                $attendance = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $request->date)
                    ->first();

                if (! $attendance || ! $attendance->clock_out) {
                    $errors[] = $employee->full_name.': No completed attendance found.';

                    continue;
                }

                try {
                    $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);

                    if ($existing) {
                        $existing->update(array_merge(
                            ['auto_generated' => true],
                            Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
                        ));
                        $payroll = $existing;
                        $updated++;
                    } else {
                        $payroll = Payroll::create(array_merge(
                            ['employee_id' => $employee->id],
                            Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
                        ));
                        $generated++;
                    }

                    $this->payrollService->savePayrollDetails(
                        $payroll,
                        $payrollData['allowance_details'] ?? [],
                        $payrollData['deduction_details'] ?? []
                    );

                    $this->payrollService->updatePendingDeductions(
                        $employee,
                        $payrollData['new_pending_deductions'] ?? 0
                    );

                } catch (\Exception $e) {
                    $errors[] = $employee->full_name.': '.$e->getMessage();
                }
            }

            DB::commit();

            $msgParts = [];
            if ($generated > 0) $msgParts[] = "{$generated} new generated";
            if ($updated > 0) $msgParts[] = "{$updated} updated/re-calculated";
            $msg = implode(', ', $msgParts);
            if (empty($msg)) $msg = "No daily payrolls modified";

            return response()->json([
                'success' => "✅ Daily payroll processed: {$msg}. " . ($skipped > 0 ? "({$skipped} paid payrolls protected)" : ""),
                'errors' => $errors,
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => '❌ Processing Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate daily payroll in background
     */
    public function generateDailyBackground(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // GenerateDailyPayrollJob::dispatch($request->date, auth()->id());

        return response()->json([
            'success' => 'Daily payroll generation started in background. You will be notified when completed.',
            'reload' => true,
        ]);
    }

    /**
     * Update payroll (add manual allowances/deductions, edit notes)
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canEdit()) {
            return response()->json([
                'error' => 'Cannot edit paid payroll.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'manual_allowances' => 'nullable|numeric|min:0',
            'manual_deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Update manual adjustments
            $payroll->update([
                'manual_allowances' => $request->manual_allowances ?? 0,
                'manual_deductions' => $request->manual_deductions ?? 0,
                'notes' => $request->notes,
            ]);

            // Recalculate net salary
            $totalDeductions = $payroll->deductions +
                              $payroll->attendance_deductions +
                              $payroll->manual_deductions +
                              $payroll->carried_forward_deduction;

            $grossSalary = $payroll->basic_salary +
                          $payroll->allowances +
                          $payroll->manual_allowances +
                          $payroll->commission;

            $payroll->update([
                'gross_salary' => $grossSalary,
                'net_salary' => $grossSalary - $totalDeductions,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Payroll updated successfully.',
                'payroll' => $payroll->fresh(),
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Mark payroll as reviewed
     */
    public function markReviewed($id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canMarkReviewed()) {
            return response()->json([
                'error' => 'Payroll is not in generated status.',
            ], 403);
        }

        $payroll->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => 'Payroll marked as reviewed successfully.',
            'reload' => true,
        ]);
    }

    /**
     * Process payroll payment (Apply adjustments AND mark as paid)
     */
    public function processPayment(Request $request, $id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canMarkPaid()) {
            return response()->json(['error' => 'Payroll cannot be marked as paid.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'manual_allowances' => 'nullable|numeric|min:0',
            'manual_deductions' => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $manualAllowances = $request->manual_allowances ?? 0;
            $manualDeductions = $request->manual_deductions ?? 0;

            // ── Auto Loan Deduction (salary_deduction type only) ──
            $loanDeductionAmount = 0;
            $activeLoan = null;
            if ($payroll->payroll_type === 'monthly') {
                $activeLoan = \App\Models\Hr\Loan::where('employee_id', $payroll->employee_id)
                    ->where('loan_type', 'salary_deduction')
                    ->active()
                    ->first();

                if ($activeLoan) {
                    $loanDeductionAmount = min($activeLoan->monthly_installment, $activeLoan->remaining_amount);
                }
            }

            // Recalculate totals with loan deduction
            $totalDeductions = $payroll->deductions
                + $payroll->attendance_deductions
                + $manualDeductions
                + $payroll->carried_forward_deduction
                + $loanDeductionAmount;

            $grossSalary = $payroll->basic_salary
                + $payroll->allowances
                + $manualAllowances
                + $payroll->commission;

            $netSalary = max(0, $grossSalary - $totalDeductions);

            // Mark as paid
            $payroll->update([
                'status'            => 'paid',
                'manual_allowances' => $manualAllowances,
                'manual_deductions' => $manualDeductions + $loanDeductionAmount,
                'gross_salary'      => $grossSalary,
                'net_salary'        => $netSalary,
                'paid_amount'       => $netSalary,
                'payment_date'      => now(),
                'payment_notes'     => $request->notes,
            ]);

            // ── Record Loan Payment & Update Loan ──
            if ($activeLoan && $loanDeductionAmount > 0) {
                \App\Models\Hr\LoanPayment::create([
                    'loan_id'      => $activeLoan->id,
                    'amount'       => $loanDeductionAmount,
                    'payment_date' => now(),
                    'type'         => 'salary_deduction',
                    'source'       => 'payroll_auto',
                    'payroll_id'   => $payroll->id,
                    'notes'        => "Auto-deducted from {$payroll->month} payroll",
                ]);

                $activeLoan->increment('paid_amount', $loanDeductionAmount);
                $activeLoan->increment('installments_paid');
                $activeLoan->refresh();

                if ($activeLoan->paid_amount >= $activeLoan->amount) {
                    $activeLoan->update(['status' => 'paid']);
                }
            }

            // ── Finalize Sales Commission Tracking ──
            $commissionDetails = $payroll->details()->where('type', 'commission')->get();
            foreach ($commissionDetails as $cd) {
                if (preg_match('/(?:Sale\s*#|Invoice\s*#|#)([A-Za-z0-9\-]+)/i', $cd->name . ' ' . $cd->description, $matches)) {
                    $invNo = trim($matches[1]);
                    if (class_exists(\App\Models\Sale::class)) {
                        \App\Models\Sale::where('invoice_no', $invNo)
                            ->increment('commission_paid', floatval($cd->amount));
                    }
                }
            }

            // ── Clear / Mark as Paid any previous unpaid monthly payrolls consolidated into this payout ──
            if ($payroll->payroll_type === 'monthly') {
                Payroll::where('employee_id', $payroll->employee_id)
                    ->where('payroll_type', 'monthly')
                    ->where('month', '<', $payroll->month)
                    ->where('status', '!=', 'paid')
                    ->update([
                        'status'        => 'paid',
                        'paid_amount'   => DB::raw('net_salary'),
                        'payment_date'  => now(),
                        'payment_notes' => "Consolidated & paid via {$payroll->month} Payroll (#{$payroll->id})",
                    ]);
            }

            DB::commit();

            $loanNote = $loanDeductionAmount > 0
                ? " (includes Rs. " . number_format($loanDeductionAmount, 2) . " loan deduction)"
                : "";

            return response()->json([
                'success' => "💸 <b>Payment Recorded!</b> Rs. " . number_format($netSalary, 2) . " processed for <b>" . $payroll->employee->full_name . "</b>{$loanNote}.",
                'reload'  => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => '❌ System Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Delete payroll
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('hr.payroll.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        // Only allow deletion if not paid
        if ($payroll->status === 'paid') {
            return response()->json([
                'error' => 'Cannot delete paid payroll.',
            ], 403);
        }

        $payroll->delete();

        return response()->json([
            'success' => 'Payroll deleted successfully.',
            'reload' => true,
        ]);
    }

    /**
     * Auto-generate daily payroll when employee checks out
     * This should be called from attendance checkout process
     */
    public function autoGenerateDaily(Employee $employee, Attendance $attendance)
    {
        // Check if employee uses daily wages
        if (! $employee->salaryStructure || ! $employee->salaryStructure->use_daily_wages) {
            return;
        }

        // Check if payroll already exists for this exact date
        $date = $attendance->date;
        $exists = Payroll::where('employee_id', $employee->id)
            ->where('month', $date)
            ->where('payroll_type', 'daily')
            ->exists();

        if ($exists) {
            return;
        }

        try {
            DB::beginTransaction();

            $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);

            $payroll = Payroll::create(array_merge(
                ['employee_id' => $employee->id],
                Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
            ));

            $this->payrollService->savePayrollDetails(
                $payroll,
                $payrollData['allowance_details'] ?? [],
                $payrollData['deduction_details'] ?? []
            );

            $this->payrollService->updatePendingDeductions(
                $employee,
                $payrollData['new_pending_deductions'] ?? 0
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto-generate daily payroll failed: '.$e->getMessage());
        }
    }

    /**
     * Display Employee Self-Service Commission Portal
     */
    public function myCommission(Request $request)
    {
        $user = auth()->user();
        
        // Find employee associated with logged in user or selected filter
        $employee = null;
        if ($request->filled('employee_id') && ($user->isSuperAdmin() || $user->can('hr.payroll.view'))) {
            $employee = Employee::find($request->employee_id);
        }

        if (!$employee) {
            $employee = Employee::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
        }

        if (!$employee && ($user->isSuperAdmin() || $user->can('hr.payroll.view'))) {
            $employee = Employee::active()->first();
        }

        if (!$employee) {
            return view('hr.payroll.my-commission', [
                'employee' => null,
                'sales' => collect(),
                'summary' => [
                    'total_sales_count' => 0,
                    'total_sales_net' => 0,
                    'total_commission_earned' => 0,
                    'total_commission_paid' => 0,
                    'total_commission_pending' => 0,
                ],
                'employees' => Employee::active()->orderBy('first_name')->get(),
            ]);
        }

        // Fetch all sales for this employee
        $salesRaw = class_exists(\App\Models\Sale::class)
            ? \App\Models\Sale::with(['customer_relation', 'payments'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->get()
            : collect();

        $processedSales = [];
        $totalNetSum = 0;
        $totalCommissionEarned = 0;
        $totalCommissionPaid = 0;
        $totalCommissionPending = 0;

        foreach ($salesRaw as $sale) {
            $saleTotal = floatval($sale->total_net);
            $gst       = floatval($sale->total_gst ?? 0);
            $advTax    = floatval($sale->total_adv_tax ?? 0);
            
            // Tax-exclusive base: Net Amount minus GST (18%) and Sale Tax (5%)
            $taxExclusiveBase = max(0, $saleTotal - $gst - $advTax);
            
            $commPct = $sale->commission_percentage !== null ? floatval($sale->commission_percentage) : 1.0;
            $maxComm = round($taxExclusiveBase * ($commPct / 100), 2);

            $posPaid = max(0, floatval($sale->cash) + floatval($sale->card) - max(0, floatval($sale->change)));
            $customerPaymentsSum = $sale->payments->sum('amount');
            $totalReceived = $posPaid + $customerPaymentsSum;
            
            $isFullyPaid = ($totalReceived >= ($saleTotal - 0.01)) && ($saleTotal > 0);
            $commPaid = floatval($sale->commission_paid);

            // Status determination
            if ($commPaid >= $maxComm && $maxComm > 0) {
                $status = 'PAID';
                $statusLabel = 'Salary Mein Add Ho Gaya';
                $statusBadge = 'bg-success';
            } elseif ($isFullyPaid && $maxComm > 0) {
                $status = 'READY';
                $statusLabel = 'Payment Recv - Agli Salary Mein Add Hoga';
                $statusBadge = 'bg-primary';
            } elseif ($sale->sale_status !== 'post') {
                $status = 'DC_ONLY';
                $statusLabel = 'DC Note (Invoice Pending)';
                $statusBadge = 'bg-secondary';
            } else {
                $status = 'AWAITING_PAYMENT';
                $statusLabel = 'Customer Payment Pending';
                $statusBadge = 'bg-warning text-dark';
            }

            $commPending = max(0, $maxComm - $commPaid);

            $totalNetSum += $saleTotal;
            $totalCommissionEarned += $maxComm;
            $totalCommissionPaid += $commPaid;
            $totalCommissionPending += $commPending;

            $processedSales[] = [
                'sale' => $sale,
                'invoice_no' => $sale->invoice_no,
                'date' => $sale->sale_date ?? $sale->created_at->format('Y-m-d'),
                'customer' => $sale->customer_relation->customer_name ?? 'N/A',
                'total_net' => $saleTotal,
                'total_gst' => $gst,
                'total_adv_tax' => $advTax,
                'tax_base' => $taxExclusiveBase,
                'comm_pct' => $commPct,
                'max_comm' => $maxComm,
                'received' => $totalReceived,
                'is_fully_paid' => $isFullyPaid,
                'comm_paid' => $commPaid,
                'comm_pending' => $commPending,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
            ];
        }

        $allEmployees = (auth()->user()->isSuperAdmin() || auth()->user()->can('hr.payroll.view'))
            ? Employee::active()->orderBy('first_name')->get()
            : collect();

        return view('hr.payroll.my-commission', [
            'employee' => $employee,
            'sales' => collect($processedSales),
            'summary' => [
                'total_sales_count' => count($processedSales),
                'total_sales_net' => $totalNetSum,
                'total_commission_earned' => $totalCommissionEarned,
                'total_commission_paid' => $totalCommissionPaid,
                'total_commission_pending' => $totalCommissionPending,
            ],
            'employees' => $allEmployees,
        ]);
    }
}
