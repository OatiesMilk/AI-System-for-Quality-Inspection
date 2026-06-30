<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to one or more roles, e.g. ->middleware('role:system_admin,product_manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // if you are not log in as a user or if you are logged in as a user, but your role is not permitted to access this redirect
        // deny access
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'You do not have access to this section of the system.');
        }

        // otherwise, accept redirect
        return $next($request);
    }
}
