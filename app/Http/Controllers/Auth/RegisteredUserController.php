<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
     * Roles each creating role is permitted to assign to a new account.
     *
     * System admins hold the highest privilege per the system's RBAC design and may
     * create any account, including other admins and product managers. Product managers
     * are restricted to the operational floor roles they directly staff, so they cannot
     * mint their own admin or manager peers.
     */
    private const ROLE_ASSIGNMENT_MATRIX = [
        'system_admin' => [
            'quality_inspector',
            'product_manager',
            'system_admin',
            'shoe_constructor',
        ],
        'product_manager' => [
            'quality_inspector',
            'shoe_constructor',
        ],
    ];

    /**
     * Display the user-creation view, scoped to roles the current user may assign.
     */
    public function create(Request $request): View
    {
        return view('auth.register', ['roles' => $this->assignableRolesFor($request)]);
    }

    /**
     * Handle an incoming user-creation request from a system admin or product manager.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $assignableRoles = $this->assignableRolesFor($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:'.implode(',', $assignableRoles)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        event(new Registered($user));

        $redirectRoute = $request->user()->role === 'system_admin'
            ? 'dashboard.admin'
            : 'dashboard.manager';

        return redirect()->route($redirectRoute)
            ->with('status', "Account created for {$user->name}.");
    }

    /**
     * @return list<string>
     */
    private function assignableRolesFor(Request $request): array
    {
        return self::ROLE_ASSIGNMENT_MATRIX[$request->user()->role] ?? [];
    }
}
