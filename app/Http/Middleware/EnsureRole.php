<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Batasi akses hanya untuk role yang diberikan pada route.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            // API memakai format error RULE-07; halaman web memakai 403 biasa.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya role '.$role.' yang diizinkan.',
                    'code' => 'FORBIDDEN',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
