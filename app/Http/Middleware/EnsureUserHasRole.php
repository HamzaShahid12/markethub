<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level role gate, registered as the 'role' middleware alias
 * (see README for the bootstrap/app.php wiring). Usage:
 *
 *   Route::middleware('role:admin')->group(...)
 *   Route::middleware('role:vendor,admin')->group(...)   // either role
 *
 * This is a coarse first line of defense for whole route groups.
 * Ownership checks within a role (e.g. "this vendor owns this product")
 * still go through the Policies from Phase 1 — this middleware only
 * answers "is this user even the right kind of user".
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this area.');
        }

        if ($user->status !== 'active') {
            abort(403, 'Your account has been suspended.');
        }

        if ($user->role === 'vendor') {
            $vendor = $user->vendor;

            if (! $vendor || $vendor->status !== 'approved') {
                abort(403, match ($vendor?->status) {
                    'pending' => 'Your vendor account is still awaiting approval.',
                    'rejected' => 'Your vendor application was not approved.',
                    'suspended' => 'Your vendor account has been suspended.',
                    default => 'You do not have access to this area.',
                });
            }
        }

        return $next($request);
    }
}