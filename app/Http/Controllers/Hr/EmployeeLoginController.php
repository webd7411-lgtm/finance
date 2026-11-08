<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployeeLoginController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in, redirect to portal
        if (Auth::check()) {
            return redirect()->route('employee.portal.index');
        }

        return view('hr.employee-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginId = $request->login_id;
        $password = $request->password;

        $user = null;

        // Try to authenticate using Email
        if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            $candidate = User::where('email', $loginId)->first();
            if ($candidate && Hash::check($password, $candidate->password)) {
                $user = $candidate;
            }
        } else {
            // Treat as Phone Number
            // Find employee by phone
            $employee = Employee::where('phone', $loginId)->whereNotNull('user_id')->first();
            
            if ($employee) {
                $candidate = User::find($employee->user_id);
                if ($candidate && Hash::check($password, $candidate->password)) {
                    $user = $candidate;
                }
            }
        }

        if ($user) {
            // Check if user/employee account is active
            if (method_exists($user, 'isEmployeeActive') && ! $user->isEmployeeActive()) {
                return back()->withErrors([
                    'login_id' => 'Your account has been deactivated. Please contact HR department.',
                ])->onlyInput('login_id');
            }

            // Check if branch is assigned (optional in petrol)
            if ($user->branch_id && method_exists($user, 'branch') && $user->branch) {
                if (isset($user->branch->is_active) && ! $user->branch->is_active) {
                    return back()->withErrors([
                        'login_id' => 'Your branch is inactive. Please contact the administrator.',
                    ])->onlyInput('login_id');
                }
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(route('employee.portal.index'));
        }

        return back()->withErrors([
            'login_id' => 'The provided credentials do not match our records.',
        ])->onlyInput('login_id');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
