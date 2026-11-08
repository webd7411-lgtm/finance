<?php

namespace App\Http\Controllers;

use App\Models\StationLocation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StationLocationController extends Controller
{
    /**
     * Display a listing of station locations
     */
    public function index(Request $request)
    {
        $query = StationLocation::withCount(['tanks', 'pumps']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $locations = $query->orderByDesc('is_default')->orderBy('name')->paginate(10)->withQueryString();

        $totalLocations = StationLocation::count();
        $activeLocations = StationLocation::where('status', 'active')->count();

        return view('locations.index', compact('locations', 'totalLocations', 'activeLocations'));
    }

    /**
     * Store a newly created station location
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['nullable', 'string', 'max:50', 'unique:station_locations,code'],
            'city'         => ['nullable', 'string', 'max:100'],
            'address'      => ['nullable', 'string', 'max:500'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'string', Rule::in(['active', 'inactive'])],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $location = StationLocation::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'create',
            'module'  => 'locations',
            'details' => "Created Station Location: {$location->name}",
        ]);

        return redirect()->route('locations.index')->with('success', "Station Location '{$location->name}' created successfully!");
    }

    /**
     * Update the specified station location
     */
    public function update(Request $request, StationLocation $location)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['nullable', 'string', 'max:50', Rule::unique('station_locations', 'code')->ignore($location->id)],
            'city'         => ['nullable', 'string', 'max:100'],
            'address'      => ['nullable', 'string', 'max:500'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'string', Rule::in(['active', 'inactive'])],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $location->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update',
            'module'  => 'locations',
            'details' => "Updated Station Location: {$location->name}",
        ]);

        return redirect()->route('locations.index')->with('success', "Station Location '{$location->name}' updated successfully!");
    }

    /**
     * Remove the specified station location
     */
    public function destroy(StationLocation $location)
    {
        if ($location->is_default) {
            return back()->with('error', "Primary/Default Station Location cannot be deleted.");
        }

        if ($location->tanks()->exists() || $location->pumps()->exists()) {
            return back()->with('error', "Cannot delete station '{$location->name}' because tanks or pumps are assigned to it. Reassign them first.");
        }

        $locName = $location->name;
        $location->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'delete',
            'module'  => 'locations',
            'details' => "Deleted Station Location: {$locName}",
        ]);

        return redirect()->route('locations.index')->with('success', "Station Location '{$locName}' has been removed.");
    }
}
