<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun tidak ditemukan atau tidak aktif.'], 403);
            }

            abort(403, 'Akun tidak ditemukan atau tidak aktif.');
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }

    
}
