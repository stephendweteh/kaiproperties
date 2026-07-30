<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWriter
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole([User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
