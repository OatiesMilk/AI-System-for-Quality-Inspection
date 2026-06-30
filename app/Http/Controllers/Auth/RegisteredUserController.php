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
     * Roles a product manager is allowed to assign to a new account.
     */
    private const ASSIGNABLE_ROLES = [
        'quality_inspector',
        'product_manager',
        'system_admin',
        'shoe_constructor',
    ];

    /**
     * Display the user-creation view (product manager only).
     */
    public function create(): View
    {
        return view('auth.register', ['roles' => self::ASSIGNABLE_ROLES]);
    }

    /**
     * Handle an incoming user-creation request from a product manager.
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
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        event(new Registered($user));

        return redirect()->route('dashboard.manager')
            ->with('status', "Account created for {$user->name}.");
    }
}
