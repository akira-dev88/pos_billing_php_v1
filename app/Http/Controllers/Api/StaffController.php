<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    // ✅ Create staff
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:manager,cashier', // ❗ restrict
        ]);

        $user = User::create([
            'tenant_uuid' => app('tenant_uuid'),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user);
    }

    // ✅ List staff
    public function index()
    {
        $users = User::where('tenant_uuid', app('tenant_uuid'))
            ->whereIn('role', ['manager', 'cashier'])
            ->get();

        return response()->json($users);
    }

    // ✏️ Update staff
    public function update(Request $request, $user_uuid)
    {
        $user = User::where('user_uuid', $user_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:manager,cashier',
            'password' => 'nullable|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json($user);
    }

    // 🗑️ Delete staff
    public function destroy($user_uuid)
    {
        $user = User::where('user_uuid', $user_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $user->delete();

        return response()->json([
            'message' => 'Staff deleted'
        ]);
    }
}
