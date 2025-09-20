<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Permission; // your extended Permission with group()

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'permissions')->get();
        $roles = Role::all();
        $permissions = Permission::with('group')->get();

        return view('admin.rbac.users', compact('users', 'roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'nullable|string|min:6',
            'roles'      => 'array',
            'roles.*'    => Rule::exists('roles', 'name'),  // ensure role names exist
            'permissions'   => 'array',
            'permissions.*' => Rule::exists('permissions', 'name'), // ensure permission names exist
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        $user->syncRoles($validated['roles'] ?? []);
        $user->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully!',
            'user' => $user
        ]);
    }

    public function edit($id)
    {
        $user = User::with('roles', 'permissions')->findOrFail($id);

        return response()->json([
            'user'       => $user,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'user_perms' => $user->permissions->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password'   => 'nullable|string|min:6',
            'roles'      => 'array',
            'roles.*'    => Rule::exists('roles', 'name'),
            'permissions'   => 'array',
            'permissions.*' => Rule::exists('permissions', 'name'),
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        $user->syncRoles($validated['roles'] ?? []);
        $user->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully!',
            'user' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!'
        ]);
    }
}


