<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Biometric Devices
        if (!Schema::hasTable('biometric_devices')) {
            Schema::create('biometric_devices', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('ip_address');
                $table->integer('port')->default(4370);
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('model')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. HR Departments
        if (!Schema::hasTable('hr_departments')) {
            Schema::create('hr_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 3. HR Designations
        if (!Schema::hasTable('hr_designations')) {
            Schema::create('hr_designations', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_sale_officer')->default(false);
                $table->text('description')->nullable();
                $table->boolean('requires_location')->default(false);
                $table->timestamps();
            });
        }

        // 4. HR Shifts
        if (!Schema::hasTable('hr_shifts')) {
            Schema::create('hr_shifts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->time('start_time');
                $table->time('end_time');
                $table->time('break_start')->nullable();
                $table->time('break_end')->nullable();
                $table->integer('grace_minutes')->default(15);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 5. HR Employees
        if (!Schema::hasTable('hr_employees')) {
            Schema::create('hr_employees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->constrained('hr_departments')->cascadeOnDelete();
                $table->foreignId('designation_id')->constrained('hr_designations')->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('hr_shifts')->nullOnDelete();
                $table->time('custom_start_time')->nullable();
                $table->time('custom_end_time')->nullable();
                $table->longText('face_encoding')->nullable();
                $table->string('face_photo')->nullable();
                $table->foreignId('biometric_device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
                $table->string('device_user_id')->nullable()->index();
                $table->timestamp('fingerprint_enrolled_at')->nullable();
                $table->timestamp('last_device_sync_at')->nullable();
                $table->unsignedInteger('punch_gap_minutes')->nullable();
                $table->decimal('pending_deductions', 10, 2)->default(0.00);
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('weekly_off')->nullable();
                $table->boolean('is_docs_submitted')->default(false);
                $table->integer('casual_leaves_allocated')->default(0);
                $table->integer('sick_leaves_allocated')->default(0);
                $table->date('date_of_birth')->nullable();
                $table->date('joining_date');
                $table->enum('status', ['active', 'non-active', 'terminated'])->default('active');
                $table->timestamps();
            });
        }

        // 6. HR Employee Documents
        if (!Schema::hasTable('hr_employee_documents')) {
            Schema::create('hr_employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->string('type');
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->timestamps();
            });
        }

        // 7. HR Holidays
        if (!Schema::hasTable('hr_holidays')) {
            Schema::create('hr_holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('date');
                $table->date('end_date')->nullable();
                $table->enum('type', ['public', 'company', 'optional'])->default('public');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 8. HR Employee Holiday Pivot
        if (!Schema::hasTable('hr_employee_holiday')) {
            Schema::create('hr_employee_holiday', function (Blueprint $table) {
                $table->id();
                $table->foreignId('holiday_id')->constrained('hr_holidays')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 9. HR Attendances
        if (!Schema::hasTable('hr_attendances')) {
            Schema::create('hr_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->date('date');
                $table->time('check_in_time')->nullable();
                $table->time('check_out_time')->nullable();
                $table->string('check_in_photo')->nullable();
                $table->decimal('check_in_latitude', 10, 8)->nullable();
                $table->decimal('check_in_longitude', 11, 8)->nullable();
                $table->string('check_in_location')->nullable();
                $table->string('check_out_photo')->nullable();
                $table->decimal('check_out_latitude', 10, 8)->nullable();
                $table->decimal('check_out_longitude', 11, 8)->nullable();
                $table->string('check_out_location')->nullable();
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
                $table->boolean('is_late')->default(false);
                $table->integer('late_minutes')->default(0);
                $table->boolean('is_early_in')->default(false);
                $table->integer('early_in_minutes')->default(0);
                $table->boolean('is_early_leave')->default(false);
                $table->integer('early_leave_minutes')->default(0);
                $table->decimal('total_hours', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->string('device_id')->nullable();
                $table->timestamps();
            });
        }

        // 10. HR Leaves
        if (!Schema::hasTable('hr_leaves')) {
            Schema::create('hr_leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->string('leave_type');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('reason')->nullable();
                $table->boolean('deduct_salary')->default(false);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamps();
            });
        }

        // 11. HR Loans
        if (!Schema::hasTable('hr_loans')) {
            Schema::create('hr_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->enum('loan_type', ['salary_deduction', 'self_paid'])->default('salary_deduction');
                $table->decimal('amount', 10, 2);
                $table->decimal('installment_amount', 10, 2)->default(0.00);
                $table->integer('total_installments')->nullable();
                $table->integer('installments_paid')->default(0);
                $table->string('start_month', 7)->nullable();
                $table->string('expected_end_month', 7)->nullable();
                $table->date('disbursed_at')->nullable();
                $table->date('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
                $table->text('reason')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('paid_amount', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 12. HR Loan Payments
        if (!Schema::hasTable('hr_loan_payments')) {
            Schema::create('hr_loan_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('type')->default('salary_deduction');
                $table->string('source')->default('manual');
                $table->unsignedBigInteger('payroll_id')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 13. HR Loan Scheduled Deductions
        if (!Schema::hasTable('hr_loan_scheduled_deductions')) {
            Schema::create('hr_loan_scheduled_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('deduction_month');
                $table->enum('status', ['pending', 'deducted', 'skipped'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 14. HR Salary Structures
        if (!Schema::hasTable('hr_salary_structures')) {
            Schema::create('hr_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_structure_id')->nullable()->constrained('hr_salary_structures')->nullOnDelete();
                $table->string('name')->nullable();
                $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->cascadeOnDelete();
                $table->enum('salary_type', ['salary', 'commission', 'both'])->default('salary');
                $table->decimal('base_salary', 12, 2)->default(0.00);
                $table->decimal('daily_wages', 10, 2)->nullable();
                $table->boolean('use_daily_wages')->default(false);
                $table->decimal('commission_percentage', 5, 2)->nullable();
                $table->decimal('sales_target', 12, 2)->nullable();
                $table->longText('commission_tiers')->nullable();
                $table->longText('allowances')->nullable();
                $table->longText('deductions')->nullable();
                $table->longText('attendance_deduction_policy')->nullable();
                $table->boolean('carry_forward_deductions')->default(false);
                $table->decimal('leave_salary_per_day', 10, 2)->nullable();
                $table->string('absent_deduction_type')->default('manual');
                $table->date('effective_date')->nullable();
                $table->timestamps();
            });
        }

        // 15. Employee Salary Structures (Assignment History)
        if (!Schema::hasTable('employee_salary_structures')) {
            Schema::create('employee_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->foreignId('salary_structure_id')->constrained('hr_salary_structures')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_custom')->default(false);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'is_active']);
                $table->index(['salary_structure_id', 'is_active']);
            });
        }

        // 16. HR Payrolls
        if (!Schema::hasTable('hr_payrolls')) {
            Schema::create('hr_payrolls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->enum('payroll_type', ['monthly', 'daily', 'commission']);
                $table->string('month');
                $table->decimal('basic_salary', 10, 2);
                $table->decimal('gross_salary', 10, 2)->default(0.00);
                $table->decimal('allowances', 10, 2)->default(0.00);
                $table->decimal('attendance_deductions', 10, 2)->default(0.00);
                $table->decimal('manual_deductions', 10, 2)->default(0.00);
                $table->decimal('manual_allowances', 10, 2)->default(0.00);
                $table->decimal('carried_forward_deduction', 10, 2)->default(0.00);
                $table->decimal('carried_forward_to_next', 10, 2)->default(0.00);
                $table->decimal('deductions', 10, 2)->default(0.00);
                $table->decimal('bonuses', 10, 2)->default(0.00);
                $table->decimal('commission', 10, 2)->default(0.00);
                $table->decimal('net_salary', 10, 2);
                $table->decimal('paid_amount', 12, 2)->nullable();
                $table->string('payment_method')->nullable();
                $table->text('payment_notes')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('auto_generated')->default(false);
                $table->enum('status', ['generated', 'reviewed', 'paid'])->default('generated');
                $table->date('payment_date')->nullable();
                $table->unsignedBigInteger('voucher_id')->nullable();
                $table->unsignedBigInteger('salary_account_id')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        // 17. HR Payroll Details
        if (!Schema::hasTable('hr_payroll_details')) {
            Schema::create('hr_payroll_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained('hr_payrolls')->cascadeOnDelete();
                $table->enum('type', ['allowance', 'deduction', 'commission']);
                $table->string('name');
                $table->decimal('amount', 10, 2);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 18. HR Settings
        if (!Schema::hasTable('hr_settings')) {
            Schema::create('hr_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('group')->default('general');
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
        Schema::dropIfExists('hr_payroll_details');
        Schema::dropIfExists('hr_payrolls');
        Schema::dropIfExists('employee_salary_structures');
        Schema::dropIfExists('hr_salary_structures');
        Schema::dropIfExists('hr_loan_scheduled_deductions');
        Schema::dropIfExists('hr_loan_payments');
        Schema::dropIfExists('hr_loans');
        Schema::dropIfExists('hr_leaves');
        Schema::dropIfExists('hr_attendances');
        Schema::dropIfExists('hr_employee_holiday');
        Schema::dropIfExists('hr_holidays');
        Schema::dropIfExists('hr_employee_documents');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_shifts');
        Schema::dropIfExists('hr_designations');
        Schema::dropIfExists('hr_departments');
        Schema::dropIfExists('biometric_devices');
    }
};
