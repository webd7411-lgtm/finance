<?php

namespace App\Services;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayrollDetail;
use App\Models\Hr\SalaryStructure;
use App\Models\Hr\Holiday;
use App\Models\Hr\Leave;
use App\Models\Hr\Loan;
use App\Models\Hr\LoanPayment;
use Carbon\Carbon;

class PayrollCalculationService
{
    /**
     * Get the effective salary structure for an employee
     * Uses the direct salaryStructure relationship
     */
    public function getEffectiveSalaryStructure(Employee $employee): ?SalaryStructure
    {
        // Use the activeSalaryStructure relation defined in Employee model
        $structure = $employee->activeSalaryStructure;
        
        if (!$structure) {
            // Fallback for standalone legacy structures if needed
            $structure = SalaryStructure::where('employee_id', $employee->id)->latest('id')->first();
        }

        return $structure;
    }

    /**
     * Calculate allowances with support for both fixed and percentage types
     */
    private function calculateAllowances(SalaryStructure $structure): array
    {
        $allowances = collect($structure->allowances ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalAllowances = 0;
        $details = [];

        foreach ($allowances as $allowance) {
            $name = $allowance['name'] ?? 'Allowance';
            $type = $allowance['type'] ?? 'fixed'; // 'fixed' or 'percentage'
            $value = floatval($allowance['amount'] ?? 0);

            if ($type === 'percentage') {
                // Calculate based on base salary
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $allowance['description'] ?? null;
            }

            $totalAllowances += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'calculation_type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalAllowances,
            'details' => $details,
        ];
    }

    /**
     * Calculate fixed deductions from salary structure
     */
    private function calculateFixedDeductions(SalaryStructure $structure): array
    {
        $deductions = collect($structure->deductions ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalDeductions = 0;
        $details = [];

        foreach ($deductions as $deduction) {
            $name = $deduction['name'] ?? 'Deduction';
            $type = $deduction['type'] ?? 'fixed';
            $value = floatval($deduction['amount'] ?? 0);

            if ($type === 'percentage') {
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $deduction['description'] ?? null;
            }

            $totalDeductions += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalDeductions,
            'details' => $details,
        ];
    }

    /**
     * Calculate attendance-based deductions for monthly payroll
     */
    public function calculateMonthlyAttendanceDeductions(
        Employee $employee,
        string $month,
        SalaryStructure $structure
    ): array {
        $startDate = Carbon::parse($month.'-01')->startOfMonth();
        $endDate = Carbon::parse($month.'-01')->endOfMonth();

        $policy = $structure->attendance_deduction_policy ?? [];
        
        // Absent deduction calculation logic
        if (($structure->absent_deduction_type ?? 'manual') === 'automatic' && $structure->base_salary > 0) {
            // Calculate working days (excluding weekends/holidays)
            $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate, $employee);
            if ($totalWorkingDays > 0) {
                $perDayDeduction = round($structure->base_salary / $totalWorkingDays, 2);
            } else {
                $perDayDeduction = 0;
            }
        } else {
            $perDayDeduction = $structure->leave_salary_per_day ?? 0;
            $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate, $employee);
        }
        
        $daysInMonth = $startDate->daysInMonth;

        // Fetch attendance records for the period
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Attendance stats & Absence detection
        $attendancesByDate = $attendances->keyBy('date');
        $daysPresent = 0;
        $daysAbsent = 0;
        $daysPaidLeave = 0;
        $absentRecords = [];
        $paidLeaveRecords = [];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $att = $attendancesByDate->get($dateStr);

            // Check day status (supports multiple off days like "Monday,Tuesday")
            $offDays = $employee->weekly_off ? array_map('trim', explode(',', strtolower($employee->weekly_off))) : ['sunday'];
            $isWeeklyOff = in_array(strtolower($current->format('l')), $offDays);

            $isHoliday = Holiday::isHoliday($dateStr, $employee->id);

            // Check for approved leave AND whether it should deduct salary
            $approvedLeave        = Leave::getApprovedLeave($employee->id, $dateStr);
            $isLeave              = !is_null($approvedLeave);
            $leaveDeductsSalary   = $isLeave && $approvedLeave->deduct_salary;

            if ($att) {
                if (in_array(strtolower($att->status), ['present', 'late'])) {
                    $daysPresent++;
                } elseif (strtolower($att->status) === 'absent') {
                    // If the status is 'absent' but it's a weekly off or holiday, don't count as absent
                    if ($isWeeklyOff || $isHoliday) {
                        // Skip
                    } elseif ($isLeave && !$leaveDeductsSalary) {
                        // No deduction — leave with full pay
                        $daysPaidLeave++;
                        $paidLeaveRecords[] = [
                            'date' => $current->format('d M Y'),
                            'day' => $current->format('l'),
                            'type' => $approvedLeave->leave_type,
                        ];
                    } else {
                        $daysAbsent++;
                        $absentRecords[] = [
                            'date'      => $current->format('d M Y'),
                            'day'       => $current->format('l'),
                            'deduction' => $perDayDeduction,
                        ];
                    }
                } elseif (strtolower($att->status) === 'leave') {
                    // Attendance record marked as "leave" — check if should deduct
                    if ($leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                        $daysAbsent++;
                        $absentRecords[] = [
                            'date'      => $current->format('d M Y'),
                            'day'       => $current->format('l'),
                            'deduction' => $perDayDeduction,
                            'note'      => 'Leave (Unpaid)',
                        ];
                    } else {
                        // Leave with no deduction — Paid Leave (or it's an off day)
                        if (!$isWeeklyOff && !$isHoliday) {
                            $daysPaidLeave++;
                            $paidLeaveRecords[] = [
                                'date' => $current->format('d M Y'),
                                'day' => $current->format('l'),
                                'type' => $approvedLeave->leave_type ?? 'Leave',
                            ];
                        }
                    }
                }
            } else {
                // No attendance record for this day
                if ($isLeave && !$leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                    // Employee is on paid leave — no deduction
                    $daysPaidLeave++;
                    $paidLeaveRecords[] = [
                        'date' => $current->format('d M Y'),
                        'day' => $current->format('l'),
                        'type' => $approvedLeave->leave_type,
                    ];
                } elseif ($isLeave && $leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                    // Leave quota exhausted — count as absent (deduction applies)
                    $daysAbsent++;
                    $absentRecords[] = [
                        'date'      => $current->format('d M Y'),
                        'day'       => $current->format('l'),
                        'deduction' => $perDayDeduction,
                        'note'      => 'Leave (Unpaid)',
                    ];
                } elseif (!$isWeeklyOff && !$isHoliday) {
                    // Regular absent (no leave, no holiday)
                    $daysAbsent++;
                    $absentRecords[] = [
                        'date'      => $current->format('d M Y'),
                        'day'       => $current->format('l'),
                        'deduction' => $perDayDeduction,
                    ];
                }
            }
            $current->addDay();
        }

