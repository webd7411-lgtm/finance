<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.shifts.view')) {
            abort(403, 'Unauthorized action.');
        }
        $shifts = Shift::orderBy('name')->paginate(12);

        return view('hr.shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'grace_minutes' => 'required|integer|min:0|max:60',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Shift::where('is_default', true)->update(['is_default' => false]);
        }

        if ($request->filled('edit_id')) {
            if (! auth()->user()->can('hr.shifts.edit')) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized action.'], 403);
                }
                abort(403, 'Unauthorized action.');
            }
            $shift = Shift::findOrFail($request->edit_id);
            $shift->update($request->all());
            $message = 'Shift Updated Successfully';
        } else {
            if (! auth()->user()->can('hr.shifts.create')) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized action.'], 403);
                }
                abort(403, 'Unauthorized action.');
            }
            Shift::create($request->all());
            $message = 'Shift Created Successfully';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => $message, 'reload' => true]);
        }

        return redirect()->route('hr.shifts.index')->with('success', $message);
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('hr.shifts.delete')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }
        $shift = Shift::findOrFail($id);

        // Check if shift is assigned to employees
        if ($shift->employees()->count() > 0) {
            $err = 'Cannot delete shift. It is assigned to '.$shift->employees()->count().' employees.';
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => $err], 422);
            }
            return redirect()->route('hr.shifts.index')->with('error', $err);
        }

        $shift->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => 'Shift Deleted Successfully', 'reload' => true]);
        }

        return redirect()->route('hr.shifts.index')->with('success', 'Shift Deleted Successfully');
    }

    /**
     * Sync Shifts to All Devices
     */
    public function syncToDevices(\App\Services\BiometricDeviceService $service)
    {
        if (! auth()->user()->can('hr.shifts.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $devices = \App\Models\BiometricDevice::all();
        if ($devices->isEmpty()) {
            return response()->json(['error' => 'No biometric devices found.'], 404);
        }

        $results = [];
        foreach ($devices as $device) {
            $res = $service->syncShifts($device);
            $results[] = "Device {$device->name}: ".$res['message'];
        }

        return response()->json(['success' => 'Sync completed. '.implode(' | ', $results)]);
    }
}
