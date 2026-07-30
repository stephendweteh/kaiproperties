<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(User::ROLE_ADMIN)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Super Admin access required.',
                ], 403);
            }

            abort(403, 'Super Admin access required.');
        }

        return $next($request);
    }
}