        $lateCheckIns = $attendances->where('is_late', true)->count();
        $earlyCheckOuts = $attendances->where('is_early_leave', true)->count();
        $totalLateMinutes = $attendances->sum('late_minutes');
        $totalEarlyMinutes = $attendances->sum('early_leave_minutes');

        // If no attendance data exists (at least one punch)
        $hasAttendanceData = $attendances->count() > 0;

        // Calculate deductions
        $absenceDeduction = 0;
        $lateDeduction = 0;
        $earlyDeduction = 0;
        $deductionDetails = [];

        // Absence deduction (absent days × per-day deduction)
        if ($daysAbsent > 0 && $perDayDeduction > 0) {
            $absenceDeduction = $daysAbsent * $perDayDeduction;
            $deductionDetails[] = [
                'name' => "Absence Deduction ({$daysAbsent} days)",
                'amount' => $absenceDeduction,
                'description' => "{$daysAbsent} absent days × Rs. {$perDayDeduction}",
            ];
        }

        // Late check-in penalty
        if ($lateCheckIns > 0) {
            $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
            if ($latePenalty > 0) {
                $lateDeduction = $lateCheckIns * $latePenalty;
                $deductionDetails[] = [
                    'name' => "Late Check-in Penalty ({$lateCheckIns} times)",
                    'amount' => $lateDeduction,
                    'description' => "{$lateCheckIns} late check-ins × Rs. {$latePenalty}",
                ];
            }

            // Alternative: Use late rules if defined
            if (empty($latePenalty) && ! empty($policy['late_rules'])) {
                foreach ($attendances->where('is_late', true) as $att) {
                    $deduction = $this->calculateLateDeduction(
                        $att->late_minutes ?? 0,
                        $policy['late_rules'],
                        $structure->base_salary / $daysInMonth // Per-day rate based on actual month days
                    );
                    $lateDeduction += $deduction;
                }
                if ($lateDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Late Check-in Penalties ({$lateCheckIns} times, {$totalLateMinutes} min)",
                        'amount' => $lateDeduction,
                        'description' => 'Calculated based on late rules',
                    ];
                }
            }
        }

        // Early check-out penalty
        if ($earlyCheckOuts > 0) {
            $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
            if ($earlyPenalty > 0) {
                $earlyDeduction = $earlyCheckOuts * $earlyPenalty;
                $deductionDetails[] = [
                    'name' => "Early Check-out Penalty ({$earlyCheckOuts} times)",
                    'amount' => $earlyDeduction,
                    'description' => "{$earlyCheckOuts} early check-outs × Rs. {$earlyPenalty}",
                ];
            }

            // Alternative: Use early rules if defined
            if (empty($earlyPenalty) && ! empty($policy['early_rules'])) {
                foreach ($attendances->where('is_early_leave', true) as $att) { // Fixed: is_early_leave
                    $deduction = $this->calculateEarlyDeduction(
                        $att->early_leave_minutes ?? 0, // Fixed: early_leave_minutes
                        $policy['early_rules'],
                        $structure->base_salary / $daysInMonth
                    );
                    $earlyDeduction += $deduction;
                }
                if ($earlyDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Early Check-out Penalties ({$earlyCheckOuts} times, {$totalEarlyMinutes} min)",
                        'amount' => $earlyDeduction,
                        'description' => 'Calculated based on early rules',
                    ];
                }
            }
        }

        $totalDeduction = $absenceDeduction + $lateDeduction + $earlyDeduction;

        // Build detailed records for late and early
        $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
        $lateRecords = $attendances->where('is_late', true)->map(function ($att) use ($latePenalty) {
            return [
                'date' => Carbon::parse($att->date)->format('d M Y'),
                'day' => Carbon::parse($att->date)->format('l'),
                'check_in' => $att->clock_in ? Carbon::parse($att->clock_in)->format('h:i A') : 'N/A',
                'late_minutes' => $att->late_minutes ?? 0,
                'deduction' => $latePenalty,
            ];
        })->values()->toArray();

        $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
        $earlyRecords = $attendances->where('is_early_leave', true)->map(function ($att) use ($earlyPenalty) {
            return [
                'date' => Carbon::parse($att->date)->format('d M Y'),
                'day' => Carbon::parse($att->date)->format('l'),
                'check_out' => $att->clock_out ? Carbon::parse($att->clock_out)->format('h:i A') : 'N/A',
                'early_minutes' => $att->early_leave_minutes ?? 0,
                'deduction' => $earlyPenalty,
            ];
        })->values()->toArray();

        return [
            'total' => $totalDeduction,
            'details' => $deductionDetails,
            'breakdown' => [
                'has_data' => $hasAttendanceData,
                'total_working_days' => $totalWorkingDays,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'days_paid_leave' => $daysPaidLeave,
                'late_check_ins' => $lateCheckIns,
                'early_check_outs' => $earlyCheckOuts,
                'total_late_minutes' => $totalLateMinutes,
                'total_early_minutes' => $totalEarlyMinutes,
                'absence_deduction' => $absenceDeduction,
                'late_deduction' => $lateDeduction,
                'early_deduction' => $earlyDeduction,
                'absent_records' => $absentRecords,
                'paid_leave_records' => $paidLeaveRecords,
                'late_records' => $lateRecords,
                'early_records' => $earlyRecords,
            ],
        ];
    }

    /**
     * Calculate working days in a date range (based on employee's off days)
     */
    private function getWorkingDaysInRange(Carbon $startDate, Carbon $endDate, Employee $employee = null): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        // Get employee off days (default to Sunday if not set)
        $offDays = ['sunday'];
        if ($employee && $employee->weekly_off) {
            $offDays = array_map('trim', explode(',', strtolower($employee->weekly_off)));
        }

        while ($current->lte($endDate)) {
            $dayName = strtolower($current->format('l'));
            $dateStr = $current->format('Y-m-d');
            
            $isWeeklyOff = in_array($dayName, $offDays);
            $isHoliday = Holiday::isHoliday($dateStr, $employee ? $employee->id : null);
            
            if (!$isWeeklyOff && !$isHoliday) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate monthly payroll for a salaried employee
     */
    public function calculateMonthlyPayroll(Employee $employee, string $month): array
    {
        // Get effective salary structure (handles custom assignments)
        $structure = $this->getEffectiveSalaryStructure($employee);

        if (! $structure) {
            throw new \Exception('Employee has no salary structure assigned.');
        }

        $baseSalary = $structure->base_salary ?? 0;

        // Calculate allowances (supports fixed and percentage types)
        $allowanceData = $this->calculateAllowances($structure);
        $activeAllowances = $allowanceData['total'];
        $allowanceDetails = $allowanceData['details'];

        // Calculate fixed deductions
        $fixedDeductionData = $this->calculateFixedDeductions($structure);
        $activeFixedDeductions = $fixedDeductionData['total'];
        $fixedDeductionDetails = $fixedDeductionData['details'];

        // Calculate attendance-based deductions
        $attendanceDeductionData = $this->calculateMonthlyAttendanceDeductions($employee, $month, $structure);
        $attendanceDeductions = $attendanceDeductionData['total'];
        $attendanceBreakdown = $attendanceDeductionData['breakdown'];

        // Calculate loan deductions (supports monthly skip & auto salary_deduction mode)
        $loanDeductionData = $this->calculateLoanDeductions($employee, $month);
        $loanDeductions = $loanDeductionData['total'];

        // Add attendance deduction details and loan deduction details to deduction details
        $allDeductionDetails = array_merge(
            $fixedDeductionDetails,
            $attendanceDeductionData['details'],
            $loanDeductionData['details']
        );

        // Calculate commission if applicable
        $commissionData = ['total' => 0, 'description' => 'Not applicable', 'commission_details' => []];
        if (in_array($structure->salary_type, ['commission', 'both'])) {
            $commissionData = $this->calculateSalesCommission($employee, $month, $structure);
        }
        $commission = $commissionData['total'];

        // Map commission details to satisfy savePayrollDetails format
        $mappedCommissionDetails = collect($commissionData['commission_details'] ?? [])->map(function($c) {
            return [
                'name' => "Commission (Sale #{$c['invoice_no']})",
                'amount' => $c['commission'],
                'description' => isset($c['json_meta']) ? $c['json_meta'] : $c['description'],
                'type' => 'commission'
            ];
        })->toArray();

        // Check for previous unpaid monthly payrolls for this employee (Arrears Consolidation)
        $unpaidPreviousPayrolls = Payroll::where('employee_id', $employee->id)
            ->where('payroll_type', 'monthly')
            ->where('month', '<', $month)
            ->where('status', '!=', 'paid')
            ->orderBy('month', 'asc')
            ->get();

        $totalArrears = 0;
        $arrearDetails = [];

        foreach ($unpaidPreviousPayrolls as $prev) {
            $prevNet = floatval($prev->net_salary);
            if ($prevNet > 0) {
                $totalArrears += $prevNet;
                $formattedMonth = \Carbon\Carbon::parse($prev->month . '-01')->format('F Y');
                $arrearDetails[] = [
                    'name' => "Unpaid Arrears ({$formattedMonth})",
                    'amount' => $prevNet,
                    'type' => 'allowance',
                    'description' => "Unpaid salary & commission balance from {$formattedMonth} (Payroll #{$prev->id})",
                ];
            }
        }

        $allAllowanceDetails = array_merge($allowanceDetails, $arrearDetails);
        $totalAllowancesAndArrears = $activeAllowances + $totalArrears;

        // Calculate gross salary (includes base, allowances, arrears, and commission)
        $grossSalary = $baseSalary + $totalAllowancesAndArrears + $commission;

        // Calculate total deductions
        $totalDeductions = $activeFixedDeductions + $attendanceDeductions + $loanDeductions;

        // Calculate net salary (prevent negative)
        $netSalary = max(0, $grossSalary - $totalDeductions);

        return [
            'payroll_type' => 'monthly',
            'month' => $month,
            'basic_salary' => $baseSalary,
            'gross_salary' => $grossSalary,
            'allowances' => $totalAllowancesAndArrears,
            'deductions' => $activeFixedDeductions,
            'attendance_deductions' => $attendanceDeductions,
            'manual_deductions' => 0,
            'manual_allowances' => 0,
            'carried_forward_deduction' => 0,
            'bonuses' => 0,
            'commission' => $commission,
            'net_salary' => $netSalary,
            'auto_generated' => true,
            'status' => 'generated',
            'allowance_details' => $allAllowanceDetails,
            'deduction_details' => $allDeductionDetails,
            'attendance_breakdown' => $attendanceBreakdown,
            'commission_details' => $mappedCommissionDetails,
        ];
    }

    /**
     * Calculate loan deductions for an employee for a given month
     */
    public function calculateLoanDeductions(Employee $employee, string $month): array
    {
        $loans = Loan::where('employee_id', $employee->id)
            ->salaryDeduction()
            ->active()
            ->get();

        $totalLoanDeduction = 0;
        $details = [];
        $loanPaymentsToCreate = [];

        foreach ($loans as $loan) {
            // Check start_month constraint
            if ($loan->start_month && $month < $loan->start_month) {
                continue;
            }

            // Check if user/HR manually skipped deduction for this month
            if ($loan->isDeductionSkippedForMonth($month)) {
                $details[] = [
                    'name' => "Loan Installment (Loan #{$loan->id}) - Skipped",
                    'amount' => 0,
                    'type' => 'loan_deduction',
                    'description' => "Installment deferred for {$month} by management request.",
                    'loan_id' => $loan->id,
                ];
                continue;
            }

            // Check if there is a custom scheduled deduction for this month
            $scheduled = $loan->getScheduledDeductionForMonth($month);
            if ($scheduled && $scheduled->status === 'pending') {
                $installment = floatval($scheduled->amount);
            } else {
                $installment = floatval($loan->monthly_installment);
            }

            // Cap at remaining amount
            $installment = min($installment, $loan->remaining_amount);

            if ($installment > 0) {
                $totalLoanDeduction += $installment;
                $details[] = [
                    'name' => "Loan Installment (Loan #{$loan->id})",
                    'amount' => $installment,
                    'type' => 'loan_deduction',
                    'description' => "Monthly repayment for Loan #{$loan->id} (Remaining after deduction: Rs. " . number_format(max(0, $loan->remaining_amount - $installment), 2) . ")",
                    'loan_id' => $loan->id,
                ];

                $loanPaymentsToCreate[] = [
                    'loan_id' => $loan->id,
                    'amount' => $installment,
                    'scheduled_id' => $scheduled?->id,
                ];
            }
        }

        return [
            'total' => $totalLoanDeduction,
            'details' => $details,
            'loan_payments' => $loanPaymentsToCreate,
        ];
    }

    /**
     * Save payroll details (allowances and deductions breakdown)
     */
    public function savePayrollDetails(Payroll $payroll, array $allowanceDetails, array $deductionDetails, array $commissionDetails = []): void
    {
        // Save allowances
        foreach ($allowanceDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => $detail['type'] ?? 'allowance',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }

        // Save deductions
        foreach ($deductionDetails as $detail) {
            $typeToSave = (isset($detail['type']) && $detail['type'] === 'loan_deduction') ? 'deduction' : ($detail['type'] ?? 'deduction');
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => $typeToSave,
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);

            // Process Loan Payment creation & balance updates idempotently
            if (isset($detail['type']) && $detail['type'] === 'loan_deduction' && !empty($detail['loan_id']) && floatval($detail['amount']) > 0) {
                $loanId = intval($detail['loan_id']);
                $amount = floatval($detail['amount']);

                // Avoid duplicate loan payment for the same payroll and loan
                $alreadyPaidForPayroll = LoanPayment::where('payroll_id', $payroll->id)
                    ->where('loan_id', $loanId)
                    ->exists();

                if (!$alreadyPaidForPayroll) {
                    $loan = Loan::find($loanId);
                    if ($loan) {
                        LoanPayment::create([
                            'loan_id' => $loan->id,
                            'amount' => $amount,
                            'payment_date' => now(),
                            'type' => 'salary_deduction',
                            'source' => 'payroll_auto',
                            'payroll_id' => $payroll->id,
                            'reference' => 'PAYROLL-' . $payroll->id,
                            'notes' => "Automatic deduction for payroll month {$payroll->month}",
                        ]);

                        $loan->increment('paid_amount', $amount);
                        $loan->increment('installments_paid');
                        $loan->refresh();

                        if ($loan->paid_amount >= $loan->amount) {
                            $loan->update(['status' => 'paid']);
                        }

                        // Mark any pending scheduled deduction for this month as deducted
                        \App\Models\Hr\LoanScheduledDeduction::where('loan_id', $loan->id)
                            ->where('deduction_month', $payroll->month)
                            ->where('status', 'pending')
                            ->update(['status' => 'deducted']);
                    }
                }
            }
        }

        // Save commissions
        foreach ($commissionDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'commission',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }
    }

    /**
     * Update employee's pending deductions
     */
    public function updatePendingDeductions(Employee $employee, float $newPendingDeductions): void
    {
        $employee->update([
            'pending_deductions' => $newPendingDeductions,
        ]);
    }

    /**
     * Calculate sales commission for an employee for a given month.
     *
     * Commission Logic (International ERP Standard):
     * - Base Amount = Tax-Exclusive Amount (total_net - GST 18% - Sale Tax 5%)
     * - Rate = Manual commission_percentage set on Sale (or fallback to structure)
     * - Trigger = FULL payment received on Sale Invoice (SIN- / posted)
     * - DC-only or partial payment does not trigger commission until fully paid.
     *
     * @param Employee        $employee
     * @param string          $month    Format: 'Y-m'
     * @param SalaryStructure $structure
     * @return array
     */
    public function calculateSalesCommission(Employee $employee, string $month, SalaryStructure $structure): array
    {
        // Get all posted sales assigned to this employee
        $sales = \App\Models\Sale::where('employee_id', $employee->id)
            ->where('total_net', '>', 0)
            ->where('sale_status', 'post')
            ->get();

        if ($sales->isEmpty()) {
            return [
                'total' => 0,
                'description' => 'No sales assigned to this employee',
                'commission_details' => [],
            ];
        }

        $totalNewCommission = 0;
        $commissionDetails  = [];
        $salesUpdates       = [];

        foreach ($sales as $sale) {
            $saleTotal = floatval($sale->total_net);
            $gst       = floatval($sale->total_gst ?? 0);
            $advTax    = floatval($sale->total_adv_tax ?? 0);
            
            // Tax-exclusive base: Net Amount minus GST & Sale Tax
            $taxExclusiveBase = max(0, $saleTotal - $gst - $advTax);
            // Calculate how much commission has ACTUALLY been disbursed in a PAID payroll for this sale
            $paidViaPayrolls = \App\Models\Hr\PayrollDetail::where('type', 'commission')
                ->where(function($q) use ($sale) {
                    $q->where('name', 'like', "%Sale #{$sale->invoice_no}%")
                      ->orWhere('name', 'like', "%#{$sale->invoice_no}%")
                      ->orWhere('description', 'like', "%{$sale->invoice_no}%");
                })
                ->whereHas('payroll', function($q) {
                    $q->where('status', 'paid');
                })
                ->sum('amount');

            $alreadyPaid = max(floatval($paidViaPayrolls), 0);

            // Determine maximum commission amount for this sale
            $maxCommission = 0;
            if ($sale->commission_percentage !== null && floatval($sale->commission_percentage) > 0) {
                $maxCommission = round($taxExclusiveBase * (floatval($sale->commission_percentage) / 100), 2);
            } elseif (floatval($sale->total_commission) > 0) {
                $maxCommission = floatval($sale->total_commission);
            } elseif ($structure->commission_tiers && count($structure->commission_tiers) > 0) {
                $maxCommission = $structure->calculateTieredCommission($taxExclusiveBase);
            } elseif ($structure->commission_percentage > 0) {
                $maxCommission = ($taxExclusiveBase * $structure->commission_percentage) / 100;
            }

            // Check if this sale's commission was already included in an earlier month's monthly payroll
            // (If unpaid, it is already accounted for in the previous month's carried-forward Arrears)
            $includedInPrevMonths = \App\Models\Hr\PayrollDetail::where('type', 'commission')
                ->where(function($q) use ($sale) {
                    $q->where('name', 'like', "%Sale #{$sale->invoice_no}%")
                      ->orWhere('name', 'like', "%#{$sale->invoice_no}%")
                      ->orWhere('description', 'like', "%{$sale->invoice_no}%");
                })
                ->whereHas('payroll', function($q) use ($month, $employee) {
                    $q->where('employee_id', $employee->id)
                      ->where('payroll_type', 'monthly')
                      ->where('month', '<', $month);
                })
                ->exists();

            if ($includedInPrevMonths) {
                continue;
            }

            // If no commission or already fully paid in a finalized paid payroll — skip
            if ($maxCommission <= 0 || $alreadyPaid >= ($maxCommission - 0.01)) {
                continue;
            }

            // Sum of all payments linked to this sale
            $endOfMonth = \Carbon\Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
            $totalPaymentsOnSale = \App\Models\CustomerPayment::where('sale_id', $sale->id)
                ->where('payment_date', '<=', $endOfMonth)
                ->sum('amount');

            // Fallback for legacy POS sales where CustomerPayment might not have been created
            if ($totalPaymentsOnSale == 0) {
                $posPaid = max(0, floatval($sale->cash) + floatval($sale->card) - max(0, floatval($sale->change)));
                $totalPaymentsOnSale = $posPaid;
            }

            // Trigger rule: Full payment required (customer must pay 100% of invoice amount)
            if ($totalPaymentsOnSale < ($saleTotal - 0.01)) {
                continue; // Partial or zero payment — commission not eligible yet
            }

            // Full payment received -> Entire remaining unpaid commission is earned
            $newCommission = max(0, $maxCommission - $alreadyPaid);

            if ($newCommission > 0) {
                $totalNewCommission += $newCommission;
                $commPctStr = ($sale->commission_percentage !== null) ? floatval($sale->commission_percentage) . '%' : 'Calculated';

                $commissionDetails[] = [
                    'sale_id'        => $sale->id,
                    'invoice_no'     => $sale->invoice_no,
                    'sale_total'     => $saleTotal,
                    'tax_base'       => $taxExclusiveBase,
                    'comm_rate'      => $commPctStr,
                    'payment_ratio'  => '100%',
                    'commission'     => $newCommission,
                    'description'    => "Sale #{$sale->invoice_no}: Tax Base Rs. " . number_format($taxExclusiveBase, 2) . " @ {$commPctStr} → Rs. " . number_format($newCommission, 2) . " commission (Fully Paid)",
                    'json_meta'      => json_encode([
                        'sale_total' => $saleTotal,
                        'tax_exclusive_base' => $taxExclusiveBase,
                        'comm_percentage' => $commPctStr,
                        'max_commission' => $maxCommission,
                        'customer_paid_total' => $totalPaymentsOnSale,
                        'paid_so_far' => $alreadyPaid,
                        'current_commission' => $newCommission,
                        'remaining_commission' => max(0, $maxCommission - ($alreadyPaid + $newCommission)),
                        'text_desc' => "Sale #{$sale->invoice_no}: Tax Base Rs. " . number_format($taxExclusiveBase, 2) . " @ {$commPctStr} → Rs. " . number_format($newCommission, 2) . " commission",
                    ]),
                ];
                $salesUpdates[] = [
                    'sale'           => $sale,
                    'max_commission' => $maxCommission,
                    'new_paid'       => $alreadyPaid + $newCommission,
                ];
            }
        }

        static::$pendingCommissionUpdates[$employee->id] = $salesUpdates;

        $description = count($commissionDetails) > 0
            ? 'Commission from ' . count($commissionDetails) . ' sale(s): ' . implode('; ', array_column($commissionDetails, 'description'))
            : 'No eligible fully-paid commission sales this period';

        return [
            'total'              => $totalNewCommission,
            'description'        => $description,
            'commission_details' => $commissionDetails,
        ];
    }

    /**
     * Pending commission updates per employee (keyed by employee ID).
     * These are committed after payroll is successfully saved.
     */
    public static array $pendingCommissionUpdates = [];

    /**
     * Commit commission_paid updates for an employee after payroll is saved.
     */
    public function commitCommissionUpdates(int $employeeId): void
    {
        $updates = static::$pendingCommissionUpdates[$employeeId] ?? [];
        foreach ($updates as $update) {
            $update['sale']->update(['total_commission' => $update['max_commission']]);
        }
        unset(static::$pendingCommissionUpdates[$employeeId]);
    }

    /**
     * Generate an instant commission payroll record triggered by a customer payment.
     */
    public function generateInstantCommission(\App\Models\CustomerPayment $payment): void
    {
        if (!$payment->sale_id) return;
        
        $sale = \App\Models\Sale::find($payment->sale_id);
        if (!$sale || !$sale->employee_id) return;

        // Must be a posted sale invoice (not DC draft or unposted)
        if ($sale->sale_status !== 'post') return;

        $employee = \App\Models\Hr\Employee::find($sale->employee_id);
        if (!$employee || strtolower($employee->status) !== 'active') return;

        $structure = $this->getEffectiveSalaryStructure($employee);
        if (!$structure) return;
        
        // Ensure employee structure or sale permits commission
        if (!in_array($structure->salary_type, ['commission', 'both']) && empty($sale->commission_percentage)) {
            return;
        }

        $saleTotal = floatval($sale->total_net);
        if ($saleTotal <= 0) return;

        $gst     = floatval($sale->total_gst ?? 0);
        $advTax  = floatval($sale->total_adv_tax ?? 0);
        
        // Tax-exclusive base: Net Amount minus GST & Sale Tax
        $taxExclusiveBase = max(0, $saleTotal - $gst - $advTax);
        $alreadyPaid      = floatval($sale->commission_paid);

        // Determine max commission
        $maxCommission = 0;
        if ($sale->commission_percentage !== null && floatval($sale->commission_percentage) > 0) {
            $maxCommission = round($taxExclusiveBase * (floatval($sale->commission_percentage) / 100), 2);
        } elseif (floatval($sale->total_commission) > 0) {
            $maxCommission = floatval($sale->total_commission);
        } elseif ($structure->commission_tiers && count($structure->commission_tiers) > 0) {
            $maxCommission = $structure->calculateTieredCommission($taxExclusiveBase);
        } elseif ($structure->commission_percentage > 0) {
            $maxCommission = ($taxExclusiveBase * $structure->commission_percentage) / 100;
        }

        if ($maxCommission <= 0) {
            return;
        }

        // Update total_commission on sale record for tracking
        $sale->total_commission = $maxCommission;

        if ($alreadyPaid >= $maxCommission) {
            return;
        }

        // Calculate total payments received on sale
        $totalPaymentsOnSale = \App\Models\CustomerPayment::where('sale_id', $sale->id)->sum('amount');
        
        // Fallback for legacy POS sales where CustomerPayment might not have been created
        if ($totalPaymentsOnSale == 0) {
            $posPaid = max(0, floatval($sale->cash) + floatval($sale->card) - max(0, floatval($sale->change)));
            $totalPaymentsOnSale = $posPaid;
        }
        
        // Trigger condition: FULL PAYMENT REQUIRED (Customer must pay 100% of invoice amount)
        if ($totalPaymentsOnSale < ($saleTotal - 0.01)) {
            return; // Not fully paid yet
        }
        
        // Full payment confirmed -> Entire remaining commission is earned
        $newCommission = max(0, $maxCommission - $alreadyPaid);

        if ($newCommission > 0) {
            $month = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m');
            
            // Find existing UNPAID monthly payroll for this employee & month
            $payroll = \App\Models\Hr\Payroll::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('payroll_type', 'monthly')
                ->where('status', '!=', 'paid')
                ->first();

            $isNewMonthlyPayroll = false;
            $allowancesToSave = [];
            $deductionsToSave = [];

            if (!$payroll) {
                // Check if employee has an active monthly salary structure or is eligible for monthly payroll
                $structure = $this->getEffectiveSalaryStructure($employee);
                if ($structure) {
                    $payrollData = $this->calculateMonthlyPayroll($employee, $month);
                    
                    // We explicitly add the new commission initially
                    $payrollData['commission'] = ($payrollData['commission'] ?? 0) + $newCommission;
                    $payrollData['gross_salary'] = ($payrollData['gross_salary'] ?? 0) + $newCommission;
                    $payrollData['net_salary'] = ($payrollData['net_salary'] ?? 0) + $newCommission;

                    $payroll = \App\Models\Hr\Payroll::create(array_merge(
                        ['employee_id' => $employee->id],
                        \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'breakdown', 'attendance_breakdown', 'commission_details'])
                    ));

                    $isNewMonthlyPayroll = true;
                    $allowancesToSave = $payrollData['allowance_details'] ?? [];
                    $deductionsToSave = $payrollData['deduction_details'] ?? [];
                } else {
                    // Fallback standalone commission payroll
                    $payroll = \App\Models\Hr\Payroll::where('employee_id', $employee->id)
                        ->where('sale_id', $sale->id)
                        ->where('payroll_type', 'commission')
                        ->where('status', '!=', 'paid')
                        ->first();

                    if (!$payroll) {
                        $payroll = \App\Models\Hr\Payroll::create([
                            'employee_id' => $employee->id,
                            'sale_id' => $sale->id,
                            'payroll_type' => 'commission', 
                            'month' => $month,
                            'basic_salary' => 0,
                            'gross_salary' => 0, // Will be updated below
                            'allowances' => 0,
                            'deductions' => 0,
                            'attendance_deductions' => 0,
                            'manual_deductions' => 0,
                            'manual_allowances' => 0,
                            'carried_forward_deduction' => 0,
                            'bonuses' => 0,
                            'commission' => 0, // Will be updated below
                            'net_salary' => 0, // Will be updated below
                            'status' => 'generated',
                            'auto_generated' => true,
                            'notes' => "Instant Commission for Sale #{$sale->invoice_no} (Triggered by Full Payment)",
                        ]);
                    }
                }
            }

            // 1. Create the PayrollDetail for this commission immediately!
            // This ensures any re-calculation sums it properly.
            \App\Models\Hr\PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'commission',
                'name' => "Sales Commission (Sale #{$sale->invoice_no})",
                'amount' => $newCommission,
                'description' => "Invoice #{$sale->invoice_no}: Tax Base Rs. {$taxExclusiveBase} -> {$newCommission} Commission (Fully Paid)",
            ]);

            // 2. Update the main Payroll record
            if ($isNewMonthlyPayroll) {
                // Since this recalculates totals based on PayrollDetail records, 
                // it will correctly sum the newly created commission detail above!
                $this->savePayrollDetails($payroll, $allowancesToSave, $deductionsToSave);
            } else {
                // Update existing monthly or standalone commission payroll directly
                $payroll->commission   += $newCommission;
                $payroll->gross_salary += $newCommission;
                $payroll->net_salary   += $newCommission;
                $payroll->save();
            }

            // Track total expected commission on the sale
            \Illuminate\Support\Facades\DB::table('sales')
                ->where('id', $sale->id)
                ->update([
                    'total_commission' => $maxCommission,
                ]);
        }
    }

    /**
     * Consolidate all unpaid commission payrolls for an employee into their single monthly payroll
     */
    public function consolidateEmployeeMonthPayrolls(int $employeeId, string $month): void
    {
        $monthlyPayroll = Payroll::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('payroll_type', 'monthly')
            ->first();

        if (!$monthlyPayroll) {
            return;
        }

        $commissionPayrolls = Payroll::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('payroll_type', 'commission')
            ->where('status', '!=', 'paid')
            ->get();

        if ($commissionPayrolls->isEmpty()) {
            return;
        }

        foreach ($commissionPayrolls as $commPayroll) {
            // Move all commission details to monthly payroll
            PayrollDetail::where('payroll_id', $commPayroll->id)
                ->update(['payroll_id' => $monthlyPayroll->id]);

            // Delete standalone commission payroll
            $commPayroll->delete();
        }

        // Re-sum commission details for monthly payroll
        $totalComm = PayrollDetail::where('payroll_id', $monthlyPayroll->id)
            ->where('type', 'commission')
            ->sum('amount');

        $monthlyPayroll->commission = $totalComm;
        $monthlyPayroll->gross_salary = floatval($monthlyPayroll->basic_salary) + floatval($monthlyPayroll->allowances) + floatval($monthlyPayroll->manual_allowances) + floatval($totalComm);
        
        $totalDeductions = floatval($monthlyPayroll->deductions) + floatval($monthlyPayroll->attendance_deductions) + floatval($monthlyPayroll->manual_deductions) + floatval($monthlyPayroll->carried_forward_deduction);
        
        // Check loan deduction
        $activeLoan = Loan::where('employee_id', $employeeId)->salaryDeduction()->active()->first();
        $loanPayment = LoanPayment::where('payroll_id', $monthlyPayroll->id)->first();
        $loanDeduction = 0;
        if ($loanPayment) {
            $loanDeduction = floatval($loanPayment->amount);
        } elseif ($activeLoan && !$activeLoan->isDeductionSkippedForMonth($month)) {
            $loanDeduction = floatval($activeLoan->monthly_installment);
        }
        
        $totalDeductions += $loanDeduction;
        $monthlyPayroll->net_salary = max(0, $monthlyPayroll->gross_salary - $totalDeductions);
        $monthlyPayroll->save();
    }

    /**
     * Generate (Calculate and Save) monthly payroll for an employee
     */
    public function generateMonthlyPayrollForEmployee(Employee $employee, string $month): ?Payroll
    {
        // 1. Check if already exists
        $exists = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('payroll_type', 'monthly')
            ->exists();

        if ($exists) {
            return null;
        }

        // 2. Calculate
        $payrollData = $this->calculateMonthlyPayroll($employee, $month);

        // 3. Create Payroll Record
        $payroll = Payroll::create(array_merge(
            ['employee_id' => $employee->id],
            \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'breakdown', 'attendance_breakdown', 'commission_details'])
        ));

        // 4. Save Details
        $this->savePayrollDetails(
            $payroll,
            $payrollData['allowance_details'] ?? [],
            $payrollData['deduction_details'] ?? [],
            $payrollData['commission_details'] ?? []
        );

        // 5. Consolidate any standalone commission payrolls into this monthly payroll
        $this->consolidateEmployeeMonthPayrolls($employee->id, $month);

        return $payroll;
    }
}
