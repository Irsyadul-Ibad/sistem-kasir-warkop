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

        if (! $user || $user->is_active){
            return response()->json(['message' => 'akun tidak ditemukan atau tidak aktif'], 403);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'akses ditolak'], 403);
        }

        return $next($request);
    }

    
}
