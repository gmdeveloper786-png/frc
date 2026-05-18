<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovedChildMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if ($user->isChild() && ! $user->isApproved()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account is pending admin approval.',
                    'status'  => $user->status,
                ], 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is pending admin approval.',
            ]);
        }

        return $next($request);
    }
}
