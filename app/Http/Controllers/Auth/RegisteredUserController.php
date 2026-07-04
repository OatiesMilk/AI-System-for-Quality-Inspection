<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Roles a system admin may assign to a new account. System admins hold the highest
     * privilege per the system's RBAC design and are the only role permitted to create
     * accounts, including other admins.
     */
    private const ASSIGNABLE_ROLES = [
        'quality_inspector',
        'product_manager',
        'system_admin',
        'shoe_constructor',
    ];

    /**
     * Display the user-creation view (system admin only).
     */
    public function create(): View
    {
        return view('auth.register', ['roles' => self::ASSIGNABLE_ROLES]);
    }

    /**
     * Handle an incoming user-creation request from a system admin.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
            'shift' => ['nullable', 'in:am,pm'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'shift' => $validated['role'] === 'shoe_constructor' ? ($validated['shift'] ?? null) : null,
        ]);

        event(new Registered($user));

        AuditLog::record('user.created', $request->user(), [
            'target_user_id' => $user->id,
            'name'           => $user->name,
            'role'           => $user->role,
        ]);

        return redirect()->route('dashboard.admin')
            ->with('status', "Account created for {$user->name}.");
    }
}
