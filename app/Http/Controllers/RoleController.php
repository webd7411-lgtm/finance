<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner,incharge']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('id', 'ASC')->get();
        $allPermissions = Permission::orderBy('name', 'ASC')->get();

        return view('roles.index', compact('roles', 'allPermissions'));
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name,' . $editId,
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $roleName = strtolower(trim($request->name));

        if (!empty($editId)) {
            $role = Role::findOrFail($editId);
            $role->name = $roleName;
            $role->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully!',
                    'reload' => true,
                ]);
            }
            return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
        } else {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully!',
                    'reload' => true,
                ]);
            }
            return redirect()->route('roles.index')->with('success', 'Role created successfully!');
        }
    }

    public function updatePermissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'edit_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            return redirect()->back()->withErrors($validator);
        }

        $role = Role::findOrFail($request->edit_id);

        if (strtolower($role->name) === 'owner') {
            $role->syncPermissions(Permission::all());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Owner permanently retains all permissions by design.',
                ]);
            }
            return back()->with('success', 'Owner permanently retains all permissions by design.');
        }

        $role->syncPermissions($request->permissions ?? []);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permissions for '{$role->name}' updated successfully!",
            ]);
        }

        return back()->with('success', "Permissions for '{$role->name}' updated successfully!");
    }

    public function update(Request $request, $id)
    {
        $request->merge(['edit_id' => $id]);
        if ($request->has('permissions')) {
            $res = $this->updatePermissions($request);
            if ($request->ajax() || $request->wantsJson()) {
                return $res;
            }
            return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
        }
        return $this->store($request);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (in_array(strtolower($role->name), ['owner', 'incharge', 'cashier'])) {
            return redirect()->route('roles.index')->with('error', "System core role '{$role->name}' cannot be deleted.");
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', "Cannot delete role '{$role->name}' because {$role->users()->count()} user(s) are assigned to it. Please reassign them first.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    public function delete($id)
    {
        return $this->destroy($id);
    }
}
