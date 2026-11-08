<?php

namespace Tests\Feature;

use App\Models\Hr\Attendance;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\Employee;
use App\Models\Hr\Leave;
use App\Models\Hr\Loan;
use App\Models\Hr\Payroll;
use App\Models\Hr\SalaryStructure;
use App\Models\Hr\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
        ]);
    }

    public function test_owner_can_access_hr_management_pages(): void
    {
        $routes = [
            'hr.departments.index',
            'hr.designations.index',
            'hr.shifts.index',
            'hr.employees.index',
            'hr.attendance.index',
            'hr.leaves.index',
            'hr.loans.index',
            'hr.salary-structure.index',
            'hr.payroll.index',
            'hr.holidays.index',
            'hr.biometric-devices.index',
            'my-attendance',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->owner)->get(route($route));
            $response->assertStatus(200);
        }
    }

    public function test_cashier_can_access_my_attendance_but_blocked_from_admin_hr(): void
    {
        // Cashier can view their own attendance page
        $resSelf = $this->actingAs($this->cashier)->get(route('my-attendance'));
        $resSelf->assertStatus(200);

        // Cashier cannot access administrative HR modules
        $resAdmin = $this->actingAs($this->cashier)->get(route('hr.departments.index'));
        $resAdmin->assertStatus(403);
    }

    public function test_department_and_designation_crud(): void
    {
        // 1. Create Department
        $deptResponse = $this->actingAs($this->owner)->postJson(route('hr.departments.store'), [
            'name' => 'Fuel Operations',
            'description' => 'Forecourt pump operators and supervisors',
        ]);
        $deptResponse->assertStatus(200);
        $this->assertDatabaseHas('hr_departments', ['name' => 'Fuel Operations']);
        $department = Department::where('name', 'Fuel Operations')->first();

        // 2. Update Department
        $updateDept = $this->actingAs($this->owner)->postJson(route('hr.departments.store'), [
            'edit_id' => $department->id,
            'name' => 'Forecourt Operations',
            'description' => 'Updated forecourt department',
        ]);
        $updateDept->assertStatus(200);
        $this->assertDatabaseHas('hr_departments', ['name' => 'Forecourt Operations']);

        // 3. Create Designation
        $desigResponse = $this->actingAs($this->owner)->postJson(route('hr.designations.store'), [
            'name' => 'Pump Attendant',
            'description' => 'Fuel dispensing officer',
        ]);
        $desigResponse->assertStatus(200);
        $this->assertDatabaseHas('hr_designations', ['name' => 'Pump Attendant']);
    }

    public function test_shift_management(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('hr.shifts.store'), [
            'name' => 'Morning Station Shift',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'grace_minutes' => 15,
            'is_default' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 'Shift Created Successfully',
            'reload' => true,
        ]);
        $this->assertDatabaseHas('hr_shifts', [
            'name' => 'Morning Station Shift',
            'is_default' => 1,
        ]);

        // Test non-AJAX form submit redirects with flash message
        $htmlResponse = $this->actingAs($this->owner)->post(route('hr.shifts.store'), [
            'name' => 'Evening Station Shift',
            'start_time' => '15:00',
            'end_time' => '23:00',
            'grace_minutes' => 10,
            'is_default' => 0,
        ]);
        $htmlResponse->assertRedirect(route('hr.shifts.index'));
        $htmlResponse->assertSessionHas('success', 'Shift Created Successfully');
    }

    public function test_employee_creation_with_linked_user(): void
    {
        $department = Department::create(['name' => 'Accounts']);
        $designation = Designation::create(['name' => 'Cashier']);
        $shift = Shift::create([
            'name' => 'General Shift',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'grace_minutes' => 15,
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('hr.employees.store'), [
            'first_name' => 'Zubair',
            'last_name' => 'Khan',
            'email' => 'zubair.khan@petrolstation.com',
            'phone' => '03001234567',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'shift_id' => $shift->id,
            'joining_date' => '2026-01-01',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('hr_employees', [
            'first_name' => 'Zubair',
            'email' => 'zubair.khan@petrolstation.com',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'zubair.khan@petrolstation.com',
        ]);

        $employee = Employee::where('email', 'zubair.khan@petrolstation.com')->first();
        $this->assertNotNull($employee->user_id);
        $this->assertEquals($department->id, $employee->department_id);

        // Test Updating Employee (Edit mode without password change)
        $editResponse = $this->actingAs($this->owner)->postJson(route('hr.employees.store'), [
            'edit_id' => $employee->id,
            'first_name' => 'Zubair Updated',
            'last_name' => 'Khan',
            'email' => 'zubair.khan@petrolstation.com',
            'phone' => '03009999999',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'shift_id' => $shift->id,
            'joining_date' => '2026-01-01',
            'password' => '', // blank to keep existing
            'status' => 'active',
            'role' => 'cashier',
        ]);

        $editResponse->assertStatus(200);
        $editResponse->assertJson(['success' => 'Employee saved successfully']);
        $this->assertDatabaseHas('hr_employees', [
            'id' => $employee->id,
            'first_name' => 'Zubair Updated',
            'phone' => '03009999999',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $employee->user_id,
            'name' => 'Zubair Updated Khan',
        ]);
    }

    public function test_attendance_marking_and_calculation(): void
    {
        $department = Department::create(['name' => 'Pumps']);
        $designation = Designation::create(['name' => 'Dispenser']);
        $shift = Shift::create([
            'name' => 'Full Day Shift',
            'start_time' => '06:00:00',
            'end_time' => '23:59:00',
            'grace_minutes' => 15,
        ]);

        $user = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'shift_id' => $shift->id,
            'first_name' => 'Ahmed',
            'last_name' => 'Raza',
            'email' => 'ahmed.raza@petrol.com',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Self-service clock-in via my-attendance.mark
        $clockInRes = $this->actingAs($user)->postJson(route('my-attendance.mark'), [
            'type' => 'check_in',
            'latitude' => 24.8607,
            'longitude' => 67.0011,
            'location' => 'Main Petrol Pump Station',
        ]);
        $clockInRes->assertStatus(200);

        $attendance = Attendance::where('employee_id', $employee->id)->whereDate('date', Carbon::today())->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->check_in_time);

        // Self-service clock-out
        $clockOutRes = $this->actingAs($user)->postJson(route('my-attendance.mark'), [
            'type' => 'check_out',
            'latitude' => 24.8607,
            'longitude' => 67.0011,
            'location' => 'Main Petrol Pump Station',
        ]);
        $clockOutRes->assertStatus(200);

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_time);
    }

    public function test_leave_request_and_approval(): void
    {
        $department = Department::create(['name' => 'Maintenance']);
        $designation = Designation::create(['name' => 'Technician']);
        $employee = Employee::create([
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'first_name' => 'Bilal',
            'last_name' => 'Tariq',
            'email' => 'bilal@petrol.com',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Create leave request
        $leaveRes = $this->actingAs($this->owner)->postJson(route('hr.leaves.store'), [
            'employee_id' => $employee->id,
            'leave_type' => 'Sick',
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'reason' => 'Medical appointment',
        ]);
        $leaveRes->assertStatus(200);

        $leave = Leave::where('employee_id', $employee->id)->first();
        $this->assertNotNull($leave);
        $this->assertEquals('pending', $leave->status);

        // Approve leave
        $approveRes = $this->actingAs($this->owner)->patchJson(route('hr.leaves.update-status', $leave->id), [
            'status' => 'approved',
        ]);
        $approveRes->assertStatus(200);

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
    }

    public function test_loan_and_payroll_generation_with_loan_deduction(): void
    {
        $department = Department::create(['name' => 'Security']);
        $designation = Designation::create(['name' => 'Guard']);
        $employee = Employee::create([
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'first_name' => 'Farooq',
            'last_name' => 'Azam',
            'email' => 'farooq@petrol.com',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // 1. Create Salary Structure
        $salaryStructure = SalaryStructure::create([
            'employee_id' => $employee->id,
            'name' => 'Farooq Salary Package',
            'salary_type' => 'salary',
            'base_salary' => 35000.00,
            'allowances' => [
                ['name' => 'Fuel Allowance', 'amount' => 5000, 'type' => 'fixed', 'is_active' => 'true'],
            ],
            'deductions' => [],
        ]);

        // 2. Create Loan of 20,000 with 2,000 monthly deduction
        $loanRes = $this->actingAs($this->owner)->postJson(route('hr.loans.store'), [
            'employee_id' => $employee->id,
            'amount' => 20000.00,
            'installment_amount' => 2000.00,
            'total_installments' => 10,
            'loan_type' => 'salary_deduction',
            'reason' => 'Family emergency advance',
        ]);
        $loanRes->assertStatus(200);

        $loan = Loan::where('employee_id', $employee->id)->first();
        $this->assertNotNull($loan);

        // Approve Loan
        $this->actingAs($this->owner)->postJson(route('hr.loans.approve', $loan->id))->assertStatus(200);
        $loan->refresh();
        $this->assertEquals('approved', $loan->status);

        // 3. Generate Monthly Payroll for current month
        $month = Carbon::now()->format('Y-m');
        $payrollRes = $this->actingAs($this->owner)->postJson(route('hr.payroll.generate-monthly'), [
            'month' => $month,
            'employee_ids' => [$employee->id],
        ]);
        $payrollRes->assertStatus(200);

        $payroll = Payroll::where('employee_id', $employee->id)->where('month', $month)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(35000.00, floatval($payroll->basic_salary));

        // 4. Mark Payroll as Paid
        $paidRes = $this->actingAs($this->owner)->patchJson(route('hr.payroll.mark-paid', $payroll->id), [
            'notes' => 'Salary disbursed via Bank',
        ]);
        $paidRes->assertStatus(200);

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);

        // 5. Loan should reflect the deduction
        $loan->refresh();
        $this->assertEquals(2000.00, floatval($loan->paid_amount));
        $this->assertEquals(1, $loan->installments_paid);
    }

    public function test_employee_portal_for_unlinked_and_linked_users(): void
    {
        // 1. Owner without employee profile visits employee portal -> sees friendly no-employee screen
        $resNoEmp = $this->actingAs($this->owner)->get(route('employee.portal.index'));
        $resNoEmp->assertStatus(200);
        $resNoEmp->assertSee('No Employee Profile Linked');
        $resNoEmp->assertSee(route('dashboard'));

        // 2. User linked to an employee visits portal -> sees active portal
        $dept = Department::create(['name' => 'Forecourt']);
        $desig = Designation::create(['name' => 'Cashier Operator']);

        $employee = Employee::create([
            'user_id' => $this->cashier->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_code' => 'EMP-0099',
            'first_name' => 'Cashier',
            'last_name' => 'Staff',
            'email' => 'cashier.portal@station.test',
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        $resEmp = $this->actingAs($this->cashier)->get(route('employee.portal.index'));
        $resEmp->assertStatus(200);
        $resEmp->assertSee('Cashier Staff');

        // 3. Employee logout route works
        $logoutRes = $this->actingAs($this->cashier)->post(route('employee.logout'));
        $logoutRes->assertRedirect(route('employee.login'));
        $this->assertGuest();
    }
}
