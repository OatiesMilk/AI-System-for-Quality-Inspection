<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Roles a system admin may assign when editing an existing account.
     */
    private const ASSIGNABLE_ROLES = [
        'quality_inspector',
        'product_manager',
        'system_admin',
        'shoe_constructor',
    ];

    public function edit(User $user): View
    {
        return view('dashboards.admin-edit-user', [
            'editedUser' => $user,
            'roles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
            'shift' => ['nullable', 'in:am,pm'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'shift' => $validated['role'] === 'shoe_constructor' ? ($validated['shift'] ?? null) : null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLog::record('user.updated', $request->user(), [
            'target_user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]);

        return redirect()->route('dashboard.admin')
            ->with('status', "Account updated for {$user->name}.");
    }
}
